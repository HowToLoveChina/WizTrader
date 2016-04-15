<?php
//! 强类型检查
#declare(strict_type=1);

/*
	方法是用来产生买入和卖出的信息
	
需要用这样的形式来定义	
	
class Method0007 implements iMethod (Method){
};
	
*/

interface iMethod
{
	//! 设定名字
	function set_name();
	//==========================================================================
	//! 交易日开始前
	function hook_day_before();
	//! 交易日结束后
	function hook_day_after();	
	//==========================================================================
	//! 正式交易前，集合竞价后
	function hook_market_before();
	//! 正式交易结束前两分钟
	function hook_market_after();
	//==========================================================================
	//! 委托前
	function hook_entrust_before(string $stock,float $price, int $amount);
	//! 委托后
	function hook_entrust_after(string $stock,float $price,int $amount);
	//==========================================================================
	//! 撤销前
	function hook_cancel_before(string $stock,float $price,int $amount);
	//! 撤销后
	function hook_cancel_after(string $stock/*撤销的股票代码*/,float $price/*撤销的价*/, int $amount/*撤销的量*/, bool $result/*撤销是否成功*/);
	//==========================================================================
	//! 每一笔行情数据,返回一个数组  array("buy"=>data,"sell"=>data)
	//! data用来选择股票
	function hook_price(string $stock, array $price/*数组，具体结构由运行方式来决定*/);
	//! 返回股票池数组
	function hook_pool(){array;
	//! 支持的运行方法
	function hook_support(){array;
	//! 返回需要的指标
	function hook_need(){array;
};

//! 交易类
class cMethod{
	function __construct(){
		//! 方法的名字
		$this->name = "";
		//! 方法需要的参数，默认的是DAY,HIGH,LOW,OPEN,CLOSE,AMOUNT,VOLUME
		$this->need = array();
	}	
	final function Set( string $type/*运行的类型*/ , int $ymd/*运行的日期YYYYMMDD*/ ){
		$this->type = $type;
		$this->ymd = $ymd;
	}
	//! 需要什么样的指标
	final function Require(string $algo /*算法比如MA*/, string $field/*对哪个字段*/, string $period/*指标周期*/){
		$item="$algo|$field|$period";
		$self->need[]=array("name"=>$item,"field"=>$field,"period"=>$period,"value":0);
		//! 需要数据序列和值
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
		//! 实时计算数据
		$self->price = price ;
		foreach($self->need as $algo => $param){
			//!    完整的名字             算法         字段            周期
			//!    EMA|OPEN|5             EMA          OPEN            5
			//!    如示调用计算五日开盘均价
			//!    CROSS|OPEN,CLOSE|0       CROSS        OPEN,CLOSE    0
			  
			$field = $param['field'];
			$value = $self->expr( $param['field'] );
 			$price[ $param['name'] ]=$self->$algo($field,$param['period'],$value);
		}
		return $this->hook_price($price['stock'],$price);
	}
	//! 产生唯一的股票池,如果含all，那么只需要返回all
	final function Pool(){
		$pool = $this->hook_pool();
		if( in_array("all",$pool) ){
			$self->pool = array( "all" );
		}else{
			$self->pool = array_unique($pool);
		}
		return $self->pool;
	}
	//! 返回支持的运行方法
	final function Support(){
		return $this->hook_support();
	}
	
	final function H(){
		return $self->price['high'];
	}
	final function L(){
		#3， LOW 最低价
		#返回该周期最低价。
		return $self->price['low'];
	}
	final function C(){
		#6， C 收盘价
		#返回该周期收盘价。
		#用法： C
		return $self->price['close'];
	}		
	final function V(){
		#8， V 成交量
		#返回该周期成交量。
		return $self->price['volume'];
		
	final function O(){
		#10， O： 开盘价
		#返回该周期开盘价。
		return $self->price['open'];
	}
	final function ADVANCE(){
		#11， ADVANCE 上涨家数
		#返回该周期上涨家数。
		#用法： ADVANCE　(本函数仅对大盘有效)
		return $self->advance;
	}
	final function DECLINE(){
		#12， DECLINE 下跌家数
		#返回该周期下跌家数。
		#用法： DECLINE　(本函数仅对大盘有效)
		return $self->decline;
	}
	final function AMOUNT(){
		#13， AMOUNT 成交额
		#返回该周期成交额。
		#用法： AMOUNT
		return $self->price['amount'];
	}
	final function ASKPRICE($an){
		#14， ASKPRICE 委卖价
		#返回委卖1--委卖3价格。
		#用法： ASKPRICE(N)　N取1—3。
		#(本函数仅个股在分笔成交分析周期有效)
		return $self->price["ask_".$am];
	}
	final function ASKVOL($an){
		#15， ASKVOL 委卖量
		#返回委卖1--委卖3量。
		#用法： ASKVOL(N)　N取1—3。
		#(本函数仅个股在分笔成交分析周期有效)
		return $self->price["askv_".$am];
	}
	final function BIDPRICE($an){
		#16， BIDPRICE 委买价
		#返回委买1--委买3价格。
		#用法： BIDPRICE(N)　N取1—3。
		#(本函数仅个股在分笔成交分析周期有效)
		return $self->price["bid_".$am];
	}
	final function BIDVOL($an){
		#17， BIDVOL 委买量
		#返回委买1--委买3量。
		#用法： BIDVOL(N)　N取1—3。
		#(本函数仅个股在分笔成交分析周期有效)
		return $self->price["bidv_".$am];
	}
	final function BUYVOL(){
		#18， BUYVOL 主动性买盘
		#返回主动性买单量。
		#用法：　BUYVOL　当本笔成交为主动性买盘时，其数值等于成交量，否则为0。(本函数仅个股在分笔成交分析周期有效)
		if( $self->price['dir'] == 'B' ){
			return $self->price['volume'];
		}
		return 0;
	}

	final function SELLVOL(){
		#19， SELLVOL 主动性卖盘
		#返回主动性卖单量。
		#用法：　SELLVOL　当本笔成交为主动性卖盘时，其数值等于成交量，否则为0。(本函数仅个股在分笔成交分析周期有效)
		if( $self->price['dir'] == 'S' ){
			return $self->price['volume'];
		}
		return 0;
	}
	final function ISBUYORDER(){
		#20， ISBUYORDER 主动性买单
		#返回该成交是否为主动性买单。
		#用法： ISBUYORDER　当本笔成交为主动性买盘时，返回1，否则为0。
		#(本函数仅个股在分笔成交分析周期有效)
		if( $self->price['dir'] == 'B' ){
			return 1
		}
		return 0
	}
	final function ISSELLORDER(){
		#21， ISSELLORDER 主动性卖单
		#返回该成交是否为主动性卖单。
		#用法： ISSELLORDER　当本笔成交为主动性卖盘时，返回1，否则为0。
		#(本函数仅个股在分笔成交分析周期有效)
		if( $self->price['dir'] == 'S' ){
			return 1;
		}
		return 0;
	}
	final function BSAMOUNT(){
		#22， 成交额（元）：AMOUNT 逐笔成交当前的成交额
		return $self->price['amount'];
	}
	final function VOLINSTK(){
		#23， 持仓量：VOLINSTK
		}
	}
	final function BUYVOL(){
		#24， 外盘（手）：BUYVOL
		return $self->buyvol;
	}

	final function SELLVOL(){
		#25， 内盘（手）：SELLVOL
		return $self->sellvol;
	}
	final function DATE(){
		#1， DATE 日期
		#取得该周期从1900以来的年月日。
		#用法： DATE　例如函数返回1000101，表示2000年1月1日。
		return $self->date;
	}

	final function TIME(){
		#2， TIME 时间
		#取得该周期的时分秒。
		#用法： TIME　函数返回有效值范围为(000000-235959)。
		return $self->time;
	}
	final function YEAR(){
		#3， YEAR 年份
		#取得该周期的年份。
		#用法：YEAR
		return intval($self->date/10000);
	}
	final function MONTH(){
		#4， MONTH 月份
		#取得该周期的月份。
		#用法：MONTH　函数返回有效值范围为(1-12)。
		return intval( ($self->date/100) % 100);
	}
	final function WEEK(){
		#5， WEEK 星期
		#取得该周期的星期数。
		#用法： WEEK　函数返回有效值范围为(0-6)，0表示星期天。
		list($y,$m,$d) = $self->YmdFromInt($self->date);
		return date("w",mktime($y,$m,$d))
	}

	final function DAY(){
		#6， DAY 日期
		#取得该周期的日期。
		#用法： DAY　函数返回有效值范围为(1-31)。
		return intval($self->date% 100);
	}
	final function HOUR(){
		#7， HOUR 小时
		#取得该周期的小时数。
		#用法： HOUR　函数返回有效值范围为(0-23)，对于日线及更长的分析周期值为0。
		return intval($self->time / 100);
	} 
	final function MINUTE(){
		#8， MINUTE 分钟
		#取得该周期的分钟数。
		#用法： MINUTE　函数返回有效值范围为(0-59)，对于日线及更长的分析周期值为0。
		return intval($self->time % 100);
	}
	final function FROMOPEN(){
		#9， FROMOPEN 分钟
		#求当前时刻距开盘有多长时间。
		#用法：　FROMOPEN　返回当前时刻距开盘有多长时间，单位为分钟。
		#例如:　 FROMOPEN　当前时刻为早上十点，则返回31。
		return $self->minutes;
	}
	final function TFILT($aw,$bymd,$eymd){
		#10， TFILT 分钟
		#对指定时间段的数据进行过滤,该时间段以外的数据无效.
		#用法:
		#TFILT(X,D1,M1,D2,M2)
		#例如TFILT(CLOSE,1040101,1025,1040101,1345)表示在2004年1月1日的10:25到2004年1月1日的13:45的收盘价是有效的.
		#周期以日为基本单位的,分时为0有效.
	}
	final function PERIOD(){
		#11， 周期：PERIOD
		#取得周期类型.
		#结果从0到11,依次分别是1/5/15/30/60分钟,日/周/月,多分钟,多日,季,年.
		#引用函数
		return $self->period;
	}
	final function BACKSET(){
		#2， BACKSET 向前赋值
		#将当前位置到若干周期前的数据设为1。
		#用法：　BACKSET(X，N)　若X非0，则将当前位置到N周期前的数值设为1。
		#例如：　BACKSET(CLOSE>OPEN，2)　若收阳则将该周期及前一周期数值设为1，否则为0。
	}
	final function BARSCOUNT(){
		#3， BARSCOUNT 有效数据周期数
		#求总的周期数。
		#用法：　BARSCOUNT(X)　第一个有效数据到当前的天数。
		#例如：　BARSCOUNT(CLOSE)　对于日线数据取得上市以来总交易日数，对于分笔成交取得当日成交笔数，对于1分钟线取得当日交易分钟数。
	}
	final function CURRBARSCOUNT(){
		#4， CURRBARSCOUNT 到最后交易日的周期数
		#求到最后交易日的周期数.
		#用法:
		#CURRBARSCOUNT 求到最后交易日的周期数
	}
	final function TOTALBARSCOUNT(){
		#5， TOTALBARSCOUNT 总的周期数
		#求总的周期数.
		#用法:
		#TOTALBARSCOUNT 求总的周期数 
	}
	final function BARSLAST(){
		#6， BARSLAST 上一次条件成立位置
		#上一次条件成立到当前的周期数。
		#用法：　BARSLAST(X)　上一次X不为0到现在的天数。
		#例如：　BARSLAST(CLOSE/REF(CLOSE,1)>=1.1)　表示上一个涨停板到当前的周期数。
	}
	final function BARSSINCE(cond)
		#7， BARSSINCE 第一个条件成立位置
		#第一个条件成立到当前的周期数。
		#用法：　BARSSINCE(X)　第一次X不为0到现在的天数。
		#例如：　BARSSINCE(HIGH>10)　表示股价超过10元时到当前的周期数。
	}
	final function COUNT(){
		#8， COUNT 统计
		#统计满足条件的周期数。
		#用法：　COUNT(X，N)　统计N周期中满足X条件的周期数，若N=0则从第一个有效值开始。
		#例如：　COUNT(CLOSE>OPEN，20)　表示统计20周期内收阳的周期数。
	}
	final function HHV(){
		#9， HHV 最高值
		#求最高值。
		#用法：　HHV(X，N)　求N周期内X最高值，N=0则从第一个有效值开始。
		#例如：　HHV(HIGH,30)　表示求30日最高价。
	}
	final function HHVBARS(){
		#10， HHVBARS 上一高点位置
		#求上一高点到当前的周期数。
		#用法：　HHVBARS(X，N)　求N周期内X最高值到当前周期数，N=0表示从第一个有效值开始统计。
		#例如：　HHVBARS(HIGH，0)　求得历史新高到到当前的周期数。
	}
	final function LLV(){
		#11， LLV 最低值
		#求最低值。
		#用法：　LLV(X，N)　求N周期内X最低值，N=0则从第一个有效值开始。
		#例如：　LLV(LOW，0)　表示求历史最低价。
	}
	final function LLVBARS(){
		#12， LLVBARS 上一低点位置
		#求上一低点到当前的周期数。
		#用法：　LLVBARS(X，N)　求N周期内X最低值到当前周期数，N=0表示从第一个有效值开始统计。
		#例如：　LLVBARS(HIGH，20)　求得20日最低点到当前的周期数。
	}
	final function REVERSE(){
		#13， REVERSE 求相反数
		#求相反数。
		#用法：　REVERSE(X)　返回-X。
		#例如：　REVERSE(CLOSE)　返回-CLOSE。
	}
	final function REF(){
		#14， REF 向前引用
		#引用若干周期前的数据。
		#用法：　REF(X，A)　引用A周期前的X值。
		#例如：　REF(CLOSE，1)　表示上一周期的收盘价，在日线上就是昨收。
	}
	final function REFDATE(){
		#15， REFDATE 指定引用
		#引用指定日期的数据。
		#用法：　REFDATE(X，A)　引用A日期的X值。
		#例如：　REF(CLOSE，20011208)　表示2001年12月08日的收盘价。
	}
	final function SUM(){
		#16， SUM 总和
		#求总和。
		#用法：　SUM(X，N)　统计N周期中X的总和，N=0则从第一个有效值开始。
		#例如：　SUM(VOL，0)　表示统计从上市第一天以来的成交量总和。
	}
	final function FILTER(){
		#17， FILTER 过滤
		#过滤连续出现的信号。
		#用法：　FILTER(X，N)　X满足条件后，删除其后N周期内的数据置为0。
		#例如：　FILTER(CLOSE>OPEN，5)　查找阳线，5天内再次出现的阳线不被记录在内。
	}
	final function SUMBARS(){
		#18， SUMBARS 累加到指定值的周期数
		#向前累加到指定值到现在的周期数。
		#用法：　SUMBARS(X，A)　将X向前累加直到大于等于A，返回这个区间的周期数。
		#例如：　SUMBARS(VOL，CAPITAL)　求完全换手到现在的周期数。
	}
	final function SMA(){
		#19， SMA 移动平均
		#返回移动平均。
		#用法：　SMA(X，N，M)　X的N日移动平均，M为权重，如Y=(X*M+Y'*(N-M))/N
	}
	final function MA(){
		#20， MA 简单移动平均
		#返回简单移动平均。
		#用法：　MA(X，M)　X的M日简单移动平均。
	}
	final function DMA(){
		#21， DMA 动态移动平均
		#求动态移动平均。
		#用法：　DMA(X，A)　求X的动态移动平均。
		#算法：　若Y=DMA(X，A)则 Y=A*X+(1-A)*Y'，其中Y'表示上一周期Y值，A必须小于1。
		#例如：　DMA(CLOSE，VOL/CAPITAL)　表示求以换手率作平滑因子的平均价。
	}
	final function EMA(){
		#22， EMA(或EXPMA) 指数移动平均
		#返回指数移动平均。
		#用法：　EMA(X，M)　X的M日指数移动平均。
	}
	final function MEMA(){
		#23， MEMA 平滑移动平均
		#返回平滑移动平均
		#用法：　MEMA(X，M)　X的M日平滑移动平均。MEMA(X,N)与MA的差别在于起始值为一平滑值,而不是初始值
	}
	final function EXPMEMA(){
		#24， EXPMEMA 指数平滑移动平均
		#返回指数平滑移动平均。
		#用法：　EXPMEMA(X，M)　X的M日指数平滑移动平均。EXPMEMA同EMA(即EXPMA)的差别在于他的起始值为一平滑值
	}
	final function RANGE(){
		#25， RANGE 介于某个范围之间
		#用法：　RANGE(A,B,C)　A在B和C。
		#例如：　RANGE(A，B，C)表示A大于B同时小于C时返回1，否则返回0
	}
	final function CONST(){
		#26， CONST 取值设为常数
		#用法: 　CONST(A)　取A最后的值为常量.
		#例如：　CONST(INDEXC)表示取大盘现价。
	}
	final function ISLASTBAR(){
		#27， ISLASTBAR 判断是否为最后一个周期
	}
	final function BARSLASTCOUNT(){
		#28， BARSLASTCOUNT 统计条件连续成立次数
		#用法:
		#BARSLASTCOUNT(X),统计连续满足X条件的周期数.
		#例如:BARSLASTCOUNT(CLOSE>OPEN)表示统计连续收阳的周期数
	}
	final function XMA(){
		#29， XMA 偏移移动平均
		#用法:
		#XMA(X,M):X的M日偏移移动平均
	}
	final function TOPRANGE(){
		#30， TOPRANGE 当前值是近多少周期内的最大值
		#用法:
		#TOPRANGE(X):X是近多少周期内X的最大值
		#例如:TOPRANGE(HIGH)表示当前最高价是近多少周期内的最高价
	}
	final function LOWRANGE(){
		#31， LOWRANGE 当前值是近多少个周期内的最小值
		#用法:
		#LOWRANGE(X):X是近多少周期内X的最小值
		#例如:LOWRANGE(LOW)表示当前最高价是近多少周期内的最小价
	}
	final function CROSS(A,B,N):
		#
		#逻辑函数
		#1， CROSS 上穿
		#两条线交叉。
		#用法：　CROSS(A，B)　表示当A从下方向上穿过B时返回1，否则返回0。
		#例如：　CROSS(MA(CLOSE，5)，MA(CLOSE，10))　表示5日均线与10日均线交金叉。
	}
	final function LONGCROSS(aR1,aR2,aPeriod):
		#2， LONGCROSS 维持一定周期后上穿
		#两条线维持一定周期后交叉。
		#用法：　LONGCROSS(A，B，N)　表示A在N周期内都小于B，本周期从下方向上穿过B时返回1，否则返回0。
	}
	final function UPNDAY(aWhich,aPeriod):
		#3， UPNDAY 连涨
		#返回是否连涨周期数。
		#用法：　UPNDAY(CLOSE,M)　表示连涨M个周期。
	}
	final function DOWNDAY(aWhich,aPeriod):
		#4， DOWNNDAY 连跌
		#返回是否连跌周期。
		#用法：　DOWNNDAY(CLOSE，M)　表示连跌M个周期。
	}
	final function NDAY(){
		#5， NDAY 连大
		#返回是否持续存在X>Y。
		#用法：　NDAY(CLOSE，OPEN，3)　表示连续3日收阳线。
	}
	final function EXIST(){
		#6， EXIST 存在
		#是否存在。
		#用法：　EXIST(CLOSE>OPEN，10)　表示前10日内存在着阳线。
	}
	final function EVERY(){	
		#7， EVERY 一直存在
		#一直存在。
		#用法：　EVERY(CLOSE>OPEN，10)　表示前10日内一直阳线。
	}
	final function LAST(){
		#8， LAST 持续存在
		#用法：　LAST(X,A,B)　 A>B，表示从前A日到前B日一直满足X条件。若A为0，表示从第一天开始，B为0，表示到最后日止。
		#例如：　LAST(CLOSE>OPEN，10，5)　表示从前10日到前5日内一直阳线。
	}
	final function TESTSKIP(){
		#9， TESTSKIP(A):不满足A则直接返回.
		#用法:
		#TESTSKIP(A) 
		#表示如果不满足条件A则改公式直接返回，不再计算接下来的表达式
	}
	final function NOT($a){
		#1， NOT 取反
		#求逻辑非。
		#用法：　NOT(X)　返回非X，即当X=0时返回1，否则返回0。
		#例如：　NOT(ISUP)　表示平盘或收阴。
	}
	final function IF($aCond,$aTrue,$aFalse):
		#2， IF 逻辑判断
		#根据条件求不同的值。
		#用法：　IF(X，A，B)　若X不为0则返回A，否则返回B。
		#例如：　IF(CLOSE>OPEN，HIGH，LOW)表示该周期收阳则返回最高值，否则返回最低值。
		if( $aCond != 0 ):
			return $aTrue;
		return $aFalse;
	final function IFN(){
		#4， IFN 逻辑判断
		#根据条件求不同的值。
		#用法：　IFN(X，A，B)　若X不为0则返回B，否则返回A。
		#例如：　IFN(CLOSE>OPEN，HIGH，LOW)　表示该周期收阴则返回最高值，否则返回最低值。
		if( $aCond == 0 ):
			return $aTrue;
		return $aFalse;
	}
	final function MAX($aA,$aB){
		#5， MAX 较大值
		#求最大值。
		#用法：　MAX(A,B)　返回A和B中的较大值。
		#例如：　MAX(CLOSE-OPEN，0)　表示若收盘价大于开盘价返回它们的差值，否则返回0。
		return max($aA,$aB);
	}
	final function MIN($aA,$aB){
		#6， MIN 较小值
		#求最小值。
		#用法：　MIN(A，B)　返回A和B中的较小值。
		#例如：　MIN(CLOSE，OPEN)　返回开盘价和收盘价中的较小值。
		return min($aA,$aB);
	}
	final function ACOS($a):
		#
		#数学函数
		#1， ACOS 反余弦
		#反余弦值。
		#用法：　ACOS(X)　返回X的反余弦值。
		return acos($a);
	}
	final function ASIN($a):
		#2， ASIN 反正弦
		#反正弦值。
		#用法：　ASIN(X)　返回X的反正弦值。
		return asin($a);
	}
	final function ATAN($a){
		#3， ATAN 反正切
		#反正切值。
		#用法：　ATAN(X)　返回X的反正切值。
		return atan(a);
	}
	final function COS($a){
		#4， COS 余弦
		#余弦值。
		#用法：　COS(X)　返回X的余弦值。
		return cos($a);
	}
	final function SIN($a):
		#5， SIN 正弦
		#正弦值。
		#用法：　SIN(X)　返回X的正弦值。
		return sin($a);
	}
	final function TAN($a){
		#6， TAN 正切
		#正切值。
		#用法：　TAN(X)　返回X的正切值。
		return tan($a);
	}
	final function EXP($a){
		#7， EXP 指数
		#指数。
		#用法：　EXP(X)　e的X次幂。
		#例如：　EXP(CLOSE)　返回e的CLOSE次幂。
		return exp($a);
	}
	final function LN($a){
		#8， LN 自然对数
		#求自然对数。
		#用法：　LN(X)　以e为底的对数。
		#例如：　LN(CLOSE)　求收盘价的对数。
		return log($a,M_E)
	}
	final function LOG($a){
		#9， LOG 对数
		#求10为底的对数。
		#用法：　LOG(X)　取得X的对数。
		#例如：　LOG(100)　等于2。
		return log($a,10);
	}
	final function SQRT($a){
		#10， SQRT 开方
		#开平方。
		#用法：　SQRT(X)　 求X的平方根。
		#例如：　SQRT(CLOSE)　收盘价的平方根。
		return sqrt($a);
	}
	final function ABS($a){
		#11， ABS 绝对值
		#求绝对值。
		#用法：　ABS(X)　返回X的绝对值。
		#例如：　ABS(-34)　返回34。
		return abs($a);
	}
	final function POW($a){
		#12， POW 乘幂
		#乘幂。
		#用法：　POW(A，B)　返回A的B次幂。
		#例如：　POW(CLOSE，3)　求得收盘价的3次方。
		return pow($a);
	}
	final function CEILING(a):
		#13， CEILING 向上舍入
		#向上舍入。
		#用法：　CEILING(A)　返回沿A数值增大方向最接近的整数。
		#例如：　CEILING(12.3)　求得13，CEILING(-3.5)求得-3。
		return ceil($a);
	}	
	final function FLOOR($a){
		#14， FLOOR 向下舍入
		#向下舍入。
		#用法：　FLOOR(A)　返回沿A数值减小方向最接近的整数。
		#例如：　FLOOR(12.3)　求得12，FLOOR(-3.5)求得-4。
		return floor($a);
	}
	final function INTPART($a){
		#15， INTPART 取整
		#用法：　INTPART(A)　返回沿A绝对值减小方向最接近的整数。
		#例如：　INTPART(12.3)　求得12，INTPART(-3.5)求得-3。
		return intval(a);
	}
	final function BETWEEN($a,$big,$little){
		#16， BETWEEN： 介于
		#介于。
		#用法：　BETWEEN(A，B，C)　表示A处于B和C之间时返回1，否则返回0。
		#例如：　BETWEEN(CLOSE，MA(CLOSE，10)，MA(CLOSE，5))表示收盘价介于5日均线和10日均线之间。
		if $big>$a and $a > $little:
			reutrn 1;
		return 0;
	}
	final function AVEDEV($aseq,$an){
		#统计函数
		#1， AVEDEV 平均绝对方差
		#AVEDEV(X，N) 　返回平均绝对方差。
	}
	final function DEVSQ($aseq,$an){
		#2， DEVSQ 数据偏差平方和
		#DEVSQ(X，N) 　返回数据偏差平方和。
	}
	final function FORCAST($aseq,$an){
		#3， FORCAST 线性回归预测值
		#FORCAST(X，N)　 返回线性回归预测值。
	}
	final function SLOPE($aseq,$an){
		#4， SLOPE 线性回归斜率
		#SLOPE(X，N)　 返回线性回归斜率。
	}
	final function STD($aseq,$an){
		#5， STD 估算标准差
		#STD(X，N)　 返回估算标准差。
	}
	final function STDP($aseq,$an){
		#6， STDP 总体标准差
		#STDP(X，N)　 返回总体标准差。
	}
	final function VAR($aseq,$an){
		#7， VAR 估算样本方差
		#VAR(X，N)　 返回估算样本方差。
	}
	final function VARP($aseq,$an){
		#8， VARP 总体样本方差
		#VARP(X，N)　 返回总体样本方差 。
	}
	final function	BLOCKSETNUM($aname){
		#1， BLOCKSETNUM 板块股票个数
		#用法：　BLOCKSETNUM(板块名称)　返回该板块股票个数。
	}
	final function HORCALC(){
		#2， HORCALC 多股统计
		#用法：　HORCALC(板块名称，数据项，计算方式，权重)
		#数据项：100-HIGH，101-OPEN，102-LOW，103-CLOSE，104-VOL，105-涨幅
		#计算方式：　0-累加，1-排名次
		#权重：　0-总股本，1-流通股本，2-等同权重，3-流通市值
	}
	final function INSORT(){
		#3， INSORT 板块排序选股
		#用法:INSORT(板块名称,指标名称,指标线,升降序),
		#返回该股在板块中的排序序号
		#例如:INSORT('房地产','KDJ',3,0)表示该股的KDJ指标第三个输出即J之值在房地产板块中的排名,
		#最后一个参数为0表示降序排名
	}
	final function COST():
		#形态函数
		#1， COST 成本分布
		#成本分布情况。
		#用法：　COST(10)，表示10%获利盘的价格是多少，即有10%的持仓量在该价格以下，其余90%在该价格以上，为套牢盘。
		#该函数仅对日线分析周期有效。
	}
	final function PEAK(){
		#2， PEAK 波峰值
		#前M个ZIG转向波峰值。
		#用法：　PEAK(K，N，M)　表示之字转向ZIG(K，N)的前M个波峰的数值，M必须大于等于1。
		#例如：　PEAK(1,5,1)　表示%5最高价ZIG转向的上一个波峰的数值。
	}
	final function PEAKBARS(){
		#3， PEAKBARS 波峰位置
		#前M个ZIG转向波峰到当前距离。
		#用法：　PEAKBARS(K，N，M)　表示之字转向ZIG(K，N)的前M个波峰到当前的周期数，M必须大于等于1。
		#例如：　PEAKBARS (0，5，1)　表示%5开盘价ZIG转向的上一个波峰到当前的周期数。
	}
	final function SAR(){
		#4， SAR 抛物转向
		#抛物转向。
		#用法：　 SAR(N，S，M)，N为计算周期，S为步长，M为极值。
		#例如：　SAR(10，2，20)　表示计算10日抛物转向，步长为2%，极限值为20%。
	}
	final function SARTURN(){
		#5， SARTURN 抛物转向点
		#抛物转向点。
		#用法：　SARTURN(N，S，M)　N为计算周期，S为步长，M为极值，若发生向上转向则返回1，若发生向下转向则返回-1，否则为0。
		#其用法与SAR函数相同。
	}
	final function TROUGH(){
		#6， TROUGH 波谷值
		#前M个ZIG转向波谷值。
		#用法：　TROUGH(K，N，M)　表示之字转向ZIG(K，N)的前M个波谷的数值，M必须大于等于1。
		#例如：　TROUGH(2，5，2)　表示%5最低价ZIG转向的前2个波谷的数值。
	}
	final function TROUGHBARS(){
		#7， TROUGHBARS 波谷位置
		#前M个ZIG转向波谷到当前距离。
		#用法：　TROUGHBARS(K，N，M)　表示之字转向ZIG(K，N)的前M个波谷到当前的周期数，M必须大于等于1。
		#例如：　TROUGH(2，5，2)　表示%5最低价ZIG转向的前2个波谷到当前的周期数。
	}
	final function WINNER(){
		#8， WINNER 获利盘比例
		#获利盘比例。
		#用法：　WINNER(CLOSE)　表示以当前收市价卖出的获利盘比例。
		#例如：　返回0.1表示10%获利盘，WINNER(10.5)表示10.5元价格的获利盘比例。
		#该函数仅对日线分析周期有效。
	}
	final function LWINNER(){
		#9， LWINNER 近期获利盘比例
		#近期获利盘比例。
		#用法：　LWINNER(5，CLOSE)　表示最近5天的那部分成本以当前收市价卖出的获利盘比例。例如返回0.1表示10%获利盘。
	}
	final function PWINNER(){		
		#10， PWINNER 远期获利盘比例
		#远期获利盘比例。
		#用法：　PWINNER(5，CLOSE)　表示5天前的那部分成本以当前收市价卖出的获利盘比例。例如返回0.1表示10%获利盘。
	}
	final function COSTEX(){
		#11， COSTEX 区间成本
		#区间成本。
		#用法：　COSTEX(CLOSE，REF(CLOSE))，表示近两日收盘价格间筹码的成本，例如返回10表示区间成本为20元。
		#该函数仅对日线分析周期有效。
	}
	final function PPART(){
		#12， PPART 远期成本分布比例
		#远期成本分布比例。
		#用法：　PPART(10)，表示10前的成本占总成本的比例，0.2表示20%。
	}
	final function ZIG(){
		#13， ZIG 之字转向
		#之字转向。
		#用法：　ZIG(K，N)　当价格变化量超过N%时转向，K表示0:开盘价，1:最高价，2:最低价，3:收盘价，其余:数组信息
		#例如：　ZIG(3，5)　表示收盘价的5%的ZIG转向。
	}
	final function MYSORTIDX(){
		#15， MYSORTIDX 个股相对强弱
		#返回该股在全市场中的排名,该函数需要扩展数据支持
	}
	final function ALLSTKNUM(aMarket="sh"):
		#16， ALLSTKNUM 交易股票数
		#返回当日所有交易股票数,该函数需要扩展数据支持
		#
		#大盘函数
	}
	final function INDEXA(aMarket="sh"):
		#1， INDEXA 　 返回大盘成交额，如：D:INDEXA,输出大盘成交额;
	}
	final function INDEXADV(aMarket="sh"):
		#2， INDEXADV 返回上涨家数
	}
	final function INDEXDEC(aMarket="sh"):
		#3， INDEXDEC 返回下跌家数
	}
	final function INDEXC(aMarket="sh"):
		#4， INDEXC 　 返回大盘收盘价
	}
	final function INDEXH(aMarket="sh"):
		#5， INDEXH 　 返回大盘最高价
	}
	final function INDEXL(aMarket="sh"):
		#6， INDEXL 　 返回大盘最低价
	}
	final function INDEXO(aMarket="sh"):
		#7， INDEXO 　 返回大盘开盘价
	}
	final function INDEXV(aMarket="sh"):
		#8， INDEXV 　 返回大盘成交量
	}
}



	'''
	#涉及未来函数，所以不实现	
	final function DH(beg=0,end=0):
		#27， 不定周期最高价：DHIGH
		
	final function DO(beg=0,end=0):
		#28， 不定周期开盘价：DOPEN
		}
	final function DL(beg=0,end=0):
		#29， 不定周期最低价：DLOW
		}
	final function DC(beg=0,end=0):
		#30， 不定周期收盘价：DCLOSE
		}
	final function DV(beg=0,end=0):
		#31， 不定周期成交量：DVOL
		}
	'''
