<?php
/**
 * $Id: common.php 90 2011-09-20 12:24:31Z zhouguoqiang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include_once DISCUZ_ROOT.'./discuz_version.php';
include_once DISCUZ_ROOT.'./api/manyou/version.php';

/**
 * plugin_search_delete_threads
 *
 * @param array $threads 
 * 			* tid
 * 			* fid
 * 			* uid
 * @access public
 * @return mixed
 */
function plugin_search_delete_threads($threads, $isRedelete = false) {
	global $db, $tablepre, $timestamp;

	if (!$threads) {
		return false;
	}

	$action = 'delete';
	if ($isRedelete) {
		$action = 'redelete';
	}

	$sql = "REPLACE INTO {$tablepre}threadlogs (tid, fid, otherid, uid, action, dateline) VALUES ";
	$tids = array();
	foreach($threads as $thread) {
		$sql .= sprintf("('%d', '%d', '%d', '%d', '%s', '%d'), ", $thread['tid'], $thread['fid'], 0, $thread['uid'], $action, $timestamp);
		$tids[] = $thread['tid'];
	}
	$sql = trim($sql, ', ');
	$db->query($sql, 'UNBUFFERED');

	$ids = implode(',', $tids);
	$db->query("DELETE FROM {$tablepre}postlogs WHERE tid IN ($ids)", 'UNBUFFERED');

	return true;
}

/**
 * plugin_search_delete_posts
 *
 * @param mixed $posts
 * @access public
 * @return mixed
 */
function plugin_search_delete_posts($posts) {
	global $db, $tablepre, $timestamp;

	if (!$posts) {
		return false;
	}

	$sql = "REPLACE INTO {$tablepre}postlogs (pid, tid, fid, uid, action, dateline) VALUES ";
	$pids = array();
	foreach($posts as $post) {
		$sql .= sprintf("('%d', '%d', '%d', '%d', 'delete', '%d'), ", $post['pid'], $post['tid'], $post['fid'], $post['uid'], $timestamp);
		$pids[] = $post['pid'];
	}
	$sql = trim($sql, ', ');

	include DISCUZ_ROOT . '/forumdata/cache/plugin_search.php';
	if ($_DPLUGIN['search'] && $_DPLUGIN['search']['vars']) {
		if ($_DPLUGIN['search']['vars']['sync_delete']) {
			$response = search_call_method('post.delete', array('pids' => $pids), $_DPLUGIN['search']['vars']['search_ip']);
			if ($response['errMessage'] == 'OK') {
				return true;
			} else {
	//			error_log(var_export($response, true));
			}
		}
	}
	$db->query($sql, 'UNBUFFERED');

	return true;
}

function plugin_search_delete_forum($fid) {
	global $db, $tablepre, $timestamp;

	$fid = intval($fid);

	if (!$fid) {
		return false;
	}

	$sql = "REPLACE INTO {$tablepre}threadlogs (tid, fid, otherid, action, dateline) VALUES (0, $fid, 0, 'delforum', $timestamp)";

	include DISCUZ_ROOT . '/forumdata/cache/plugin_search.php';
	$db->query("DELETE FROM {$tablepre}postlogs WHERE fid = $fid ", 'UNBUFFERED');
	$db->query("DELETE FROM {$tablepre}threadlogs WHERE fid = $fid ", 'UNBUFFERED');

	if ($_DPLUGIN['search'] && $_DPLUGIN['search']['vars']) {
		if ($_DPLUGIN['search']['vars']['sync_delete']) {
			$response = search_call_method('forum.delete', array('fid' => $fid), $_DPLUGIN['search']['vars']['search_ip']);
			if ($response['errMessage'] == 'OK') {
				return true;
			} else {
//				error_log(var_export($response, true));
			}
		}
	}

	$db->query($sql, 'UNBUFFERED');
	return true;
}

function plugin_search_ban_user($uid, $expiry = 0, $isDeletePost = false, $isRestore = false) {
	global $db, $tablepre, $timestamp;

	$uid = intval($uid);

	if (!$uid) {
		return false;
	}

	if ($isDeletePost) {
		$db->query("DELETE FROM {$tablepre}postlogs WHERE uid = $uid ", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}threadlogs WHERE uid = $uid ", 'UNBUFFERED');
	}

	if ($isRestore) {
		$action = 'unbanuser';
	} else {
		$action = 'banuser';
	}

	$sql = "REPLACE INTO {$tablepre}threadlogs (uid, action, dateline, expiry, otherid) VALUES ($uid, '$action', $timestamp, $expiry, '$isDeletePost')";

	include DISCUZ_ROOT . '/forumdata/cache/plugin_search.php';

	if ($_DPLUGIN['search'] && $_DPLUGIN['search']['vars']) {
		if ($_DPLUGIN['search']['vars']['sync_delete']) {
			$params = array('uid' => $uid,
							'expiry' => $expiry,
							'isDeletePost' => $isDeletePost,
							'action' => $action,
						   );

			$response = search_call_method('user.ban', $params, $_DPLUGIN['search']['vars']['search_ip']);
			if ($response['errMessage'] == 'OK') {
				return true;
			} else {
//				error_log(var_export($response, true));
			}
		}
	}

	$db->query($sql, 'UNBUFFERED');
	return true;
}

function plugin_search_delete_user($uid, $expiryDay = 0, $isDeletePost = false, $isRestore = false) {
	global $db, $tablepre, $timestamp;

	$uid = intval($uid);

	if (!$uid) {
		return false;
	}

	if ($isDeletePost) {
		$db->query("DELETE FROM {$tablepre}postlogs WHERE uid = $uid ", 'UNBUFFERED');
		$db->query("DELETE FROM {$tablepre}threadlogs WHERE uid = $uid ", 'UNBUFFERED');
	}

	$expiry = 0;
	if ($expiryDay) {
		$expiry = time() + 86400 * $expiryDay;
	}

	if ($isRestor) {
		$action = 'unbanuser';
	} else {
		$action = 'deluser';
	}

	$sql = "REPLACE INTO {$tablepre}threadlogs (uid, action, dateline, expiry, otherid) VALUES ($uid, '$action', $timestamp, $expiry, '$isDeletePost')";

	include DISCUZ_ROOT . '/forumdata/cache/plugin_search.php';

	if ($_DPLUGIN['search'] && $_DPLUGIN['search']['vars']) {
		if ($_DPLUGIN['search']['vars']['sync_delete']) {
			$params = array('uid' => $uid,
							'expiry' => $expiry,
							'isDeletePost' => $isDeletePost,
							'action' => $action,
						   );

			$response = search_call_method('user.delete', $params, $_DPLUGIN['search']['vars']['search_ip']);
			if ($response['errMessage'] == 'OK') {
				return true;
			} else {
//				error_log(var_export($response, true));
			}
		}
	}

	$db->query($sql, 'UNBUFFERED');
	return true;
}

/**
 * dz客户端, 与搜索交互
 */

function search_call_method($method, $args, $ip = '') {
	global $discuz_uid, $_DCACHE, $timestamp;

//	$args = array('t_ids' => array('中文\'`~!@#$%^&*()_+\'"标点'));

	$my_siteKey = $_DCACHE['settings']['my_sitekey'];
	$my_siteId  = $_DCACHE['settings']['my_siteid'];
	if (!$discuz_uid || !$my_siteKey || !$method || !$my_siteId) {
		return array('errCode' => 1, 'errMessage' => 'please enable the plugin.');
	}

	$apiUrl = 'http://search.manyou.com/api.php';
	$params = array(
					'format' => 'PHP',
					'uId' => $discuz_uid,
					'sId' => $my_siteId,
					'ts' => $timestamp,
					'productType' => 'DISCUZ',
					'siteVersion' => DISCUZ_VERSION,
					'apiVersion' => MANYOU_API_VERSION,
					'method' => $method,
				   );

	// generateSig
	ksort($params);
	$str = '';
	foreach ($params as $k=>$v) {
		$str .= $k . '=' . $v . '&';
	}
	ksort($args);
	$str .= search_build_array_query($args, 'args');
	$str .= $my_siteKey;
	//$params['sig'] = urlencode($str);
	$params['sig'] = md5($str);
	/*
	error_log('Params:');
	error_log(var_export($params, true));
	*/

	// createPostString
	ksort($params);
	//error_log(var_export($params, true));
	$data = '';
	foreach ($params as $k=>$v) {
		$data .= $k . '=' . $v . '&';
	}

	ksort($args);
	$data .= search_build_array_query($args, 'args', true);
	//error_log("data: $data");
	return search_post_request($apiUrl, $data, $ip);
}

function search_build_array_query($data, $key = '', $isEncode = false) {
	$ret = array();
	foreach ($data as $k => $v) {
		if ($isEncode) {
			$k = urlencode($k);
		}

		if ($key) {
			$k = $key . "[" . $k . "]";
		}

		if (is_array($v)) {
			array_push($ret, search_build_array_query($v, $k, $isEncode));
		} else {
			if ($isEncode) {
				$v = urlencode($v);
			}
			array_push($ret, $k . "=" . $v);
		}
	}

	return join('&', $ret);
}


function search_post_request($url, $data, $ip) {
	$response = dfopen($url, 0, $data, '', false, $ip);
	$res = unserialize($response);
	if (is_array($res)) {
	} else {
		$res = array('errCode' => 1,
					 'errMessage' => 'unkown error',
					 'result' => $response,
					);
	}
	return $res;
}


?>
