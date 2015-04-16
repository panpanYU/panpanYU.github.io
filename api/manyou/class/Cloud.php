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
	 * @params $appName ��Ӧ�����ƿ�ѡ
	 * @return array ����״̬��ά����
	 *  + name connect Ӧ������
	 *    + name Ӧ������
	 *    + status ����״̬
	 *  + name search Ӧ������
	 *    + name Ӧ������
	 *    + status ����״̬
	 *  + apiVersion Discuz! ƽ̨�ͻ��˰汾��
	 * @return array
	 *  + status true | false �Ƿ����ɹ�
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
	 * @params $appName (manyou, payment, search, stats, connect, security) ����Ӣ����
	 * @params $status ����״̬(normal, close)
	 * @access public
	 * @return array
	 *  ��ά����
	 *  + ����Ӣ����
	 *   + Discuz!���ر��β����Ƿ�ɹ� true false
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

		// ��������ʱ��������������ɺ�ͳһ���»���
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