<?php
//! 强类型检查
#declare(strict_type=1);

/*
	券商接口
*/
interface iBroker{
	#登陆
	function hook_login();

	#退出
	function hook_logout():bool; 

	#买入
	function hook_buy():bool;

	#卖出
	function hook_sell():bool;

	#查持仓
	function hook_hold():bool;

	#撤单
	function hook_cancel():bool;
};
class cBroker{
	function __construct(){
		
	}
	function Login($username,$password){
		return $this->hook_login($username,$password);
	}
	function Logout(){
		return $this->hook_logout($username,$password);
	}
	function Buy($stock,$price,$amount){
		return $this->hook_buy($stock,$price,$amount);
	}
	function Sell($stock,$price,$amount){
		return $this->hook_login($stock,$price,$amount);
	}
	function Hold(){
		return $this->hook_login();
	}
	function Cancel($stock,$price){
		return $this->hook_login($stock,$price);
	}
}