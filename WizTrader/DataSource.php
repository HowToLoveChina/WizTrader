<?php
//! 强类型检查
#declare(strict_type=1);

/*
	数据源类
*/
interface iDataSource{
	#打开数据源
	function hook_open():bool;
	#关闭数据源
	function hook_close():bool; 
	#实时数据
	function hook_real_time():array;
	#回测数据
	function hook_back_test():array;
};
class cDataSource{
	function __construct(){
		$self->hist  = array("day","week","month","year");
		$self->fields=array{
					"tick" => array(0=>"date",1=>"price",2=>"amount",3=>dir),
					"day"  => array(0=>"date",1=>'open',2=>'close',3=>'high',4=>'low',5=>'amount'),
					"finish"=>array()
				}
	}
	//! 打开数据源
	final 
	function Open(array $need /*需要的数据*/, 
						string $period/*周期间隔*/, 
						string $stock/*股票*/, 
						int $begin/*开始日 YYYYMMDD*/,
						int $end/*结束日 YYYYMMDD*/,
						bool $custom=False/*历史数据不使用固化的就用真*/):bool{
		$self->period = $period;
		$self->stock = $stock;
		$self->begin = $begin;
		$self->end = $end ;
		$self->custom = $custom;
		foreach ($need as $item){
			//! 切分成 项和周期
			list($i,$t)=explode("|",$item);
		}
		if( ! $self->CustomDatasource() ){
			return $self->HistoryOpen();
		}
		return $self->hook_open();
	}
	private 
	function CustomDatasource():bool{
		//! 指明了要定制返回真
		if ($self->custom){
			return true;
		}
		//! 不是历史数据返回真
		return (!in_array($self->period,$this->hist));
	}
	//! 关闭数据源
	final 
	function Close():bool{
		if( ! $self->CustomDatasource() ){
			return $self->HistoryClose();
		}
		return $self->hook_close();
	}
	//! 获取最新的价格变动
	final 
	function Price():array{
		if( ! $self->CustomDatasource() ){
			return $self->HistoryPrice();
		}
		return $self->hook_price();
	}
	//! 实时价格
	final 
	function Realtime():array{
		//! 取得实时数据
		return self->hook_real_time();
	}
	//! 固化历史数据
	final 
	function HistoryOpen():bool{
		switch($self->period){
			case "day":
			case "1":
			case "5":
			case "10":
			case "15":
			case "30":
			case "hour":
			case "week":
			case "month":
			case "year":
			case "tick":
				$self->path = sprintf("%s/%s",$self->base,$self->period);
				break;
			case "60":
				$self->path = sprintf("%s/hour",$self->base);
				break;
			default:
				return false;
		}
		$self->day = $begin;
		$self->tick = $begin;
		$self->fp = NULL;
		return true;
	}
	private 
	function HistoryClose():bool{
		if( $self->fp = NULL ){
			return true;
		}
		fclose($self->fp);
		$self->fp = NULL;
		return True;
	}
	//! 打开数据文件
	private 
	function HistoryFileOpen():bool{
		while($self->fp == NULL ){
			//! 打开当天的数据文件
			$fname = sprintf("%s/%d/%s.csv",$self->path,$self->day,$self->stock);
			$self->fp = fopen($fname,"rt");
			if ($self->fp != FALSE){
				return true;
			}
			if( $self->HistoryDateInc() > $self->end ){
				return false;
			}
		}
		return true;
	}
	//! 日期加上一			
	private 
	function HistoryDateInc():int{
		list($y,$m,$d) = $self->YmdFromInt($self->day+1);
		if( $d > 31 ){
			$d = 1 ;
			$m ++ ;
		}
		if( $m > 12 ){
			$m = 1 ;
			$y ++ ;
		}
		$self->day = $y * 10000 + $m *100 + $d ;
		return $self->day;
	}
	private 
	function HistoryPrice():array{
		//!
		while( $line = fgets($self->fp) ){
			if( ! is_numeric( $line{0} ) ){
				continue;
			}
			//! 取得了一条记录,用逗号切分成数组
			$rec = explode(","),$line);
			//! 转换成价格记录
			$price = $self->HistoryRecord($rec);
		}
		#读文件失败了，那么就关闭文件吧
		fclose($self->fp);
		$self->fp == NULL;
		#要是往后都没有数据了，那么就返回一个空数组
		if( ! $self->HistoryFileOpen() ){
			return array();
		} 
		#还能打开，还有。就再读一条吧
		return $self->HistoryPrice();
	}
	//! 根据周期生成数据集合
	private 
	function HistoryRecord(array $src):array{
		//! 基本数据
		$price=array();
		$price['stock']=$self->stock;
		#根据字段的对照关系生成基本数据 
		foreach( $self->fields[$self->period] as $idx=> $name){
			$price[$name]=$src[$idx];
		}
		//! 实时指标在Method里计算
		return $price;
	}
	private 
	function YmdFromInt($ymd):array{
		$y = intval($ymd / 10000) ;
		$m = intval(($ymd - $y*10000)/100);
		$d = intval($ymd % 100 );
		return array($y,$m,$d);
	}
	final 
	function MA($field,$period,$value){
		static $cache = array();
		if ( ! array_key_exists($field,$cache) ){
			$cache[$field] = array ();
		}
		if( ! array_key_exists($period,$cache[$field]) ){
			//! 均值的初始值
			$cache[$field][$period] = $value ;
		}
		//! 简经迭代算法
		$cache[$field][$period] = ($cache[$field][$period] * (intval($period)-1)+$value)/$period;
		return $cache[$field][$period];
	}
}
