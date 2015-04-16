<?php
/**
 * $Id: my_search.php 128 2011-09-21 18:03:43Z zhouguoqiang $
 */

define('CURSCRIPT', 'my_search');

require_once './include/common.inc.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/cache_scriptlang.php';

if ($script == 'admincp') {
	$isfounder = isfounder();
	require_once DISCUZ_ROOT.'./plugins/search/register.php';
} elseif ($mod == 'form') {
	require_once DISCUZ_ROOT.'./plugins/search/form.inc.php';
} else {
	require_once DISCUZ_ROOT.'./plugins/search/index.inc.php';
}

function isfounder($user = '') {
	$user = empty($user) ? array('uid' => $GLOBALS['discuz_uid'], 'adminid' => $GLOBALS['adminid'], 'username' => $GLOBALS['discuz_userss']) : $user;
	$founders = str_replace(' ', '', $GLOBALS['forumfounders']);
	if($user['adminid'] <> 1) {
		return FALSE;
	} elseif(empty($founders)) {
		return TRUE;
	} elseif(strexists(",$founders,", ",$user[uid],")) {
		return TRUE;
	} elseif(!is_numeric($user['username']) && strexists(",$founders,", ",$user[username],")) {
		return TRUE;
	} else {
		return FALSE;
	}
}
?>
