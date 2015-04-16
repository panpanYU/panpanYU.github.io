<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect_logging.php 139 2011-09-22 06:22:18Z liuwenxue $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('NOROBOT', TRUE);
define('CURSCRIPT', 'logging');

require_once './include/common.inc.php';
require_once DISCUZ_ROOT.'./include/misc.func.php';
require_once DISCUZ_ROOT.'./include/login.func.php';
require_once DISCUZ_ROOT.'./uc_client/client.php';

$auth_code = authcode($auth_hash);
$auth_code = explode('|', authcode($auth_hash));
$conuin = authcode($auth_code[0]);
$conuinsecret = authcode($auth_code[1]);
$conopenid = authcode($auth_code[2]);
$user_auth_fields = authcode($auth_code[3]);

if (!$conuin || !$conuinsecret || !$conopenid) {
	showmessage('qqconnect:connect_invalid_params', dreferer());
}

if($action == 'seccode') {

	$seccodecheck = 1;
	include template('header_ajax');
	include template('seccheck');
	include template('footer_ajax');

} elseif($action == 'login') {

	if($discuz_uid) {
		$ucsynlogin = '';
		showmessage('login_succeed', $indexname);
	}

	$field = $loginfield == 'uid' ? 'uid' : 'username';

	if(!($loginperm = logincheck())) {
		showmessage('login_strike');
	}

	// ºöÂÔÑéÖ¤Âë
	$seccodecheck = 0;
	$seccodescript = '';

	if($seccodecheck && $seccodedata['loginfailedcount']) {
		$seccodecheck = $db->result_first("SELECT count(*) FROM {$tablepre}failedlogins WHERE ip='$onlineip' AND count>='$seccodedata[loginfailedcount]' AND $timestamp-lastupdate<=900");
		$seccodescript = '<script type="text/javascript" reload="1">if($(\'seccodelayer\').innerHTML == \'\') ajaxget(\'logging.php?action=seccode\', \'seccodelayer\');</script>';
	}

	if(!submitcheck('loginsubmit', 1, $seccodecheck)) {

		$discuz_action = 6;

		$referer = dreferer();

		$thetimenow = '(GMT '.($timeoffset > 0 ? '+' : '').$timeoffset.') '.
			dgmdate("$dateformat $timeformat", $timestamp + $timeoffset * 3600).

		$styleselect = '';
		$query = $db->query("SELECT styleid, name FROM {$tablepre}styles WHERE available='1'");
		while($styleinfo = $db->fetch_array($query)) {
			$styleselect .= "<option value=\"$styleinfo[styleid]\">$styleinfo[name]</option>\n";
		}

		$cookietimecheck = !empty($_DCOOKIE['cookietime']) ? 'checked="checked"' : '';

		if($seccodecheck) {
			$seccode = random(6, 1) + $seccode{0} * 1000000;
		}

		$username = !empty($_DCOOKIE['loginuser']) ? htmlspecialchars($_DCOOKIE['loginuser']) : '';
		include template('qqconnect:login');

	} else {

		$discuz_uid = 0;
		$discuz_user = $discuz_pw = $discuz_secques = '';
		$result = userlogin();

		if($result > 0) {
			$ucsynlogin = $allowsynlogin ? uc_user_synlogin($discuz_uid) : '';
			$db->query("UPDATE {$tablepre}members SET conisbind='1' WHERE uid='$discuz_uid'");

			$connect_member = $db->fetch_first("SELECT uid FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");
			if($connect_member) {
				$db->query("UPDATE {$tablepre}member_connect SET conuin='$conuin', conuinsecret='$conuinsecret', conopenid='$conopenid', conisregister='0' WHERE uid='$connect_member[uid]'");
			} else {
				$db->query("INSERT INTO {$tablepre}member_connect (uid, conuin, conuinsecret, conopenid, conisregister) VALUES ('$discuz_uid', '$conuin', '$conuinsecret', '$conopenid', '0')");
			}

			$db->query("INSERT INTO {$tablepre}connect_memberbindlog (uid, uin, type, dateline) VALUES ('$discuz_uid', '$conopenid', '1', '$timestamp')");

			dsetcookie('connect_js_name', 'user_bind', 86400);
			dsetcookie('connect_js_params', base64_encode(serialize(array('type' => 'registerbind'))), 86400);
			dsetcookie('connect_is_bind', '1', 31536000);
			dsetcookie('connect_uin', $conopenid, 31536000);
			dsetcookie('connect_login', 1, 31536000);
			dsetcookie('stats_qc_reg', 2, 86400, 0, false);
			$inshowmessage = 1;

			if(!empty($inajax)) {
				$msgforward = unserialize($msgforward);
				$mrefreshtime = intval($msgforward['refreshtime']) * 1000;
				include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';
				$usergroups = $_DCACHE['usergroups'][$groupid]['grouptitle'];
				$message = 1;
				include template('qqconnect:login');
			} else {
				if($groupid == 8) {
					showmessage('login_succeed_inactive_member', 'memcp.php');
				} else {
					showmessage('login_succeed', dreferer());
				}
			}
		} elseif($result == -1) {
			$ucresult['username'] = addslashes($ucresult['username']);
			$auth = authcode("$ucresult[username]\t".FORMHASH, 'ENCODE');
			if($inajax) {
				$message = 2;
				$location = $regname.'?action=activation&auth='.rawurlencode($auth);
				include template('login');
			} else {
				showmessage('login_activation', $regname.'?action=activation&auth='.rawurlencode($auth));
			}
		} else {
			$password = preg_replace("/^(.{".round(strlen($password) / 4)."})(.+?)(.{".round(strlen($password) / 6)."})$/s", "\\1***\\3", $password);
			$errorlog = dhtmlspecialchars(
				$timestamp."\t".
				($ucresult['username'] ? $ucresult['username'] : stripslashes($username))."\t".
				$password."\t".
				($secques ? "Ques #".intval($questionid) : '')."\t".
				$onlineip);
			writelog('illegallog', $errorlog);
			loginfailed($loginperm);
			$fmsg = $ucresult['uid'] == '-3' ? (empty($questionid) || $answer == '' ? 'login_question_empty' : 'login_question_invalid') : 'login_invalid';
			showmessage($fmsg, 'logging.php?action=login');
		}

	}

} else {
	showmessage('undefined_action');
}

?>
