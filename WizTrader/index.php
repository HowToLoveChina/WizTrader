<?php

include_once("Global.php");
include_once("ScriptParser.php");


if( sizeof($_GET) > 0 ){
	cgi_server();
}else{
	cli_server();
}


function cgi_server(){
	//print_r($GLOBALS);
	echo "CGI";
}

function cli_server(){
	printf("%d>工作模式: CLI\n",__LINE__);
	global $Job,$Policy,$Method,$DataSource;
	if( $_SERVER['argc'] < 2 ){
		printf("%d>需要输入控制文件job后缀",__LINE__);
		return ;
	}
	$file = $_SERVER['argv'][1];
	$parser = new cScriptParser('PHP');
	$parser->Run(False,$file);
}


