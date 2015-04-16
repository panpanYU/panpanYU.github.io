<?php

/**
 *	  [Discuz!] (C)2001-2099 Comsenz Inc.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  $Id: cloud_connect.php 103 2011-09-21 07:07:57Z houdelei $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

require libfile("function/connect");

$anchor = in_array($anchor, array('setting', 'service')) ? $anchor : 'setting';
$current = array($anchor => 1);

$connectnav = array();

$connectnav[0] = array('connect_menu_setting', 'cloud&operation=connect&anchor=setting', $current['setting']);
$connectnav[1] = array('connect_menu_service', 'cloud&operation=connect&anchor=service', $current['service']);
$pluginid = $db->result_first("SELECT pluginid FROM {$tablepre}plugins WHERE identifier = 'qqconnect'");

if(!$_G['inajax']) {
	cpheader();
}

if($anchor == 'setting') {
	$setting = array();
	$query = $db->query("SELECT * FROM {$tablepre}settings WHERE variable IN ('connectappid', 'connectappkey', 'my_siteid', 'my_sitekey', 'connect', 'connect_api_ip', 'regconnect')");

	while($row = $db->fetch_array($query)) {
		$setting[$row['variable']] = $row['value'];
	}

	$setting['connect'] = (array)unserialize($setting['connect']);

	if (!submitcheck('connectsubmit')) {
		shownav('cloud', 'menu_cloud_connect');
		showsubmenu('menu_cloud_connect', $connectnav);
		showformheader('cloud&operation=connect&anchor=setting');
		showtableheader();

		showsetting('connect_setting_allow', 'connectnew[allow]', $setting['connect']['allow'], 'radio', 0, 1, lang('connect_setting_allow_comment'));

		showsetting('setting_access_register_connect_uinlimit', 'connectnew[register_uinlimit]', $setting['connect']['register_uinlimit'], 'text', '', '', lang('setting_access_register_connect_uinlimit_comment'));

		showtagfooter('tbody');

		showsubmit('connectsubmit', 'submit');
		showtablefooter();
		showformfooter();
	} else {
		$new_register_uinlimit = intval(trim($connectnew['register_uinlimit']));
		$new_allow = intval(trim($connectnew['allow']));
		
		$connectnew = array(
							'register_uinlimit' => $new_register_uinlimit,
							'allow' => $new_allow,
							);

		$connectnew_data = array_merge($setting['connect'], $connectnew);
		$connectnew_string = serialize(array_map('stripslashes', $connectnew_data));
       
		$regconnectnew = !$setting['connect']['allow'] && $new_allow ? 1 : $setting['regconnect'];
		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES
				  ('regconnect', '$regconnectnew'),
				  ('connect', '$connectnew_string')");

		updatecache('plugins');
		updatecache('settings');
		cloud_cpmsg('connect_setting_update_succeed', 'action=cloud&operation=connect', 'succeed');
	}

} elseif ($anchor == 'service') {
	$params = array(
					'link_url' => $BASESCRIPT.'?action=cloud&operation=connect&anchor=setting',
					'self_url' => $BASESCRIPT.'?action=cloud&operation=connect&anchor=service',
					);

	$signUrl = generateSiteSignUrl($params);

	$cloudDomain = 'http://cp.discuz.qq.com';
	$logoUrl = $cloudDomain.'/connect/service/?'.$signUrl;
	headerLocation($logoUrl);
}

?>