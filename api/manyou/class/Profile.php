<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Profile.php 2 2011-09-07 06:49:01Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Profile {

	function setMYML($uId, $appId, $markup, $actionMarkup) {
		$fields = array(
			'myml' => $markup,
			'profileLink' => $actionMarkup
		);
		$where = array(
			'uid' => $uId,
			'appid' => $appId
		);
		updatetable('userapp', $fields, $where);
		$result = $GLOBALS['db']->affected_rows();
		return new APIResponse($result);
	}

	function setActionLink($uId, $appId, $actionMarkup) {
		$fields = array(
			'profilelink' => $actionMarkup
		);
		$where = array(
			'uid' => $uId,
			'appid'	=> $appId
		);
		updatetable('userapp', $fields, $where);
		$result = $GLOBALS['db']->affected_rows();
		return new APIResponse($result);
	}

}

?>