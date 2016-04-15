<?php

if( defined("GLOBAL_INIT") ){
	global $ProcLock,$Pids;
	return ;
}

define("GLOBAL_INIT",1);

$Job=array();
$Policy=array();
$Method=array();
$DataSource=array();
$Pids = array();
$ProcLock = NULL;
$ProcNum = 16 ; 			#硬件支持的线程数*2

$ProcLock = sem_get ( ftok("/","/") , $ProcNum , 0666 );

#命令行不能用ob_start
#ob_start("GlobalDeinit");

function proc_wait(){
	global $Pids,$ProcLock;
	//! 进程满了
	while( sizeof($Pids) != 0 ){
		$flag = false;
		//! 看看有没有退出的
		foreach($Pids as $idx => $pid){
			$status = 0 ;
			$r = pcntl_waitpid($pid,$status,WNOHANG);
			if( $r != 0 ){
				//! 有一个退出了，就可以再试试了
				unset($Pids[$idx]);
				//var_dump($Pids);
				@sem_release($ProcLock);
				$flag = true;
				break;
			}
		}
		if( ! $flag ){
			sleep(1);
		}
	}
	//! 到这里本级全部退出了
}	

function proc_fork($subfunc,$args){
	global $Pids,$ProcLock;
	//! 进程满了
	while( ! sem_acquire ( $ProcLock , true) ){
		sleep(1);
		if( sizeof($self->pids) == 0 ){
			//! 本级没有需要等待的，那么就干等
			continue;
		}
		//! 看看有没有退出的
		foreach($Pids as $idx => $pid){
			$status = 0 ;
			$r = pcntl_waitpid($pid,$status,WNOHANG);
			if( $r != 0 ){
				//! 有一个退出了，就可以再试试了
				@sem_release($ProcLock);
				unset($Pids[$idx]);
				break;
			}
		}
	}
	//!到这里获得了一个信号量
	$pid = pcntl_fork ();
	if( $pid == 0 ){
		//! 新进程清空进程列表
		$Pids = array();
		$subfunc($args);
		GlobalDeinit();
		exit(0);
	}else{
		//! 原进程加入新的 PID
		$Pids[]=$pid;
	}
	return $pid;
}

function GlobalDeinit(){
	global $ProcLock;
	@sem_remove($ProcLock);
	return TRUE;
}

function GlobalExceptionHandler()
{

    if($e = error_get_last()) {
	    throw new Exception($e['message']);
	}
	/*
    //$e['type']对应php_error常量
    $message = '';
    $message .= "出错信息:\t".$e['message']."\n\n";
    $message .= "出错文件:\t".$e['file']."\n\n";
    $message .= "出错行数:\t".$e['line']."\n\n";
    $message .= "\t\t请工程师检查出现程序".$e['file']."出现错误的原因\n";
    $message .= "\t\t希望能引起你对程序开发过程当中出现错误原因的重视\n";
    $message .= "\t\t希望能您早点解决故障出现的原因\n";
	*/
}



