<?php
/**
 * $Id: index.inc.php 122 2011-09-21 15:26:01Z zhouguoqiang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!is_array($my_search_data)) {
	$my_search_data = unserialize($my_search_data);
}
if(empty($my_search_data['status']) || empty($my_siteid) || empty($my_sitekey)) {
	showmessage('search:no_open');
}

function plugin_search_sign_params() {
	global $my_siteid, $my_sitekey, $timestamp, $discuz_uid, $discuz_user, $_DSESSION;

	$params = array('sId' => $my_siteid,
					'ts' => $timestamp,
					'cuId' => $discuz_uid,
					'cuName' => $discuz_user,
					'gId' => $_DSESSION['groupid'],
					'agId' => $_DSESSION['adminid'],
					'egIds' => str_replace("\t", ',', $_DSESSION['extgroupids']),
					//				'fIds' => '',
					'fmSign' => '',
				   );

	$groupIds = explode(',', $_DSESSION['groupid']);
	if ($_DSESSION['adminid']) {
		$groupIds[] = $_DSESSION['adminid'];
	}
	if ($_DSESSION['extgroupids']) {
		$groupIds = array_merge($groupIds, explode("\t", $_DSESSION['extgroupids']));
	}

	$groupIds = array_unique($groupIds);
	foreach($groupIds as $k => $v) {
		$params['ugSign' . $v] = '';
	}

	$params['sign'] = md5(implode('|', $params) . '|' . $my_sitekey);
	return $params;
}

$params = plugin_search_sign_params();

$extra = array('q', 'fId', 'author', 'scope', 'source');
foreach($extra as $v) {
	if ($_GET[$v]) {
		$params[$v] = $_GET[$v];
	}
}
$params['charset'] = $GLOBALS['charset'];
if ($my_search_data['domain']) {
	$domain = $my_search_data['domain'];
} else {
	$domain = 'search.discuz.qq.com';
}
$url = 'http://' . $domain . '/f/discuz?' . http_build_query($params);

header('Location: ' . $url);

?>
