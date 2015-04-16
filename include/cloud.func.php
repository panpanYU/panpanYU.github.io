<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cloud.func.php 126 2011-09-21 16:50:07Z zhouguoqiang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

/**
* 从Discuz! X移植按Discuz! 7.2目录结构改写的libfile函数，返回库文件的全路径
*
* @param string $libname 库文件分类及名称
* @param string $folder 模块目录'module','include','class'
* @return string
*
* @example require DISCUZ_ROOT.'./source/function/function_cache.php'
* @example 我们可以利用此函数简写为：require libfile('function/cache');
*
*/
function libfile($libname, $folder = '') {

	if ($folder == 'admincp' || $folder == 'admin') {
		$libpath = DISCUZ_ROOT.'./admin';
	} else {
		$libpath = DISCUZ_ROOT.'./include/'.$folder;
	}

	if(strstr($libname, '/')) {
		list($pre, $name) = explode('/', $libname);
		if ($pre == 'function') {
			return realpath("{$libpath}/{$name}.func.php");
		} else {
			return realpath("{$libpath}/{$pre}/{$pre}_{$name}.php");
		}
	} else {
		return realpath("{$libpath}/{$libname}.php");
	}
}

/**
 * 从Discuz! X移植按Discuz! 7.2目录结构改写的loadcache函数，兼容X和7.2的文件命名
 *
 * @param mixed $cachenames
 * @param boolean $force
 *
 * @example if(!isset($_DCACHE['settings'])) {
 * @example		require_once DISCUZ_ROOT.'./forumdata/cache/cache_settings.php';
 * @example	}
 * @example 可以利用此函数简写为：loadcache('settings');
 *
 * @example 原Discuz! X中的loadcache('common_setting');可原样书写
 */
function loadcache($cachenames, $force = false) {
	global $_DCACHE;

	$cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
	foreach ($cachenames as $k) {

		if(strpos($k, 'common_') !== false) {
			list($pre, $name) = explode('common_', $k);
			$k = $name . 's';
		}

		if(!isset($_DCACHE[$k]) || $force) {
			@include sprintf('%s./forumdata/cache/cache_%s.php', DISCUZ_ROOT, $k);
		}
	}

	return true;
}

/**
 * 后台语言显示或转换
 *
 * @param string $name 语言名称
 * @param array $replace 替换变量数组 key =>val, 当为 false 且此语言不存在,则不会输出或返回任何内容
 * @param boolean $output 是否直接输出
 * @return string
 */
function cplang($name, $replace = array(), $output = false) {
	$ret = '';

	$ret = lang($name, false);

	$ret = $ret ? $ret : ($replace === false ? '' : $name);
	if($replace && is_array($replace)) {
		$s = $r = array();
		foreach($replace as $k => $v) {
			$s[] = '{'.$k.'}';
			$r[] = $v;
		}
		$ret = str_replace($s, $r, $ret);
	}
	$output && print($ret);
	return $ret;
}

function openCloud() {
	global $_DCACHE;

	$result = inserttable('settings', array('variable' => 'cloud_status', 'value' => '1'), 0, true);

	// 开通云之后删除connect的id
	if(!empty($_DCACHE['settings']['connectsiteid']) || !empty($_DCACHE['settings']['connectsitekey']) || !empty($_DCACHE['settings']['my_siteid_old']) || !empty($_DCACHE['settings']['my_sitekey_sign_old'])) {
		$GLOBALS['db']->query("DELETE FROM {$GLOBALS['tablepre']}settings WHERE variable='connectsiteid' OR variable='connectsitekey' OR variable='my_siteid_old' OR variable='my_sitekey_sign_old'");
	}

	// 更新缓存
	require_once libfile('function/cache');
	updatecache('settings');
	loadcache('settings', true);

	return $result;
}

/**
 *
 * 检查云服务状态
 * @return mixed 已开通云cloud，只开通漫游需要升级manyou，未开通false, 状态异常showmessage
 */
function checkcloudstatus($showMessage = true) {
	global $_DCACHE;

	$res = false;

	$cloudStatus = $_DCACHE['settings']['cloud_status'];
	$site_id = $_DCACHE['settings']['my_siteid'];
	$site_key = $_DCACHE['settings']['my_sitekey'];

	// 已开通云
	if($site_id && $site_key) {
		switch($cloudStatus) {
		case 1:
			$res = 'cloud';
			break;
		case 2:
			$res = 'unconfirmed';
			break;
		default:
			$res = 'upgrade';
		}
	// 未注册状态
	} elseif(!$cloudStatus && !$site_id && !$site_key) {
		$res = 'register';
	} elseif($showMessage) {
		if(defined('IN_ADMINCP')) {
			cloud_cpmsg('cloud_status_error', '', 'error');
		} else {
			showmessage('cloud_status_error');
		}
	}

	return $res;
}

/**
 *
 * 根据站点Key生成验证串以及完整URL getx参数
 * @param array $params get或者post的参数
 * @param boolean $isEncode 不将 & 编码成 &amp;
 * @param boolean $isCamelCase 站点id、用户id等是否采用驼峰命名规则
 *
 * @return string $url的参数
 */
function generateSiteSignUrl($params = array(), $isEncode = true, $isCamelCase = false) {
	global $_DCACHE;

	$ts = TIMESTAMP;
	$sId = $_DCACHE['settings']['my_siteid'];
	$sKey = $_DCACHE['settings']['my_sitekey'];
	$uid = $GLOBALS['discuz_uid'];

	if(!is_array($params)) {
		$params = array();
	}

	unset($params['sig'], $params['ts']);

	if ($isCamelCase) {
		$params['sId'] = $sId;
		$params['sSiteUid'] = $uid;
	} else {
		$params['s_id'] = $sId;
		$params['s_site_uid'] = $uid;
	}

	ksort($params);

	$str = buildArrayQuery($params, '', $isEncode);
	$sig = md5(sprintf('%s|%s|%s', $str, $sKey, $ts));

	$params['ts'] = $ts;
	$params['sig'] = $sig;

	$url = buildArrayQuery($params, '', $isEncode);
	return $url;
}

/**
 *
 * 注册云平台，调用云平台接口产生sId和sKey
 * @param string $cloudApiIp api.discuz.qq.com的IP地址
 *
 * @return array sId和sKey
 */
function registercloud($cloudApiIp = '') {

	require_once DISCUZ_ROOT.'./api/manyou/class/CloudClient.php';
	require_once libfile('function/cache');

	$cloudClient = new Discuz_Cloud_Client();
	$returnData = $cloudClient->register();

	// 第一次出错，有IP就重试一次，没有IP提示DNS出错
	if($cloudClient->errno == 1 && $cloudApiIp) {

		$cloudClient->setCloudIp($cloudApiIp);
		$returnData = $cloudClient->register();

		// 按IP重试期间如果没有发生错误，则将IP写入setting
		if (!$cloudClient->errno) {
			inserttable('settings', array('variable' => 'cloud_api_ip', 'value' => $cloudApiIp), 0, true);
		}
	}

	// 处理结果
	if($cloudClient->errno) {
		$result = array('errCode' => $cloudClient->errno, 'errMessage' => $cloudClient->errmsg);
	} else {

		$sId = intval($returnData['sId']);
		$sKey = addslashes($returnData['sKey']);

		if ($sId && $sKey) {

			$GLOBALS['db']->query("REPLACE INTO {$GLOBALS['tablepre']}settings (variable, value) VALUES
						('my_siteid', '$sId'),
						('my_sitekey', '$sKey'),
						('cloud_status', '2')");
			updatecache('settings');
			loadcache('settings', true);

			$result = array('errCode' => 0);
		} else {
			// 结果不正确，提示出错
			$result = array('errCode' => 2);
		}
	}

	return $result;
}

function upgrademanyou($cloudApiIp = '') {

	require_once DISCUZ_ROOT.'./api/manyou/class/CloudClient.php';

	$cloudClient = new Discuz_Cloud_Client();
	$returnData = $cloudClient->sync();

	// 第一次出错，有IP就重试一次，没有IP提示DNS出错
	if($cloudClient->errno == 1 && $cloudApiIp) {

		$cloudClient->setCloudIp($cloudApiIp);
		$returnData = $cloudClient->sync();

		// 按IP重试期间如果没有发生错误，则将IP写入setting
		if (!$cloudClient->errno) {
			inserttable('settings', array('variable' => 'cloud_api_ip', 'value' => $cloudApiIp), 0, true);
			require_once libfile('function/cache');
			updatecache('settings');
			loadcache('settings', true);
		}
	}

	// 处理结果
	if($cloudClient->errno) {
		$result = array('errCode' => $cloudClient->errno, 'errMessage' => $cloudClient->errmsg);
	} else {
		$result = array('errCode' => 0);
	}

	return $result;
}

/**
 *
 * 获取云应用列表
 * @params boolean $usecache 是否从缓存中获取信息
 * @return array 列表信息二维数组
 *  + name connect 应用名称
 *    + name 应用名称
 *    + status 开启状态
 *  + name search 应用名称
 *    + name 应用名称
 *    + status 开启状态
 */
function getcloudapps($usecache = true) {
	global $_DCACHE;

	$apps = array();

	if($usecache) {
		loadcache('settings');
		$apps = $_DCACHE['settings']['cloud_apps'];
	} else {
		$apps = $GLOBALS['db']->result_first("SELECT value FROM {$GLOBALS['tablepre']}settings WHERE variable='cloud_apps'");
	}

	if($apps && !is_array($apps)) {
		$apps = unserialize($apps);
	}

	if(!$apps) {
		$apps = array();
	}

	return $apps;
}

/**
 *
 * 获取单个云应用状态
 * @params string $appName 云应用英文名称
 * @params boolean $usecache 是否从缓存中获取信息
 * @return boolean true | false 程序会将normal状态返回true，其他状态均返回false
 */
function getcloudappstatus($appName, $usecache = true) {

	$res = false;

	$apps = getcloudapps($usecache);
	if($apps && $apps[$appName]) {
		$res = ($apps[$appName]['status'] == 'normal');
	}

	return $res;
}

/**
 *
 * 设置云应用状态
 * @params string $appName 云应用英文名称
 * @params integer $status 状态( normal | audit | close | disable | pause)
 * @params boolean $usecache 是否从缓存中获取信息
 * @params boolean $updatecache 是否重建缓存
 * @params boolean true | false
 */
function setcloudappstatus($appName, $status, $usecache = true, $updatecache = true) {

	// 云应用设置状态时，本地客户端没有对应的函数，认为没有该应用客户端
	$method = 'setcloudappstatus_'.$appName;
	if(!function_exists($method)) {
		return false;
	}

	// 回调各应用自身的处理方法
	if(!@call_user_func($method, $appName, $status)) {
		return false;
	}

	// 更新云应用列表状态
	$apps = getcloudapps($usecache);

	$app = array('name' => $appName, 'status' => $status);

	$apps[$appName] = $app;
	$apps = addslashes(serialize($apps));

	$res = inserttable('settings', array('variable' => 'cloud_apps', 'value' => $apps), 0, true);

	if(!empty($updatecache)) {
		// 更新缓存统一在云基础平台处理，不在各应用更新
		require_once libfile('function/cache');
		updatecache('settings');
		loadcache('settings', true);
		// updatecache(array('plugins', 'settings', 'styles'));
	}

	return $res;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_manyou($appName, $status) {

	$available = 0;
	if($status == 'normal') {
		$available = 1;
	}
	$res = inserttable('settings', array('variable' => 'my_status', 'value' => $available), 0, true);
	// 更新插件状态
	if(!updatecloudpluginavailable('manyou', $available)) {
		return false;
	}
	return $res;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_connect($appName, $status) {

	$available = 0;
	if($status == 'normal') {
		$available = 1;
	}

	$connect_setting = $GLOBALS['db']->result_first("SELECT value FROM {$GLOBALS['tablepre']}settings WHERE variable='connect'");
	if($connect_setting && !is_array($connect_setting)) {
		$connect_setting = unserialize($connect_setting);
	}

	if(!$connect_setting) {
		$connect_setting = array();
	}
	$connect_setting['allow'] = $available;

	$connectnew = addslashes(serialize($connect_setting));

	$res = inserttable('settings', array('variable' => 'connect', 'value' => $connectnew), 0, true);

	// 更新插件状态
	if(!updatecloudpluginavailable('qqconnect', $available)) {
		return false;
	}

	return $res;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_security($appName, $status) {

	return true;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_stats($appName, $status) {
	$available = 0;
	if($status == 'normal') {
		$available = 1;
	}

	if(!updatecloudpluginavailable('cloudstats', $available)) {
		return false;
	}

	return true;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_search($appName, $status) {

	$available = 0;
	if($status == 'normal') {
		$available = 1;
	}

	if (!is_array($my_search_data)) {
		$my_search_data = array();
	}
	if($available) {
		/*
		require_once DISCUZ_ROOT.'./api/manyou/class/CloudClient.php';
		SearchHelper::allowSearchForum();
		*/
	}

	// 更新插件状态
	if(!updatecloudpluginavailable('search', $available)) {
		return false;
	}

	return true;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_smilies($appName, $status) {

	$available = 0;
	if($status == 'normal') {
		$available = 1;
	}

	if(!updatecloudpluginavailable('soso_smilies', $available)) {
		return false;
	}

	return true;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_qqgroup($appName, $status) {

	return true;
}

/**
 *
 * 兼容处理，设置各个应用的状态，由setcloudappstatus调用，非外层调用
 */
function setcloudappstatus_union($appName, $status) {

	return true;
}

/**
 *
 * 更新云系统插件可用状态
 */
function updatecloudpluginavailable($identifier, $available) {

	$available = intval($available);
	$identifier = addslashes(strval($identifier));

    // 如果出入可用，则安装插件
    if ($available == 1) {
        cloud_installplugin($identifier);
    }
	$pluginId = $GLOBALS['db']->result_first("SELECT pluginid FROM {$GLOBALS['tablepre']}plugins WHERE identifier='$identifier'");
	if($pluginId) {
		updatetable('plugins', array('available' => $available), array('pluginid' => $pluginId));
	}
	if ($available != 1) {
    	@chdir(DISCUZ_ROOT);
    	require_once libfile('function/cache');
    	updatecache('settings');
    	updatecache('plugins');
	}

    return true;
}

/**
 * header跳转
 */
function headerLocation($url) {
	ob_end_clean();
	ob_start();
	@header('location: '.$url);
	exit;
}

/**
 * http_build_query
 */
function buildArrayQuery($data, $key = '', $isEncode = false) {

	if ($key) {
		$query =  array($key => $data);
	} else {
		$query = $data;
	}

	if ($isEncode) {
		return cloud_http_build_query($query, '', '&');
	} else {
		return cloud_http_build_query($query);
	}
}

/**
 * cloud_http_build_query
 * PHP 5.0 ~ 5.1.1 版本有问题
 */
function cloud_http_build_query($data, $numeric_prefix='', $arg_separator='', $prefix='') {
	$render = array();
	if (empty($arg_separator)) {
		$arg_separator = @ini_get('arg_separator.output');
		empty($arg_separator) && $arg_separator = '&';
	}
	foreach ((array) $data as $key => $val) {
		if (is_array($val) || is_object($val)) {
			$_key = empty($prefix) ? "{$key}[%s]" : sprintf($prefix, $key) . "[%s]";
			$_render = cloud_http_build_query($val, '', $arg_separator, $_key);
			if (!empty($_render)) {
				$render[] = $_render;
			}
		} else {
			if (is_numeric($key) && empty($prefix)) {
				$render[] = urlencode("{$numeric_prefix}{$key}") . "=" . urlencode($val);
			} else {
				if (!empty($prefix)) {
					$_key = sprintf($prefix, $key);
					$render[] = urlencode($_key) . "=" . urlencode($val);
				} else {
					$render[] = urlencode($key) . "=" . urlencode($val);
				}
			}
		}
	}
	$render = implode($arg_separator, $render);
	if (empty($render)) {
		$render = '';
	}
	return $render;
}

function cloud_get_api_version() {
	return '0.4';
}

/**
 *
 *  兼容X2的 cpmsg
 */
function cloud_cpmsg($message, $url = '', $type = '', $values = array(), $extra = '', $halt = TRUE) {
	global $lang;
	include language('admincp.cloud');

	$lang = array_merge($lang, $extend_lang);

	$message = cplang($message, $values);

	if($url && strpos($url, 'action=') === 0) {
		$url = ADMINSCRIPT.'?'.$url;
	}

	cpmsg($message, $url, $type, $extra, $halt);
}

//note 添加数据
if(!function_exists('inserttable')) {
	function inserttable($tablename, $insertsqlarr, $returnid=0, $replace = false, $silent=0) {
		$insertkeysql = $insertvaluesql = $comma = '';
		foreach ($insertsqlarr as $insert_key => $insert_value) {
			$insertkeysql .= $comma.'`'.$insert_key.'`';
			$insertvaluesql .= $comma.'\''.$insert_value.'\'';
			$comma = ', ';
		}
		$method = $replace?'REPLACE':'INSERT';
		$res = $GLOBALS['db']->query($method.' INTO '.$GLOBALS['tablepre'].$tablename.' ('.$insertkeysql.') VALUES ('.$insertvaluesql.')', $silent?'SILENT':'');
		if($returnid && !$replace) {
			return $GLOBALS['db']->insert_id();
		}
		return $res;
	}
}

//note 更新数据
if(!function_exists('updatetable')) {
	function updatetable($tablename, $setsqlarr, $wheresqlarr, $silent=0) {
		$setsql = $comma = '';
		foreach ($setsqlarr as $set_key => $set_value) {
			$setsql .= $comma.'`'.$set_key.'`'.'=\''.$set_value.'\'';
			$comma = ', ';
		}
		$where = $comma = '';
		if(empty($wheresqlarr)) {
			$where = '1';
		} elseif(is_array($wheresqlarr)) {
			foreach ($wheresqlarr as $key => $value) {
				$where .= $comma.'`'.$key.'`'.'=\''.$value.'\'';
				$comma = ' AND ';
			}
		} else {
			$where = $wheresqlarr;
		}
		return $GLOBALS['db']->query('UPDATE '.$GLOBALS['tablepre'].$tablename.' SET '.$setsql.' WHERE '.$where, $silent?'SILENT':'');
	}
}

//note 去掉slassh
if(!function_exists('sstripslashes')) {
	function sstripslashes($string) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = sstripslashes($val);
			}
		} else {
			$string = stripslashes($string);
		}
		return $string;
	}
}

if(!function_exists('dstripslashes')) {
	function dstripslashes($string) {
		return sstripslashes($string);
	}
}

function cloud_installplugin($name, $repair = 0) {
    require_once libfile('function/cloud_plugin');
    if ($repair == 1) {
    	@chdir(DISCUZ_ROOT);
    	require_once libfile('function/cache');
    	updatecache('settings');
    	updatecache('plugins');
    }
    return _installplugin($name, $repair);
}

?>
