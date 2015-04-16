<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: APIResponse.php 2 2011-09-07 06:49:01Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class APIResponse {

	var $result;

	var $mode;

	function APIResponse($res, $mode = null) {
		$this->result = $res;
		$this->mode = $mode;
	}

	function getResult() {
		return $this->result;
	}

	function getMode() {
		return $this->mode;
	}

}

?>