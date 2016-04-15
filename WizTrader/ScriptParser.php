<?php
//! 这是Parser的升级版，重点是减少内存的需求，简单回溯实现对脚本的处理
//! 不是一次性读入再执行表

include_once("Global.php");
#语法分析类
include_once("Grammar.php");

class cScriptParser{
	#策略中的配置
	#type 变量类型
	#key  配置项
	#var  脚本使用的变量名或是函数名
	#need 是否必须
	#desc 描述说明
	var $extension = array( 'PHP'=>'php','PYTHON'=>'py');
	
	#根据后缀决定用哪种文件级别的处理器
	var	$file_builder = array( 
			 "JOB" => "BuildJob"
			,"PLC" => "BuildPolicy" 
			,"MTH" => "BuildMethod"
			,"DS"  => "BuildDataSource"
			);
	#任务文件的各类数据项
	var $job_vars = array(
			 array('type' => 'file'		,'key' => '$策略清单' , 'need'=> True )
			,array('type' => 'string'	,'key' => '$任务名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			,array('type' => 'int'		,'key' => '$起始资金' , 'var'=>'money','need' => True )
			,array('type' => 'string'	,'key' => '$任务类型' , 'var'=>'run_type','need' => True )
			,array('type' => 'string'	,'key' => '$回测周期' , 'var'=>'period' , 'need' => True )
			,array('type' => 'string'	,'key' => '$数据来源' , 'var'=>'data_source' , 'need' => True )
			
			,array('type' => 'int'		,'key' => '$回测起始' , 'var'=>'date_begin' , 'need' => False )
			,array('type' => 'int'		,'key' => '$回测结束' , 'var'=>'date_end' , 'need' => False )
			,array('type' => 'string'	,'key' => '$单笔资金' , 'var'=>'per' , 'need' => False )
			,array('type' => 'string'	,'key' => '$券商类型' , 'var'=>'broker' , 'need' => False )
			,array('type' => 'string'	,'key' => '$账户用户' , 'var'=>'username' , 'need' => False )
			,array('type' => 'string'	,'key' => '$帐户密码' , 'var'=>'password' , 'need' => False )
			);
	#策略文件的各类数据项
	var $policy_vars = array( 
			array('type' => 'string','key' => '$策略名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			,array('type' => 'int','key' => '$起始资金' , 'need' => False )
			,array('type' => 'file','key' => '$方法清单' , 'need' => True ) 
			,array('type' => 'func','key' => '$择时调用' , 'var' => "hook_choose", 'need' => False ,'desc' => '根据历史数据决定是否在当天启用自己')
			);
	#方法文件的各类数据项
	var $method_vars = array(
			 array('type' => 'string','key' => '$方法名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			,array('type' => 'func','key' => '$选股器' , 'var'=>'filter','need' => False , 'desc' => '用昨日数据生成股票池')
			);
	#数据源文件的各类数据项
	var $datasource_vars = array(
			 array('type' => 'string','key' => '$数据源名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			);
	#文件生成到哪里去
	var $script_base = "/tmp";
	
	var	$lang_formats = array (
		"PHP" => array(
			"class"=> array(
				"JOB" => array (
					"include" => array(
						"include_once('Job.php');\r\n",
						"include_once('Method.php');\r\n",
						"include_once('DataSource.php');\r\n",
						"include_once('Policy.php');\r\n"
					),
					'def' => "class %s extends cJob implment iJob{ \r\n"
				),
				"POLICY" => array ('def' => "class %s extends cPolicy implment iPolicy{ \r\n"),
				"METHOD" => array ('def' => "class %s extends cMethod implment iMethod{ \r\n"),
				"DATASOURCE" => array ('def' => "class %s extends cDataBase implment iDataBase{ \r\n"),
				'end' => "}#%s\r\n"
			),
			"var" => array( 
				"begin"			=> "	function __construct(){ #%d\r\n" ,
				"builder"		=> "BuildCommonVarsPHP_var" , 
				"method_init" 	=> "		\$this->method = array();\r\n",
				"method_item"	=> "		\$this->method[] = new %s(); #@%d\r\n",
				"end"			=> "	}//__construct #%d\r\n",
				'int'			=> "		\$this->%s=%d;#%s\r\n",
				'float'			=> "		\$this->%s=%f;#%s\r\n",
				'string'		=> "		\$this->%s='%s';#%s\r\n"
			),
			"func" => array(
				'def'			=> "	function %s(\$stock){\r\n",
				'end'			=> "	\r\n\t}//%s\r\n"
			),
			"run" => array(
				"include" => array(),
				"object" => "\$o = new %s(); \r\n",
				"execute"=> "\$o->Run();\r\n"
			)
		),
		"PYTHON" =>array(
			"class" => array(
				"JOB" => array (
					"include" => array(
						"from Job import *\r\n",
						"from Method import *\r\n",
						"from DataSource import *\r\n",
						"from Policy import *\r\n",
					),
					'def' => "	def %s(cJob):\r\n"
				),
				"POLICY" 	=> array ('def' => "	def %s (cPolicy):\r\n"),
				"METHOD" 	=> array ('def' => "	def %s (cMethod):\r\n"),
				"DATASOURCE"=> array ('def' => "	def %s (cDataBase):\r\n"),
				'end' => "}#%s\r\n"
			),
			"var" => array( 
				"begin"			=> "	def __init__(self){ #%d\r\n" ,
				"builder"		=> "BuildCommonVarsPYTHON_var" , 
				"method_init" 	=> "		self.method = []\r\n",
				"method_item"	=> "		self.method[] = new %s(); #@%d\r\n",
				"end"			=> "	#//__construct #%d\r\n",
				'int'			=>"		self.%s=%d;#%s\r\n",
				'float'			=>"		self.%s=%f;#%s\r\n",
				'string'		=>"		self.%s='%s';#%s\r\n"
			),
			"func" => array(
				'def'			=> "	def%s(self,stock):\r\n",
				'end'			=> "	#%s\r\n"
			),
			"run" => array(
				"include" => array("if __name__ == '__main__' :\r\n"),
				"object" => "	o = %s(); \r\n",
				"execute"=> "	o.Run();\r\n"
			)
		) #当前语言数组结束
	);#语言数组结束
	
	

	function __construct($alang='PHP'){
		$this->LangSpecify($alang);
	}
	function LangSpecify($alang){
		$this->script_type = $alang;
		if( $alang == "" ){
			#没有指定那么就读取文件中的生成语言类型
			return ;
		}
		if( ! array_key_exists($this->script_type,$this->extension) ){
			throw new Exception(sprintf("不支持的语言类型%s",$this->script_type));
			return ;
		}
		$this->script_extension = $this->extension[$this->script_type];
		$this->lang_format = $this->lang_formats[$alang];
	}
	#输入文件名
	final 
	function Run($afp,$afilename){
		#不管内容，加载到内存
		$data = $this->LoadFile($afilename);
		#找后缀
		$fi=@pathinfo($afilename);
		#根据后缀执行生成，首次运行将会产生$fp
		#再递归调用，所以暂时不实现%指令
		$ext = strtoupper($fi['extension']);
		if( ! array_key_exists($ext,$this->file_builder) ){
			throw new Exception(__LINE__ . sprintf(">不支持的后缀名 %s",$ext) );
			return False;
		}
		if( $this->script_type == "" || array_key_exists('$任务语言',$data) ){
			if( array_key_exists('$任务语言',$data) ){
				$lang = $data['$任务语言']['value'];
				if( $lang != "" ){
					#如果出错了，那么会有意外抛出
					$this->LangSpecify($lang);
				}else{
					throw new Exception(__LINE__ . sprintf(">没有指定生成脚本语言类型") );
					return False;
				}
			}
		}
		$func = $this->file_builder[$ext];
		return $this->$func($afp,$data);
	}
	#检查必须的值是否存在
	function BuildCheckNeed($aneed,$adata){
		$err = "";
		#检查必须的变量
		foreach($aneed as $e){
			if( sizeof($e) == 0 ){
				continue; 
			}
			if( !$e['need']){
				continue;
			}
			if( ! array_key_exists($e['key'],$adata) ){
				$err .=sprintf("缺少必须的配置项%s\r\n",$e['key']);
			}
		}
		if( $err != "" ){
			#print_r($need);
			#print_r($data);
			throw new Exception(sprintf("%d>%s",__LINE__,$err) );
			return False;
		}
		return True;
	}
	#生成策略
	final
	function BuildPolicy($afp,array $adata){
		if( ! $this->BuildCheckNeed($this->policy_vars,$adata) ){
			return False;
		}
		#传入的句柄有效，那么就不能在这里关
		$donotclose = ($afp != FALSE);
		$name = $adata['$策略名称']['value'];
		$this->BuildCheckFp($afp,$name);
		$this->BuildCommon($afp,"POLICY",$name,$adata);
		#关闭
		if(!$donotclose){ 
			fclose($afp);
		}
		return True;
	}
	function BuildCheckFp(&$afp,$aname){
		if( $afp !== FALSE ){
			return True;
		}
		$fn = sprintf("%s/%s.%s",$this->script_base,$aname,$this->script_extension);
		$afp = fopen($fn,"wt+");
		if( $afp === FALSE ){
			throw new Exception( sprintf("%d> 打开文件[%s]失败",__LINE__,$fn) );
			return False;
		}
		fprintf($afp,"#===========================================\r\n");
		fprintf($afp,"#创建策略脚本于 %s \r\n",date("Y-m-d H:i:s") );
		fprintf($afp,"#===========================================\r\n");
		return True;
	}
	#创建相应的函数
	final
	function BuildCommonFunc($afp,$atype,$adata){
		foreach( $this->BuildCommonVarsGet($atype)  as $e){
			#非函数类的不处理
			if( $e['type'] != 'func' ){
				continue;
			}
			$name = $e['key'];
			#生成函数名
			$func = $this->BuildCommonFuncName($afp,$e);
			#生成分析器
			$o = new cGrammar($this->script_type);
			#逐行分析
			if( array_key_exists($name,$adata) ){
				$o->ScriptBuilderArray($afp,$adata[$name]['data'],$func);
			}else{
				fprintf($afp,"		#脚本未实现本函数\r\n");
			}
			#结束函数代码，能跑到最后，当然就是返回真
			$this->BuildCommonFuncEnd($afp,$func);
		}
	}
	#结束函数尾部
	final
	function BuildCommonFuncEnd($afp,$aname){
		$format = $this->lang_format['func'];
		#在Gramar里生成了 return true;
		fprintf($afp,$format['end'],$aname);
	}
	#创建函数，返回函数名 
	final
	function BuildCommonFuncName($afp,array $adata,$aname=""):string{
		if( $aname == "" ){
			$aname = $adata['var'];
		}
		$format = $this->lang_format['func'];
		fprintf($afp,$format['def'],$aname);
		return $aname;
	}
	#
	final
	function BuildCommonFile($afp,$atype,$adata){
		foreach( $this->BuildCommonVarsGet($atype)  as $e){
			if( sizeof($e) == 0 ){
				continue; 
			}
			if( $e['type'] != 'file' ){
				continue;
			}
			if( ! array_key_exists($e['key'],$adata) ){
				continue;
			}
			$x = $adata[$e['key']];
			foreach( $x['data'] as $i){
				fprintf($afp,"#===========================================\r\n");
				fprintf($afp,"# %s %s START @ %d \r\n",$atype,$i['text'], $i['line']);
				fprintf($afp,"#===========================================\r\n");
				switch($atype){
					case "JOB":
						$this->Run( $afp, $i['text'].".plc");
						return;
					case "POLICY":
						$this->Run( $afp, $i['text'].".mth");
						return;
					case "METHOD":
					case "DATASOURCE":
						throw new Exception(sprint("%d>配置不正确，不支持FILE类型参数%s",__LINE__,$atype));
						return;
					default:
						throw new Exception(sprint("%d>不支持类型%s",__LINE__,$atype));
						return ;
				}//switch
			}//foreach
		}
	}
	#返回通用数据源
	function BuildCommonVarsGet($atype){
		switch($atype){
			case "JOB":
				return $this->job_vars;
			case "POLICY":
				return $this->policy_vars;
			case "METHOD":
				return $this->method_vars;
			case "DATASOURCE":
				return $this->datasource_vars;
			default:
				throw new Exception(sprintf("%d>未知的格式%s",__LINE__,$atype));
				return False;
		}
	}
	#
	final
	function BuildCommonVars($afp,$atype,$adata){
		$format = $this->lang_format['var'];
		#这是初始化函数声明
		fprintf($afp,$format['begin'],__LINE__);
		#生成变量集
		foreach( $this->BuildCommonVarsGet($atype)  as $e){
			if( sizeof($e) == 0 ){
				continue; 
			}
			if( ! array_key_exists('var',$e) ){
				continue;
			}
			$name = $e['var'];$key = $e['key'];
			if( ! array_key_exists($key,$adata) ){
				#变量不存在
				continue ;
			}
			$value = $adata[$key]['value'];
			if( is_null($value) ){
				//! 空值就不处理了
				continue;
			}
			fprintf($afp,$format[$e['type']],$name,$value,$e['key']);
			$data  = $adata[$key]['data'];
			if( is_array($data) ){
				foreach($data as $i) {
					fprintf($afp,"	#%s\r\n",$i['text']);
				}
			}
		}
		if( $atype == "JOB" ){
			fprintf($afp,$format['method_init']);
			foreach( $adata['$策略清单']['data'] as $n){
				$name=sprintf("%s_%s_%s",strtolower($this->script_type),"policy",trim($n['text']));
				fprintf($afp,$format['method_item'],$name,$n['line']);
			}
		}
		if( $atype == "POLICY" ){
			fprintf($afp,$format['method_init']);
			foreach( $adata['$方法清单']['data'] as $n){
				$name=sprintf("%s_%s_%s",strtolower($this->script_type),"method",trim($n['text']));
				fprintf($afp,$format['method_item'],$name,$n['line']);
			}
		}
		#初始化函数结束
		fprintf($afp,$format['end'],__LINE__);
	}
	#生成方法
	final
	function BuildMethod(&$afp,array $adata){
		if( ! $this->BuildCheckNeed($this->method_vars,$adata) ){
			return False;
		}
		#传入的句柄有效，那么就不能在这里关
		$donotclose = ($afp != FALSE);
		$name = $adata['$方法名称']['value'];
		$this->BuildCheckFp($afp,$name);
		$this->BuildCommon($afp,"METHOD",$name,$adata);			#生成变量，函数，依赖
		#关闭
		if(!$donotclose){ 
			fclose($afp);
			$afp = False;
		}
		return True;
	}
	#生成任务
	final
	function BuildJob(&$afp,array $adata){
		if( ! $this->BuildCheckNeed($this->job_vars,$adata) ){
			printf("%d>%s\r\n",__LINE__,__FUNCTION__);
			return False;
		}
		#传入的句柄有效，那么就不能在这里关
		$donotclose = ($afp != FALSE);
		$name = $adata['$任务名称']['value'];
		$this->BuildCheckFp($afp,$name);
		$classname = $this->BuildCommon($afp,"JOB",$name,$adata);			#生成变量，函数，依赖
		#关闭
		if(!$donotclose){ 
			fclose($afp);
			$afp == False;
		}
		return True;
	}
	#生成数据源
	final
	function BuildDataSource(&$afp,array $adata){
		if( ! $this->BuildCheckNeed($this->datasource_vars,$adata) ){
			return False;
		}
		#传入的句柄有效，那么就不能在这里关
		$donotclose = ($afp != FALSE);
		$name = $adata['$数据源名称']['value'];
		$this->BuildCheckFp($afp,$name);
		$this->BuildCommon($afp,"DATASOURCE",$name,$data);			#生成变量，函数，依赖
		#关闭
		if(!$donotclose){ 
			fclose($afp);
			$afp = False;
		}
		return True;
	}
	#去掉不必要的空格
	final
	function Compact($aline){
		$xline = trim($aline);
		while( $xline != $aline ){
			$aline = $xline ; 
			$xline = str_replace("  "," ",$aline);
			$xline = str_replace("\t"," ",$xline);
		}
		return trim($aline);
	}
	#把文件加载到内存里
	final
	function LoadFile($afilename){
		$fc = file($afilename);
		$bl = "";
		$data = array();
		$lc = 0 ;
		foreach($fc as $line ){
			#echo $line;
			//行号
			$lc ++  ;
			$line = $this->Compact($line);
			if( $line == "" ){
				continue;
			}
			$ch = $line{0} ;
			$skip=True;
			switch($ch){
				#注释
				case '#':
					#此处放continue无效
					break;
				#章节
				case '$':
					$value = "";
					@list($section,$value) = @explode(" ",$line);
					$data[$section]=array('value'=>$value,'data'=>array(),'line'=>$lc,'text'=>$section);
					$bl = $section ;
					break;
				#文件暂时不支持吧
				case '%':
					break;
				default:
					$skip=False;
					break;
			}
			if( $skip ){
				continue;
			}
			#记录行号
			$data[$bl]['data'][] = array( 'line' => $lc,'text' => $line);
		}
		return $data;
	}
	#结束类
	final
	function BuildCommonClassEnd($afp,string $atype , string $aname){
		$format = $this->lang_format['class'];
		fprintf($afp,$format['end'], $atype,$aname);
	}
	final 
	function BuildCommonClassName($afp,string $atype,string $aname){
		$cname = strtolower($this->script_type) . "_" . strtolower($atype) . "_" . $aname;
		$formats = $this->lang_format['class'];
		if( ! array_key_exists($atype,$formats) ){
			throw new Execption(sprintf("%d>不支持的类型%s",__LINE__,$atype));
			return;
		}
		$format = $formats [ $atype ];
		#生成引用
		if( array_key_exists("include" , $format ) ){
			foreach($format['include'] as $line ){
				fprintf($afp,"%s",$line);
			}
		}
		#生成类名
		fprintf($afp,$format['def'],$cname);
		return $cname;
	}

	#
	final
	function BuildCommon($afp,string $atype,string $aname,array $adata){
		$class_name = $this->BuildCommonClassName($afp,$atype,$aname);			#生成类名
		#生成初始化函数和属性变量
		$this->BuildCommonVars($afp,$atype,$adata);
		#生成函数方法
		$this->BuildCommonFunc($afp,$atype,$adata);
		#当前类完成
		$this->BuildCommonClassEnd($afp,$atype,$aname);
		#生成依赖内容
		$this->BuildCommonFile($afp,$atype,$adata);
		if( $atype != "JOB" ){
			return ;
		}
		#生成启动代码
		$this->BuildCommonRun($afp,$class_name);
	}
	
	final
	function BuildCommonRun($afp,$aclass_name){
		$format = $this->lang_format['run'];
		#生成引用
		if( array_key_exists("include" , $format ) ){
			foreach($format['include'] as $line ){
				fprintf($afp,"%s",$line);
			}
		}
		fprintf($afp,$format['object'],$aclass_name);
		fprintf($afp,$format['execute']);
	}
}

