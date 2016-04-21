<?php
include_once("DataSource.php");


class cDataSource_MYSQL  extends cDataSource implements  iDataSource{
	var $callback;
	var $type;
	var $stocks;
	var $res;
	var $source;
	var $begin;
	var $end;
	function hook_open($amarket/*市场*/,
						$astocks/*关注的股票*/,
						$atype="RT"/*回测还是实时,以及粒度*/,	/**/
						$aStartDate=""/*起始时间*/,
						$aEndDate=""/*结束时间*/){
		$atype = strtoupper($atype);
		if( $atype == "RT" ){
			//! 实时
			$this->source = "price_sina";
		}else{
			//! 回测
			$this->source = sprintf("hist_%s",$atype); 
		}
		$this->type = $atype;
		$this->stocks = $astocks ;
		$this->res = False;
		$this->begin = $aStartDate;
		$this->end = $aEndDate;
	}
	#关闭数据源
	function hook_close(){
	}
	#实时数据
	function hook_real_time(){
		static $last_version=0;
		if( $res === FALSE ){
			$sql="select * from control";
			$res=mysql_query($sql);
			$rec=mysql_fetch_array($res);
			mysql_free_result($res);
			if( $rec['update'] == $last_version ){
				return array();
			}
			$sql=sprintf("select * from price_sina where stamp='%s' ", $rec['stamp']);
			$this->res=mysql_query($sql);
		}
		$rec=mysql_fetch_array($this->res);
		if( $rec === False ){
			mysql_free_result($res);
			$res = False;
			return array();
		}
		return $rec;
	}
	#回测数据
	function hook_back_test($adata){
		static $last_version=0;
		if( $this->cond == "" ){
			foreach($this->stocks as $code){
				if($cond != "" ){
					$cond .= " or ";
				}
				$cond .= sprintf("stock = '%s' ",$code);
			}
			$this->cond = $cond ;
			$sql=sprintf("select * from %s where (%s) and date >='%s' and date<='%s' order by date asc ", 
						$this->source,$this->start,$this->end);
			$this->res = mysql_query($sql); 
		}
		$rec = mysql_fethc_array($res);
		return $rec;
	}
	#改成数据驱动
	function hook_run(){
		
	}
};
