<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: APIErrorResponse.php 2 2011-09-07 06:49:01Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//note 服务器返回结果对象
class APIErrorResponse {
	var $errCode = 0;
	
	var $errMessage = '';

	function APIErrorResponse($errCode, $errMessage) {
		$this->errCode = $errCode;
		$this->errMessage = $errMessage;
	}

	function getErrCode() {
		return $this->errCode;
	}

	function getErrMessage() {
		return $this->errMessage;
	}

	function getResult() {
		return null;
	}

}

?>