<?php
//! ǿ���ͼ��
#declare(strict_type=1);

/*
	���Ե���;��
	1.�����������Ĺ�Ʊ��
	2.�����ʽ�ͳֲ���Ϣ
	
class PolicyMiddle implements iPolicy (Policy){
	
};
	
*/
interface iPolicy{
	//! �趨����
	function set_name();
	//! ����һ������
	function hook_add_method(string $method);
	//! ����֧�ֵ����з�ʽ array("backtest","test","monitor","market");
	function hook_support_type();
	//! ͬһ��ʱ����ϣ�ֻ����һ��
	function hook_buy_period(string $date /*YYYYMMDDHHIISS.mmm*/ , array $buyset );
	function hook_sell_period(string $date /*YYYYMMDDHHIISS.mmm*/ , array $sellset );

	//! �ռ��ز⿪�̽׶�ģ��
	function hook_market_begin(string $date );
	//! �ռ��ز����̽׶�ģ��
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
	//! Ϊ�������ӷ���
	final function AddMethod(string $method){
		$this->methods[]=$method;
	}
	//! ��ʼ��
	private function Init(){
		//! �ϲ����еĹ�Ʊ��
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
	//! ����
	final function Run(string $type/*���з�ʽ*/, iDataSource $datasource/*����Դ����*/,string $period="day"/*�ز�����ݿ���*/,int $begin=0/*�ز���ʼʱ��YYYYMMDD*/,int $end=0/*�ز����ʱ��YYYYMMDD*/,bool $ignore=FALSE/*������֧��ʱ�Ĵ���ʽ*/){
		//! ������Բ�֧���������з�ʽ
		if( ! in_array( $type , $this->support_type() ) ){
			return false;
		}
		//! �������з�ʽ
		$this->type = $type ;
		//! ��������Դ
		$this->datasource = $ds;
		//! ����ǲ������з�����֧��������з�ʽ
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
		//! �ں���ģʽ�£��Ѳ�֧�ֵ�ȥ��
		foreach($remove as $n){
			unset($this->methods[$n]);
		}
		//! ��ʼ��������Ʊ��
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
	//! �ز�
	private function BackTest(array $stocks,string $period,int $begin,int $end){
		$self->begin = $begin;
		$self->end   = $end;
		$self->period = $period;
		//!��һ�׶ζ������������������
		//! �����м�Ŀ¼������ʱ��㣬��Ҫʱ���Է�������λģ��
		$base = $this->BackTestPhase1($stocks);
		//! 
		//!�ڶ��׶����ɲ�λģ����Ϣ
		$this->BackTestPhase2($base);
		return $base ;
	}
	//! �ڶ��׶�����λģ��
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
	//! ��һ�׶����ɻز��ź��ļ�
	private function BackTestPhase1($stocks):string {
		$inter = sprintf("inter/%s_%s",$this->name,date("ymdhis")) ;
		@mkdir( $inter );
		foreach($this->methods as $idx=> $method ){
			$path = sprintf("%s/%s",$inter,$method);
			@mkdir($path);
		}
		//! ���ɽ���
		foreach($this->methods as $idx=> $method ){
			$func = $self->BackTestEnumStock;
			$args = array($inter,$method,$stock); 
			//! �½��̽��������
			proc_fork($func,$args);
		}
		//! �ϲ����еķ����ź��ļ�
		$cmd = sprintf("cat %s/*.bs > %s/%s.temp",$inter,$inter,$self->name);
		system(cmd);
		$cmd = sprintf("sort %s/%s.temp > %s/%s.signal",$inter,$self->name,$inter,$self->name);
		system(cmd);
		return $inter;
	}
	//! Ϊÿ������ö����Ҫ����Ĺ�Ʊ
	private function BackTestEnumStock(string $inter/*����м�����Ŀ¼*/,
									string $method/*Ҫ���еķ�������*/,
									$stocks/*��Ʊ�����б�*/):bool {
		//!���������м�Ŀ¼
		$path = sprintf("%s/%s",$inter,$method);
		@mkdir($path);
		//! ö�ٹ�Ʊ
		$pool = $this->$method->pool;
		foreach($stocks as $stock){
			if( ! in_array("all",$pool) && ! in_array($stock,$pool) ){
				continue;
			}
			$func = $self->BackTestEnumStockRecord;
			$args = array ($path,$method,$stock) ;
			//! �ҵ�����Ҫ�Ĺ�Ʊ�ˣ��������½��̣��������ֱ�Ӿ����к��ͷ�
			proc_fork($func,$args);
		}
		$proc_wait();
		//! �ϲ���ǰ�����е����������źţ�Ȼ������
		//! �ϲ����еĹ�Ʊ�ź��ļ�
		$cmd = sprintf("cat %s/*.bs > %s/%s.temp",$path,$path,$method);
		system(cmd);
		//! �ϲ����ϼ������  $method.bs
		$cmd = sprintf("sort %s/%s.temp > %s/%s.bs",$path,$method,$inter,$method);
		system(cmd);
		//! �˴������ڴ治������⣬��ʱ������
		//! ������������Ը��� 
		//!		cat *.bs > $method.tmp
		//!     sort $method.tmp > $method.bs
		/*
		$dh = opendir($path);
		if( $dh == FALSE){
			return FALSE;
		}
		$all = array();
		while (($file = readdir($dh)) !== false){
			//! ����Ҫ��ȥ��
			if( $file{0} == "." ){
				continue;
			}
			//!��Ϊ�������
			$d = file( sprintf("%s/%s",$path,$file) );
			$all += $d ;
		}	
		closedir($dh);
		
		$fn = sprintf("%s/%s.bs",$inter,$method);
		file_put_contents($fn,$all);
		*/
		return TRUE;
	}
	//! ö�ٹ�Ʊ������
	private function BackTestEnumStockRecord(string $base/* ��ǰ�����ݸ�Ŀ¼��ʽ��policy/method */,
											string $method/*���÷���*/,
											string $stock/*��ǰ��Ʊ*/){
		$this->datasource->Open($period,$this->begin,$this->end);
		$fn = sprintf("%s/%s.bs",$base,$stock);
		$fp = fopen($fn,"wt+");
		//!  �������¼��𣬲��гɽ����ͷ���
		//!         ʱ��,�����źŵķ���,��/��,��Ʊ,�۸�,�ɽ���,����
		#fprintf($fp,"date,method,action,stock,price,amount,dir,\r\n");
		//! Ϊ�˼��ٺϲ�ʱ�Ĺ���������ͷ������
		while( $price = $this->datasource->EnumPrice() ){
			//! ���÷���������۸�
			$result = $method->Price($stock,$price);
			//! û���źų���
			if( sizeof($result) == 0 ){
				continue;
			}
			//! ���ص�ǰ�����ͺͼ۸�
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
	//! ����ʱ�䷵��ʱ��
	private function MarketTime()/*:int*/{
		//! Ҫ������
		$w = date('w');
		if( $w <1 || $w > 5 ){
			return 0;
		}
		//! Ҫ����ʱ��
		if( $hi < 915 || ($hi > 1130 and $hi<1300) || ($hi>1500)){
			return 0;
		}
		//! ��ȷ����
		return int(date("His"));
	}
	//! ʵ��
	private function Testing($stocks){
		//! ���ǵ�ǰʱ��
		while( $this->MarketTime() == 0 ){
			sleep(1);
		}
		
		while( ($stamp=$this->MarketTime())>0 ){
			$price = $datasource->Realtime();
			
		}
	}
	//! ���
	private function Monitor($stocks){
		//! ���ǵ�ǰʱ��
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
	//! ʵ��
	private function Market($stocks){
		//! ���ǵ�ǰʱ��
		while( self.MarketTime() == 0 ){
			sleep(1);
		}
		
		while( ($stamp=self.MarketTime())>0 ){
			$price = $datasource->Realtime();
			
		}
	}

	
}