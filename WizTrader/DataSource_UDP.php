<?php
include_once("DataSource.php");

/**
需要全局变量 $CSV_ROOT
	//! 除了TICK
	CSV_ROOT/{type}/ym/ymd.csv
	CSV_ROOT/tick/ym/ymd/stock.csv
*/

class cDataSource_UDP  extends cDataSource implements  iDataSource{
	var $type;
	var $stocks;
	var $res;
	var $source;
	var $begin;
	var $end;
	function hook_open($amarket/*市场*/,
						$astocks/*关注的股票*/,
						$atype/*回调还是实时,以及粒度*/,
						$aStartDate/*起始时间*/,
						$aEndDate/*结束时间*/){
		$this->type = strtoupper($atype);
		if( $this->type == "RT" ){
			return FALSE;
		}
	}
	#关闭数据源
	function hook_close(){
	}
	#实时数据
	//! CSV数据源不支持回测
	function hook_real_time(){
		return False;
	}
	#回测数据
	function hook_back_test($adate){
		global $CSV_ROOT;
		if( $this->res === FALSE ){
			$FF=TRUE;
			if( sizoef($this->flist) == 0 ){
				$DF=TRUE;
				$base = sprintf( "%s/%s/%d",$CSV_ROOT,$this->type,$this->now/100000000 /*去掉日时分秒*/);
				//! 取得文件列表
			} 
			$fn = array_pop($this->flist);
			$this->data = file($fn);
			//! 弹出第一行标题
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
	#改成数据驱动
	function hook_run(){
		
	}
};
