<?php
include_once("DataSource.php");

/**
��Ҫȫ�ֱ��� $CSV_ROOT
	//! ����TICK
	CSV_ROOT/{type}/ym/ymd.csv
	CSV_ROOT/tick/ym/ymd/stock.csv
*/

class cDataSource_CSV  extends cDataSource implements  iDataSource{
	var $type;
	var $stocks;
	var $res;
	var $source;
	var $begin;
	var $end;
	function hook_open($amarket/*�г�*/,
						$astocks/*��ע�Ĺ�Ʊ*/,
						$atype/*�ص�����ʵʱ,�Լ�����*/,
						$aStartDate/*��ʼʱ��*/,
						$aEndDate/*����ʱ��*/){
		$this->type = strtoupper($atype);
		if( $this->type == "RT" ){
			return FALSE;
		}
	}
	#�ر�����Դ
	function hook_close(){
	}
	#ʵʱ����
	//! CSV����Դ��֧�ֻز�
	function hook_real_time(){
		return False;
	}
	#�ز�����
	function hook_back_test($adate){
	//function hook_back_test(&$FF/*�ļ��任��*/,$DF/*���ڱ任*/){
		global $CSV_ROOT;
		if( $this->res === FALSE ){
			$FF=TRUE;
			if( sizoef($this->flist) == 0 ){
				$DF=TRUE;
				$base = sprintf( "%s/%s/%d",$CSV_ROOT,$this->type,$this->now/100000000 /*ȥ����ʱ����*/);
				//! ȡ���ļ��б�
			} 
			$fn = array_pop($this->flist);
			$this->data = file($fn);
			//! ������һ�б���
			$this->fields = explode(",",array_pop($this->data));
			$this->res = TRUE;
		}
		if( sizeof($this->data) == 0 ){
			$this->res = FALSE;
			return $this->hook_back_test($FF,$DF);
		}
		$line = array_pop($this->data);
		return explode(",",$line);
	}
	#�ĳ���������
	function hook_run(){
		
	}
	
	
};
