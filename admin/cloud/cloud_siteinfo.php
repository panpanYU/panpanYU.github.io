<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cloud_siteinfo.php 136 2011-09-22 04:07:15Z yexinhao $
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

if(submitcheck('syncsubmit')) {

	if($cloudstatus != 'cloud') {
		cloud_cpmsg('cloud_open_first', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'", 3000);</script>');
	}

	require_once DISCUZ_ROOT.'./api/manyou/class/CloudClient.php';
	$cloudClient = new Discuz_Cloud_Client();

	if ($_DCACHE['settings']['my_status']) {
		// 开启了漫游，同时同步漫游的站点信息
		manyouSync();
	}

	$res = $cloudClient->sync();

	if(!$res) {
		cloud_cpmsg('cloud_sync_failure', '', 'error', array('errCode' => $cloudClient->errno, 'errMessage' => $cloudClient->errmsg));
	} else {
		cloud_cpmsg('cloud_sync_success', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=siteinfo\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=siteinfo\'", 3000);</script>');
	}
} elseif(submitcheck('resetsubmit')) {

	if($cloudstatus != 'cloud') {
		cloud_cpmsg('cloud_open_first', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'", 3000);</script>');
	}

	require_once DISCUZ_ROOT.'./api/manyou/class/CloudClient.php';
	$cloudClient = new Discuz_Cloud_Client();
	$res = $cloudClient->resetKey();

	if(!$res) {
		cloud_cpmsg($cloudClient->errmsg, '', 'error');
	} else {
		$sId = $res['sId'];
		$sKey = $res['sKey'];

		// DB::query("REPLACE INTO ".DB::table('common_setting')." (`skey`, `svalue`) VALUES ('my_siteid', '$sId'), ('my_sitekey', '$sKey'), ('cloud_status', '1')");
		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES ('my_siteid', '$sId'), ('my_sitekey', '$sKey'), ('cloud_status', '1')");
		updatecache('settings');

		cloud_cpmsg('cloud_reset_success', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=siteinfo\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=siteinfo\'", 3000);</script>');
	}
} elseif(submitcheck('ipsubmit')) {

	$_POST['cloud_api_ip'] = trim($_POST['cloud_api_ip']);
	$_POST['my_ip'] = trim($_POST['my_ip']);
	$_POST['connect_api_ip'] = trim($_POST['connect_api_ip']);

	// if($cloud_api_ip != $_POST['cloud_api_ip'] || $my_ip != $_POST['my_ip']) {
		// DB::query("REPLACE INTO ".DB::table('common_setting')." (`skey`, `svalue`) VALUES ('cloud_api_ip', '{$_G['gp_cloud_api_ip']}'), ('my_ip', '{$_G['gp_my_ip']}')");
		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES ('cloud_api_ip', '{$_POST['cloud_api_ip']}'), ('my_ip', '{$_POST['my_ip']}'), ('connect_api_ip', '{$_POST['connect_api_ip']}')");
		updatecache('settings');
	// }

	$locationUrl = $callback == 'doctor' ? ADMINSCRIPT.'?frames=yes&action=cloud&operation=doctor' : ADMINSCRIPT.'?frames=yes&action=cloud&operation=siteinfo';

	cloud_cpmsg('cloud_ipsetting_success', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.$locationUrl.'\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.$locationUrl.'\'", 3000);</script>');

} elseif ($anchor == 'cloud_ip') {

	loadcache('setings');
	ajaxshowheader();
	echo '<div class="fcontent">';
	echo '
		<h3 class="flb" id="fctrl_showblock" style="cursor: move;">
			<em id="return_showblock" fwin="showblock">'.$lang['cloud_api_ip_btn'].'</em>
			<span><a title="'.$lang['close'].'" onclick="hideWindow(\'cloudApiIpWin\');return false;" class="flbc" href="javascript:;">'.$lang['close'].'</a></span>
		</h3>
		';
	echo '<div style="margin: 0 10px; width: 700px;">';
	showformheader('cloud');
	showhiddenfields(array('operation' => $operation));
	if($callback) {
		showhiddenfields(array('callback' => $callback));
	}
	showtableheader();
	showsetting('cloud_api_ip', 'cloud_api_ip', $_DCACHE['settings']['cloud_api_ip'], 'text');
	showsetting('cloud_manyou_ip', 'my_ip', $_DCACHE['settings']['my_ip'], 'text');
	showsetting('cloud_connect_api_ip', 'connect_api_ip', $_DCACHE['settings']['connect_api_ip'], 'text');
	showsubmit('ipsubmit');
	showtablefooter();
	showformfooter();
	echo '</div>';
	echo '</div>';
	ajaxshowfooter();
	exit;
} else {
	echo '<link rel="stylesheet" type="text/css" href="images/admincp/cloud/cloud.css" />';
	shownav('cloud', 'menu_cloud_siteinfo');
	showsubmenu('menu_cloud_siteinfo');
	showtips('cloud_siteinfo_tips');
	echo '<script type="text/javascript">var disallowfloat = "";</script>';
	showformheader('cloud');
	showhiddenfields(array('operation' => $operation));
	showtableheader();
	showtitle('menu_cloud_siteinfo');
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_name').'</strong>',
		$bbname
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_url').'</strong>',
		$boardurl
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_id').'</strong>',
		$my_siteid
	));
	showsubmit('syncsubmit', 'cloud_sync', '', '<input type="submit" class="btn" id="submit_resetsubmit" name="resetsubmit" value="'.$lang['cloud_resetkey'].'" />&nbsp; <input type="button" class="btn" onClick="showWindow(\'cloudApiIpWin\', \''.ADMINSCRIPT.'?action=cloud&operation=siteinfo&anchor=cloud_ip\'); return false;" value="'.$lang['cloud_api_ip_btn'].'" />');
	showtablefooter();
	showformfooter();
}

?>