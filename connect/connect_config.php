<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect_config.php 52 2011-09-14 09:39:14Z yexinhao $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if(!$discuz_uid) {
	showmessage('not_loggedin', '', array(), array('showmsg' => true, 'login' => 1));
}

require_once DISCUZ_ROOT.'./include/connect.func.php';

$op = !empty($op) ? $op : '';
$referer = dreferer();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if($op == 'unbind') {

		$connect_member = $db->fetch_first("SELECT * FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");

		if($connect_member['conisregister']) {
			if($newpassword1 !== $newpassword2) {
				showmessage('profile_passwd_notmatch', $referer);
			}
			if(!$newpassword1 || $newpassword1 != addslashes($newpassword1)) {
				showmessage('profile_passwd_illegal', $referer);
			}
		}

		// debug 解绑不理会接口的返回状态
		$response = connect_user_unbind();

		$db->query("UPDATE {$tablepre}member_connect SET conuin='', conuinsecret='', conopenid='', conispublishfeed='0', conispublisht='0', conisregister='0', conisqzoneavatar='0', conisfeed='0' WHERE uid='$discuz_uid'");
		$db->query("UPDATE {$tablepre}members SET conisbind='0' WHERE uid='$discuz_uid'");
		$db->query("INSERT INTO {$tablepre}connect_memberbindlog (uid, uin, type, dateline) VALUES ('$discuz_uid', '{$connect_member[conopenid]}', '2', '$timestamp')");

		// debug 修改用户密码需要由ucenter来处理
		if($connect_member['conisregister']) {
			require_once DISCUZ_ROOT.'./uc_client/client.php';
			uc_user_edit($discuz_user, null, $newpassword1, null, 1);
		}

		foreach($_DCOOKIE as $k => $v) {
			dsetcookie($k);
		}

		$discuz_uid = $adminid = 0;

		showmessage('qqconnect:connect_config_unbind_success', 'logging.php?action=login');

	}elseif ($op == 'passwd') {

		$connect_member = $db->fetch_first("SELECT * FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");

		if($connect_member['conisregister']) {
			if($newpassword1 !== $newpassword2) {
				showmessage('profile_passwd_notmatch', $referer);
			}
			if(!$newpassword1 || $newpassword1 != addslashes($newpassword1)) {
				showmessage('profile_passwd_illegal', $referer);
			}

			if($connect_member['conisregister']) {
				require_once DISCUZ_ROOT.'./uc_client/client.php';
				uc_user_edit($discuz_user, null, $newpassword1, null, 1);
			}

			$db->query("UPDATE {$tablepre}member_connect SET conisregister='0' WHERE uid='$discuz_uid'");

			showmessage('qqconnect:connect_setting_save_succeed', 'plugin.php?id=qqconnect:spacecp');
		}else {
			dheader('location: plugin.php?id=qqconnect:spacecp');
		}
	}

} else {
	dheader('location: plugin.php?id=qqconnect:spacecp');
}
?>
