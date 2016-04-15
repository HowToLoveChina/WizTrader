<?php
//! ǿ���ͼ��
#declare(strict_type=1);

/*
	���������������������������Ϣ
	
��Ҫ����������ʽ������	
	
class Method0007 implements iMethod (Method){
};
	
*/

interface iMethod
{
	//! �趨����
	function set_name();
	//==========================================================================
	//! �����տ�ʼǰ
	function hook_day_before();
	//! �����ս�����
	function hook_day_after();	
	//==========================================================================
	//! ��ʽ����ǰ�����Ͼ��ۺ�
	function hook_market_before();
	//! ��ʽ���׽���ǰ������
	function hook_market_after();
	//==========================================================================
	//! ί��ǰ
	function hook_entrust_before(string $stock,float $price, int $amount);
	//! ί�к�
	function hook_entrust_after(string $stock,float $price,int $amount);
	//==========================================================================
	//! ����ǰ
	function hook_cancel_before(string $stock,float $price,int $amount);
	//! ������
	function hook_cancel_after(string $stock/*�����Ĺ�Ʊ����*/,float $price/*�����ļ�*/, int $amount/*��������*/, bool $result/*�����Ƿ�ɹ�*/);
	//==========================================================================
	//! ÿһ����������,����һ������  array("buy"=>data,"sell"=>data)
	//! data����ѡ���Ʊ
	function hook_price(string $stock, array $price/*���飬����ṹ�����з�ʽ������*/);
	//! ���ع�Ʊ������
	function hook_pool(){array;
	//! ֧�ֵ����з���
	function hook_support(){array;
	//! ������Ҫ��ָ��
	function hook_need(){array;
};

//! ������
class cMethod{
	function __construct(){
		//! ����������
		$this->name = "";
		//! ������Ҫ�Ĳ�����Ĭ�ϵ���DAY,HIGH,LOW,OPEN,CLOSE,AMOUNT,VOLUME
		$this->need = array();
	}	
	final function Set( string $type/*���е�����*/ , int $ymd/*���е�����YYYYMMDD*/ ){
		$this->type = $type;
		$this->ymd = $ymd;
	}
	//! ��Ҫʲô����ָ��
	final function Require(string $algo /*�㷨����MA*/, string $field/*���ĸ��ֶ�*/, string $period/*ָ������*/){
		$item="$algo|$field|$period";
		$self->need[]=array("name"=>$item,"field"=>$field,"period"=>$period,"value":0);
		//! ��Ҫ�������к�ֵ
		$self->data[$item]=array("seq"=>array(),"value"=>0);
	}
	//! 
	final function DayBefore(){
		return $this->hook_day_before();
	}
	//! 
	final function DayAfter(){
		return $this->hook_day_after();
	}
	//! 
	final function MarketBefore(){
		return $this->hook_market_before();
	}
	//! 
	final function MarketAfter(){
		return $this->hook_market_after();
	}
	//! 
	final function EntrustBefore(){
		return $this->hook_entrust_before();
	}
	//! 
	final function EntrustAfter(){
		return $this->hook_entrust_after();
	}
	//! 
	final function CancelBefore(){
		return $this->hook_cancel_before();
	}
	//! 
	final function CancelAfter(){
		return $this->hook_cancel_after();
	}
	//! 
	final function Price(string $stock,array $price){
		//! ʵʱ��������
		$self->price = price ;
		foreach($self->need as $algo => $param){
			//!    ����������             �㷨         �ֶ�            ����
			//!    EMA|OPEN|5             EMA          OPEN            5
			//!    ��ʾ���ü������տ��̾���
			//!    CROSS|OPEN,CLOSE|0       CROSS        OPEN,CLOSE    0
			  
			$field = $param['field'];
			$value = $self->expr( $param['field'] );
 			$price[ $param['name'] ]=$self->$algo($field,$param['period'],$value);
		}
		return $this->hook_price($price['stock'],$price);
	}
	//! ����Ψһ�Ĺ�Ʊ��,�����all����ôֻ��Ҫ����all
	final function Pool(){
		$pool = $this->hook_pool();
		if( in_array("all",$pool) ){
			$self->pool = array( "all" );
		}else{
			$self->pool = array_unique($pool);
		}
		return $self->pool;
	}
	//! ����֧�ֵ����з���
	final function Support(){
		return $this->hook_support();
	}
	
	final function H(){
		return $self->price['high'];
	}
	final function L(){
		#3�� LOW ��ͼ�
		#���ظ�������ͼۡ�
		return $self->price['low'];
	}
	final function C(){
		#6�� C ���̼�
		#���ظ��������̼ۡ�
		#�÷��� C
		return $self->price['close'];
	}		
	final function V(){
		#8�� V �ɽ���
		#���ظ����ڳɽ�����
		return $self->price['volume'];
		
	final function O(){
		#10�� O�� ���̼�
		#���ظ����ڿ��̼ۡ�
		return $self->price['open'];
	}
	final function ADVANCE(){
		#11�� ADVANCE ���Ǽ���
		#���ظ��������Ǽ�����
		#�÷��� ADVANCE��(���������Դ�����Ч)
		return $self->advance;
	}
	final function DECLINE(){
		#12�� DECLINE �µ�����
		#���ظ������µ�������
		#�÷��� DECLINE��(���������Դ�����Ч)
		return $self->decline;
	}
	final function AMOUNT(){
		#13�� AMOUNT �ɽ���
		#���ظ����ڳɽ��
		#�÷��� AMOUNT
		return $self->price['amount'];
	}
	final function ASKPRICE($an){
		#14�� ASKPRICE ί����
		#����ί��1--ί��3�۸�
		#�÷��� ASKPRICE(N)��Nȡ1��3��
		#(�������������ڷֱʳɽ�����������Ч)
		return $self->price["ask_".$am];
	}
	final function ASKVOL($an){
		#15�� ASKVOL ί����
		#����ί��1--ί��3����
		#�÷��� ASKVOL(N)��Nȡ1��3��
		#(�������������ڷֱʳɽ�����������Ч)
		return $self->price["askv_".$am];
	}
	final function BIDPRICE($an){
		#16�� BIDPRICE ί���
		#����ί��1--ί��3�۸�
		#�÷��� BIDPRICE(N)��Nȡ1��3��
		#(�������������ڷֱʳɽ�����������Ч)
		return $self->price["bid_".$am];
	}
	final function BIDVOL($an){
		#17�� BIDVOL ί����
		#����ί��1--ί��3����
		#�÷��� BIDVOL(N)��Nȡ1��3��
		#(�������������ڷֱʳɽ�����������Ч)
		return $self->price["bidv_".$am];
	}
	final function BUYVOL(){
		#18�� BUYVOL ����������
		#����������������
		#�÷�����BUYVOL�������ʳɽ�Ϊ����������ʱ������ֵ���ڳɽ���������Ϊ0��(�������������ڷֱʳɽ�����������Ч)
		if( $self->price['dir'] == 'B' ){
			return $self->price['volume'];
		}
		return 0;
	}

	final function SELLVOL(){
		#19�� SELLVOL ����������
		#������������������
		#�÷�����SELLVOL�������ʳɽ�Ϊ����������ʱ������ֵ���ڳɽ���������Ϊ0��(�������������ڷֱʳɽ�����������Ч)
		if( $self->price['dir'] == 'S' ){
			return $self->price['volume'];
		}
		return 0;
	}
	final function ISBUYORDER(){
		#20�� ISBUYORDER ��������
		#���ظóɽ��Ƿ�Ϊ�������򵥡�
		#�÷��� ISBUYORDER�������ʳɽ�Ϊ����������ʱ������1������Ϊ0��
		#(�������������ڷֱʳɽ�����������Ч)
		if( $self->price['dir'] == 'B' ){
			return 1
		}
		return 0
	}
	final function ISSELLORDER(){
		#21�� ISSELLORDER ����������
		#���ظóɽ��Ƿ�Ϊ������������
		#�÷��� ISSELLORDER�������ʳɽ�Ϊ����������ʱ������1������Ϊ0��
		#(�������������ڷֱʳɽ�����������Ч)
		if( $self->price['dir'] == 'S' ){
			return 1;
		}
		return 0;
	}
	final function BSAMOUNT(){
		#22�� �ɽ��Ԫ����AMOUNT ��ʳɽ���ǰ�ĳɽ���
		return $self->price['amount'];
	}
	final function VOLINSTK(){
		#23�� �ֲ�����VOLINSTK
		}
	}
	final function BUYVOL(){
		#24�� ���̣��֣���BUYVOL
		return $self->buyvol;
	}

	final function SELLVOL(){
		#25�� ���̣��֣���SELLVOL
		return $self->sellvol;
	}
	final function DATE(){
		#1�� DATE ����
		#ȡ�ø����ڴ�1900�����������ա�
		#�÷��� DATE�����纯������1000101����ʾ2000��1��1�ա�
		return $self->date;
	}

	final function TIME(){
		#2�� TIME ʱ��
		#ȡ�ø����ڵ�ʱ���롣
		#�÷��� TIME������������Чֵ��ΧΪ(000000-235959)��
		return $self->time;
	}
	final function YEAR(){
		#3�� YEAR ���
		#ȡ�ø����ڵ���ݡ�
		#�÷���YEAR
		return intval($self->date/10000);
	}
	final function MONTH(){
		#4�� MONTH �·�
		#ȡ�ø����ڵ��·ݡ�
		#�÷���MONTH������������Чֵ��ΧΪ(1-12)��
		return intval( ($self->date/100) % 100);
	}
	final function WEEK(){
		#5�� WEEK ����
		#ȡ�ø����ڵ���������
		#�÷��� WEEK������������Чֵ��ΧΪ(0-6)��0��ʾ�����졣
		list($y,$m,$d) = $self->YmdFromInt($self->date);
		return date("w",mktime($y,$m,$d))
	}

	final function DAY(){
		#6�� DAY ����
		#ȡ�ø����ڵ����ڡ�
		#�÷��� DAY������������Чֵ��ΧΪ(1-31)��
		return intval($self->date% 100);
	}
	final function HOUR(){
		#7�� HOUR Сʱ
		#ȡ�ø����ڵ�Сʱ����
		#�÷��� HOUR������������Чֵ��ΧΪ(0-23)���������߼������ķ�������ֵΪ0��
		return intval($self->time / 100);
	} 
	final function MINUTE(){
		#8�� MINUTE ����
		#ȡ�ø����ڵķ�������
		#�÷��� MINUTE������������Чֵ��ΧΪ(0-59)���������߼������ķ�������ֵΪ0��
		return intval($self->time % 100);
	}
	final function FROMOPEN(){
		#9�� FROMOPEN ����
		#��ǰʱ�̾࿪���ж೤ʱ�䡣
		#�÷�����FROMOPEN�����ص�ǰʱ�̾࿪���ж೤ʱ�䣬��λΪ���ӡ�
		#����:�� FROMOPEN����ǰʱ��Ϊ����ʮ�㣬�򷵻�31��
		return $self->minutes;
	}
	final function TFILT($aw,$bymd,$eymd){
		#10�� TFILT ����
		#��ָ��ʱ��ε����ݽ��й���,��ʱ��������������Ч.
		#�÷�:
		#TFILT(X,D1,M1,D2,M2)
		#����TFILT(CLOSE,1040101,1025,1040101,1345)��ʾ��2004��1��1�յ�10:25��2004��1��1�յ�13:45�����̼�����Ч��.
		#��������Ϊ������λ��,��ʱΪ0��Ч.
	}
	final function PERIOD(){
		#11�� ���ڣ�PERIOD
		#ȡ����������.
		#�����0��11,���ηֱ���1/5/15/30/60����,��/��/��,�����,����,��,��.
		#���ú���
		return $self->period;
	}
	final function BACKSET(){
		#2�� BACKSET ��ǰ��ֵ
		#����ǰλ�õ���������ǰ��������Ϊ1��
		#�÷�����BACKSET(X��N)����X��0���򽫵�ǰλ�õ�N����ǰ����ֵ��Ϊ1��
		#���磺��BACKSET(CLOSE>OPEN��2)���������򽫸����ڼ�ǰһ������ֵ��Ϊ1������Ϊ0��
	}
	final function BARSCOUNT(){
		#3�� BARSCOUNT ��Ч����������
		#���ܵ���������
		#�÷�����BARSCOUNT(X)����һ����Ч���ݵ���ǰ��������
		#���磺��BARSCOUNT(CLOSE)��������������ȡ�����������ܽ������������ڷֱʳɽ�ȡ�õ��ճɽ�����������1������ȡ�õ��ս��׷�������
	}
	final function CURRBARSCOUNT(){
		#4�� CURRBARSCOUNT ��������յ�������
		#��������յ�������.
		#�÷�:
		#CURRBARSCOUNT ��������յ�������
	}
	final function TOTALBARSCOUNT(){
		#5�� TOTALBARSCOUNT �ܵ�������
		#���ܵ�������.
		#�÷�:
		#TOTALBARSCOUNT ���ܵ������� 
	}
	final function BARSLAST(){
		#6�� BARSLAST ��һ����������λ��
		#��һ��������������ǰ����������
		#�÷�����BARSLAST(X)����һ��X��Ϊ0�����ڵ�������
		#���磺��BARSLAST(CLOSE/REF(CLOSE,1)>=1.1)����ʾ��һ����ͣ�嵽��ǰ����������
	}
	final function BARSSINCE(cond)
		#7�� BARSSINCE ��һ����������λ��
		#��һ��������������ǰ����������
		#�÷�����BARSSINCE(X)����һ��X��Ϊ0�����ڵ�������
		#���磺��BARSSINCE(HIGH>10)����ʾ�ɼ۳���10Ԫʱ����ǰ����������
	}
	final function COUNT(){
		#8�� COUNT ͳ��
		#ͳ��������������������
		#�÷�����COUNT(X��N)��ͳ��N����������X����������������N=0��ӵ�һ����Чֵ��ʼ��
		#���磺��COUNT(CLOSE>OPEN��20)����ʾͳ��20��������������������
	}
	final function HHV(){
		#9�� HHV ���ֵ
		#�����ֵ��
		#�÷�����HHV(X��N)����N������X���ֵ��N=0��ӵ�һ����Чֵ��ʼ��
		#���磺��HHV(HIGH,30)����ʾ��30����߼ۡ�
	}
	final function HHVBARS(){
		#10�� HHVBARS ��һ�ߵ�λ��
		#����һ�ߵ㵽��ǰ����������
		#�÷�����HHVBARS(X��N)����N������X���ֵ����ǰ��������N=0��ʾ�ӵ�һ����Чֵ��ʼͳ�ơ�
		#���磺��HHVBARS(HIGH��0)�������ʷ�¸ߵ�����ǰ����������
	}
	final function LLV(){
		#11�� LLV ���ֵ
		#�����ֵ��
		#�÷�����LLV(X��N)����N������X���ֵ��N=0��ӵ�һ����Чֵ��ʼ��
		#���磺��LLV(LOW��0)����ʾ����ʷ��ͼۡ�
	}
	final function LLVBARS(){
		#12�� LLVBARS ��һ�͵�λ��
		#����һ�͵㵽��ǰ����������
		#�÷�����LLVBARS(X��N)����N������X���ֵ����ǰ��������N=0��ʾ�ӵ�һ����Чֵ��ʼͳ�ơ�
		#���磺��LLVBARS(HIGH��20)�����20����͵㵽��ǰ����������
	}
	final function REVERSE(){
		#13�� REVERSE ���෴��
		#���෴����
		#�÷�����REVERSE(X)������-X��
		#���磺��REVERSE(CLOSE)������-CLOSE��
	}
	final function REF(){
		#14�� REF ��ǰ����
		#������������ǰ�����ݡ�
		#�÷�����REF(X��A)������A����ǰ��Xֵ��
		#���磺��REF(CLOSE��1)����ʾ��һ���ڵ����̼ۣ��������Ͼ������ա�
	}
	final function REFDATE(){
		#15�� REFDATE ָ������
		#����ָ�����ڵ����ݡ�
		#�÷�����REFDATE(X��A)������A���ڵ�Xֵ��
		#���磺��REF(CLOSE��20011208)����ʾ2001��12��08�յ����̼ۡ�
	}
	final function SUM(){
		#16�� SUM �ܺ�
		#���ܺ͡�
		#�÷�����SUM(X��N)��ͳ��N������X���ܺͣ�N=0��ӵ�һ����Чֵ��ʼ��
		#���磺��SUM(VOL��0)����ʾͳ�ƴ����е�һ�������ĳɽ����ܺ͡�
	}
	final function FILTER(){
		#17�� FILTER ����
		#�����������ֵ��źš�
		#�÷�����FILTER(X��N)��X����������ɾ�����N�����ڵ�������Ϊ0��
		#���磺��FILTER(CLOSE>OPEN��5)���������ߣ�5�����ٴγ��ֵ����߲�����¼���ڡ�
	}
	final function SUMBARS(){
		#18�� SUMBARS �ۼӵ�ָ��ֵ��������
		#��ǰ�ۼӵ�ָ��ֵ�����ڵ���������
		#�÷�����SUMBARS(X��A)����X��ǰ�ۼ�ֱ�����ڵ���A����������������������
		#���磺��SUMBARS(VOL��CAPITAL)������ȫ���ֵ����ڵ���������
	}
	final function SMA(){
		#19�� SMA �ƶ�ƽ��
		#�����ƶ�ƽ����
		#�÷�����SMA(X��N��M)��X��N���ƶ�ƽ����MΪȨ�أ���Y=(X*M+Y'*(N-M))/N
	}
	final function MA(){
		#20�� MA ���ƶ�ƽ��
		#���ؼ��ƶ�ƽ����
		#�÷�����MA(X��M)��X��M�ռ��ƶ�ƽ����
	}
	final function DMA(){
		#21�� DMA ��̬�ƶ�ƽ��
		#��̬�ƶ�ƽ����
		#�÷�����DMA(X��A)����X�Ķ�̬�ƶ�ƽ����
		#�㷨������Y=DMA(X��A)�� Y=A*X+(1-A)*Y'������Y'��ʾ��һ����Yֵ��A����С��1��
		#���磺��DMA(CLOSE��VOL/CAPITAL)����ʾ���Ի�������ƽ�����ӵ�ƽ���ۡ�
	}
	final function EMA(){
		#22�� EMA(��EXPMA) ָ���ƶ�ƽ��
		#����ָ���ƶ�ƽ����
		#�÷�����EMA(X��M)��X��M��ָ���ƶ�ƽ����
	}
	final function MEMA(){
		#23�� MEMA ƽ���ƶ�ƽ��
		#����ƽ���ƶ�ƽ��
		#�÷�����MEMA(X��M)��X��M��ƽ���ƶ�ƽ����MEMA(X,N)��MA�Ĳ��������ʼֵΪһƽ��ֵ,�����ǳ�ʼֵ
	}
	final function EXPMEMA(){
		#24�� EXPMEMA ָ��ƽ���ƶ�ƽ��
		#����ָ��ƽ���ƶ�ƽ����
		#�÷�����EXPMEMA(X��M)��X��M��ָ��ƽ���ƶ�ƽ����EXPMEMAͬEMA(��EXPMA)�Ĳ������������ʼֵΪһƽ��ֵ
	}
	final function RANGE(){
		#25�� RANGE ����ĳ����Χ֮��
		#�÷�����RANGE(A,B,C)��A��B��C��
		#���磺��RANGE(A��B��C)��ʾA����BͬʱС��Cʱ����1�����򷵻�0
	}
	final function CONST(){
		#26�� CONST ȡֵ��Ϊ����
		#�÷�: ��CONST(A)��ȡA����ֵΪ����.
		#���磺��CONST(INDEXC)��ʾȡ�����ּۡ�
	}
	final function ISLASTBAR(){
		#27�� ISLASTBAR �ж��Ƿ�Ϊ���һ������
	}
	final function BARSLASTCOUNT(){
		#28�� BARSLASTCOUNT ͳ������������������
		#�÷�:
		#BARSLASTCOUNT(X),ͳ����������X������������.
		#����:BARSLASTCOUNT(CLOSE>OPEN)��ʾͳ������������������
	}
	final function XMA(){
		#29�� XMA ƫ���ƶ�ƽ��
		#�÷�:
		#XMA(X,M):X��M��ƫ���ƶ�ƽ��
	}
	final function TOPRANGE(){
		#30�� TOPRANGE ��ǰֵ�ǽ����������ڵ����ֵ
		#�÷�:
		#TOPRANGE(X):X�ǽ�����������X�����ֵ
		#����:TOPRANGE(HIGH)��ʾ��ǰ��߼��ǽ����������ڵ���߼�
	}
	final function LOWRANGE(){
		#31�� LOWRANGE ��ǰֵ�ǽ����ٸ������ڵ���Сֵ
		#�÷�:
		#LOWRANGE(X):X�ǽ�����������X����Сֵ
		#����:LOWRANGE(LOW)��ʾ��ǰ��߼��ǽ����������ڵ���С��
	}
	final function CROSS(A,B,N):
		#
		#�߼�����
		#1�� CROSS �ϴ�
		#�����߽��档
		#�÷�����CROSS(A��B)����ʾ��A���·����ϴ���Bʱ����1�����򷵻�0��
		#���磺��CROSS(MA(CLOSE��5)��MA(CLOSE��10))����ʾ5�վ�����10�վ��߽���档
	}
	final function LONGCROSS(aR1,aR2,aPeriod):
		#2�� LONGCROSS ά��һ�����ں��ϴ�
		#������ά��һ�����ں󽻲档
		#�÷�����LONGCROSS(A��B��N)����ʾA��N�����ڶ�С��B�������ڴ��·����ϴ���Bʱ����1�����򷵻�0��
	}
	final function UPNDAY(aWhich,aPeriod):
		#3�� UPNDAY ����
		#�����Ƿ�������������
		#�÷�����UPNDAY(CLOSE,M)����ʾ����M�����ڡ�
	}
	final function DOWNDAY(aWhich,aPeriod):
		#4�� DOWNNDAY ����
		#�����Ƿ��������ڡ�
		#�÷�����DOWNNDAY(CLOSE��M)����ʾ����M�����ڡ�
	}
	final function NDAY(){
		#5�� NDAY ����
		#�����Ƿ��������X>Y��
		#�÷�����NDAY(CLOSE��OPEN��3)����ʾ����3�������ߡ�
	}
	final function EXIST(){
		#6�� EXIST ����
		#�Ƿ���ڡ�
		#�÷�����EXIST(CLOSE>OPEN��10)����ʾǰ10���ڴ��������ߡ�
	}
	final function EVERY(){	
		#7�� EVERY һֱ����
		#һֱ���ڡ�
		#�÷�����EVERY(CLOSE>OPEN��10)����ʾǰ10����һֱ���ߡ�
	}
	final function LAST(){
		#8�� LAST ��������
		#�÷�����LAST(X,A,B)�� A>B����ʾ��ǰA�յ�ǰB��һֱ����X��������AΪ0����ʾ�ӵ�һ�쿪ʼ��BΪ0����ʾ�������ֹ��
		#���磺��LAST(CLOSE>OPEN��10��5)����ʾ��ǰ10�յ�ǰ5����һֱ���ߡ�
	}
	final function TESTSKIP(){
		#9�� TESTSKIP(A):������A��ֱ�ӷ���.
		#�÷�:
		#TESTSKIP(A) 
		#��ʾ�������������A��Ĺ�ʽֱ�ӷ��أ����ټ���������ı��ʽ
	}
	final function NOT($a){
		#1�� NOT ȡ��
		#���߼��ǡ�
		#�÷�����NOT(X)�����ط�X������X=0ʱ����1�����򷵻�0��
		#���磺��NOT(ISUP)����ʾƽ�̻�������
	}
	final function IF($aCond,$aTrue,$aFalse):
		#2�� IF �߼��ж�
		#����������ͬ��ֵ��
		#�÷�����IF(X��A��B)����X��Ϊ0�򷵻�A�����򷵻�B��
		#���磺��IF(CLOSE>OPEN��HIGH��LOW)��ʾ�����������򷵻����ֵ�����򷵻����ֵ��
		if( $aCond != 0 ):
			return $aTrue;
		return $aFalse;
	final function IFN(){
		#4�� IFN �߼��ж�
		#����������ͬ��ֵ��
		#�÷�����IFN(X��A��B)����X��Ϊ0�򷵻�B�����򷵻�A��
		#���磺��IFN(CLOSE>OPEN��HIGH��LOW)����ʾ�����������򷵻����ֵ�����򷵻����ֵ��
		if( $aCond == 0 ):
			return $aTrue;
		return $aFalse;
	}
	final function MAX($aA,$aB){
		#5�� MAX �ϴ�ֵ
		#�����ֵ��
		#�÷�����MAX(A,B)������A��B�еĽϴ�ֵ��
		#���磺��MAX(CLOSE-OPEN��0)����ʾ�����̼۴��ڿ��̼۷������ǵĲ�ֵ�����򷵻�0��
		return max($aA,$aB);
	}
	final function MIN($aA,$aB){
		#6�� MIN ��Сֵ
		#����Сֵ��
		#�÷�����MIN(A��B)������A��B�еĽ�Сֵ��
		#���磺��MIN(CLOSE��OPEN)�����ؿ��̼ۺ����̼��еĽ�Сֵ��
		return min($aA,$aB);
	}
	final function ACOS($a):
		#
		#��ѧ����
		#1�� ACOS ������
		#������ֵ��
		#�÷�����ACOS(X)������X�ķ�����ֵ��
		return acos($a);
	}
	final function ASIN($a):
		#2�� ASIN ������
		#������ֵ��
		#�÷�����ASIN(X)������X�ķ�����ֵ��
		return asin($a);
	}
	final function ATAN($a){
		#3�� ATAN ������
		#������ֵ��
		#�÷�����ATAN(X)������X�ķ�����ֵ��
		return atan(a);
	}
	final function COS($a){
		#4�� COS ����
		#����ֵ��
		#�÷�����COS(X)������X������ֵ��
		return cos($a);
	}
	final function SIN($a):
		#5�� SIN ����
		#����ֵ��
		#�÷�����SIN(X)������X������ֵ��
		return sin($a);
	}
	final function TAN($a){
		#6�� TAN ����
		#����ֵ��
		#�÷�����TAN(X)������X������ֵ��
		return tan($a);
	}
	final function EXP($a){
		#7�� EXP ָ��
		#ָ����
		#�÷�����EXP(X)��e��X���ݡ�
		#���磺��EXP(CLOSE)������e��CLOSE���ݡ�
		return exp($a);
	}
	final function LN($a){
		#8�� LN ��Ȼ����
		#����Ȼ������
		#�÷�����LN(X)����eΪ�׵Ķ�����
		#���磺��LN(CLOSE)�������̼۵Ķ�����
		return log($a,M_E)
	}
	final function LOG($a){
		#9�� LOG ����
		#��10Ϊ�׵Ķ�����
		#�÷�����LOG(X)��ȡ��X�Ķ�����
		#���磺��LOG(100)������2��
		return log($a,10);
	}
	final function SQRT($a){
		#10�� SQRT ����
		#��ƽ����
		#�÷�����SQRT(X)�� ��X��ƽ������
		#���磺��SQRT(CLOSE)�����̼۵�ƽ������
		return sqrt($a);
	}
	final function ABS($a){
		#11�� ABS ����ֵ
		#�����ֵ��
		#�÷�����ABS(X)������X�ľ���ֵ��
		#���磺��ABS(-34)������34��
		return abs($a);
	}
	final function POW($a){
		#12�� POW ����
		#���ݡ�
		#�÷�����POW(A��B)������A��B���ݡ�
		#���磺��POW(CLOSE��3)��������̼۵�3�η���
		return pow($a);
	}
	final function CEILING(a):
		#13�� CEILING ��������
		#�������롣
		#�÷�����CEILING(A)��������A��ֵ��������ӽ���������
		#���磺��CEILING(12.3)�����13��CEILING(-3.5)���-3��
		return ceil($a);
	}	
	final function FLOOR($a){
		#14�� FLOOR ��������
		#�������롣
		#�÷�����FLOOR(A)��������A��ֵ��С������ӽ���������
		#���磺��FLOOR(12.3)�����12��FLOOR(-3.5)���-4��
		return floor($a);
	}
	final function INTPART($a){
		#15�� INTPART ȡ��
		#�÷�����INTPART(A)��������A����ֵ��С������ӽ���������
		#���磺��INTPART(12.3)�����12��INTPART(-3.5)���-3��
		return intval(a);
	}
	final function BETWEEN($a,$big,$little){
		#16�� BETWEEN�� ����
		#���ڡ�
		#�÷�����BETWEEN(A��B��C)����ʾA����B��C֮��ʱ����1�����򷵻�0��
		#���磺��BETWEEN(CLOSE��MA(CLOSE��10)��MA(CLOSE��5))��ʾ���̼۽���5�վ��ߺ�10�վ���֮�䡣
		if $big>$a and $a > $little:
			reutrn 1;
		return 0;
	}
	final function AVEDEV($aseq,$an){
		#ͳ�ƺ���
		#1�� AVEDEV ƽ�����Է���
		#AVEDEV(X��N) ������ƽ�����Է��
	}
	final function DEVSQ($aseq,$an){
		#2�� DEVSQ ����ƫ��ƽ����
		#DEVSQ(X��N) ����������ƫ��ƽ���͡�
	}
	final function FORCAST($aseq,$an){
		#3�� FORCAST ���Իع�Ԥ��ֵ
		#FORCAST(X��N)�� �������Իع�Ԥ��ֵ��
	}
	final function SLOPE($aseq,$an){
		#4�� SLOPE ���Իع�б��
		#SLOPE(X��N)�� �������Իع�б�ʡ�
	}
	final function STD($aseq,$an){
		#5�� STD �����׼��
		#STD(X��N)�� ���ع����׼�
	}
	final function STDP($aseq,$an){
		#6�� STDP �����׼��
		#STDP(X��N)�� ���������׼�
	}
	final function VAR($aseq,$an){
		#7�� VAR ������������
		#VAR(X��N)�� ���ع����������
	}
	final function VARP($aseq,$an){
		#8�� VARP ������������
		#VARP(X��N)�� ���������������� ��
	}
	final function	BLOCKSETNUM($aname){
		#1�� BLOCKSETNUM ����Ʊ����
		#�÷�����BLOCKSETNUM(�������)�����ظð���Ʊ������
	}
	final function HORCALC(){
		#2�� HORCALC ���ͳ��
		#�÷�����HORCALC(������ƣ���������㷽ʽ��Ȩ��)
		#�����100-HIGH��101-OPEN��102-LOW��103-CLOSE��104-VOL��105-�Ƿ�
		#���㷽ʽ����0-�ۼӣ�1-������
		#Ȩ�أ���0-�ܹɱ���1-��ͨ�ɱ���2-��ͬȨ�أ�3-��ͨ��ֵ
	}
	final function INSORT(){
		#3�� INSORT �������ѡ��
		#�÷�:INSORT(�������,ָ������,ָ����,������),
		#���ظù��ڰ���е��������
		#����:INSORT('���ز�','KDJ',3,0)��ʾ�ùɵ�KDJָ������������Jֵ֮�ڷ��ز�����е�����,
		#���һ������Ϊ0��ʾ��������
	}
	final function COST():
		#��̬����
		#1�� COST �ɱ��ֲ�
		#�ɱ��ֲ������
		#�÷�����COST(10)����ʾ10%�����̵ļ۸��Ƕ��٣�����10%�ĳֲ����ڸü۸����£�����90%�ڸü۸����ϣ�Ϊ�����̡�
		#�ú����������߷���������Ч��
	}
	final function PEAK(){
		#2�� PEAK ����ֵ
		#ǰM��ZIGת�򲨷�ֵ��
		#�÷�����PEAK(K��N��M)����ʾ֮��ת��ZIG(K��N)��ǰM���������ֵ��M������ڵ���1��
		#���磺��PEAK(1,5,1)����ʾ%5��߼�ZIGת�����һ���������ֵ��
	}
	final function PEAKBARS(){
		#3�� PEAKBARS ����λ��
		#ǰM��ZIGת�򲨷嵽��ǰ���롣
		#�÷�����PEAKBARS(K��N��M)����ʾ֮��ת��ZIG(K��N)��ǰM�����嵽��ǰ����������M������ڵ���1��
		#���磺��PEAKBARS (0��5��1)����ʾ%5���̼�ZIGת�����һ�����嵽��ǰ����������
	}
	final function SAR(){
		#4�� SAR ����ת��
		#����ת��
		#�÷����� SAR(N��S��M)��NΪ�������ڣ�SΪ������MΪ��ֵ��
		#���磺��SAR(10��2��20)����ʾ����10������ת�򣬲���Ϊ2%������ֵΪ20%��
	}
	final function SARTURN(){
		#5�� SARTURN ����ת���
		#����ת��㡣
		#�÷�����SARTURN(N��S��M)��NΪ�������ڣ�SΪ������MΪ��ֵ������������ת���򷵻�1������������ת���򷵻�-1������Ϊ0��
		#���÷���SAR������ͬ��
	}
	final function TROUGH(){
		#6�� TROUGH ����ֵ
		#ǰM��ZIGת�򲨹�ֵ��
		#�÷�����TROUGH(K��N��M)����ʾ֮��ת��ZIG(K��N)��ǰM�����ȵ���ֵ��M������ڵ���1��
		#���磺��TROUGH(2��5��2)����ʾ%5��ͼ�ZIGת���ǰ2�����ȵ���ֵ��
	}
	final function TROUGHBARS(){
		#7�� TROUGHBARS ����λ��
		#ǰM��ZIGת�򲨹ȵ���ǰ���롣
		#�÷�����TROUGHBARS(K��N��M)����ʾ֮��ת��ZIG(K��N)��ǰM�����ȵ���ǰ����������M������ڵ���1��
		#���磺��TROUGH(2��5��2)����ʾ%5��ͼ�ZIGת���ǰ2�����ȵ���ǰ����������
	}
	final function WINNER(){
		#8�� WINNER �����̱���
		#�����̱�����
		#�÷�����WINNER(CLOSE)����ʾ�Ե�ǰ���м������Ļ����̱�����
		#���磺������0.1��ʾ10%�����̣�WINNER(10.5)��ʾ10.5Ԫ�۸�Ļ����̱�����
		#�ú����������߷���������Ч��
	}
	final function LWINNER(){
		#9�� LWINNER ���ڻ����̱���
		#���ڻ����̱�����
		#�÷�����LWINNER(5��CLOSE)����ʾ���5����ǲ��ֳɱ��Ե�ǰ���м������Ļ����̱��������緵��0.1��ʾ10%�����̡�
	}
	final function PWINNER(){		
		#10�� PWINNER Զ�ڻ����̱���
		#Զ�ڻ����̱�����
		#�÷�����PWINNER(5��CLOSE)����ʾ5��ǰ���ǲ��ֳɱ��Ե�ǰ���м������Ļ����̱��������緵��0.1��ʾ10%�����̡�
	}
	final function COSTEX(){
		#11�� COSTEX ����ɱ�
		#����ɱ���
		#�÷�����COSTEX(CLOSE��REF(CLOSE))����ʾ���������̼۸�����ĳɱ������緵��10��ʾ����ɱ�Ϊ20Ԫ��
		#�ú����������߷���������Ч��
	}
	final function PPART(){
		#12�� PPART Զ�ڳɱ��ֲ�����
		#Զ�ڳɱ��ֲ�������
		#�÷�����PPART(10)����ʾ10ǰ�ĳɱ�ռ�ܳɱ��ı�����0.2��ʾ20%��
	}
	final function ZIG(){
		#13�� ZIG ֮��ת��
		#֮��ת��
		#�÷�����ZIG(K��N)�����۸�仯������N%ʱת��K��ʾ0:���̼ۣ�1:��߼ۣ�2:��ͼۣ�3:���̼ۣ�����:������Ϣ
		#���磺��ZIG(3��5)����ʾ���̼۵�5%��ZIGת��
	}
	final function MYSORTIDX(){
		#15�� MYSORTIDX �������ǿ��
		#���ظù���ȫ�г��е�����,�ú�����Ҫ��չ����֧��
	}
	final function ALLSTKNUM(aMarket="sh"):
		#16�� ALLSTKNUM ���׹�Ʊ��
		#���ص������н��׹�Ʊ��,�ú�����Ҫ��չ����֧��
		#
		#���̺���
	}
	final function INDEXA(aMarket="sh"):
		#1�� INDEXA �� ���ش��̳ɽ���磺D:INDEXA,������̳ɽ���;
	}
	final function INDEXADV(aMarket="sh"):
		#2�� INDEXADV �������Ǽ���
	}
	final function INDEXDEC(aMarket="sh"):
		#3�� INDEXDEC �����µ�����
	}
	final function INDEXC(aMarket="sh"):
		#4�� INDEXC �� ���ش������̼�
	}
	final function INDEXH(aMarket="sh"):
		#5�� INDEXH �� ���ش�����߼�
	}
	final function INDEXL(aMarket="sh"):
		#6�� INDEXL �� ���ش�����ͼ�
	}
	final function INDEXO(aMarket="sh"):
		#7�� INDEXO �� ���ش��̿��̼�
	}
	final function INDEXV(aMarket="sh"):
		#8�� INDEXV �� ���ش��̳ɽ���
	}
}



	'''
	#�漰δ�����������Բ�ʵ��	
	final function DH(beg=0,end=0):
		#27�� ����������߼ۣ�DHIGH
		
	final function DO(beg=0,end=0):
		#28�� �������ڿ��̼ۣ�DOPEN
		}
	final function DL(beg=0,end=0):
		#29�� ����������ͼۣ�DLOW
		}
	final function DC(beg=0,end=0):
		#30�� �����������̼ۣ�DCLOSE
		}
	final function DV(beg=0,end=0):
		#31�� �������ڳɽ�����DVOL
		}
	'''
