<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: menu_cloud.php 11 2011-09-11 08:29:02Z yexinhao $
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

// isfounder 在文件被包含时已判断
require_once DISCUZ_ROOT.'./include/cloud.func.php';
$cloudstatus = checkcloudstatus(false);

if ($cloudstatus == 'cloud') {
	$cloud_menu = array(
		array('menu_cloud_applist', 'cloud&operation=applist'),
		array('menu_cloud_siteinfo', 'cloud&operation=siteinfo'),
		array('menu_cloud_doctor', 'cloud&operation=doctor')
	);
	$apps = getcloudapps();
	if(is_array($apps) && $apps) {
		foreach($apps as $app) {
			if($app['status'] != 'close') {
				array_push($cloud_menu, array("menu_cloud_{$app['name']}", "cloud&operation={$app['name']}"));
			}
		}
	}

} else {
	if ($cloudstatus == 'upgrade') {
		$menuitem = 'menu_cloud_upgrade';
	} else {
		$menuitem = 'menu_cloud_open';
	}

	$cloud_menu = array(
		array($menuitem, 'cloud&operation=open'),
		array('menu_cloud_doctor', 'cloud&operation=doctor')
	);
}

showmenu('cloud', $cloud_menu);
?>