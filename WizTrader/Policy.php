<?php
//! 强类型检查
#declare(strict_type=1);

/*
	策略的用途：
	1.管理方法产生的股票池
	2.管理资金和持仓信息
	
class PolicyMiddle implements iPolicy (Policy){
	
};
	
*/
interface iPolicy{
	//! 设定名字
	function set_name();
	//! 传入一个方法
	function hook_add_method(string $method);
	//! 返回支持的运行方式 array("backtest","test","monitor","market");
	function hook_support_type();
	//! 同一个时间点上，只处理一次
	function hook_buy_period(string $date /*YYYYMMDDHHIISS.mmm*/ , array $buyset );
	function hook_sell_period(string $date /*YYYYMMDDHHIISS.mmm*/ , array $sellset );

	//! 日级回测开盘阶段模拟
	function hook_market_begin(string $date );
	//! 日级回测收盘阶段模拟
	function hook_market_end(string $date );
};
class cPolicy{
	function __construct(){
		$this->all_type = array("backtest","test","monitor","market");
		$this->type 			= NULL;
		$this->datasource 		= NULL;
		$this->name 			= NULL;
		$this->begin 			= NULL;
		$this->end 				= NULL;
	}
	//! 为策略增加方法
	final function AddMethod(string $method){
		$this->methods[]=$method;
	}
	//! 初始化
	private function Init(){
		//! 合并所有的股票池
		$all = array();
		foreach($this->methods as $method){
			$this->$method = new $method;
			$pool = $this->$method->Pool();
			$pool = array_merge($pool,$all);
		}
		$pool = array_unique($pool);
		if( in_array("all",$this) ){
			$this->pool = array("all");
		}else{
			$this->pool = $pool;
		}
		
	}
	//! 运行
	final function Run(string $type/*运行方式*/, iDataSource $datasource/*数据源对象*/,string $period="day"/*回测的数据颗粒*/,int $begin=0/*回测起始时间YYYYMMDD*/,int $end=0/*回测结束时间YYYYMMDD*/,bool $ignore=FALSE/*方法不支持时的处理方式*/){
		//! 这个策略不支持这种运行方式
		if( ! in_array( $type , $this->support_type() ) ){
			return false;
		}
		//! 保存运行方式
		$this->type = $type ;
		//! 保存数据源
		$this->datasource = $ds;
		//! 检查是不是所有方法都支持这个运行方式
		$remove = array();
		foreach($this->methods as $idx=>$method){
			if( ! in_array($type,$this->$method->Support()) ){
				if( ! $ignore ){ 
					$this->errmsg = "$method not support type $type";
					return false;
				}else{
					$remove[] = $idx;
				}
			}
		}
		//! 在忽略模式下，把不支持的去掉
		foreach($remove as $n){
			unset($this->methods[$n]);
		}
		//! 初始化产生股票池
		$this->Init();
		if( in_array("all",$this->pool) ){
			$stocks = $datasource->all();
		}else{
			$stocks = $this->pool;
		}
		switch($type){
			case "backtest":
				return $this->BackTest($stocks,$period,$begin,$end);
			case "testing":
				return $this->Testing($stocks);
			case "monitor":
				return $this->Monitor($stocks);
			case "market":
				return $this->Market($stocks);
		}
	}
	//! 回测
	private function BackTest(array $stocks,string $period,int $begin,int $end){
		$self->begin = $begin;
		$self->end   = $end;
		$self->period = $period;
		//!第一阶段多进程生成买入卖出点
		//! 生成中间目录后面有时间点，必要时可以反复做仓位模拟
		$base = $this->BackTestPhase1($stocks);
		//! 
		//!第二阶段生成仓位模拟信息
		$this->BackTestPhase2($base);
		return $base ;
	}
	//! 第二阶段做仓位模拟
	private function BackTestPhase2(string $inter){
		$fc = file( sprintf("%s/%s.signal",$inter,$self->name) );
		#fprintf($fp,"date,method,action,stock,price,amount,dir,\r\n");
		$IdxDate 	= 0 ;
		$IdxMethod 	= 1 ;
		$IdxAction 	= 2 ;
		$IdxStock 	= 3 ;
		$IdxPrice 	= 4 ;
		$IdxAmount 	= 5 ;
		$IdxDir 	= 6 ;
		$date = "";
		foreach($fc as $line ){
			$rec = explode(",",$line);
			if( $rec[$IdxDate] != $date ){
				$self->hook_market_end ($date,$recs);
				$date = $rec;
				$recs = array();
				$recs [] = $rec;
			}
		}
	}
	//! 第一阶段生成回测信号文件
	private function BackTestPhase1($stocks):string {
		$inter = sprintf("inter/%s_%s",$this->name,date("ymdhis")) ;
		@mkdir( $inter );
		foreach($this->methods as $idx=> $method ){
			$path = sprintf("%s/%s",$inter,$method);
			@mkdir($path);
		}
		//! 生成进程
		foreach($this->methods as $idx=> $method ){
			$func = $self->BackTestEnumStock;
			$args = array($inter,$method,$stock); 
			//! 新进程将不会出来
			proc_fork($func,$args);
		}
		//! 合并所有的方法信号文件
		$cmd = sprintf("cat %s/*.bs > %s/%s.temp",$inter,$inter,$self->name);
		system(cmd);
		$cmd = sprintf("sort %s/%s.temp > %s/%s.signal",$inter,$self->name,$inter,$self->name);
		system(cmd);
		return $inter;
	}
	//! 为每个方法枚举需要处理的股票
	private function BackTestEnumStock(string $inter/*存放中间结果的目录*/,
									string $method/*要运行的方法类名*/,
									$stocks/*股票代码列表*/):bool {
		//!成生方法中间目录
		$path = sprintf("%s/%s",$inter,$method);
		@mkdir($path);
		//! 枚举股票
		$pool = $this->$method->pool;
		foreach($stocks as $stock){
			if( ! in_array("all",$pool) && ! in_array($stock,$pool) ){
				continue;
			}
			$func = $self->BackTestEnumStockRecord;
			$args = array ($path,$method,$stock) ;
			//! 找到了需要的股票了，产生的新进程，不会出来直接就运行后释放
			proc_fork($func,$args);
		}
		$proc_wait();
		//! 合并当前策略中的所有买卖信号，然后排序
		//! 合并所有的股票信号文件
		$cmd = sprintf("cat %s/*.bs > %s/%s.temp",$path,$path,$method);
		system(cmd);
		//! 合并到上级，变成  $method.bs
		$cmd = sprintf("sort %s/%s.temp > %s/%s.bs",$path,$method,$inter,$method);
		system(cmd);
		//! 此处或有内存不足的问题，暂时不考虑
		//! 如果遇到，可以改用 
		//!		cat *.bs > $method.tmp
		//!     sort $method.tmp > $method.bs
		/*
		$dh = opendir($path);
		if( $dh == FALSE){
			return FALSE;
		}
		$all = array();
		while (($file = readdir($dh)) !== false){
			//! 不需要的去掉
			if( $file{0} == "." ){
				continue;
			}
			//!作为数组读入
			$d = file( sprintf("%s/%s",$path,$file) );
			$all += $d ;
		}	
		closedir($dh);
		
		$fn = sprintf("%s/%s.bs",$inter,$method);
		file_put_contents($fn,$all);
		*/
		return TRUE;
	}
	//! 枚举股票的数据
	private function BackTestEnumStockRecord(string $base/* 当前的数据根目录格式是policy/method */,
											string $method/*适用方法*/,
											string $stock/*当前股票*/){
		$this->datasource->Open($period,$this->begin,$this->end);
		$fn = sprintf("%s/%s.bs",$base,$stock);
		$fp = fopen($fn,"wt+");
		//!  日线以下级别，才有成交量和方向
		//!         时间,触发信号的方法,买/卖,股票,价格,成交量,方向
		#fprintf($fp,"date,method,action,stock,price,amount,dir,\r\n");
		//! 为了减少合并时的工作量，表头不加入
		while( $price = $this->datasource->EnumPrice() ){
			//! 调用方法来处理价格
			$result = $method->Price($stock,$price);
			//! 没有信号出来
			if( sizeof($result) == 0 ){
				continue;
			}
			//! 返回当前的类型和价格
			foreach( $result as $type ){
				fprintf($fp,"%s,%s,%s,%s,%s,%d,%s,\r\n",
						$price['date'],$method,
						$type,$stock,
						$price['price'],$price['amount'],$price['dir']);
			}
		}
		fclose($fp);
		$self->datasource->Close();
	}
	//! 交易时间返回时分
	private function MarketTime()/*:int*/{
		//! 要工作日
		$w = date('w');
		if( $w <1 || $w > 5 ){
			return 0;
		}
		//! 要交易时段
		if( $hi < 915 || ($hi > 1130 and $hi<1300) || ($hi>1500)){
			return 0;
		}
		//! 精确到秒
		return int(date("His"));
	}
	//! 实测
	private function Testing($stocks){
		//! 这是当前时间
		while( $this->MarketTime() == 0 ){
			sleep(1);
		}
		
		while( ($stamp=$this->MarketTime())>0 ){
			$price = $datasource->Realtime();
			
		}
	}
	//! 监测
	private function Monitor($stocks){
		//! 这是当前时间
		while( $this->MarketTime() == 0 ){
			sleep(1);
		}
		
		while( ($stamp=$this->MarketTime())>0 ){
			$price = $datasource->Realtime();
			foreach( $this->methods as $m ){
				$m->price($price['stock'],$price);
			} 
			
		}
	}
	//! 实盘
	private function Market($stocks){
		//! 这是当前时间
		while( self.MarketTime() == 0 ){
			sleep(1);
		}
		
		while( ($stamp=self.MarketTime())>0 ){
			$price = $datasource->Realtime();
			
		}
	}

	
}