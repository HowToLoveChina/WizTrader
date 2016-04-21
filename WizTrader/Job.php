<?php
include_once("DataSource_BIN.php");
include_once("DataSource_MYSQL.php");
include_once("DataSource_CSV.php");
include_once("DataSource_UDP.php");


define("DEBUG",TRUE);


class cJob{
	function Run(){
		if( False == $this->Init() ){
			if(DEBUG)printf("%s.%d>运行期初始化失败",__FUNCTION__,__LINE__);
			return False;
		}
		$this -> InstallDataCallback();
		switch($this -> run_type){
		case "回测":
			//! 生成买卖信号点
			$this->mDataSource->Run($this);
			//! 模拟仓位处理
			$this->Emulate();
			break;
		case "实测":
		case "实盘":
		case "监控":
			//! 加入工作日周期任务表
			$this -> InstallCron();
			$this -> InstallTimeCallback();
			$this->mDataSource->Run($this);
			break;
		default:
			if(DEBUG)printf("%s.%d>不支持的运行类型",__FUNCTION__,__LINE__);
			return False;
		}
	}
	/**初始化运行设定
	*	@return bool
	*/
	function Init(){
		if( False == $this->SetDataSource() ){
			if(DEBUG)printf("%s.%d>使用了不支持的数据源",__FUNCTION__,__LINE__);
			return False;
		}
		switch($this->run_type){
		case "回测":
			break;
		case "实盘":
		case "实测":
			//! 设定交易券商接口
			if( False == $this->SetBroker() ){
				if(DEBUG)printf("%s.%d>使用了不支持的券商接口",__FUNCTION__,__LINE__);
				return False;
			}
			if( False == $this->SetReceiver() ){
				//! 通知对象允许失败
				if(DEBUG)printf("%s.%d>设定通知接收对象失败",__FUNCTION__,__LINE__);
			}
			break;
		case "监控":
			if( False == $this->SetReceiver() ){
				//! 通知对象允许失败
				if(DEBUG)printf("%s.%d>设定通知接收对象失败",__FUNCTION__,__LINE__);
				return False;
			}
			break;
		default:
			if(DEBUG)printf("%s.%d>不支持的运行类型",__FUNCTION__,__LINE__);
			return False;
		}
		return True;
	}
	/**
	*	设定数据来源
	*	@return bool
	*/
	function SetDataSource(){
		switch( $this->data_source){
		case "MYSQL":
			$this->mDataSource = new cDataBase_MYSQL();
			break;
		case "CSV":
			$this->mDataSource = new cDataBase_CSV();
			break;
		case "BIN":
			$this->mDataSource = new cDataBase_BIN();
			break;
		case "UDP":
			$this->mDataSource = new cDataBase_UDP();
			break;
		default:
			return False;
		}
		return True;
	}
	/** 设定通知对象
	*
	*/
	function SetReceiver(){
		if( $this->notify_setting == "" ){
			return False;
		}
		return True;
	}
	/** 设定券商接口
	*		实测只能是模拟盘
	*/
	function SetBroker(){
		if ( $this->broker == "" ){
			return False;
		}
		$this->mBroker = False;
		if( $this->run_type == "实测"){
			$this->mBroker = new Broker_TEST();
			return True;
		}
		switch($this->broker){
		case "华泰":
			$this->mBroker = new Broker_HT();
			break;
		case "佣金宝":
			$this->mBroker = new Broker_YJB();
			break;
		case "银河":
			$this->mBroker = new Broker_YH();
			break;
		case "模拟":
			$this->mBroker = new Broker_TEST();
			break;
		}
		return True;
	}
	/** 生成定时调用的符号连接，除了回测都可以
	*/
	function InstallCron(){
		//将自己做一个符号连接到一个特定的目录
		//由系统的回调来定时调用
	}
	/** 将数据回调函数设定到数据源中去
	*/
	function InstallDataCallback(){
		if( False == $this->mDataSource->InstallDataCallBack($this->DataCallBack) ){
			return False;
		}
		return True;
	}
	/** 将时间回调函数设定到数据源中去
	*/
	function InstallTimeCallback(){
		if( False == $this->mDataSource->InstallTimeCallBack($this->TimeCallBack) ){
			return False;
		}
		return True;
	}
	/** 数据回调
	*	@param [in] int $ymd 年月日  YYYYMMDD
	*	@param [in] int $his 时分秒   HHIISS
	*	@param [in] string $period 数据粒度  ::= TICK | DAY | 5d | MONTH | YEAR | 1 | 5 | 10 | 30 | 60 
	*	@return bool 
	*/
	function DataCallBack(/*int*/$ymd,/*int*/$his,$period,$record){
		if(DEBUG)
		if( ! is_int($ymd)){
			return False;
		}
		if(DEBUG)
		if( ! is_int ($his) ){
			return False;
		}
		if(DEBUG)
		if( ! is_string($period)){
			return False;
		}
		if(DEBUG)
		if( ! is_array($record)){
			return False;
		}
		foreach( $this->method as $e){
			$e->EntryData($ymd,$his,$period,$record);
		}
	}
	/** 时间回调
	*/
	function TimeCallBack(/*int*/$ymd,/*int*/$his,$period){
		if(DEBUG)
		if( ! is_int($ymd)){
			return False;
		}
		if(DEBUG)
		if( ! is_int ($his) ){
			return False;
		}
		if(DEBUG)
		if( ! is_string($period)){
			return False;
		}
		foreach( $this->method as $e){
			$e->EntryTime($ymd,$his,$period);
		}
	}
};


interface iJob{
	
	
};


