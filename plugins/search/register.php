<?php
/**
 * $Id: register.php 83 2011-09-20 08:26:06Z zhouguoqiang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./forumdata/plugins/search.lang.php';
include_once DISCUZ_ROOT . './plugins/search/common.php';
include_once DISCUZ_ROOT . './plugins/search/register.func.php';

/*
if (!in_array('search', $plugins['available'])) {
	showmessage('search:disabled');
}
*/

if ($action == 'displayinvitecode') {
	echo my_site_invite_code();
	exit;
}

if(!$isfounder) {
	showmessage('search:nofounder');
}

if (submitcheck('mysyncsubmit')) {
	if ($my_action == 'sync') {
		$sitekey = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable='siteuniqueid'");

		$register = 0;

		$maxPostId = $db->result_first("SELECT pid FROM {$tablepre}posts ORDER BY pid DESC LIMIT 1");
		if(!$my_siteid) {
			$register = 1;
			$res = my_site_register($sitekey, $bbname, $boardurl.'manyou/', UC_API, $maxPostId, $charset, $timeoffset, 0, 0, true, $mysearch_invite, $my_status);
		} else {
			$res = my_site_refresh($my_siteid, $bbname, $boardurl.'manyou/', UC_API, $maxPostId, $charset, $timeoffset, 0, 0, $my_sitekey, $sitekey, true, $mysearch_invite, $my_status);
		}

		if($res['errCode']) {
			showmessage('search:error', NULL, 'HALTED');
		} else {
			require_once DISCUZ_ROOT.'./include/cache.func.php';
			if($register) {
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_siteid', '{$res[result][mySiteId]}'), ('my_sitekey', '{$res[result][mySiteKey]}'), ('my_search_status', '1')");
				updatecache('settings');
				showmessage('search:open', 'my_search.php?script=admincp');
			} else {
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_search_status', '1')");
				updatecache('settings');
				showmessage('search:sync', 'my_search.php?script=admincp');
			}
		}
	} elseif ($my_action == 'close') {
		$res = my_site_close($my_siteid, $my_sitekey);
		if ($res['errCode']) {
			showmessage('search:error');
		} else {
			$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES ('my_search_status', '0')");
			require_once DISCUZ_ROOT.'./include/cache.func.php';
			updatecache('settings');
			showmessage('search:close', 'my_search.php?script=admincp');
		}
	}
}

if ($my_search_status) {
	$selfUrl = $boardurl.'my_search.php?script=admincp';

	$my_suffix = !empty($my_suffix) ? $my_suffix : '/admin/view';

	$my_prefix = 'http://search.manyou.com';
	$tmp_suffix = $my_suffix;
	$myUrl = $my_prefix.$tmp_suffix;
	$hash = md5($my_siteid.'|'.$discuz_uid.'|'.$my_sitekey.'|'.$timestamp);
	$delimiter = strrpos($myUrl, '?') ? '&' : '?';
	$url = $myUrl.$delimiter.'s_id='.$my_siteid.'&dz_id='.$discuz_uid.'&dz_url='.rawurlencode($selfUrl).'&my_suffix='.rawurlencode($my_suffix).'&timestamp='.$timestamp.'&my_sign='.$hash;
}

include template('my_search_admincp');

?>
