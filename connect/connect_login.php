<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect_login.php 139 2011-09-22 06:22:18Z liuwenxue $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/connect.func.php';

$referer = dreferer();
preg_match('/^(http|https|ftp|javascript):/i', $referer, $matches);
if($matches) {
    $referer = 'index.php';
}

$op = $op ? trim($op, '/') : '';
if(!in_array($op, array('init', 'callback', 'change'))) {
	showmessage('undefined_action');
}

// debug ��ȡδ��Ȩ��request token
if($op == 'init') {

	dsetcookie('con_request_token');
	dsetcookie('con_request_token_secret');

	// debug �����û�δ��Ȩ��tmp token
	$response = connect_get_request_token();
	if(empty($response) || isset($response['error_code'])) {
		showmessage('qqconnect:connect_get_request_token_failed', $referer);
	}

	$request_token = $response['oauth_token'];
	$request_token_secret = $response['oauth_token_secret'];

	// debug ��δ��Ȩ��oauth_token��oauth_token_secret�ǵ�cookie��
	dsetcookie('con_request_token', $request_token);
	dsetcookie('con_request_token_secret', $request_token_secret);

	// debug ���ظ�ConnectӦ����Ȩҳ��Ĳ���
	$params = array(
		'oauth_token' => $request_token,
		'oauth_consumer_key' => $connectappid,
		'oauth_callback' => $connect_setting['callback_url'],
	);

	dsetcookie('connect_referer', $referer);

	// debug �����û���Ӧ����Ȩҳ
	$redirect = $connect_setting['api_url'] . '/oauth/qzoneoauth_authorize?'.connect_http_build_query($params, '', '&');
	dheader('Location:' . $redirect);

// debug Callback����
} elseif($op == 'callback') {

	$params = $_GET;

	// debug ��֤Connect���ص�Sig
	if(!connect_is_valid_openid($params['openid'], $params['timestamp'], $params['oauth_signature'])) {
		showmessage('qqconnect:connect_invalid_params', $referer);
	}

	// debug ����̳��ҳ���ض���connect���е�¼��Ȩǰ��connect���ص���̳��callbackҳ��IE9���˼�������ͼ������callback���⵼��֮ǰ��cookie�޷�ȡ����
	// debug �������������������һ�±�ҳ��ʹ֮�ص�������ͼ��Ŀ���ǽ��IE9������ͼ�ò���cookie
	if(!isset($_GET['receive'])) {
		echo '<script type="text/javascript">setTimeout("window.location.href=\'connect.php?receive=yes&'.str_replace("'", "\'", connect_http_build_query($_GET, '', '&')).'\'", 1)</script>';
		exit;
	}

	// debug �û���Ȩ���tmp token
	$request_token = $params['oauth_token'];
	$verify_code = $params['oauth_vericode'];

	if($request_token && $verify_code) {
		$response = connect_get_access_token($request_token, $verify_code);

		if(empty($response) || isset($response['error_code'])) {
			showmessage('qqconnect:connect_get_access_token_failed', $referer);
		}

		$conuin = $response['oauth_token'];
		$conuinsecret = $response['oauth_token_secret'];
		$conopenid = $response['openid'];
		if(!$conuin || !$conuinsecret || !$conopenid) {
			showmessage('qqconnect:connect_get_access_token_failed', $referer);
		}
	} else {
		showmessage('qqconnect:connect_get_request_token_failed', $referer);
	}

	// debug ������
	$query = $db->query("SELECT uin FROM {$tablepre}uin_black");
	while ($result = $db->fetch_array($query)) {
		$connect_blacklist[] = $result['uin'];
	}
	if(in_array($conuin, $connect_blacklist)) {
		$params = array(
			'oauth_token' => $request_token,
			'oauth_consumer_key' => $connectappid,
		);
		$change_qq_url = $connect_setting['change_qq_url'];
		showmessage('qqconnect:connect_uin_in_blacklist', $referer, array('changeqqurl' => $change_qq_url));
	}

	// debug ��½�ɹ��󷵻صĵ�ַ
	$referer = $_DCOOKIE['connect_referer'];
	$referer = $referer && (strpos($referer, 'logging') === false) && (strpos($referer, 'login') === false) ? $referer : 'index.php';

	// debug ��Cookies
	$cookie_expires = 2592000;
	dsetcookie('client_created', time(), $cookie_expires);
	dsetcookie('client_token', $conopenid, $cookie_expires);

	$connect_member = array();
	// debug ��ȡ��QC���û��󶨹�ϵ
	$connect_member = $db->fetch_first("SELECT uid, conuin, conuinsecret, conopenid FROM {$tablepre}member_connect WHERE conopenid='$conopenid'");
	if($connect_member) {
		$member = $db->fetch_first("SELECT uid, conisbind FROM {$tablepre}members WHERE uid='$connect_member[uid]'");
		if($member) {
			if(!$member['conisbind']) {
				unset($connect_member);
			} else {
				$connect_member['conisbind'] = $member['conisbind'];
			}
		} else {
			$db->query("DELETE FROM {$tablepre}member_connect WHERE uid='$connect_member[uid]'");
			unset($connect_member);
		}
	}

	// debug ������̳�˺ŵ�¼��Ȼ���ٵ����QQ
	if($discuz_uid) {

		// debug ��ǰʹ�õ�QQ���Ѿ�����һ����̳�˺ţ��������̳�˺Ų��ǵ�ǰ��¼��̳���˺�
		if($connect_member && $connect_member['uid'] != $discuz_uid) {
			showmessage('qqconnect:connect_register_bind_uin_already', $referer, array('username' => $discuz_user));
		}

		$current_connect_member = $db->fetch_first("SELECT * FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");
		if($current_connect_member) {
			// debug ��ǰ��̳��¼���Ѿ�������һ��QQ���ˣ��޷��ٰ󶨵�ǰ���QQ��
			if($current_connect_member['conopenid'] && $current_connect_member['conopenid'] != $conopenid) {
				showmessage('qqconnect:connect_register_bind_already', $referer);
			}

			$db->query("UPDATE {$tablepre}member_connect SET conuin='$conuin', conuinsecret='$conuinsecret', conopenid='$conopenid', conisregister='0' WHERE uid='$discuz_uid'");

		} else { // debug ��ǰ��¼����̳�˺Ų�û�а��κ�QQ�ţ�����԰󶨵�ǰ�����QQ��
			$db->query("INSERT INTO {$tablepre}member_connect (uid, conuin, conuinsecret, conopenid, conisregister) VALUES ('$discuz_uid', '$conuin', '$conuinsecret', '$conopenid', '0')");
		}

		$db->query("UPDATE {$tablepre}members SET conisbind='1' WHERE uid='$discuz_uid'");

		// debug �û���֪ͨConnect
		dsetcookie('connect_js_name', 'user_bind', 86400);
		dsetcookie('connect_js_params', base64_encode(serialize(array('type' => 'loginbind'))), 86400);
		dsetcookie('connect_login', 1, 31536000);
		dsetcookie('connect_is_bind', '1', 31536000);
		dsetcookie('connect_uin', $conopenid, 31536000);
		dsetcookie('stats_qc_reg', 3, 86400, 0, false);
		$inshowmessage = 1;

		// debug ��¼QC�û���
		$timestamp = time();
		$db->query("INSERT INTO {$tablepre}connect_memberbindlog (uid, uin, type, dateline) VALUES ('$discuz_udi', '$conopenid', '1', '$timestamp')");

		showmessage('qqconnect:connect_register_bind_success', $referer);

	// debug δ��¼�û�
	} else {

		if($connect_member) { // debug �˷�֧���û�ֱ�ӵ��QQ��¼���������QQ���Ѿ����һ����̳�˺��ˣ���ֱ�ӵǽ���̳��

			$db->query("UPDATE {$tablepre}member_connect SET conuin='$conuin', conuinsecret='$conuinsecret' WHERE uid='$connect_member[uid]'");

			$params['mod'] = 'login';
			connect_login($connect_member);

			include_once DISCUZ_ROOT . './forumdata/cache/cache_usergroups.php';
			$usergroups = $_DCACHE['usergroups'][$groupid]['grouptitle'];
			dsetcookie('stats_qc_login', 3, 86400, 0, false);
			showmessage('login_succeed', $referer);

		} else { // debug �˷�֧���û�ֱ�ӵ��QQ��¼���������QQ�Ż�δ���κ���̳�˺ţ�������ת��һ����ҳ�����û�ע�������̳�˺Ż��һ�����е���̳�˺�

			// debug Ϊ�����������access token
			// debug ��access token���ܺ󣬴���ע�����
			$encode[] = authcode($conuin, 'ENCODE');
			$encode[] = authcode($conuinsecret, 'ENCODE');
			$encode[] = authcode($conopenid, 'ENCODE');
			$auth_hash = authcode(implode('|', $encode), 'ENCODE');
			// debug ���ܴ���Cookie
			dsetcookie('con_auth_hash', $auth_hash);

			unset($params['op']);
			$params['mod'] = 'register';
			$params['referer'] = $referer;

			unset($params['oauth_token']);
			unset($params['oauth_verifycode']);

			$redirect = 'connect.php?'.connect_http_build_query($params, '', '&');
			header("Location: $redirect");
			exit;
		}
	}

// debug ����QQ�˺��ص�¼
} elseif($op == 'change') {

	dsetcookie('con_request_token');
	dsetcookie('con_request_token_secret');

	// debug �����û�δ��Ȩ��tmp token
	$response = connect_get_request_token();
	if(empty($response) || isset($response['error_code'])) {
		showmessage('qqconnect:connect_get_request_token_failed', $referer);
	}

	$request_token = $response['oauth_token'];
	$request_token_secret = $response['oauth_token_secret'];

	dsetcookie('con_request_token', $request_token);
	dsetcookie('con_request_token_secret', $request_token_secret);

	$params = array(
		'oauth_token' => $request_token,
		'oauth_consumer_key' => $connectappid,
		'oauth_callback' => $connect_setting['callback_url'],
	);

	$redirect = $connect_setting['api_url'] . '/oauth/qzoneoauth_authorize?'.connect_http_build_query($params, '', '&');
	header('Location:' . $redirect);
	exit;
}

function connect_login($connect_member) {

	global $db, $tablepre, $_DCACHE, $_DCOOKIE;

	$member = $db->fetch_first("SELECT m.uid AS discuz_uid, m.username AS discuz_user, m.password AS discuz_pw,
							   m.secques AS discuz_secques,
							   m.email, m.adminid, m.groupid, m.styleid, m.lastvisit, m.lastpost,
							   m.conisbind,
							   u.allowinvisible
							   FROM {$tablepre}members m LEFT JOIN {$tablepre}usergroups u USING (groupid)
							   WHERE m.uid='$connect_member[uid]'");
	if (!$member) {
		return false;
	}

	$member['discuz_userss'] = $member['discuz_user'];
	$member['discuz_user'] = addslashes($member['discuz_user']);
	foreach ($member as $var => $value) {
		$GLOBALS[$var] = $value;
	}

	$GLOBALS['styleid'] = $member['styleid'] ? $member['styleid'] : $_DCACHE['settings']['styleid'];

	$cookietime = 1296000;
	dsetcookie('connect_login', 1, $cookietime);
	dsetcookie('connect_is_bind', '1', 31536000);
	dsetcookie('connect_uin', $connect_member['conopenid'], 31536000);

	dsetcookie('cookietime', $cookietime, 31536000);
	dsetcookie('auth', authcode("$member[discuz_pw]\t$member[discuz_secques]\t$member[discuz_uid]", 'ENCODE'), $cookietime, 1, true);
	dsetcookie('loginuser');
	dsetcookie('activationauth');
	dsetcookie('pmnum');

	$GLOBALS['sessionexists'] = 0;

	return true;
}
?>
