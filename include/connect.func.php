<?php

require_once DISCUZ_ROOT.'include/cloud.func.php';

function connect_output_javascript($jsurl) {
	return '<script type="text/javascript">function con_handle_response(str){} _attachEvent(window, \'load\', function () { appendscript(\''.$jsurl.'\', \'\', 1, \'utf-8\') }, document);</script>';
}

function connect_output_php($url, $postData = '', $ip = '') {

	$response = dfopen($url, 0, $postData, '', false, $ip);
	return $response;
}

// 用户绑定通知
function connect_user_bind_js($params) {
	global $connect_setting, $_DCOOKIE;

	$jsname = $_DCOOKIE['connect_js_name'];
	if($jsname != 'user_bind') {
		return false;
	}

	$jsparams = unserialize(base64_decode($_DCOOKIE['connect_js_params']));
	$jsurl = $connect_setting['url'].'/notify/user/bind';

	if($jsparams) {
		$params = array_merge($params, $jsparams);
	}

	$func = 'connect_'.$jsname.'_params';
	$other_params = $func ();
	$params = array_merge($other_params, $params);
	$params['sig'] = connect_get_sig($params, connect_get_sig_key());

	$jsurl .= '?'.connect_http_build_query($params, '', '&');

	dsetcookie('connect_js_name');
	dsetcookie('connect_js_params');
	return connect_output_javascript($jsurl);
}

// 用户解绑通知
function connect_user_unbind() {
	global $connect_setting, $connectappid, $clientip, $db, $tablepre, $discuz_uid, $cloud_api_ip;

	$api_url = $connect_setting['connect_api_url'].'/connect/user/unbind';
	$connect_member = $db->fetch_first("SELECT conopenid FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");

	$params = array (
		'oauth_consumer_key' => $connectappid,
		'client_ip' => $clientip,
		'response_type' => 'php',
		'openid' => $connect_member['conopenid'],
		'source' => 'qzone',
	);

	$params['sig'] = connect_get_sig($params, connect_get_sig_key());

	$response = connect_output_php($api_url.'?', connect_http_build_query($params, '', '&'), $cloud_api_ip);
	$result = (array) unserialize($response);
	return $result;
}

// 用户绑定通知需要提交的参数
function connect_user_bind_params() {
	global $_DCOOKIE, $_DSESSION, $discuz_uid, $connectappid, $connectappkey, $tablepre, $db, $timestamp;

	$user = $db->fetch_first("SELECT m.bday, m.gender, m.showemail, mc.conisqzoneavatar FROM {$tablepre}members m, {$tablepre}member_connect mc WHERE m.uid = $discuz_uid AND mc.uid = $discuz_uid");

	switch ($user['gender']) {
		case 1:
			$sex = 'male';
			break;
		case 2:
			$sex = 'female';
			break;
		default:
			$sex = 'unknown';
	}

	$is_public_email = $user['showemail'] == 1 ? 1 : 2;
	$is_use_qq_avatar = $user['conisqzoneavatar'] == 1 ? 1 : 2;
	$birthday = $user['bday'] == '0000-00-00' ? '' : $user['bday'];

	$agent = md5(time() . rand() . uniqid());
	$inputArray = array(
						'uid' => $discuz_uid,
						'agent' => $agent,
						'time' => $timestamp
					   );
	$input = "uid=$discuz_uid&agent=$agent&time=$timestamp";
	$avatar_input = authcode($input, 'ENCODE', UC_KEY);

	$params = array (
					 'oauth_consumer_key' => $connectappid,
					 'u_id' => $discuz_uid,
					 'username' => $_DSESSION['discuz_user'],
					 'email' =>  $_DSESSION['email'],
					 'birthday' => $birthday,
					 'sex' => $sex,
					 'is_public_email' => $is_public_email,
					 'is_use_qq_avatar' => $is_use_qq_avatar,
					 's_id' => null,
					 'avatar_input' => $avatar_input,
					 'avatar_agent' => $agent,
					 'site_ucenter_id' => UC_APPID,
					 'source' => 'qzone',
					);

	return $params;
}

/**
 * 验证Connect返回的sig
 */
function connect_check_sig($params) {
	global $connect_setting, $connectappkey;

	if(!$params) {
		return false;
	}

	$valid_params = array();
	foreach($params as $key => $value) {
		if(substr($key, 0, 4) == 'con_') {
			$valid_params[$key] = $value;
		}
	}
	$sig = $valid_params['con_sig'];
	unset($valid_params['con_sig']);
	ksort($valid_params);
	$str = '';
	foreach($valid_params as $k => $v) {
		if($v) {
			$str .= $k.'='.$v.'&';
		}
	}

	return $sig === md5($str.$connectappkey);
}

/**
 * 获取生产站点签名的密钥
 * @return string 签名加密密钥 = appid + | + appkey（非原来的站点key）
 */
function connect_get_sig_key() {
	global $connectappid, $connectappkey;

	return $connectappid . '|' . $connectappkey;
}

/**
 * 获取 Connect 参数签名
 * @param array $params 参数数组
 * @param string $app_key 加密key
 * @return string
 */
function connect_get_sig($params, $app_key) {
	ksort($params);
	$base_string = '';
	foreach($params as $key => $value) {
		$base_string .= $key.'='.$value;
	}
	$base_string .= $app_key;
	return md5($base_string);
}

// 获取用户tmp token
function connect_get_request_token() {
	global $connect_setting, $connect_api_ip;

	$api_url = $connect_setting['api_url'].'/oauth/qzoneoauth_request_token';

	$extra = array();
	$sig_params = connect_get_oauth_signature_params($extra);
	$sig_params['oauth_signature'] = connect_get_oauth_signature($api_url, $sig_params, 'GET');

	$response = connect_output_php($api_url.'?'.connect_http_build_query($sig_params, '', '&'), '', $connect_api_ip);
	parse_str($response, $params);
	return $params;
}

// 获取用户access token
function connect_get_access_token($request_token, $verify_code) {
	global $connect_setting, $_DCOOKIE, $connect_api_ip;

	$api_url = $connect_setting['api_url'].'/oauth/qzoneoauth_access_token';

	$extra = array();
	$extra['oauth_token'] = $request_token;
	$extra['oauth_vericode'] = $verify_code;
	$sig_params = connect_get_oauth_signature_params($extra);
	$oauth_token_secret = $_DCOOKIE['con_request_token_secret'];
	$sig_params['oauth_signature'] = connect_get_oauth_signature($api_url, $sig_params, 'GET', $oauth_token_secret);

	dsetcookie('con_request_token');
	dsetcookie('con_request_token_secret');

	$response = connect_output_php($api_url.'?'.connect_http_build_query($sig_params, '', '&'), '', $connect_api_ip);
	parse_str($response, $params);
	return $params;
}

// 生成OAuth签名
function connect_get_oauth_signature($url, $params, $method = 'POST', $oauth_token_secret = '') {

	global $connectappkey;

	// http method
	$method = strtoupper($method);
	if(!in_array($method, array ('GET', 'POST'))) {
		return FALSE;
	}

	// 请求URL
	$url = rawurlencode($url);

	// OAuth请求参数
	$param_str = rawurlencode(connect_http_build_query($params, '', '&'));

	// Signature Base String
	$base_string = $method.'&'.$url.'&'.$param_str;

	// 密钥
	$key = $connectappkey.'&'.$oauth_token_secret;

	$signature = connect_custom_hmac($base_string, $key);

	return $signature;
}

// OAuth请求参数
function connect_get_oauth_signature_params($extra = array ()) {
	global $connectappid;

	$params = array (
		'oauth_version' => '1.0',
		'oauth_consumer_key' => $connectappid,
		'oauth_nonce' => connect_get_nonce(),
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_timestamp' => time(),
	);
	if($extra) {
		$params = array_merge($params, $extra);
	}
	ksort($params);

	return $params;
}

// 自定义的HMAC_SHA1函数，针对PHP版本低于5.1.2的环境
function connect_custom_hmac($str, $key) {
	$signature = "";
	if(function_exists('hash_hmac')) {
		$signature = base64_encode(hash_hmac("sha1", $str, $key, true));
	} else {
		$blocksize = 64;
		$hashfunc = 'sha1';
		if(strlen($key) > $blocksize) {
			$key = pack('H*', $hashfunc($key));
		}
		$key = str_pad($key,$blocksize,chr(0x00));
		$ipad = str_repeat(chr(0x36),$blocksize);
		$opad = str_repeat(chr(0x5c),$blocksize);
		$hmac = pack(
					   'H*',$hashfunc(
									  ($key^$opad).pack(
														'H*',$hashfunc(
																	   ($key^$ipad).$str
																	  )
													   )
									 )
					  );
		$signature = base64_encode($hmac);
	}

	return $signature;
}

// 生成随机数
function connect_get_nonce() {
	return time();
}

// js输出信息
function connect_js_ouput_message($msg = '', $errMsg = '', $errCode = '') {
	$result = array (
		'result' => $msg,
		'errMessage' => $errMsg,
		'errCode' => $errCode
	);
	echo sprintf('con_handle_response(%s);', json_encode(connect_urlencode($result)));
	exit;
}

function connect_urlencode($value) {

	if (is_array($value)) {
		foreach ($value as $k => $v) {
			$value[$k] = connect_urlencode($v);
		}
	} else if (is_string($value)) {
		$value = urlencode(str_replace(array("\r\n", "\r", "\n", "\"", "\/", "\t"), array('\\n', '\\n', '\\n', '\\"', '\\/', '\\t'), $value));
	}

	return $value;
}

function connect_merge_member() {
	global $db, $discuz_uid, $tablepre;

	$connect_member = $db->fetch_first("SELECT * FROM {$tablepre}member_connect WHERE uid='$discuz_uid'");
	return $connect_member;
}

function connect_auth_field($is_user_info, $is_feed) {
	if ($is_user_info && $is_feed) {
		return 1;
	} elseif (!$is_user_info && !$is_feed) {
		return 0;
	} elseif ($is_user_info && !$is_feed) {
		return 2;
	} elseif (!$is_user_info && $is_feed) {
		return 3;
	}
}

// note 处理帖子相关函数
define('X_BOARDURL', $discuzurl);

function connect_parse_bbcode($bbcode, $fId, $pId, $isHtml, &$attachImages) {
	include_once DISCUZ_ROOT.'include/discuzcode.func.php';

	$result = preg_replace('/\[hide(=\d+)?\].+?\[\/hide\](\r\n|\n|\r)/i', '', $bbcode);
	$result = preg_replace('/\[payto(=\d+)?\].+?\[\/payto\](\r\n|\n|\r)/i', '', $result);
	$result = discuzcode($result, 0, 0, $isHtml, 1, 2, 1, 0, 0, 0, 0, 1, 0);
	$result = preg_replace('/<img src="images\//i', "<img src=\"".$boardurl."images/", $result);
	$result = connect_parse_attach($result, $fId, $pId, $attachImages, $attachImageThumb);
	return $result;
}

function connect_parse_attach($content, $fId, $pId, &$attachImages) {
	global $db, $tablepre, $boardurl;

	$permissions = connect_get_user_group_permissions(array(7));
	$visitorPermission = $permissions[7];

	$attachIds = array();
	$attachImages = array ();
	$query = $db->query("SELECT aid, filename, isimage, readperm, price FROM {$tablepre}attachments WHERE pid='$pId'");
	while ($attach = $db->fetch_array($query)) {
		$aid = $attach['aid'];
		if($attach['isimage'] == 0 || $attach['price'] > 0 || $attach['readperm'] > $visitorPermission['readPermission'] || in_array($fId, $visitorPermission['forbidViewAttachForumIds']) || in_array($attach['aid'], $attachIds)) {
			continue;
		}

		$imageItem = $boardurl.'/attachment.php?aid='.aidencode($aid);
		$attachIds[] = $aid;
		$attachImages[] = $imageItem;
	}

	$content = preg_replace('/\[attach\](\d+)\[\/attach\]/ie', 'connect_parse_attach_tag(\\1, $attachNames)', $content);
	return $content;
}

function connect_parse_attach_tag($attachId, $attachNames) {
	global $boardurl;
	include_once DISCUZ_ROOT.'include/discuzcode.func.php';
	if(array_key_exists($attachId, $attachNames)) {
		return '<span class="attach"><a href="'.$boardurl.'/attachment.php?aid='.aidencode($attachId).'">'.$attachNames[$attachId].'</a></span>';
	}
	return '';
}

function connect_get_user_group_permissions($userGroupIds) {
	global $db, $tablepre;

	$fields = array (
		'groupid' => 'userGroupId',
		'grouptitle' => 'userGroupName',
		'readaccess' => 'readPermission',
		'allowvisit' => 'allowVisit'
	);
	$userGroups = array ();
	$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE groupid IN (".implode(',', $userGroupIds).")");
	while ($row = $db->fetch_array($query)) {
		foreach ($row as $k => $v) {
			if(array_key_exists($k, $fields)) {
				$userGroups[$row['groupid']][$fields[$k]] = $v;
			}
			$userGroups[$row['groupid']]['forbidForumIds'] = array ();
			$userGroups[$row['groupid']]['allowForumIds'] = array ();
			$userGroups[$row['groupid']]['specifyAllowForumIds'] = array ();
			$userGroups[$row['groupid']]['allowViewAttachForumIds'] = array ();
			$userGroups[$row['groupid']]['forbidViewAttachForumIds'] = array ();
		}
	}

	$query = $db->query("SELECT ff.* FROM {$tablepre}forums f
			INNER JOIN {$tablepre}forumfields ff USING(fid) WHERE f.status='1'");
	while ($row = $db->fetch_array($query)) {
		$allowViewGroupIds = array ();
		if($row['viewperm']) {
			$allowViewGroupIds = explode("\t", $row['viewperm']);
		}
		$allowViewAttachGroupIds = array ();
		if($row['getattachperm']) {
			$allowViewAttachGroupIds = explode("\t", $row['getattachperm']);
		}
		foreach ($userGroups as $gid => $_v) {
			if($row['password']) {
				$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
				continue;
			}
			$perm = unserialize($row['formulaperm']);
			if(is_array($perm)) {
				if($perm[0] || $perm[1] || $perm['users']) {
					$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
					continue;
				}
			}
			if(!$allowViewGroupIds) {
				$userGroups[$gid]['allowForumIds'][] = $row['fid'];
			}
			elseif(!in_array($gid, $allowViewGroupIds)) {
				$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
			}
			elseif(in_array($gid, $allowViewGroupIds)) {
				$userGroups[$gid]['allowForumIds'][] = $row['fid'];
				$userGroups[$gid]['specifyAllowForumIds'][] = $row['fid'];
			}
			if(!$allowViewAttachGroupIds) {
				$userGroups[$gid]['allowViewAttachForumIds'][] = $row['fid'];
			}
			elseif(!in_array($gid, $allowViewAttachGroupIds)) {
				$userGroups[$gid]['forbidViewAttachForumIds'][] = $row['fid'];
			}
			elseif(in_array($gid, $allowViewGroupIds)) {
				$userGroups[$gid]['allowViewAttachForumIds'][] = $row['fid'];
			}
		}
	}
	return $userGroups;
}

function connect_share_error($message, $type = 'alert') {
	echo "connect_share_loaded = 1;";
	echo "\n";
	echo "connect_show_dialog('', '$message', '$type');";
	exit;
}

function connect_http_build_query($data, $numeric_prefix='', $arg_separator='', $prefix='') {
	$render = array();
	if (empty($arg_separator)) {
		$arg_separator = @ini_get('arg_separator.output');
		empty($arg_separator) && $arg_separator = '&';
	}
	foreach ((array) $data as $key => $val) {
		if (is_array($val) || is_object($val)) {
			$_key = empty($prefix) ? "{$key}[%s]" : sprintf($prefix, $key) . "[%s]";
			$_render = connect_http_build_query($val, '', $arg_separator, $_key);
			if (!empty($_render)) {
				$render[] = $_render;
			}
		} else {
			if (is_numeric($key) && empty($prefix)) {
				$render[] = urlencode("{$numeric_prefix}{$key}") . "=" . urlencode($val);
			} else {
				if (!empty($prefix)) {
					$_key = sprintf($prefix, $key);
					$render[] = urlencode($_key) . "=" . urlencode($val);
				} else {
					$render[] = urlencode($key) . "=" . urlencode($val);
				}
			}
		}
	}
	$render = implode($arg_separator, $render);
	if (empty($render)) {
		$render = '';
	}
	return $render;
}

function connect_is_valid_openid($openid, $timestamp, $sig) {
	global $connectappkey;
	$key = $connectappkey;
	$str = $openid.$timestamp;
	$signature = connect_custom_hmac($str, $key);

	return $sig == $signature;
}

// 将URL传递的conenct参数放到对应的数组
function connect_params($params, & $connect_params) {

	if(!$params) {
		return false;
	}
	$connect_params = array ();
	foreach ($params as $key => $value) {
		if(substr($key, 0, 4) == 'con_') {
			$connect_params[substr($key, 4)] = $value;
		}
	}
}

function connect_get_user_info($openid, $access_token, $access_token_secret) {
	global $connect_setting, $charset, $connect_api_ip;

	$api_url = $connect_setting['api_url'].'/user/get_user_info';

	$extra = array();
	$extra['oauth_token'] = $access_token;
	$extra['openid'] = $openid;
	$extra['format'] = 'xml';

	$sig_params = connect_get_oauth_signature_params($extra);
	$sig_params['oauth_signature'] = connect_get_oauth_signature($api_url, $sig_params, 'GET', $access_token_secret);

	$response = connect_output_php($api_url.'?'.connect_http_build_query($sig_params, '', '&'), '', $connect_api_ip);

	$data = connect_parse_xml($response);

	if(strtoupper($charset) != 'UTF-8') {
		require_once DISCUZ_ROOT.'./include/chinese.class.php';
		$c = new Chinese('UTF-8', $charset, true);
		foreach($data as $k => $v) {
			$data[$k] = $c->Convert($v);
		}
	}
	
	if(!$data) {
		$res = array();
	} else {
		$res = $data;
	}

	return $res;
}

function connect_parse_xml($xml) {

	$handle = xml_parser_create();
	xml_parser_set_option($handle, XML_OPTION_CASE_FOLDING, false);
	$result = xml_parse_into_struct($handle, $xml, $vals, $index);
	xml_parser_free($handle);

	$data = array();
	if ($result === 0) {
		return $data;
	}

	$num = count($index['data']);
	$ret = array_slice($vals, $index['data'][0] + 1, ($index['data'][$num] - $index['data'][0] - 1));
	foreach ($ret as $row) {
		if (trim($row['value'])) {
			$data[$row['tag']] = trim($row['value']);
		} else if ($row['data']) {
			$data[$row['tag']] = trim($row['data']);
		}
	}

	return $data;
}

function connect_decode_appkey($appkey) {

	if (!$appkey) {
		return;
	}
	$parse_result = base64_decode($appkey);
	$data = explode('|', $parse_result);
	if(count($data) != 3) {
		return;
	}

	$result = array(
					'connectappkey' => $data[0],
					'my_siteid' => $data[1],
					'my_sitekey' => $data[2],
					);
	return $result;
}

function connect_encode_appkey($connectAppKey, $sId, $sKey) {

	if(!$connectAppKey || !$sId || !$sKey) {
		return;
	}

	return base64_encode(sprintf("%s|%s|%s", $connectAppKey, $sId, $sKey));
}

function connect_get_manyou_sig($params, $sKey = '') {

	$ts = $params['ts'];
	unset($params['ts']);

	ksort($params);
	if($sKey) {
		$base_string = sprintf("%s|%s|%s", connect_http_build_query($params, '', '&'), $ts, $sKey);
	} else {
		$base_string = sprintf("%s|%s", connect_http_build_query($params, '', '&'), $ts);
	}

	return md5($base_string);
}

function connect_filter_username($username) {
	$username = str_replace(' ', '_', trim($username));
	return cutstr($username, 15, '');
}

?>
