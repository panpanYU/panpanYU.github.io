<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: spacecp.inc.php 52 2011-09-14 09:39:14Z yexinhao $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, array(), array('login' => 1));
}

require_once DISCUZ_ROOT.'./include/connect.func.php';

$pluginop = !empty($_GET['pluginop']) ? $_GET['pluginop'] : 'config';
if (!in_array($pluginop, array('config'))) {
	showmessage('undefined_action');
}
// debug QCÅäÖÃ
if ($pluginop == 'config') {

	$connect_member = $db->fetch_first("SELECT * FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");

	$referer = str_replace($boardurl, '', dreferer());

	include template('qqconnect:spacecp');
}

?>
