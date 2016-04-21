<?php
include_once("DataSource.php");


class cDataSource_BIN extends cDataSource implements  iDataSource{
	function hook_open($amarket/*市场*/,
						$astocks/*关注的股票*/,
						$atype/*回调还是实时,以及粒度*/,
						$aStartDate/*起始时间*/,
						$aEndDate/*结束时间*/){
	}
	#关闭数据源
	function hook_close(){
		
	} 
	#实时数据
	function hook_real_time(){
		return False;
	}
	#回测数据
	function hook_back_test($adate){
	}
	function hook_run(){
		
	}
};
