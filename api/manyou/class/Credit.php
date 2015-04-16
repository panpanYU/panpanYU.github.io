<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Credit.php 2 2011-09-07 06:49:01Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Credit extends MyBase {

	/**
	 * 获取用户积分
	 *
	 * @param integer $uId 用户Id
	 * @return integer 用户积分
	 */
	function get($uId) {
		global $_DCACHE;
		$query = $GLOBALS['db']->query('SELECT extcredits'.$_DCACHE['settings']['my_extcredit'].' AS credit FROM '.$GLOBALS['tablepre'].'members WHERE uid=\''.$uId.'\'');
		$row = $GLOBALS['db']->fetch_array($query);
		return new APIResponse($row['credit']);
	}

	/**
	 * 更新用户的积分
	 *
	 * @param integer $uId 用户Id
	 * @param integer $credits 积分值
	 * @return integer 更新后的用户积分
	 */
	function update($uId, $credits) {
		global $_DCACHE;
		$sql = sprintf('UPDATE %s SET extcredits%s=extcredits%s+(%d) WHERE uid=\'%d\'', $GLOBALS['tablepre'].'members', $_DCACHE['settings']['my_extcredit'], $_DCACHE['settings']['my_extcredit'], $credits, $uId);
		$GLOBALS['db']->query($sql);

		$query = $GLOBALS['db']->query('SELECT extcredits'.$_DCACHE['settings']['my_extcredit'].' AS credit FROM '.$GLOBALS['tablepre'].'members WHERE uid=\''.$uId.'\'');
		$row = $GLOBALS['db']->fetch_array($query);
		return new APIResponse($row['credit']);
	}

}

?>