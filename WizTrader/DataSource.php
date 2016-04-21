<?php
//! 强类型检查
#declare(strict_type=1);
class cDataSource{
	function __construct(){
		$this->hist  = array("day","week","month","year");
		$this->fields=array(
					"tick" => array(0=>"date",1=>"price",2=>"amount",3=>'dir'),
					"day"  => array(0=>"date",1=>'open',2=>'close',3=>'high',4=>'low',5=>'amount'),
					"finish" => array()
				);
	}
	//! 打开数据源
	final 
	function Open(array $need /*需要的数据*/, 
						string $market/*哪个市场*/,
						string $period/*周期间隔*/, 
						string $stock/*股票*/, 
						int $begin/*开始日 YYYYMMDD*/,
						int $end/*结束日 YYYYMMDD*/,
						bool $custom=False/*历史数据不使用固化的就用真*/):bool{
		$this->period = $period;
		$this->stock = $stock;
		$this->begin = $begin;
		$this->end = $end ;
		$this->custom = $custom;
		foreach ($need as $item){
			//! 切分成 项和周期
			list($i,$t)=explode("|",$item);
		}
		if( ! $this->CustomDatasource() ){
			return $this->HistoryOpen();
		}
		return $this->hook_open();
	}
	private 
	function CustomDatasource():bool{
		//! 指明了要定制返回真
		if ($this->custom){
			return true;
		}
		//! 不是历史数据返回真
		return (!in_array($this->period,$this->hist));
	}
	//! 关闭数据源
	final 
	function Close():bool{
		if( ! $this->CustomDatasource() ){
			return $this->HistoryClose();
		}
		return $this->hook_close();
	}
	//! 获取最新的价格变动
	final 
	function Price():array{
		if( ! $this->CustomDatasource() ){
			return $this->HistoryPrice();
		}
		return $this->hook_price();
	}
	//! 实时价格
	final 
	function Realtime():array{
		//! 取得实时数据
		return $this->hook_real_time();
	}
	//! 固化历史数据
	final 
	function HistoryOpen():bool{
		switch($this->period){
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
				$this->path = sprintf("%s/%s",$this->base,$this->period);
				break;
			case "60":
				$this->path = sprintf("%s/hour",$this->base);
				break;
			default:
				return false;
		}
		$this->day = $begin;
		$this->tick = $begin;
		$this->fp = NULL;
		return true;
	}
	private 
	function HistoryClose():bool{
		if( $this->fp = NULL ){
			return true;
		}
		fclose($this->fp);
		$this->fp = NULL;
		return True;
	}
	//! 打开数据文件
	private 
	function HistoryFileOpen():bool{
		while($this->fp == NULL ){
			//! 打开当天的数据文件
			$fname = sprintf("%s/%d/%s.csv",$this->path,$this->day,$this->stock);
			$this->fp = fopen($fname,"rt");
			if ($this->fp != FALSE){
				return true;
			}
			if( $this->HistoryDateInc() > $this->end ){
				return false;
			}
		}
		return true;
	}
	//! 日期加上一			
	private 
	function HistoryDateInc():int{
		list($y,$m,$d) = $this->YmdFromInt($this->day+1);
		if( $d > 31 ){
			$d = 1 ;
			$m ++ ;
		}
		if( $m > 12 ){
			$m = 1 ;
			$y ++ ;
		}
		$this->day = $y * 10000 + $m *100 + $d ;
		return $this->day;
	}
	private 
	function HistoryPrice():array{
		//!
		while( $line = fgets($this->fp) ){
			if( ! is_numeric( $line{0} ) ){
				continue;
			}
			//! 取得了一条记录,用逗号切分成数组
			$rec = explode(",",$line);
			//! 转换成价格记录
			$price = $this->HistoryRecord($rec);
		}
		#读文件失败了，那么就关闭文件吧
		fclose($this->fp);
		$this->fp == NULL;
		#要是往后都没有数据了，那么就返回一个空数组
		if( ! $this->HistoryFileOpen() ){
			return array();
		} 
		#还能打开，还有。就再读一条吧
		return $this->HistoryPrice();
	}
	//! 根据周期生成数据集合
	private 
	function HistoryRecord(array $src):array{
		//! 基本数据
		$price=array();
		$price['stock']=$this->stock;
		#根据字段的对照关系生成基本数据 
		foreach( $this->fields[$this->period] as $idx=> $name){
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
	#改成数据驱动
	function Run($job){
		//! 实时
		switch($job->run_type){
		case "回测":
			//! 回测
			for($day=$job->date_begin;$day<=$job->date_end;$day=$this->NextDay()){
				$this->mTCB($day,914,FALSE);
				$this->mTCB($day,915,TRUE);
				$this->HistoryOpen($day);
				$this->mTCB($day,929,TRUE);
				$this->HistoryRecord($job);
				$this->HistoryClose();
			}
			$job->
			break;
		case "实测":
		case "实盘":
		case "监控":
			$today = date("Ymd");
			$this->mTCB($today,$now);
			for( $now = date("His"); $now < 150000 ; $now = date("His") ,sleep(1) ){ 
				if( ! is_market_time($now) ){
					$this->mTCB($today,$now,FALSE);
					printf("非交易时段 %s \r", $now );
					sleep(10);
					continue;
				}
				$this->mTCB($today,$now,TRUE);
				$record = $this->Realtime();
				if( sizeof($record) > 0 ){
					$this->mDCB($today,$now);
				}
			}
			$this->mTCB(date("Ymd"),$now,False);
			return True;
		default:
			return False;
		}
	}
	function is_market_time($hm){
		if( $hm < 93000 ){
			return FALSE;
		}
		if( $hm < 113000 ){
			return TRUE;
		}
		if( $hm < 130000 ){
			return FALSE;
		}
		if( $hm < 150000 ){
			return TRUE;
		}
		return FALSE;
	}
	
	function InstallTimeCallBack($func){
		$this->mTCB = $func;
	}
	function InstallDataCallBack($func){
		$this->mDCB = $func;
	}
	
}

/*
	数据源类
*/
interface iDataSource{
	#打开数据源
	function hook_open($amarket/*市场*/,
						$astocks/*关注的股票*/,
						$atype/*回调还是实时,以及粒度*/,  //! 如果是实时的用RT或rt其他的就是粒度
						$aStartDate/*起始时间*/,
						$aEndDate/*结束时间*/);
	#关闭数据源
	function hook_close(/*void*/); 
	#实时数据
	function hook_real_time(/*void*/);
	#回测数据
	function hook_back_test($adate);
	#改成数据驱动
	function hook_run();
};

include_once("DataSource_CSV.php");
include_once("DataSource_MYSQL.php");
include_once("DataSource_BIN.php");
include_once("DataSource_UDP.php");

