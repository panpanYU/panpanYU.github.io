<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cloud_doctor.php 136 2011-09-22 04:07:15Z yexinhao $
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

@set_time_limit(0);

$op = trim($op);

if(submitcheck('setidkeysubmit')) {

	// 提交新的站点ID和KEY
	$siteId = intval(trim($_POST['my_siteid']));
	if($siteId && strcmp($_POST['my_siteid'], $siteId) !== 0) {
		cloud_cpmsg('cloud_idkeysetting_siteid_failure', '', 'error');
	}

	$_POST['my_sitekey'] = trim($_POST['my_sitekey']);
	if(empty($_POST['my_sitekey'])) {
		// 将sKey更新为空
		$siteKey = '';
	} elseif(strpos($_POST['my_sitekey'], '***')) {
		// 含有***不更新sKey
		$siteKey = false;
	} elseif(preg_match('/^[0-9a-f]{32}$/', $_POST['my_sitekey'])) {
		$siteKey = $_POST['my_sitekey'];
	} else {
		cloud_cpmsg('cloud_idkeysetting_sitekey_failure', '', 'error');
	}

	if($siteKey === false) {
		$siteKeySQL = '';
	} else {
		$siteKeySQL = "('my_sitekey', '{$siteKey}'), ";
	}

	// if($my_siteid != $siteId || $siteKeySQL || $cloud_status != $_POST['cloud_status']) {
		$_POST['cloud_status'] = intval(trim($_POST['cloud_status']));
		// DB::query("REPLACE INTO ".DB::table('common_setting')." (`skey`, `svalue`) VALUES ('my_siteid', '{$siteId}'), $siteKeySQL ('cloud_status', '{$_G['gp_cloud_status']}')");
		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES ('my_siteid', '{$siteId}'), $siteKeySQL ('cloud_status', '{$_POST['cloud_status']}')");
		updatecache('settings');
	// }

	$locationUrl = ADMINSCRIPT.'?frames=yes&action=cloud&operation=doctor';

	cloud_cpmsg('cloud_idkeysetting_success', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.$locationUrl.'\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/javascript">setTimeout("top.location = \''.$locationUrl.'\'", 3000);</script>');

} elseif($op == 'apitest') {

	// 接口IP测试
	$APIType = intval($_GET['api_type']);
	$APIIP = trim($_GET['api_ip']);

	$startTime = cloudGetMicroTime();
	$testStatus = cloudAPIConnectTest($APIType, $APIIP);
	$endTime = cloudGetMicroTime();

	$otherTips = '';

	if($APIIP) {
		if ($_GET['api_description']) {
			// API接口位置信息
			require_once DISCUZ_ROOT.'./include/chinese.class.php';
			$c = new Chinese('UTF-8', $charset, true);
			$otherTips = $c->Convert($_GET['api_description']);
		}
	} else {
		// 没有 APIIP 为使用DNS解析或者setting表的IP
		if($APIType == 1) {
			$otherTips = '<a href="javascript:;" onClick="display(\'cloud_tbody_api_test\')">'.$lang['cloud_doctor_api_test_other'].'</a>';
		} elseif($APIType == 2) {
			$otherTips = '<a href="javascript:;" onClick="display(\'cloud_tbody_manyou_test\')">'.$lang['cloud_doctor_manyou_test_other'].'</a>';
		} elseif($APIType == 3) {
			$otherTips = '<a href="javascript:;" onClick="display(\'cloud_tbody_qzone_test\')">'.$lang['cloud_doctor_qzone_test_other'].'</a>';
		}
	}

	ajaxshowheader();
	if($testStatus) {
		printf($lang['cloud_doctor_api_test_success'], $lang['cloud_doctor_result_success'], $APIIP, $endTime - $startTime, $otherTips);
	} else {
		printf($lang['cloud_doctor_api_test_failure'], $lang['cloud_doctor_result_failure'], $APIIP, $otherTips);
	}
	ajaxshowfooter();
	exit;

} elseif($op == 'setidkey') {

	// 设置ID和KEY的ajax页面
	ajaxshowheader();
	echo '<div class="fcontent">';
	echo '
		<h3 class="flb" id="fctrl_showblock" style="cursor: move;">
			<em id="return_showblock" fwin="showblock">'.$lang['cloud_doctor_setidkey'].'</em>
			<span><a title="'.$lang['close'].'" onclick="hideWindow(\'cloudApiIpWin\');return false;" class="flbc" href="javascript:;">'.$lang['close'].'</a></span>
		</h3>
		';
	echo '<div style="margin: 0 10px; width: 700px;">';
	showtips('cloud_doctor_setidkey_tips');
	showformheader('cloud');
	showhiddenfields(array('operation' => $operation));
	showhiddenfields(array('op' => $op));
	showtableheader();
	showsetting('cloud_site_id', 'my_siteid', $my_siteid, 'text');
	showsetting('cloud_site_key', 'my_sitekey', preg_replace('/(\w{2})\w*(\w{2})/', '\\1****\\2', $my_sitekey), 'text');
	showsetting('cloud_site_status', array('cloud_status', array(array('0', $lang['cloud_doctor_status_0']), array('1', $lang['cloud_doctor_status_1']), array('2', $lang['cloud_doctor_status_2']))), $cloud_status, 'select');
	showsubmit('setidkeysubmit');
	showtablefooter();
	showformfooter();
	echo '</div>';
	echo '</div>';
	ajaxshowfooter();
	exit;

} else {
	// 修复工具
	$checklist = array('qqconnect', 'cloudstats', 'manyou', 'search');
	if ($op == 'initsys') {
		foreach($checklist as $identifier) {
			cloud_installplugin($identifier, 1);
		}
	}
	require_once DISCUZ_ROOT.'./discuz_version.php';
	echo '<link rel="stylesheet" type="text/css" href="images/admincp/cloud/cloud.css" />';

	shownav('cloud', 'menu_cloud_doctor');
	showsubmenu('menu_cloud_doctor');
	showtips('cloud_doctor_tips');
	echo '<script type="text/javascript">var disallowfloat = "";</script>';

	showtableheader();

	showtagheader('tbody', '', true);
	showtitle('cloud_doctor_title_status');
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_url').'</strong>',
		$boardurl
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_id').'</strong>',
		$my_siteid
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_key').'</strong>',
		preg_replace('/(\w{2})\w*(\w{2})/', '\\1****\\2', $my_sitekey).' '.$lang['cloud_site_key_safetips']
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_status').'</strong>',
		cloudStatusResult().' <a href="javascript:;" onClick="showWindow(\'cloudApiIpWin\', \''.ADMINSCRIPT.'?action=cloud&operation=doctor&op=setidkey\'); return false;">'.$lang['cloud_doctor_modify_siteidkey'].'</a>'
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('setting_basic_bbclosed').'</strong>',
		$bbclosed ? $lang['cloud_doctor_close_yes'] : $lang['no']
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_site_version').'</strong>',
		DISCUZ_VERSION.' '.DISCUZ_RELEASE
	));
	showtagfooter('tbody');

	showtagheader('tbody', '', true);
	showtitle('cloud_doctor_title_result');

	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_php_ini_separator').'</strong>',
		cloudSeparatorOutputCheck() ? $lang['cloud_doctor_result_success'].' '.$lang['cloud_doctor_php_ini_separator_true'] : $lang['cloud_doctor_result_failure'].$lang['cloud_doctor_php_ini_separator_false']
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_fsockopen_function').'</strong>',
		function_exists('fsockopen') ? $lang['cloud_doctor_result_success'].' '.$lang['available'] : $lang['cloud_doctor_result_failure'].$lang['cloud_doctor_function_disable']
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_gethostbyname_function').'</strong>',
		function_exists('gethostbyname') ? $lang['cloud_doctor_result_success'].' '.$lang['available'] : $lang['cloud_doctor_result_failure'].$lang['cloud_doctor_function_disable']
	));

	// 云平台接口IP
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_api').'</strong>',
		cloudDNSCheckResult(1)
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_api_test').'</strong>',
		cloudGetAPIConnectJS(1)
	));
	showtagfooter('tbody');

	// 云平台其他接口IP
	showtagheader('tbody', 'cloud_tbody_api_test', false);
	showtagfooter('tbody');

	// 漫游接口IP
	showtagheader('tbody', '', true);
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_manyou').'</strong>',
		cloudDNSCheckResult(2)
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_manyou_test').'</strong>',
		cloudGetAPIConnectJS(2)
	));
	showtagfooter('tbody');

	// 漫游其他接口IP
	showtagheader('tbody', 'cloud_tbody_manyou_test', false);
	showtagfooter('tbody');

	// Qzone接口IP
	showtagheader('tbody', '', true);
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_qzone').'</strong>',
		cloudDNSCheckResult(3)
	));
	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_dns_qzone_test').'</strong>',
		cloudGetAPIConnectJS(3)
	));
	showtagfooter('tbody');

	// Qzone其他接口IP
	showtagheader('tbody', 'cloud_tbody_qzone_test', false);
	showtagfooter('tbody');

	// 插件状态检查
	showtagheader('tbody', '', true);
	showtitle('cloud_doctor_title_plugin');
	cloudShowPlugin();
	showtagfooter('tbody');

	if(getcloudappstatus('connect')) {
		showtagheader('tbody', '', true);
		showtitle('cloud_doctor_title_connect');
		showtablerow('', array('class="td24"'), array(
			'<strong>'.cplang('cloud_doctor_connect_app_id').'</strong>',
			!empty($connectappid) ? $connectappid : $lang['cloud_doctor_connect_reopen']
		));
		showtablerow('', array('class="td24"'), array(
			'<strong>'.cplang('cloud_doctor_connect_app_key').'</strong>',
			!empty($connectappkey) ? preg_replace('/(\w{2})\w*(\w{2})/', '\\1****\\2', $connectappkey).' '.$lang['cloud_site_key_safetips'] : $lang['cloud_doctor_connect_reopen']
		));
		showtagfooter('tbody');
	}

	showtablefooter();
	showGetCloudAPIIPJS();

}

// 云系统插件列表
function cloudShowPlugin() {
	global $db, $tablepre, $checklist;

	$plugins = array();
	// 需要检测的插件

	$checklists = implodeids($checklist);
	$query = $db->query("SELECT pluginid, available, name, identifier, modules, version FROM {$tablepre}plugins WHERE identifier IN ($checklists)");
	while($plugin = $db->fetch_array($query)) {
		$plugins[$plugin['identifier']] = $plugin;
	}

	showtablerow('', array('class="td24"'), array(
		'<strong>'.cplang('cloud_doctor_system_plugin_status').'</strong>',
		count($plugins) >= count($checklist) ? cplang('cloud_doctor_result_success').' '.cplang('available').' '.cplang('cloud_doctor_system_plugin_list') : cplang('cloud_doctor_result_failure').cplang('cloud_doctor_system_plugin_status_false')
	));
	foreach($plugins as $plugin) {
		$moduleStatus = cplang('cloud_doctor_plugin_module_error');
		$plugin['modules'] = @unserialize($plugin['modules']);
		if(is_array($plugin['modules']) && $plugin['modules']) {
			$moduleStatus = '';
		}

		showtablerow('', array('class="td24"'), array(
			'<strong>'.$plugin['name'].'</strong>',
			cplang('version').' '.$plugin['version'].' '.$moduleStatus
		));
	}
}

// DNS检测结果，带内容输出 type 1 为 api.discuz.qq.com, 2 为 api.manyou.com
function cloudDNSCheckResult($type = 1) {
	global $_DCACHE;
	loadcache('settings');

	switch ($type) {
		case 1:
			$setIP = ($_DCACHE['settings']['cloud_api_ip'] ? cplang('cloud_doctor_setting_ip').$_DCACHE['settings']['cloud_api_ip'] : '');
			$host = 'api.discuz.qq.com';
			break;
		case 2:
			$setIP = ($_DCACHE['settings']['my_ip'] ? cplang('cloud_doctor_setting_ip').$_DCACHE['settings']['my_ip'] : '');
			$host = 'api.manyou.com';
			break;
		case 3:
			$setIP = ($_DCACHE['settings']['connect_api_ip'] ? cplang('cloud_doctor_setting_ip').$_DCACHE['settings']['connect_api_ip'] : '');
			$host = 'openapi.qzone.qq.com';
			break;
	}
	$ip = cloudDNSCheck($host);
	if ($ip) {
		return sprintf(cplang('cloud_doctor_dns_success'), $host, $ip, $setIP, ADMINSCRIPT);
	} else {
		return sprintf(cplang('cloud_doctor_dns_failure'), $host, $setIP, ADMINSCRIPT);
	}
}

// DNS检测结果，成功返回IP，失败返回false
function cloudDNSCheck($url) {
	if (!$url) {
		return false;
	}
	$matches = parse_url($url);
	$host = $matches['host'] ? $matches['host'] : $matches['path'];
	if (!$host) {
		return false;
	}
	$ip = gethostbyname($host);
	if ($ip == $host) {
		return false;
	} else {
		return $ip;
	}
}

// 获取开通云平台状态
function cloudStatusResult() {
	$cloud_status = $GLOBALS['_DCACHE']['settings']['cloud_status'];

	if (empty($cloud_status)) {
		return cplang('cloud_doctor_status_0');
	} elseif ($cloud_status == 1) {
		return cplang('cloud_doctor_status_1');
	} elseif ($cloud_status == 2) {
		return cplang('cloud_doctor_status_2');
	}
}

// api连接测试, type 1 api.discuz.qq.com, type 2 api.manyou.com
function cloudAPIConnectTest($type = 1, $ip = '') {
	global $_DCACHE;

	if($type == 1) {
		$url = 'http://api.discuz.qq.com/site.php';
		$result = dfopen($url, 0, '', '', false, $ip ? $ip : $_DCACHE['settings']['cloud_api_ip'], 5);
	} elseif($type == 2) {
		$url = 'http://api.manyou.com/uchome.php';
		$result = dfopen($url, 0, 'action=siteRefresh', '', false, $ip ? $ip : $_DCACHE['settings']['my_ip'], 5);
	} elseif($type == 3) {
		$url = 'http://openapi.qzone.qq.com/oauth/qzoneoauth_request_token';
		$result = dfopen($url, 0, '', '', false, $ip ? $ip : $_DCACHE['settings']['connect_api_ip'], 5);
		// Qzone API测试只要有结果就是成功
		if($result) {
			return true;
		}
	}

	$result = trim($result);

	if(!$result) {
		return false;
	}

	$result = @unserialize($result);
	if(!$result) {
		return false;
	}
	return true;
}

// 获取当前微秒数
function cloudGetMicroTime() {
	list($usec, $sec) = explode(' ', microtime());
	return (floatval($usec) + floatval($sec));
}

// 输出异步加载JS
function cloudGetAPIConnectJS($type = 1, $ip = '') {
	$html = sprintf('<div id="_doctor_apitest_%1$s_%2$s"></div><script type="text/javascript">ajaxget("%3$s?action=cloud&operation=doctor&op=apitest&api_type=%1$s&api_ip=%2$s", "_doctor_apitest_%1$s_%2$s");</script>', $type, $ip, ADMINSCRIPT);
	return $html;
}

// 检查php.ini 配置的arg_separator.output参数是否为&
function cloudSeparatorOutputCheck() {
	if(!function_exists('ini_get')) {
		return false;
	}
	$separatorOutput = @ini_get('arg_separator.output');
	if(empty($separatorOutput) || $separatorOutput == '&') {
		return true;
	}
	return false;
}

function showGetCloudAPIIPJS() {

	echo
<<<EOT
<script type="text/javascript" src="images/admincp/cloud/cloud.js"></script>
<script type="text/javascript" src="http://cp.discuz.qq.com/cloud/apiIp" charset="utf-8"></script>
EOT;
}

?>