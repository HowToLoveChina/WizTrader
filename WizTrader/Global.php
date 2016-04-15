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
$ProcNum = 16 ; 			#Ӳ��֧�ֵ��߳���*2

$ProcLock = sem_get ( ftok("/","/") , $ProcNum , 0666 );

#�����в�����ob_start
#ob_start("GlobalDeinit");

function proc_wait(){
	global $Pids,$ProcLock;
	//! ��������
	while( sizeof($Pids) != 0 ){
		$flag = false;
		//! ������û���˳���
		foreach($Pids as $idx => $pid){
			$status = 0 ;
			$r = pcntl_waitpid($pid,$status,WNOHANG);
			if( $r != 0 ){
				//! ��һ���˳��ˣ��Ϳ�����������
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
	//! �����ﱾ��ȫ���˳���
}	

function proc_fork($subfunc,$args){
	global $Pids,$ProcLock;
	//! ��������
	while( ! sem_acquire ( $ProcLock , true) ){
		sleep(1);
		if( sizeof($self->pids) == 0 ){
			//! ����û����Ҫ�ȴ��ģ���ô�͸ɵ�
			continue;
		}
		//! ������û���˳���
		foreach($Pids as $idx => $pid){
			$status = 0 ;
			$r = pcntl_waitpid($pid,$status,WNOHANG);
			if( $r != 0 ){
				//! ��һ���˳��ˣ��Ϳ�����������
				@sem_release($ProcLock);
				unset($Pids[$idx]);
				break;
			}
		}
	}
	//!����������һ���ź���
	$pid = pcntl_fork ();
	if( $pid == 0 ){
		//! �½�����ս����б�
		$Pids = array();
		$subfunc($args);
		GlobalDeinit();
		exit(0);
	}else{
		//! ԭ���̼����µ� PID
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
    //$e['type']��Ӧphp_error����
    $message = '';
    $message .= "������Ϣ:\t".$e['message']."\n\n";
    $message .= "�����ļ�:\t".$e['file']."\n\n";
    $message .= "��������:\t".$e['line']."\n\n";
    $message .= "\t\t�빤��ʦ�����ֳ���".$e['file']."���ִ����ԭ��\n";
    $message .= "\t\tϣ����������Գ��򿪷����̵��г��ִ���ԭ�������\n";
    $message .= "\t\tϣ��������������ϳ��ֵ�ԭ��\n";
	*/
}



