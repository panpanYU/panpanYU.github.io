<?php

/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Cloud.php 81 2011-09-19 14:28:54Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Cloud Extends MyBase {

	/**
	 * getApps
	 *
	 * @access public
	 * @params $appName 云应用名称可选
	 * @return array 开启状态二维数组
	 *  + name connect 应用名称
	 *    + name 应用名称
	 *    + status 开启状态
	 *  + name search 应用名称
	 *    + name 应用名称
	 *    + status 开启状态
	 *  + apiVersion Discuz! 平台客户端版本号
	 * @return array
	 *  + status true | false 是否开启成功
	 *  + apiVersion
	 */
	function getApps($appName = '') {

		$apps = getcloudapps(false);

		if ($appName) {
			$apps = array($appName => $apps[$appName]);
		}

		$apps['apiVersion'] = cloud_get_api_version();

		$apps['siteInfo'] = $this->_getBaseInfo();

		return new APIResponse($apps);
	}

	/**
	 * setApps
	 *
	 * @params $appName (manyou, payment, search, stats, connect, security) 服务英文名
	 * @params $status 服务状态(normal, close)
	 * @access public
	 * @return array
	 *  二维数组
	 *  + 服务英文名
	 *   + Discuz!返回本次操作是否成功 true false
	 *  + apiVersion
	 */
	function setApps($apps) {

		if (!is_array($apps)) {
			return new APIResponse(false);
		}

		$res = array();
		$res['apiVersion'] = cloud_get_api_version();

		foreach ($apps as $appName => $status) {
			$res[$appName] = setcloudappstatus($appName, $status, false, false);
		}

		// 批量操作时，在批量操作完成后统一更新缓存
		require_once libfile('function/cache');
		updatecache('settings');
		// updatecache(array('plugin', 'setting', 'styles'));

		$res['siteInfo'] = $this->_getBaseInfo();

		return new APIResponse($res);
	}

	/**
	 * openCloud
	 *
	 * @return array
	 *  + status boolean
	 */
	function openCloud() {
		$openStatus = openCloud();

		$res = array();
		$res['status'] = $openStatus;
		$res['siteInfo'] = $this->_getBaseInfo();

		return new APIResponse($res);
	}

	function _getBaseInfo() {
		global $_DCACHE;

		$info = array();
		loadcache('settings');
		$postdata = $_DCACHE['settings']['historyposts'] ? explode("\t", $_DCACHE['settings']['historyposts']) : array(0, 0);
		$info['yesterdayPosts'] = intval($postdata[0]);
		$info['members'] = intval($_DCACHE['settings']['totalmembers']);

		$infoSql = sprintf("SELECT SUM(threads) AS threads, SUM(posts) AS posts, SUM(todayposts) AS todayposts FROM `%s` WHERE status='1'", $GLOBALS['tablepre'].'forums');
		$forumStats = $GLOBALS['db']->fetch_first($infoSql);

		$info['threads'] = intval($forumStats['threads']);
		$info['allPosts'] = intval($forumStats['posts']);
		$info['todayPosts'] = intval($forumStats['todayposts']);

		return $info;
	}

	function getStats() {
		global $_DCACHE;

		$info = array();

		$tableprelen = strlen($GLOBALS['tablepre']);
		$table = array(
			'threads' => 'threads',
			'posts' => 'allPosts',
			'members' => 'members'
		);
		$query = $GLOBALS['db']->query("SHOW TABLE STATUS");
		while($row = $GLOBALS['db']->fetch_array($query)) {
			$tablename = substr($row['Name'], $tableprelen);
			if(!isset($table[$tablename])) {
				continue;
			}
			$info[$table[$tablename]] = $row['Rows'];
		}

		loadcache('settings');
		$postdata = $_DCACHE['settings']['historyposts'] ? explode("\t", $_DCACHE['settings']['historyposts']) : array(0, 0);
		$info['yesterdayPosts'] = intval($postdata[0]);

		$avg_posts = 0; // DB::result_first("SELECT AVG(post) FROM ".DB::table('common_stat')." ORDER BY daytime DESC LIMIT 0,30");

		$info['avgPosts'] = intval($avg_posts);
		$info['statsCode'] = $_DCACHE['settings']['statcode'];

		return new APIResponse($info);
	}

}

?>