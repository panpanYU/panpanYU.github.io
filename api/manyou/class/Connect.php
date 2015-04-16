<?php

/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Connect.php 110 2011-09-21 10:26:02Z songlixin $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Connect Extends MyBase {

	function setConfig($data) {
		global $_DCACHE;
		loadcache('settings');

		$settingFields = array('connectappid', 'connectappkey');
		if (!$data) {
			return new APIResponse(false);
		}

		$connectData = unserialize($_DCACHE['settings']['connect']);
		if (!is_array($connectData)) {
			$connectData = array();
		}

		$settings = array();
		foreach($data as $k => $v) {
			if (in_array($k, $settingFields)) {
				$settings[] = "('$k', '$v')";
			} else {
				$connectData[$k] = $v;
			}
		}
		if ($connectData) {
			$connectValue = addslashes(serialize(dstripslashes($connectData)));
			$settings[] = "('connect', '$connectValue')";
		}

		if ($settings) {
			$updatesql = sprintf("REPLACE INTO %s (`variable`, `value`) VALUES %s", $GLOBALS['tablepre'].'settings', implode(',', $settings));
			$GLOBALS['db']->query($updatesql);
			require_once libfile('function/cache');
			updatecache('settings');
			return new APIResponse(true);
		}
		return new APIResponse(false);
	}

}

?>