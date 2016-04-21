<?php
//! ǿ���ͼ��
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
	//! ������Դ
	final 
	function Open(array $need /*��Ҫ������*/, 
						string $market/*�ĸ��г�*/,
						string $period/*���ڼ��*/, 
						string $stock/*��Ʊ*/, 
						int $begin/*��ʼ�� YYYYMMDD*/,
						int $end/*������ YYYYMMDD*/,
						bool $custom=False/*��ʷ���ݲ�ʹ�ù̻��ľ�����*/):bool{
		$this->period = $period;
		$this->stock = $stock;
		$this->begin = $begin;
		$this->end = $end ;
		$this->custom = $custom;
		foreach ($need as $item){
			//! �зֳ� �������
			list($i,$t)=explode("|",$item);
		}
		if( ! $this->CustomDatasource() ){
			return $this->HistoryOpen();
		}
		return $this->hook_open();
	}
	private 
	function CustomDatasource():bool{
		//! ָ����Ҫ���Ʒ�����
		if ($this->custom){
			return true;
		}
		//! ������ʷ���ݷ�����
		return (!in_array($this->period,$this->hist));
	}
	//! �ر�����Դ
	final 
	function Close():bool{
		if( ! $this->CustomDatasource() ){
			return $this->HistoryClose();
		}
		return $this->hook_close();
	}
	//! ��ȡ���µļ۸�䶯
	final 
	function Price():array{
		if( ! $this->CustomDatasource() ){
			return $this->HistoryPrice();
		}
		return $this->hook_price();
	}
	//! ʵʱ�۸�
	final 
	function Realtime():array{
		//! ȡ��ʵʱ����
		return $this->hook_real_time();
	}
	//! �̻���ʷ����
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
	//! �������ļ�
	private 
	function HistoryFileOpen():bool{
		while($this->fp == NULL ){
			//! �򿪵���������ļ�
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
	//! ���ڼ���һ			
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
			//! ȡ����һ����¼,�ö����зֳ�����
			$rec = explode(",",$line);
			//! ת���ɼ۸��¼
			$price = $this->HistoryRecord($rec);
		}
		#���ļ�ʧ���ˣ���ô�͹ر��ļ���
		fclose($this->fp);
		$this->fp == NULL;
		#Ҫ������û�������ˣ���ô�ͷ���һ��������
		if( ! $this->HistoryFileOpen() ){
			return array();
		} 
		#���ܴ򿪣����С����ٶ�һ����
		return $this->HistoryPrice();
	}
	//! ���������������ݼ���
	private 
	function HistoryRecord(array $src):array{
		//! ��������
		$price=array();
		$price['stock']=$this->stock;
		#�����ֶεĶ��չ�ϵ���ɻ������� 
		foreach( $this->fields[$this->period] as $idx=> $name){
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
	#�ĳ���������
	function Run($job){
		//! ʵʱ
		switch($job->run_type){
		case "�ز�":
			//! �ز�
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
		case "ʵ��":
		case "ʵ��":
		case "���":
			$today = date("Ymd");
			$this->mTCB($today,$now);
			for( $now = date("His"); $now < 150000 ; $now = date("His") ,sleep(1) ){ 
				if( ! is_market_time($now) ){
					$this->mTCB($today,$now,FALSE);
					printf("�ǽ���ʱ�� %s \r", $now );
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
	����Դ��
*/
interface iDataSource{
	#������Դ
	function hook_open($amarket/*�г�*/,
						$astocks/*��ע�Ĺ�Ʊ*/,
						$atype/*�ص�����ʵʱ,�Լ�����*/,  //! �����ʵʱ����RT��rt�����ľ�������
						$aStartDate/*��ʼʱ��*/,
						$aEndDate/*����ʱ��*/);
	#�ر�����Դ
	function hook_close(/*void*/); 
	#ʵʱ����
	function hook_real_time(/*void*/);
	#�ز�����
	function hook_back_test($adate);
	#�ĳ���������
	function hook_run();
};

include_once("DataSource_CSV.php");
include_once("DataSource_MYSQL.php");
include_once("DataSource_BIN.php");
include_once("DataSource_UDP.php");

