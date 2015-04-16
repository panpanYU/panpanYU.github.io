<?php

/**
 *	  [Discuz!] (C)2001-2099 Comsenz Inc.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  $Id: cloud_manyou.php 130 2011-09-22 01:41:34Z songlixin $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}
define('MY_URL', 'http://api.manyou.com/uchome.php');

$manyounav = array();
$setting = array();

$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN ('my_status', 'my_extcredit', 'my_feedpp', 'my_siteid', 'my_sitekey')");

while($row = $db->fetch_array($query)) {
	$setting[$row['variable']] = $row['value'];
}
$pluginid = $db->result_first("SELECT pluginid FROM {$tablepre}plugins WHERE identifier = 'manyou'");
$manyou['limit'] = $db->result_first("SELECT value FROM {$tablepre}pluginvars WHERE pluginid = '$pluginid' AND variable = 'limit'");

if ($setting['my_status']) {
	$anchor = in_array($anchor, array('base', 'manage')) ? $anchor : 'manage';
	$current = array($anchor => 1);
	$manyounav[0] = array('setting_manyou_manage', 'cloud&operation=manyou&anchor=manage', $current['manage']);
} else {
	$anchor = in_array($anchor, array('base', 'manage')) ? $anchor : 'base';
	$current = array($anchor => 1);
}

$manyounav[1] = array('setting_manyou_base', 'cloud&operation=manyou&anchor=base', $current['base']);

if(!$_G['inajax']) {
	cpheader();
}

if ($anchor == 'base') {
	$extcredit = array();
	foreach($extcredits as $key => $value) {
		$extcredit[] = array($key, $value['title']);
	}

	if (!submitcheck('manyousubmit')) {
        shownav('cloud', 'setting_manyou');
	    showsubmenu('setting_manyou', $manyounav);
		showformheader('cloud&operation=manyou&anchor=base');
		showtableheader();
		showsetting('setting_manyou_extcredit', array('manyounnew[my_extcredit]', $extcredit), $setting['my_extcredit'], 'select', '', '');
		showsetting('setting_manyou_index_limit', 'manyounnew[limit]', $manyou['limit'], 'text', '', '', lang('setting_manyou_index_limit_comment'));
		showsetting('setting_manyou_feedpp', 'manyounnew[my_feedpp]', $setting['my_feedpp'], 'text', '', '');
		showsubmit('manyousubmit','submit');
		showtablefooter();
		showformfooter();
	} else {
		$settingnew['my_extcredit'] = intval($manyounnew['my_extcredit']);
		$settingnew['my_feedpp'] = intval(trim($manyounnew['my_feedpp'])) ? intval(trim($manyounnew['my_feedpp'])) : 50;
		$manyounew['limit'] = intval(trim($manyounnew['limit'])) ? intval(trim($manyounnew['limit'])) : 0;

		if ($pluginid) {
			$db->query("UPDATE {$tablepre}pluginvars SET value = '$manyounew[limit]' WHERE pluginid = '$pluginid' AND variable = 'limit'");
		}

		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES
				  ('my_extcredit', '$settingnew[my_extcredit]'),
				  ('my_feedpp', '$settingnew[my_feedpp]')");

		updatecache('settings');
		updatecache('plugins');

		cloud_cpmsg('manyou_setting_update_succeed', 'action=cloud&operation=manyou&anchor=base', 'succeed');
	}
} elseif ($anchor == 'manage') {
    shownav('cloud', 'setting_manyou');
    showsubmenu('setting_manyou', $manyounav);
	if ($setting['my_status']) {

		$uchUrl = $boardurl.$BASESCRIPT.'?action=cloud&operation=manyou&anchor=' . $anchor;
		// $selfUrl = $boardurl.$BASESCRIPT.'?action=cloud&operation=manyou&anchor=manage';
		$selfUrl = $boardurl.'manyou/admincp.php?ac=userapp';

		$my_suffix = !empty($my_suffix) ? $my_suffix : '/appadmin/list';
		$my_prefix = 'http://uchome.manyou.com';
		$tmp_suffix = $my_suffix;

		$myUrl = $my_prefix.$tmp_suffix;

		$hash = md5($my_siteid.'|'.$discuz_uid.'|'.$my_sitekey.'|'.TIMESTAMP);
		$delimiter = strrpos($myUrl, '?') ? '&' : '?';
		$url = $myUrl.$delimiter.'s_id='.$my_siteid.'&uch_id='.$discuz_uid.'&uch_url='.rawurlencode($selfUrl).'&my_suffix='.rawurlencode($my_suffix).'&timestamp='.TIMESTAMP.'&my_sign='.$hash;
		$my_noticejs = my_noticejs();

		print <<<EOF
		$my_noticejs
		<script type="text/javascript" src="http://static.manyou.com/scripts/my_iframe.js"></script>
		<script language="javascript">
		var prefixURL = "$my_prefix";
		var suffixURL = "$my_suffix";
		var queryString = '';
		var url = "{$url}";
		var oldHash = null;
		var timer = null;
		var server = new MyXD.Server("ifm0");
		server.registHandler('iframeHasLoaded');
		server.registHandler('setTitle');
		server.start();
		function iframeHasLoaded(ifm_id) {
			MyXD.Util.showIframe(ifm_id);
			document.getElementById('loading').style.display = 'none';
		}
		function setTitle(x) {
			document.title = x;
		}
		</script>

		<div id="loading" style="display:block; padding:100px 0 100px 0;text-align:center;color:#999999;font-size:12px;">
		<img src="images/default/loading.gif" alt="loading..." align="absmiddle" />  {$lang['loading']}...
		</div>
		<div style="margin-top:8px;">
			<iframe id="ifm0" frameborder="0" width="810px" scrolling="no" height="810px" style="position:absolute; top:-5000px; left:-5000px;" src="{$url}"></iframe>
		</div>
		</body></html>
EOF;
		exit();

	} else {
		cloud_cpmsg('my_app_status_off', '', 'error');
	}
}

function my_noticejs() {
	$key = md5($GLOBALS['my_siteid'].TIMESTAMP.$GLOBALS['my_sitekey']);
	return '<script type="text/javascript" src="http://notice.uchome.manyou.com/notice?sId='.$GLOBALS['my_siteid'].'&ts='.TIMESTAMP.'&key='.$key.'" charset="UTF-8"></script>';
}


?>