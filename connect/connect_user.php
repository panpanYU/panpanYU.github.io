<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect_user.php 52 2011-09-14 09:39:14Z yexinhao $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('NOROBOT', TRUE);

include_once DISCUZ_ROOT.'./uc_client/client.php';
require_once DISCUZ_ROOT.'./include/connect.func.php';

$op = !empty($_GET['op']) ? trim($_GET['op'], '/') : '';
if(!in_array($op, array('get'))) {
	showmessage('undefined_action');
}

if ($op == 'get') {
	$auth_code = authcode($_DCOOKIE['con_auth_hash']);
	$auth_code = explode('|', authcode($_DCOOKIE['con_auth_hash']));
	$conuin = authcode($auth_code[0]);
	$conuinsecret = authcode($auth_code[1]);
	$conopenid = authcode($auth_code[2]);

	if ($conuin && $conuinsecret && $conopenid) {

		$connect_user_info = connect_get_user_info($conopenid, $conuin, $conuinsecret);
		if ($connect_user_info['nickname']) {
			$qq_nick = $connect_user_info['nickname'];
			$connect_nickname = connect_filter_username($qq_nick);
		}

		$ucresult = uc_user_checkname($connect_nickname);
		$first_available_username = '';
		if($ucresult >= 0) {
			$first_available_username = $connect_nickname;
		}
		echo "<span>".$qq_nick."\n".$first_available_username."</span>";
	}
}

?>
