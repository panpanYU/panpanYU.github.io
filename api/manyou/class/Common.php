<?php
/**
 * $Id: Common.php 84 2011-09-20 09:00:04Z zhouguoqiang $
 */

class Common {

	function setConfig($data) {
		global $db, $tablepre;
		$settings = array();
		if (is_array($data) && $data) {
			foreach($data as $key => $val) {
				if (substr($key, 0, 3) != 'my_') {
					continue;
				}
				$settings[] = "('$key', '$val')";
			}
			if ($settings) {
				$db->query("REPLACE INTO {$tablepre}settings (variable, value) VALUES " . implode(',', $settings));
				require_once DISCUZ_ROOT . './include/cache.func.php';
				updatecache('setting');
				return new APIResponse(true);
			}
		}
		return new APIResponse(true);
	}
}

?>
