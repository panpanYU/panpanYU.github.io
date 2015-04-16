<?php

/**
 *	  [Discuz!] (C)2001-2099 Comsenz Inc.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  $Id: admincp_cloud.php 22897 2011-05-30 09:19:11Z zhouguoqiang $
 *    临时cloud后台入口 待修改
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

@set_time_limit(600);
cpheader();

if (!defined('ADMINSCRIPT')) {
	define('ADMINSCRIPT', $BASESCRIPT);
}
if (!defined('TIMESTAMP')) {
	define('TIMESTAMP', $timestamp);
}

$adminscript = ADMINSCRIPT;
require_once DISCUZ_ROOT.'./discuz_version.php';
require_once DISCUZ_ROOT.'./include/cloud.func.php';
require_once libfile('function/cache');

$scrolltop = null;

$cloudDomain = 'http://cp.discuz.qq.com';
if($operation == 'doctor' || $operation == 'siteinfo') {
	// 得到云平台状态 诊断工具和站点信息检查状态，但忽略提示
	$cloudstatus = checkcloudstatus(false);
} else {
	$cloudstatus = checkcloudstatus();
}
$forceOpen = $_GET['force_open'] == 1 ? true : false;

if(!$operation || $operation == 'open') {

	if($cloudstatus == 'cloud' && !$forceOpen) {
		//已开通云
		cloud_cpmsg('cloud_turnto_applist', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=applist\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/JavaScript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=applist\'", 3000);</script>');
	} else {
		//未开通云服务
		if ($_GET['getConfirmInfo']) {
			ajaxshowheader();
			ajaxshowfooter();
		}

		$step = max(1, intval($_GET['step']));
		$type = $cloudstatus == 'upgrade' ? 'upgrade' : 'open';

		if($step == 1) {

			if($cloudstatus == 'upgrade' || ($cloudstatus == 'cloud' &&  $forceOpen)) {
				shownav('cloud', 'menu_cloud_upgrade');
				$itemtitle = cplang('menu_cloud_upgrade');
			} else {
				shownav('cloud', 'menu_cloud_open');
				$itemtitle = cplang('menu_cloud_open');
			}

			echo '
				<div class="itemtitle">
				<h3>'.$itemtitle.'</h3>
				<ul style="margin-right: 10px;" class="tab1"></ul>
				<ul class="stepstat" id="nav_steps"></ul>
				<ul class="tab1"></ul>
				</div>

				<div id="loading">
				<div id="loadinginner" style="display: block; padding: 100px 0; text-align: center; color: #999;">
				<img src="images/default/loading.gif" alt="loading..." style="vertical-align: middle;" /> '.$lang['cloud_page_loading'].'
				</div>
				</div>
				<div style="display:none;" id="title"></div>';

			showformheader('', 'onsubmit="return submitForm();"');

			if($cloudstatus == 'upgrade' || ($cloudstatus == 'cloud' &&  $forceOpen)) {
				echo '<div style="margin-top:10px; color: red; padding-left: 10px;" id="manyou_update_tips"></div>';
			}

			showtableheader('', '', 'id="mainArea" style="display:none;"');

			echo '
				<tr><td id="" style="border:none;"><div id="msg" class="tipsblock"></div></td></tr>
				<tr><td style="border-top:none;"><br />
				<label><input onclick="if(this.checked) {$(\'submit_submit\').disabled=false; $(\'submit_submit\').style.color=\'#000\';} else {$(\'submit_submit\').disabled=true; $(\'submit_submit\').style.color=\'#aaa\';}" id="agreeProtocal" class="checkbox" type="checkbox" checked="checked" value="1" />' . cplang('cloud_agree_protocal') . '</label><a id="protocal_url" href="javascript:;" target="_blank">' . cplang('read_protocal') . '</a>
				</td>
				</tr>';

			showsubmit('submit', 'cloud_will_open');
			showtablefooter();
			showformfooter();

			echo '<div id="siteInfo" style="display:none;;">';
			echo '<div class="fcontent">';
			echo '<h3 class="flb"><em>'.cplang('message_title').'</em><span><a href="javascript:;" class="flbc" onclick="hideWindow(\'open_cloud\');" title="'.cplang('close').'">'.cplang('close').'</a></span></h3>';

			showformheader('cloud&operation=open&step=2'.(($cloudstatus == 'cloud' && $forceOpen) ? '&force_open=1' : ''));

			echo '
				<div class="postbox">
				<div class="tplw">
				<p class="mbn tahfx">
				<strong>'.cplang('jump_to_cloud').'</strong><input type="hidden" id="cloud_api_ip" name="cloud_api_ip" value="" />
				</p>
				</div>
				</div>

				<div class="o pns"><input type="submit" class="btn" id="btn_1" value="'.cplang('continue').'" /></div>';

			showformfooter();
			echo '</div>';
			echo '</div>';

			echo <<<EOT
<link rel="stylesheet" type="text/css" href="images/admincp/cloud/cloud.css" />
<script type="text/javascript" src="images/admincp/cloud/cloud.js" charset="utf-8"></script>
<script type="text/javascript">
var cloudStatus = "$cloudstatus";
var disallowfloat = 'siteInfo';
var cloudApiIp = '';
var dialogHtml = '';
var getMsg = false;

var millisec = 10 * 1000; //10秒
var expirationText = '{$lang['cloud_time_out']}';
expirationTimeout = setTimeout("expiration()", millisec);
</script>
EOT;
			$introUrl = $cloudDomain.'/cloud/introduction';
			if($cloudstatus == 'upgrade') {
				$params = array('type' => 'upgrade');

				// 开启漫游，apps传入漫游信息
				if ($_DCACHE['settings']['my_status']) {
					$params['apps']['manyou'] = array('status' => true);
				}

				// 开启过搜索
				if (isset($_DCACHE['settings']['my_search_status'])) {

					$params['apps']['search'] = array('status' => !empty($_DCACHE['settings']['my_search_status']) ? true : false);

					$oldSiteId = empty($_DCACHE['settings']['my_siteid_old'])?'':$_DCACHE['settings']['my_siteid_old'];
					$oldSitekeySign = empty($_DCACHE['settings']['my_sitekey_sign_old'])?'':$_DCACHE['settings']['my_sitekey_sign_old'];

					// 同时开漫游和搜索，且搜索sId不同的情况
					if($oldSiteId && $oldSiteId != $_DCACHE['settings']['my_siteid'] && $oldSitekeySign) {
						$params['apps']['search']['oldSiteId'] = $oldSiteId;
						$params['apps']['search']['searchSig'] = $oldSitekeySign;
					}

				}

				// 开启过Connect
				if (isset($_DCACHE['settings']['connect'])) {
					$params['apps']['connect'] = array('status' => !empty($_DCACHE['settings']['connect']['allow']) ? true : false);

					$oldSiteId = empty($_DCACHE['settings']['connectsiteid'])?'':$_DCACHE['settings']['connectsiteid'];
					$oldSitekey = empty($_DCACHE['settings']['connectsitekey'])?'':$_DCACHE['settings']['connectsitekey'];

					// 同时开漫游和Connect，且Connect sId不同的情况
					if($oldSiteId && $oldSiteId != $_DCACHE['settings']['my_siteid'] && $oldSitekey) {
						$params['apps']['connect']['oldSiteId'] = $oldSiteId;
						// 不直接传siteKey，通过sig获取校验信息，与7.2 - X1.5转换程序中的处理一致
						$params['apps']['connect']['connectSig'] = substr(md5(substr(md5($oldSiteId.'|'.$oldSitekey), 0, 16)), 16, 16);
					}
				}

				// ADTAG
				$params['ADTAG'] = 'CP.DISCUZ.INTRODUCTION';

				// 生成Sig
				$signUrl = generateSiteSignUrl($params);
				$introUrl .= '?'.$signUrl;
			}

			echo '<script type="text/JavaScript" charset="UTF-8" src="'.$introUrl.'"></script>';

		} elseif($step == 2) {

			$statsUrl = $cloudDomain . '/cloud/stats/registerclick';
			echo '<script type="text/JavaScript" charset="UTF-8" src="'.$statsUrl.'"></script>';

			if($_DCACHE['settings']['my_siteid'] && $_DCACHE['settings']['my_sitekey']) {

				if($_DCACHE['settings']['my_status']) {
					// 开启漫游，调用调漫游的同步接口
					manyouSync();
				}

				// 同步云平台信息，升级流程
				$registerResult = upgrademanyou(trim($cloud_api_ip));

			} else {
				// 注册接口
				$registerResult = registercloud(trim($cloud_api_ip));
			}

			if($registerResult['errCode'] === 0) {
				$bindUrl = $cloudDomain.'/bind/index?'.generateSiteSignUrl(array('ADTAG' => 'CP.CLOUD.BIND.INDEX'));
				die('<script>top.location="' . $bindUrl . '";</script>');
			} elseif($registerResult['errCode'] == 1) {
				cloud_cpmsg('cloud_unknown_dns', '', 'error');
			} elseif($registerResult['errCode'] == 2) {
				cloud_cpmsg('cloud_network_busy', '', 'error', $registerResult);
			} else {
				$checkUrl = preg_match('/<a.+?>.+?<\/a>/i', $registerResult['errMessage'], $results);
				if($checkUrl) {
					foreach($results as $key => $result) {
						$registerResult['errMessage'] = str_replace($result, '{replace_' . $key . '}', $registerResult['errMessage']);
						$msgValues = array('replace_' . $key => $result);
					}
				}
				cloud_cpmsg($registerResult['errMessage'], '', 'error', $msgValues);
			}
		}
	}

//云应用列表
} elseif($operation == 'applist') {
	if($cloudstatus != 'cloud') {
		cloud_cpmsg('cloud_open_first', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/JavaScript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'", 3000);</script>');
	}

	$signParams = array('refer' => $boardurl, 'ADTAG' => 'CP.DISCUZ.APPLIST');
	$signUrl = generateSiteSignUrl($signParams);
	headerLocation($cloudDomain.'/cloud/appList/?'.$signUrl);

//站点信息 和 诊断工具
} elseif(in_array($operation, array('siteinfo', 'doctor'))) {

	require libfile("cloud/$operation", 'admincp');

//各个应用设置文件
} elseif(in_array($operation, array('manyou', 'connect', 'security', 'stats', 'search', 'smilies', 'qqgroup', 'union'))) {
    if ($first == 1) {
        updatecache('settings');
        updatecache('plugins');
    }
	if($cloudstatus != 'cloud') {
		cloud_cpmsg('cloud_open_first', '', 'succeed', array(), '<p class="marginbot"><a href="###" onclick="top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'" class="lightlink">'.cplang('message_redirect').'</a></p><script type="text/JavaScript">setTimeout("top.location = \''.ADMINSCRIPT.'?frames=yes&action=cloud&operation=open\'", 3000);</script>');
	}

	// 服务客户端状态检查
	$apps = getcloudapps();
	if(empty($apps) || empty($apps[$operation]) || $apps[$operation]['status'] == 'close') {
		cloud_cpmsg('cloud_application_close', 'action=cloud&operation=applist', 'error');
	}
	if($apps[$operation]['status'] == 'disable') {
		cloud_cpmsg('cloud_application_disable', 'action=cloud&operation=applist', 'error');
	}

	require libfile("cloud/$operation", 'admincp');

} else {
	exit('Access Denied');
}

function manyouSync() {

	require_once DISCUZ_ROOT.'./discuz_version.php';

	$setting = $GLOBALS['_DCACHE']['settings'];
	$my_url = 'http://api.manyou.com/uchome.php';

	$mySiteId = empty($setting['my_siteid'])?'':$setting['my_siteid'];
	$siteName = $setting['bbname'];
	$siteUrl = $GLOBALS['boardurl'];
	$ucUrl = defined('UC_API') ? rtrim(UC_API, '/').'/' : '';
	$siteCharset = $GLOBALS['charset'];
	$siteTimeZone = $setting['timeoffset'];
	$mySiteKey = empty($setting['my_sitekey'])?'':$setting['my_sitekey'];
	// $siteKey = DB::result_first("SELECT svalue FROM ".DB::table('common_setting')." WHERE skey='siteuniqueid'");
	$uniqueidsql = sprintf("SELECT value FROM %s WHERE variable='%s'", $GLOBALS['tablepre'].'settings', 'siteuniqueid');
	$siteKey = $siteuniqueid = $GLOBALS['db']->result_first($uniqueidsql);

	$siteLanguage = $GLOBALS['language'] ? $GLOBALS['language'] : 'zh_CN';
	$siteVersion = defined('DISCUZ_VERSION') ? DISCUZ_VERSION : '';

	$myVersion = cloud_get_api_version();

	$productType = 'DISCUZ';
	$siteRealNameEnable = '';
	$siteRealAvatarEnable = '';
	$siteEnableApp = intval($setting['my_status']);

	$key = $mySiteId . $siteName . $siteUrl . $ucUrl . $siteCharset . $siteTimeZone . $siteRealNameEnable . $mySiteKey . $siteKey;
	$key = md5($key);
	$siteTimeZone = urlencode($siteTimeZone);
	$siteName = urlencode($siteName);

	$register = false;
	$postString = sprintf('action=%s&productType=%s&key=%s&mySiteId=%d&siteName=%s&siteUrl=%s&ucUrl=%s&siteCharset=%s&siteTimeZone=%s&siteEnableRealName=%s&siteEnableRealAvatar=%s&siteKey=%s&siteLanguage=%s&siteVersion=%s&myVersion=%s&siteEnableApp=%s&from=cloud', 'siteRefresh', $productType, $key, $mySiteId, $siteName, $siteUrl, $ucUrl, $siteCharset, $siteTimeZone, $siteRealNameEnable, $siteRealAvatarEnable, $siteKey, $siteLanguage, $siteVersion, $myVersion, $siteEnableApp);

	$response = @dfopen($my_url, 0, $postString, '', false, $setting['my_ip']);
	$res = unserialize($response);
	if (!$response) {
		$res['errCode'] = 111;
		$res['errMessage'] = 'Empty Response';
		$res['result'] = $response;
	} elseif(!$res) {
		$res['errCode'] = 110;
		$res['errMessage'] = 'Error Response';
		$res['result'] = $response;
	}
	if($res['errCode']) {
		cloud_cpmsg('cloud_sync_failure', '', 'error', array('errCode'=>$res['errCode'], 'errMessage'=>$res['errMessage']));
	}
}

?>