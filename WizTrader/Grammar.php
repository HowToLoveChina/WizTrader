<?php

define("PRIO_ZERO"		, 0 );
define("PRIO_SET"		, 1 );	#赋值
define("PRIO_LOGIC"		, 2 );	#逻辑操作
define("PRIO_ADD"		, 3 );	#加减 
define("PRIO_MULI"		, 4 );	#乘除 
define("PRIO_LP"		, 5 );	#括号
define("PRIO_RP"		, 6 );	#括号
define("PRIO_CODE"		, 7 );	#嵌入的代码类别是符号，优先级是这个，但是不参与优先级计算
define("PRIO_FUNC"		, 8 );	#嵌入的代码类别是函数，优先级是这个，但是不参与优先级计算
define("PRIO_NUM"		, 9 );	#是数字
define("PRIO_VAR"		, 10);	#是变量
define("PRIO_COMMA"		, 11);  #是逗号

class cGrammar{	
	
	
	#参数是目标的生成语言
	function __construct(string $alang="PHP"){
		if( ! in_array($alang,$this->lang) ){
			throw new Exception(sprintf("不支持目标语言%s",$alang));
			return ;
		}
		#各类函数中用到的静态变量附加上去
		$this->vars 		= array();//代码中的变量名
		$this->tempvars		= 0;//临时变量计数器	
		#保存脚本类型
		$this->script_type 	= $alang;
		$this->codes 		= $this->lang_codes[$alang];
		$this->statics 		= $this->lang_statics[$alang];
		$this->funcs		= $this->lang_funcs[$alang];
		#在代码生成过程中实现与语言不相关
		switch($this->script_type){
			case 'PHP':
				$this->var_prefix='$';
				break;
			default:
				$this->var_prefix='';
		}
	}
	
	#func是要生成的目标代码的函数名称
	#code是代码的行
	function ScriptBuilderArray($fp,array $code,string $func){
		#如果有静态代码指定了，那么就生成吧
		if( array_key_exists($func,$this->statics) ){
			#加入静态代码
			fprintf($fp,"%s\r\n",$this->statics[$func]);
		}	
		$words = array();
		$vars  = array();
		//! 第一遍分词
		foreach( $code as $e ){
			$line = $this->CompactString($e['text']);
			if( strlen($line) <= 0 ){
				continue;
			}
			//! 加上行尾空格
			$line .= " ";
			if( $line{0} == '#' ){
				#加上注释行的标记
				continue;
			}
			#printf("%d>%s\n",__LINE__,$line);
			#词法分析，切分成词语
			$words[] = array (
						'symbol' => $this->SymbolBuild($line,$vars),
						'line'   => $e
					);
		}//for
		//! 第二遍 分析词汇
		$size = sizeof($words);
		for( $i = 0 ; $i < $size ; $i ++ ){
			$this->SymbolCheck($words,$i,$vars);
		}
		#printf("%d>\n",__LINE__);
		#print_r($words);
		#die();
		//! 产生静态和局部变量代码
		$this->GenerateVars($fp,$vars);
		//! 第三遍 代码生成
		for( $i = 0 ; $i < $size ; $i ++ ){
			$this->GenerateCode($fp,$words[$i],$vars);
		}
		fprintf($fp,$this->statics['FUNCTION_END']);
	}
	final
	function GenerateVars($fp,$vars){
		fprintf($fp,$this->statics['STATIC_DECLARE']);
		foreach($vars as $e){
			//! LOCAL还是STATIC
			$fmt = $this->statics[$e['type'].'_SET'];
			fprintf($fp,$fmt,$e['name'],$e['init']);
		}
	}
	#
	final
	function GenerateCode($afp,$asymbols,$avars){
		//!先把函数找出来，然后进行迭代
		$codes = array();
		$index = 0 ;
		$size = sizeof($asymbols['symbol']);
		$symbol = &$asymbols['symbol'];
		#printf("%d>\n",__LINE__);
		#print_r($symbol);
		for($i=0;$i < $size ; $i++){
			array_push($codes,$symbol[$i]);
			$index++;
			if( $symbol[$i]['prio'] != PRIO_FUNC ){
				continue;
			}
			$funcname = $symbol[$i]['text'];
			$codes[$index-1]['prio']= PRIO_ZERO;
			$codes[$index-1]['type']='符号';
			if( ($i+1) == $size || $symbol[$i+1]['text'] != '('){
				#echo sprintf("无参调用 %d \n",$index);
				//! 无参函数调用
				$codes[$index-1]['text']= $this->GenerateFunction($afp,$funcname,array());
				continue;
			}
			//  R ( 3 ) 
			//    2   3->4
			//i:0     终
			//      2
			//! 把函数的括号内的都拿出来
			$args = array_slice($symbol,$i+2,$symbol[$i+1]['match']-1);
			$codes[$index-1]['text'] = $this->GenerateFunction($afp,$funcname,$args);
			$i += $symbol[$i+1]['match']+1;
		}
		#printf("%d>\n",__LINE__);
		#print_r($codes);
		//! 第二步把表达式归约掉
		//! 获得后序表达式，产生计算过程，最终得到一个变量名?
		$express = $this->GenerateExpress($afp,$codes);
		#printf("%d>",__LINE__);
		#print_r($express);
		$islet = False;
		$code = $this->GenerateExpressCode($afp,$express,$islet);
		if( !$islet ){
			fprintf($afp,$this->statics['LINE_END'],$code);
		}
	}
	#返回的是临时变量名
	final
	function GenerateFunction($afp,$aname,$aargs){
		#printf("%d>函数%s:\n",__LINE__,$aname);
		#print_r($aargs);
		//! 这是要返回的变量名
		$result = sprintf("%stemp%d",$this->var_prefix,$this->tempvars++);
		//! 这是调用函数的部分，缺括号
		$call = sprintf($this->funcs,$aname);
		//
		$size = sizeof($aargs);
		if( $size == 0 ){
			//! 把计算过程写入文件，在生成时带了stock变量，
			//! 所以右括号在这里生成一次，
			//! 最后也生成一次才能跟模板里的配对
			fprintf($afp,"		%s=%s);#@%d\r\n",$result,$call,__LINE__);
			//! 返回最后的变量名
			return $result;
		}
		$argc = 0 ;
		#插入尾部的逗号
		$codes = array();$index = 0 ;
		//! 第一遍把函数归约掉
		for( $i=0 ; $i < $size ; $i++){
			$e = $aargs[$i];
			array_push($codes,$e);
			$index++;
			if( $e['prio'] == PRIO_FUNC ){
				$codes[$index]['prio']= PRIO_ZERO;
				$funcname = $aargs[$i]['text'];
				if( $aargs[$i+1]['text'] != '(' ){
					//! 无参函数调用
					$codes[$index]['text']= $this->GenerateFunc($afp,$funcname,array());
					continue;
				}
				$args = array_slice($aargs,$i+2,$aargs[$i+1]['match']-1);
				$codes[$index]['type'] = '符号';
				$codes[$index]['text'] = $this->GenerateFunction($afp,$funcname,$args);
				$i += ($aargs[$i+1]['match']+1);
				continue;
			}
		}
		//! 第二步把表达式归约掉
		$aargs = $codes;
		array_push($aargs,array('prio'=>PRIO_COMMA ) );
		$size  = sizeof($aargs);
		$codes = array();$index=0;
		$param = array();//最终函数调用参数
		for( $i=0 ; $i < $size ; $i++){
			$e = $aargs[$i];
			array_push($codes,$e);
			$index++;
			if( $e['prio'] != PRIO_COMMA ){
				continue;
			}
			#弹出逗号
			array_pop($codes);
			if( sizeof($codes) == 1 ){
				$param [] = $codes[0]['text'];
				$codes = array();
			}else{
				//! 获得后序表达式，产生计算过程，最终得到一个变量名?
				$express = $this->GenerateExpress($afp,$codes);
				$param [] = $this->GenerateExpressCode($afp,$express);
				$codes = array();
				$index = 0 ;
			}
		}
		//! 第三步把代码产生
		$size = sizeof($param);
		for( $i = 0 ; $i < $size ; $i ++ ){
			$call .= ",".$param[$i];
		}
		$call .= ")";
		fprintf($afp,"		%s=%s;#@%d\r\n",$result,$call,__LINE__);
		return $result ;
	}
	

	#代码生成
	function GenerateExpressCode($afp,array $asymbols , &$islet=False){
		//! 要返回的变量名
		$result = sprintf("%stemp%d",$this->var_prefix,$this->tempvars++);
		//! 后序表达式
		$size = sizeof($asymbols);
		if( $size == 1 ){
			if( sizeof($asymbols[0]) == 1 ){
				return $asymbols[0]['text'];
			}
		}
		$ops = array();
		for($i=0 ; $i < $size ; $i ++){
			#fprintf($afp,"%d>%s\r\n",__LINE__,$asymbols[$i]['text']);
			if( $asymbols[$i]['type'] == '符号' ){
				array_push($ops,$asymbols[$i]);
				continue;
			}
			#取出两个操作数
			$op1 = array_pop($ops);
			$op2 = array_pop($ops);
			#
			$op1t = ($op1['prio']==PRIO_VAR) ? sprintf("%s%s",$this->var_prefix,$op1['text']):$op1['text'];
			$op2t = ($op2['prio']==PRIO_VAR) ? sprintf("%s%s",$this->var_prefix,$op2['text']):$op2['text'];
			#赋值话直接处理
			if(  $asymbols[$i]['prio'] == PRIO_SET ){
				if( $op1['prio']==PRIO_VAR && $op2['prio']==PRIO_VAR ){
					//! 两个变量赋值,省略掉一个
					array_push($ops,$op1);
					continue;
				}
				fprintf($afp,"		%s=%s;#@%d\r\n",$op2t,$op1t,__LINE__);
				array_push($ops,array('text' => $op1t , 'prio' => PRIO_SET , 'type'=> '符号' ));
				$islet = True;
				continue;
			}
			#生成表达式，带上括号
			array_push($ops,array('text' => sprintf("(%s %s %s)",$op2t, $asymbols[$i]['text'],$op1t) , 'prio' => PRIO_ZERO ));
		}
		$code  = array_pop($ops);
		return $code['text'];
	}


	#产生表达式,所有的函数都被产生了临时变量，这里只会有常量和临时变量参与计算
	final
	function GenerateExpress($afp,$acodes){
		#语法分析
		$operate = array();
		$symbols = array();
		$n = sizeof($acodes);
		//
		$prev = False;
		$func = False;
		//
		foreach($acodes as $idx => $v){
			if( $v['type'] == '符号' ){
				array_push($symbols,$v);
				continue;
			}
			$this->ScriptBuilderCodeSynaxOperate($v,$operate,$symbols);
		}
		#余下的操作符按序入栈产生后序表达式
		while( sizeof($operate) != 0 ){
			$v = array_pop($operate);
			array_push($symbols,$v);
		}
		#保存最终的后序表达式
		return $symbols ;
	}
	final 
	function ScriptBuilderCodeSynaxOperate($v,&$operate,&$symbols){
		if( sizeof($operate) == 0 && sizeof($symbols) == 0 ){
			if($v['prio'] != PRIO_LP ){
				throw new Exception (__LINE__ . sprintf(">语法错误，最早出现的操作符不能是%s",$v['text']) );
				return False;
			}
		}
		#左括号无条件进去
		if( $v['prio'] == PRIO_LP ){
			array_push($operate,$v);
			return True;
		}
		//! 空栈直接压
		if( sizeof($operate) == 0 ){
			array_push($operate,$v);		
			return True;
		}
		#不是右括号
		if( $v['prio'] != PRIO_RP ){
			#取出前一个操作符
			$top = $operate[sizeof($operate)-1];
			//! 比栈顶的符号优先 或 顶部是括号 把操作符压进去
			if( $v['prio'] < $top['prio'] &&  $top['prio'] != PRIO_LP ){		
				array_push($symbols, array_pop($operate) );
			}
			array_push($operate,$v);
			return True;
		}
		$x = False;
		do{
			if( $x !== FALSE ){
				array_push($symbols,$x);
			}
			#都空栈了肯定出错了
			if( sizeof($operate) == 0 ){
				print_r($symbols);
				throw new Exception (__LINE__.">括号数量不匹配");
				return False;
			}
			#弹出前一个操作符
			$x = array_pop($operate);
		}while($x['prio'] != PRIO_LP);
		return True;
	}
	
	final
	function SymbolCheck(&$words,$index,&$vars){
		$e = & $words[$index]['symbol'];
		$size = sizeof($e);
		$pnest= 0 ;
		$pary = array ();
		for( $i=0 ; $i < $size ; $i ++ ){
			$el = &$e[$i];
			$text = $el['text'];
			if( $el['type'] != '运算' ){
				continue;
			}
			switch($text){
				case '(' :
					$el['prio'] = PRIO_LP;
					if( $i > 0 ){
						#这是函数调用，不能作为运算符来看待
						if($e[$i-1]['prio'] == PRIO_FUNC){
							$el['type'] = '符号';
						}
					}
					$pary[$pnest] = $i ;
					$pnest++;
					break;
				case ')':
					$pnest--;
					$el['prio'] = PRIO_RP;
					$peer = $pary[$pnest];
					//! 匹配的跟自己差多少
					$e[$peer]['match'] = $i - $peer; 
					$el['type'] = $e[$peer]['type'];
					break;
				case '>':
				case '<':
				case '==':
				case '>=':
				case '<=':
				case '!=':
					break;
			}
		}
	}
	final
	function SymbolCheckOperate($text){
		switch($text){
			case '+':
			case '-':
				return PRIO_ADD;
			case '*':
			case '-':
				return PRIO_MULI;
		}
	}
	
	final
	function SymbolCheckString($text,&$vars){
		//! 函数
		if( in_array(strtoupper($text),$this->comm_funcs ) ){
			return PRIO_FUNC;
		}
		//! 数字
		if( preg_match("/[\d\.]+/",$text ) ){
			return PRIO_NUM;
		}
		//! 变量
		if( preg_match("/[a-zA-Z0-9]+/",$text ) ){
			if( ! array_key_exists($text,$vars) ){
				//! 类型是局部变量
				$vars [$text] = array ( 'name' => $text , 'type' => 'LOCAL' , 'init' => 0 ) ;
			}
			return  PRIO_VAR ;
		}
		//!
		return PRIO_ZERO;
	}
	
	#此处只切分
	final 
	function SymbolBuild($line,&$vars){
		#状态机
		$lc = strlen($line);
		#分隔符号
		$sp = array( ' ' , '(' , ')' , '=' , '+','-','*','/','%','>','<' ,',' ,'!' ); 
		#前一个字符
		$prech="";
		#当前字符
		$ch = "";
		#构成的符号
		$word = "";
		#词序
		$wc = 0 ;
		$result = array();
		for($i=0 ; $i < $lc ; $i++){
			$ch = $line{$i} ;
			if( ! in_array($ch,$sp) ){
				$word .= $ch ;
				continue;
			}
			if( $word != '' ){
				$e = array(	'text' => $word,	'type' => '符号',	'prio' => $this->SymbolCheckString($word,$vars)  );
				array_push($result,$e);
				$word = "";
			}
			if( $ch == ' ' ){
				continue;
			}
			if( $ch == ',' ){
				array_push($result,array('text'=>',','type'=>'符号','prio'=>PRIO_COMMA));
				continue;
			}
			if( $ch == '=' ){
				$prev = $line{$i-1};
				switch($prev){
					case '>':
					case '<':
					case '=':
					case '!':
						$result[sizeof($result)-1]['text'] .= '=';
						$result[sizeof($result)-1]['prio'] .= PRIO_LOGIC;
						break;
					default:
						$e = array('text' => $ch , 'type' => '运算' ,  'prio' => PRIO_SET );
						array_push($result,$e);
						break;
				}
				continue;
			}
			$e = array ('text' => $ch ,'type' => '运算' , 'prio' => $this->SymbolCheckOperate($ch) );
			array_push($result,$e);
		}
		return $result ;
	}
	#将行内的空格和TAB减少为一个
	function CompactString($line){
		$xline = trim($line);
		while( $xline != $line ){
			$line = $xline ; 
			$xline = str_replace("\t"," ",$xline);
			$xline = str_replace("  "," ",$line);
		}
		return $xline;
	}
	
	
	#支持的目标语言
	var $lang = array("PHP","PYTHON");
	#静态变量生成模板
	var $lang_statics = array(
		"PHP" => array(
			#静态变量声明及初始化  fprinf($fp,$this->statics['STATIC'],$name,$initval);
			'STATIC_DECLARE' => "		static \$statics = array() ; \r\n".
								"		if( !in_array(\$stock,\$statics) ){\r\n".
								"			\$statics[\$stock]=array();\r\n".
								"		}\r\n",
			'STATIC_SET'	=> 	"		\$statics[\$stock][\'%s\']=\'%s\';\r\n",
			'LOCAL_SET'		=>	"		\$%s = '%s';\r\n",
			'STATIC_GET'	=> 	"\$statics[\$stock][\'%s\']",
			'LOCAL_GET'		=>	"\$local['%s']",
			'ONCE' 			=>	"		static \$run_once = array();\r\n".
								"		if(\$stock==''){\r\n".
								"			\$run_once=array();\r\n".
								"		}",
			'LINE_END'		=>	"		if(! (%s) ){\r\n".
								"			return False;\r\n".
								"		}\r\n",
			'FUNCTION_END'	=>	"		return True;\r\n"
		)
		,'PYTHON' => array(
			"ONCE" => '\t\tstatic run_once = {} ; \r\n'
					  .'\t\tif( stock == \'\'):\r\n'
					  .'\t\t\trun_once={}\r\n'
					  .'\t\t#end of if\r\n'
		)
	);
 	#支持的函数模板
 	var $comm_funcs=array(
					"A","ABS","ACOS","ADVANCE","ALLSTKNUM","AMOUNT","ASIN","ASKPRICE",
					"ASKVOL","ATAN","AVEDEV","BACKSET","BARSCOUNT","BARSLAST","BARSLASTCOUNT",
					"BETWEEN","BIDPRICE","BIDVOL","BLOCKSETNUM","BSAMOUNT","BUYVOL",
					"BUYVOL","C","CEILING","CONST","COS","COST","COSTEX","COUNT","CROSS",
					"CURRBARSCOUNT","DATE","DAY","DC","DECLINE","DEVSQ","DH","DL","DMA",
					"DO","DOWNDAY","DV","EMA","EVERY","EXIST","EXP","EXPMEMA","FILTER",
					"FLOOR","FORCAST","FROMOPEN","H","HHV","HHVBARS","HORCALC","HOUR",
					"IF","IFN","INDEXA","INDEXADV","INDEXC","INDEXDEC","INDEXH","INDEXL",
					"INDEXO","INDEXV","INSORT","INTPART","ISBUYORDER","ISLASTBAR",
					"ISSELLORDER","L","LAST","LLV","LLVBARS","LMR","LN","LOG","LONGCROSS",
					"LOWRANGE","LWINNER","MA","MAX","MEMA","MIN","MINUTE","MONTH","MYSORTIDX",
					"NDAY","NOT","O","PEAK","PEAKBARS","PERIOD","POW","PPART","PWINNER","R",
					"RANGE","REF","REFDATE","REVERSE","SAR","SARTURN","SELLVOL","SELLVOL",
					"SIN","SLOPE","SMA","SQRT","STD","STDP","SUM","SUMBARS","TAN","TESTSKIP",
					"TFILT","TIME","TOPRANGE","TOTALBARSCOUNT","TROUGH","TROUGHBARS","UPNDAY",
					"V","VAR","VARP","VOLINSTK","WEEK","WINNER","XMA","YEAR","ZIG" 				
	);
	#函数调用的格式，默认把当前的代码放进去
	var $lang_funcs=array(
			"PHP" => "\$this->%s(\$stock",
			"PYTHON"=> "self.%s(stock"
	);
	#固定代码生成器
	var $lang_codes=array(
		"PHP" => array(
			"ONCE" => "\t\tif(array_key_exists(\$stock,\$run_once)){\r\n"
					 ."\t\t\treturn False;\r\n"
					 ."\t\t}\r\n"
					 ."\t\t\$run_once[\$stock]=1;\r\n"
			)
		,"PYTHON" => array(
			"ONCE" => "\t\tif stock  in run_once :\r\n"
					."\t\t\treturn False\r\n"
					."\t\trun_once[stock]=1\r\n"
			)
		);
	
}

