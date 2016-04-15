<?php
//! ǿ���ͼ��
#declare(strict_type=1);

/*
	����Դ��
*/
interface iDataSource{
	#������Դ
	function hook_open():bool;
	#�ر�����Դ
	function hook_close():bool; 
	#ʵʱ����
	function hook_real_time():array;
	#�ز�����
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
	//! ������Դ
	final 
	function Open(array $need /*��Ҫ������*/, 
						string $period/*���ڼ��*/, 
						string $stock/*��Ʊ*/, 
						int $begin/*��ʼ�� YYYYMMDD*/,
						int $end/*������ YYYYMMDD*/,
						bool $custom=False/*��ʷ���ݲ�ʹ�ù̻��ľ�����*/):bool{
		$self->period = $period;
		$self->stock = $stock;
		$self->begin = $begin;
		$self->end = $end ;
		$self->custom = $custom;
		foreach ($need as $item){
			//! �зֳ� �������
			list($i,$t)=explode("|",$item);
		}
		if( ! $self->CustomDatasource() ){
			return $self->HistoryOpen();
		}
		return $self->hook_open();
	}
	private 
	function CustomDatasource():bool{
		//! ָ����Ҫ���Ʒ�����
		if ($self->custom){
			return true;
		}
		//! ������ʷ���ݷ�����
		return (!in_array($self->period,$this->hist));
	}
	//! �ر�����Դ
	final 
	function Close():bool{
		if( ! $self->CustomDatasource() ){
			return $self->HistoryClose();
		}
		return $self->hook_close();
	}
	//! ��ȡ���µļ۸�䶯
	final 
	function Price():array{
		if( ! $self->CustomDatasource() ){
			return $self->HistoryPrice();
		}
		return $self->hook_price();
	}
	//! ʵʱ�۸�
	final 
	function Realtime():array{
		//! ȡ��ʵʱ����
		return self->hook_real_time();
	}
	//! �̻���ʷ����
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
	//! �������ļ�
	private 
	function HistoryFileOpen():bool{
		while($self->fp == NULL ){
			//! �򿪵���������ļ�
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
	//! ���ڼ���һ			
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
			//! ȡ����һ����¼,�ö����зֳ�����
			$rec = explode(","),$line);
			//! ת���ɼ۸��¼
			$price = $self->HistoryRecord($rec);
		}
		#���ļ�ʧ���ˣ���ô�͹ر��ļ���
		fclose($self->fp);
		$self->fp == NULL;
		#Ҫ������û�������ˣ���ô�ͷ���һ��������
		if( ! $self->HistoryFileOpen() ){
			return array();
		} 
		#���ܴ򿪣����С����ٶ�һ����
		return $self->HistoryPrice();
	}
	//! ���������������ݼ���
	private 
	function HistoryRecord(array $src):array{
		//! ��������
		$price=array();
		$price['stock']=$self->stock;
		#�����ֶεĶ��չ�ϵ���ɻ������� 
		foreach( $self->fields[$self->period] as $idx=> $name){
			$price[$name]=$src[$idx];
		}
		//! ʵʱָ����Method�����
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
			//! ��ֵ�ĳ�ʼֵ
			$cache[$field][$period] = $value ;
		}
		//! �򾭵����㷨
		$cache[$field][$period] = ($cache[$field][$period] * (intval($period)-1)+$value)/$period;
		return $cache[$field][$period];
	}
}
