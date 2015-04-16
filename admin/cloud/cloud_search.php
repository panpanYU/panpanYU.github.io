<?php

/**
 *	  [Discuz!] (C)2001-2099 Comsenz Inc.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  $Id: cloud_search.php 109 2011-09-21 10:07:38Z yangli $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$anchor = in_array($anchor, array('setting', 'service')) ? $anchor : 'setting';
$current = array($anchor => 1);

$connectnav = array();

$connectnav[0] = array('connect_menu_setting', 'cloud&operation=connect&anchor=setting', $current['setting']);
$connectnav[1] = array('connect_menu_service', 'cloud&operation=connect&anchor=service', $current['service']);

if(!$_G['inajax']) {
	cpheader();
	shownav('cloud', 'menu_cloud_connect');
	showsubmenu('menu_cloud_connect', $connectnav);
}

if($anchor == 'setting') {
	$params = array(
					'link_url' => $BASESCRIPT.'?action=cloud&operation=search&anchor=setting',
					'self_url' => $BASESCRIPT.'?action=cloud&operation=search&anchor=service',
					);

	$signUrl = generateSiteSignUrl($params);


	$cloudDomain = 'http://cp.discuz.qq.com';
	$logoUrl = $cloudDomain.'/search/setting/?'.$signUrl;
	headerLocation($logoUrl);
}

?>
