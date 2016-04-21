<?php
/**
*	@author tomac
* 	@version 0.01
*	@date 2016-04-20 PM 21:43:59
*   @update
*/
//! 这是Parser的升级版，重点是减少内存的需求，简单回溯实现对脚本的处理
//! 不是一次性读入再执行表
include_once("Global.php");
#语法分析类
include_once("Grammar.php");
//!
class cScriptParser{
	#策略中的配置
	#type 变量类型
	#key  配置项
	#var  脚本使用的变量名或是函数名
	#need 是否必须
	#desc 描述说明

	var $init = False;

	//! 支持的語言清單
	var $extension = array( 
				'PHP'=>'php',
				'PYTHON'=>'py',
				'C'=>'c'
						);				
	
	//! 根据后缀决定用哪种文件级别的处理器
	var	$file_builder = array( 
			 "JOB" => "BuildJob"		//! 使用BuildJob方法来解析.JOB文件
			,"PLC" => "BuildPolicy" 	//! 使用BuildPolicy方法来解析.PLC文件
			,"MTH" => "BuildMethod"		//! 使用BuildMethod方法来解析.MTH文件
			,"DS"  => "BuildDataSource"	//! 使用BuildDataSource方法来解析.DS文件
							);
	//! 任务文件的各类数据项定义
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
	//! 策略文件的各类数据项
	var $policy_vars = array( 
			 array('type' => 'string'	,'key' => '$策略名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			,array('type' => 'int'		,'key' => '$起始资金' , 'need' => False )
			,array('type' => 'file'		,'key' => '$方法清单' , 'need' => True ) 
			,array('type' => 'func'		,'key' => '$择时调用' , 'var' => "hook_choose", 'need' => False ,'desc' => '根据历史数据决定是否在当天启用自己')
			);
	//! 方法文件的各类数据项
	var $method_vars = array(
			 array('type' => 'string'	,'key' => '$方法名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			,array('type' => 'func'		,'key' => '$选股器' , 'var'=>'filter','need' => False , 'desc' => '用昨日数据生成股票池')
			);
	//! 数据源文件的各类数据项
	var $datasource_vars = array(
			 array('type' => 'string','key' => '$数据源名称' , 'var'=>'name','need' => True , 'desc' => 'value用来生成文件名 data中存放说明')
			);
	//! 目标文件生成目录
	var $script_base = "/tmp";
	
	//! 语言格式相关的定义
	var	$lang_formats = array (
		"PHP" => array(															//! PHP语言中的定义
			"class"=> array(													//! 类中的定义
				"JOB" => array (
					"include" => array(											//! JOB类的前面包含部分
						"include_once('phar://WizTrader.phar/Job.php');\r\n",
						"include_once('phar://WizTrader.phar/Method.php');\r\n",
						"include_once('phar://WizTrader.phar/DataSource.php');\r\n",
						"include_once('phar://WizTrader.phar/Policy.php');\r\n"
					),
					'def' => "class %s extends cJob implements iJob{ \r\n",		//! 类声明代码
					'end' => "}#%s\r\n"
				),
				"POLICY" => array ('def' => "class %s extends cPolicy implements iPolicy{ \r\n"),
				"METHOD" => array ('def' => "class %s extends cMethod implements iMethod{ \r\n"),
				"DATASOURCE" => array ('def' => "class %s extends cDataBase implements iDataBase{ \r\n"),
				'end' => "}#%s\r\n"
			),
			"var" => array( 													//! 变量相关
				"begin"			=> "	function __construct(){ #%d\r\n" ,
				"builder"		=> "BuildCommonVarsPHP_var" , 
				"method_init" 	=> "		\$this->method = array();\r\n",
				"method_item"	=> "		\$this->method[] = new %s(); #@%d\r\n",
				"end"			=> "	}//__construct #%d\r\n",
				'int'			=> "		\$this->%s=%d;#%s\r\n",
				'float'			=> "		\$this->%s=%f;#%s\r\n",
				'string'		=> "		\$this->%s='%s';#%s\r\n"
			),
			"func" => array(													//! 函数声明
				'def'			=> "	function %s(\$stock){\r\n",
				'end'			=> "	\r\n\t}//%s\r\n"
			),
			"run" => array(														//! 执行
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
				'end' => "	#%s\r\n"
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
		), #当前语言数组结束
		"C" =>array(
			"class" => array(
				"JOB" => array (
					"include" => array(
						"#include 'job.hpp'\r\n",
						"#include 'method.hpp'\r\n",
						"#include 'datasource.hpp'\r\n",
						"#include 'policy.hpp'\r\n",
					),
					'def' => "	class %s :public cJob , iJob {\r\n"
				),
				"POLICY" 	=> array ('def' => "	class %s : public cPolicy , public iPolicy{\r\n"),
				"METHOD" 	=> array ('def' => "	class %s : public cMethod , public iMethod{\r\n"),
				"DATASOURCE"=> array ('def' => "	class %s : public cDataBase , public iDataBase{\r\n"),
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
				'def'			=> "	bool %s(std::string stock){\r\n",
				'end'			=> "	}#%s\r\n"
			),
			"run" => array(
				"include" => array("int main( int argc  ,char **argv){\r\n"),
				"object" => "	%s * o = new %s(); \r\n",
				"execute"=> "	o->Run();\r\n"
			)
		) #当前语言数组结束
	);#语言数组结束
	
	
	/** 構造函數指定生成的語言
	*	@param [in] string $alang  指明要生成什么语言
	*/
	function __construct(/*string*/$alang='PHP'){
		$this->init = False;
		if( ! is_string($alang) ){
			return ;
		}
		$this->init = True;
		$this->LangSpecify($alang);
	}
	/** 內部變元初始化
	*	@param [in] string $alang 指明目标语言
	*	@return bool 
	*/
	function LangSpecify(/*string*/$alang){
		//! 检测初始化标志
		if( ! $this->init ){
			return False;
		}
		//! 检测输入参数的类型
		if( ! is_string($alang) ){
			printf("输入参数类型不符\r\n");
			return False;
		} 
		//! 检测输入值合法性
		if( $alang == "" ){
			#没有指定那么就读取文件中的生成语言类型
			return False;
		}
		//! 设定script_type属性
		$this->script_type = $alang;
		//! 检查是否在支持的语言内
		if( ! array_key_exists($this->script_type,$this->extension) ){
			throw new Exception(sprintf("不支持的语言类型%s",$this->script_type));
			return False;
		}
		//! 取得扩展
		$this->script_extension = $this->extension[$this->script_type];
		//! 取得语言相关的处理配置
		$this->lang_format = $this->lang_formats[$alang];
		//! 
		return True;
	}
	/** 执行解析工作，如果出错返回假
	*	@param [in] resource $afp 输出句柄
	*	@param [in] string   $afilename  源文件名字
	*	@return bool 
	*/
	final 
	function Run(/*resource*/$afp,/*string*/$afilename){
		//! 检查输入类型是否正确
		if( ! is_resource($afp) ){
			//! 首次运行可以不是句柄
		}
		if( ! is_string($afilename) ){
			printf("%s.%d>输入的不是字符串\n",__FUNCTION__,__LINE__);
			return False;
		}
		//! 不管内容，加载到内存
		$data = $this->LoadFile($afilename);
		if( sizeof($data) < 5 ){
			//! 数据太少返回假
			printf("%s.%d>源文件内容过少\n",__FUNCTION__,__LINE__);
			return False;
		}
		//! 分析文件的后缀
		$fi=@pathinfo($afilename);
		//!根据后缀执行生成，首次运行将会产生$fp
		//!再递归调用，所以暂时不实现%指令
		$ext = strtoupper($fi['extension']);
		//! 检查是否含有这个扩展名对应的处理器？
		if( ! array_key_exists($ext,$this->file_builder) ){
			throw new Exception(__LINE__ . sprintf(">不支持的后缀名 %s",$ext) );
			return False;
		}
		//! 没有指定脚本类型，或是在脚本中指定了目标语言
		if( $this->script_type == "" || array_key_exists('$任务语言',$data) ){
			//! 检查是否含有目标脚本语言
			if( array_key_exists('$任务语言',$data) ){
				//! 取出这个目标语言
				$lang = $data['$任务语言']['value'];
				if( $lang != "" ){
					#如果出错了，那么会有意外抛出
					if( ! $this->LangSpecify($lang) ){
						throw new Exception(__LINE__ . sprintf(">指定生成脚本语言类型 $lang 错") );
						return False;
					}
				}else{
					throw new Exception(__LINE__ . sprintf(">没有指定生成脚本语言类型") );
					return False;
				}
			}else{
				throw new Exception(__LINE__ . sprintf(">没有指定生成脚本语言类型") );
				return False;
			}
		}
		//! 取出分析器
		$func = $this->file_builder[$ext];
		//! 执行分析器
		return $this->$func($afp,$data);
	}
	/** 检查必须的值是否存在,如果出错的话返回假，同时抛出异常
	*	@param [in] array $aneed 所有需要的配置项
	*	@param [in] array $data  从文件里获得的数据
	*	@return bool 
	*/
	function BuildCheckNeed(/*array*/$aneed,/*array*/$adata){
		if( ! is_array($aneed )){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array ( $adata )) {
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		//! 本地出错信息置空
		$err = "";
		//! 枚举所有的类内部配置
		foreach($aneed as $e){
			//! 如果配置项不是数组，那么跳过
			if( ! is_array($e) ){
				continue;
			}
			//! 如果对应的项是个空数组，那么跳过
			if( sizeof($e) == 0 ){
				continue; 
			}
			//! 不是必须项，那么跳过
			if( !$e['need']){
				continue;
			}
			//! 检查这个必须项是否存在
			if( ! array_key_exists($e['key'],$adata) ){
				//! 如果不存在那么把返回的出错信息中加入这个消息
				$err .=sprintf("缺少必须的配置项%s\r\n",$e['key']);
			}
		}
		//! 有出错信息，就抛出这个异常，这样可以在GUI里说明哪一条出错了
		if( $err != "" ){
			#print_r($need);
			#print_r($data);
			throw new Exception(sprintf("%d>%s",__LINE__,$err) );
			return False;
		}
		return True;
	}
	/** 生成策略脚本
	*	@param [in] resource $afp 生成脚本的句柄
	*	@param [in] array $adata 从源文件里解析出来的数据
	*	@return bool 如果出错返回假,同时会有异常抛出
	*/
	final
	function BuildPolicy(/*resource*/$afp,/*array*/ $adata){
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		//! 检查策略中必须的变量是否都具备
		if( ! $this->BuildCheckNeed($this->policy_vars,$adata) ){
			//! 如果不满足要求的话，就返回假
			//! 注意BuildCheckNeed会抛出异常
			return False;
		}
		//! 传入的句柄有效，那么就不能在这里关
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
	/**	检查文件指针是否已经打开
	*	@param [in] resource $afp 输出句柄
	*	@param [in] string $aname 要打开的文件名
	*	@return bool
	*/
	function BuildCheckFp(/*resource*/&$afp,/*string*/$aname){
		if( is_resource($afp) ){
			return True;
		}
		$fn = sprintf("%s/%s.%s",$this->script_base,$aname,$this->script_extension);
		$afp = fopen($fn,"wt+");
		if( $afp === FALSE ){
			throw new Exception( sprintf("%s.%d> 打开文件[%s]失败",__FUNCTION__,__LINE__,$fn) );
			return False;
		}
		switch($this->script_extension){
		case "c":
			//! c语言解释写入
			fprintf($afp,"//===========================================\r\n");
			fprintf($afp,"//创建策略脚本于 %s \r\n",date("Y-m-d H:i:s") );
			fprintf($afp,"//===========================================\r\n");
			break;
		case "php":
			fprintf($afp,"<?php \r\n#===========================================\r\n");
			fprintf($afp,"#创建策略脚本于 %s \r\n",date("Y-m-d H:i:s") );
			fprintf($afp,"#===========================================\r\n");
			break;
		case "py":
			fprintf($afp,"#===========================================\r\n");
			fprintf($afp,"#创建策略脚本于 %s \r\n",date("Y-m-d H:i:s") );
			fprintf($afp,"#===========================================\r\n");
			break;
		default:
			printf("%s.%d>不支持的语言类型 {$this->script_extension}\n",__FUNCTION__,__LINE__);
			return FALSE;
		}
		return True;
	}
	/** 创建相应的函数
	*	@param [in] resource $afp	输出句柄
	*	@param [in] string $atype 正在处理的源文件类型  ::= POLICY|DATASOURCE|METHOD|JOB
	*	@param [in] array $adata 源文件的内容
	*/
	final
	function BuildCommonFunc(/*resource*/$afp,/*string*/$atype,/*array*/$adata){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字符串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		foreach( $this->BuildCommonVarsGet($atype)  as $e){
			//! 非函数类的不处理
			if( $e['type'] != 'func' ){
				continue;
			}
			$name = $e['key'];
			//! 生成函数名
			$func = $this->BuildCommonFuncName($afp,$e);
			//! 生成分析器
			$o = new cGrammar($this->script_type);
			//! 逐行分析
			if( array_key_exists($name,$adata) ){
				$o->ScriptBuilderArray($afp,$adata[$name]['data'],$func);
			}else{
				fprintf($afp,"		#脚本未实现本函数\r\n");
			}
			#结束函数代码，能跑到最后，当然就是返回真
			$this->BuildCommonFuncEnd($afp,$func);
		}
		return true;
	}
	/** 结束函数尾部
	*	@param [in] resource $afp 输出句柄
	*	@param [in] string $aname 
	*/
	final
	function BuildCommonFuncEnd(/*resource*/$afp,/*stirng*/$aname){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($aname) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		
		$format = $this->lang_format['func'];
		#在Gramar里生成了 return true;
		fprintf($afp,$format['end'],$aname);
		return True;
	}
	/** 创建函数，返回函数名 
	*	@param [in] resource $afp
	*	@param [in] array $adata
	*	@param [in] string $aname
	*	@return string 
	*/
	final
	function BuildCommonFuncName(/*resource*/$afp,/*array*/ $adata,/*string*/$aname="")/*:string*/{
		if( ! is_string($aname) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return "";
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return "";
		}
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return "";
		}
		if( $aname == "" ){
			$aname = $adata['var'];
		}
		$format = $this->lang_format['func'];
		fprintf($afp,$format['def'],$aname);
		return $aname;
	}
	/** 生成文件吧
	*	@param [in] resource $afp
	*	@param [in] string $atype
	*	@param [in] array $adata
	*/
	final
	function BuildCommonFile(/*resource*/$afp,/*string*/$atype,/*array*/$adata){
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
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
		return True;
	}
	/** 根据类型获得配置
	*	@param [in] string $atype ::= JOB | POLICY | METHOD | DATASOURCE
	*	@return array
	*/
	function BuildCommonVarsGet(/*string*/$atype){
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return array();
		}
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
				return array();
		}
	}
	/** 生成变量
	*	@param [in] resource $afp 	输出句柄
	*	@param [in] string $atype
	*	@param [in] array $adata
	*/
	final
	function BuildCommonVars(/*resource*/$afp,/*string*/$atype,/*array*/$adata){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		$format = $this->lang_format['var'];
		#这是初始化函数声明
		fprintf($afp,$format['begin'],__LINE__);
		#生成变量集
		foreach( $this->BuildCommonVarsGet($atype)  as $e){
			//! 非数组跳过
			if(! is_array($e) ){
				continue;
			}
			//! 空数组跳过
			if( sizeof($e) == 0 ){
				continue; 
			}
			//! 无变量设定跳过
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
			//! 输出变量
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
		}else
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
	/** 生成方法
	*	@param [in] resource $afp	输出句柄
	*	@param [in] array $adata 	输入数据
	*	@return bool
	*/
	final
	function BuildMethod(/*resource*/&$afp,/*array*/ $adata){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
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
	/** 生成任务
	*	@param [in/out] resource $afp 
	*	@param [in]	$adata
	*/
	final
	function BuildJob(/*resource*/&$afp,/*array*/ $adata){
		if( ! is_resource($afp) ){
			//! 任务文件可以没有输出句柄在下面打开
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! $this->BuildCheckNeed($this->job_vars,$adata) ){
			printf("%d>%s\r\n",__LINE__,__FUNCTION__);
			return False;
		}
		//!传入的句柄有效，那么就不能在这里关
		$donotclose = ($afp != FALSE);
		$name = $adata['$任务名称']['value'];
		$this->BuildCheckFp($afp,$name);
		$classname = $this->BuildCommon($afp,"JOB",$name,$adata);			#生成变量，函数，依赖
		#关闭
		if(!$donotclose){ 
			fclose($afp);
			$afp = False;
		}
		return True;
	}
	/** 生成数据源
	*	@param [in] resource  $afp 
	*	@param [in] array $adata 
	*	@return bool 
	*/
	final
	function BuildDataSource(/*resource*/&$afp,/*array*/ $adata){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
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
	function Compact(/*string*/$aline){
		$xline = trim($aline);
		while( $xline != $aline ){
			$aline = $xline ; 
			$xline = str_replace("  "," ",$aline);
			$xline = str_replace("\t"," ",$xline);
		}
		return trim($aline);
	}
	/** 文件加载到内存里
	*	@param [in] string $afilename
	*	@return array
	*/
	final
	function LoadFile(/*string*/$afilename){
		if( ! is_string($afilename) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return array();
		}
		$fc = file($afilename);
		if( sizeof($fc) < 5 ){
			printf("%s.%d>源文件[$afilename]内容过少\n",__FUNCTION__,__LINE__);
			return array();
		}
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
	function BuildCommonClassEnd(/*resource*/$afp,/*string*/ $atype , /*string*/ $aname){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($aname) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		$format = $this->lang_format['class'];
		fprintf($afp,$format['end'], $atype,$aname);
		return True;
	}
	final 
	function BuildCommonClassName(/*resource*/$afp,/*string*/ $atype,/*string*/ $aname){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($aname) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
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

	/** 通用解析
	*	@param [in] resource $afp	输出句柄
	*	@param [in] string $atype	要解析的类型 ::= POLICY | METHOD | DATASOURCE | JOB
	*	@param [in] string  $aname	源文件中定义的名字
	*	@param [in] array $adata	源文件的数据
	*	@return bool
	*/
	final
	function BuildCommon(/*resource*/$afp,/*string*/ $atype,/*string*/ $aname,/*array*/ $adata){
		//! 输入参数检查
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($atype) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_array($adata) ){
			printf("%s.%d>输入的不是数组\n",__FUNCTION__,__LINE__);
			return False;
		}
		//! 生成类名
		$class_name = $this->BuildCommonClassName($afp,$atype,$aname);			
		//! 生成初始化函数和属性变量
		$this->BuildCommonVars($afp,$atype,$adata);
		//! 生成函数方法
		$this->BuildCommonFunc($afp,$atype,$adata);
		//! 当前类完成
		$this->BuildCommonClassEnd($afp,$atype,$aname);
		//! 生成依赖内容
		$this->BuildCommonFile($afp,$atype,$adata);
		if( $atype == "JOB" ){
			//! 只有任务才能生成启动代码
			$this->BuildCommonRun($afp,$class_name);
		}
		return True;
	}
	/** 生成运行代码
	*	@param [in] resource $afp 输出句柄
	*	@param [in] string  $aclass_name 要启动的类名
	*   @return bool
	*/
	final
	function BuildCommonRun(/*resource*/$afp,/*string*/$aclass_name){
		if( ! is_resource($afp) ){
			printf("%s.%d>输入的不是句柄\n",__FUNCTION__,__LINE__);
			return False;
		}
		if( ! is_string($aclass_name) ){
			printf("%s.%d>输入的不是字串\n",__FUNCTION__,__LINE__);
			return False;
		}
		$format = $this->lang_format['run'];
		if( ! is_array($format) ){
			return False;
		}
		#生成引用
		if( array_key_exists("include" , $format ) ){
			foreach($format['include'] as $line ){
				fprintf($afp,"%s",$line);
			}
		}
		fprintf($afp,$format['object'],$aclass_name);
		fprintf($afp,$format['execute']);
		return true;
	}
}

