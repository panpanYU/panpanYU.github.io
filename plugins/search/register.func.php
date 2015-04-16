<?php
/**
 * $Id: register.func.php 83 2011-09-20 08:26:06Z zhouguoqiang $
 */

define('SEARCH_URL', 'http://api.manyou.com/uchome.php');
require_once DISCUZ_ROOT . './manyou/api/version.php';

function my_site_register($siteKey, $siteName, $siteUrl, $ucUrl, $maxPostId, $siteCharset, $siteTimeZone, $siteRealNameEnable, $siteRealAvatarEnable, $siteEnableSearch, $siteSearchInvitationCode, $siteEnableApp) {
	$siteName = urlencode($siteName);
	$postString = sprintf('action=%s&productType=DISCUZ&siteVersion=%s&myVersion=%s&siteKey=%s&siteName=%s&siteUrl=%s&ucUrl=%s&maxPostId=%d&siteCharset=%s&siteTimeZone=%s&siteRealNameEnable=%s&siteRealAvatarEnable=%s&siteEnableSearch=%s&siteSearchInvitationCode=%s&siteEnableApp=%s', 'siteRegister', DISCUZ_VERSION, MANYOU_API_VERSION, $siteKey, $siteName, rawurlencode($siteUrl), rawurlencode($ucUrl), $maxPostId, $siteCharset, $siteTimeZone, $siteRealNameEnable, $siteRealAvatarEnable, $siteEnableSearch, $siteSearchInvitationCode, $siteEnableApp);
	$response = dfopen(SEARCH_URL, 0, $postString, '', false);
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
	return $res;
}

function my_site_refresh($mySiteId, $siteName, $siteUrl, $ucUrl, $maxPostId, $siteCharset, $siteTimeZone, $siteEnableRealName, $siteEnableRealAvatar, $mySiteKey, $siteKey, $siteEnableSearch, $siteSearchInvitationCode, $siteEnableApp) {
	$key = $mySiteId.$siteName.$siteUrl.$ucUrl.$siteCharset.$siteTimeZone.$siteEnableRealName.$mySiteKey.$siteKey;
	$key = md5($key);
	$siteName = urlencode($siteName);
	$postString = sprintf('action=%s&productType=DISCUZ&siteVersion=%s&myVersion=%s&key=%s&mySiteId=%d&siteName=%s&siteUrl=%s&ucUrl=%s&maxPostId=%d&siteCharset=%s&siteTimeZone=%s&siteEnableRealName=%s&siteEnableRealAvatar=%s&siteKey=%s&siteEnableSearch=%s&siteSearchInvitationCode=%s&siteEnableApp=%s', 'siteRefresh', DISCUZ_VERSION, MANYOU_API_VERSION, $key, $mySiteId, $siteName, rawurlencode($siteUrl), rawurlencode($ucUrl), $maxPostId, $siteCharset, $siteTimeZone, $siteEnableRealName, $siteEnableRealAvatar, $siteKey, $siteEnableSearch, $siteSearchInvitationCode, $siteEnableApp);
	$response = dfopen(SEARCH_URL, 0, $postString, '', false);
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
	return $res;
}

function my_site_close($mySiteId, $mySiteKey) {
	$key = $mySiteId.$mySiteKey;
	$key = md5($key);
	$postString = sprintf('action=%s&key=%s&mySiteId=%d&services[]=search', 'siteClose', $key, $mySiteId);
	$response = dfopen(SEARCH_URL, 0, $postString, '', false);
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
	return $res['result'];
}

function my_site_invite_code() {
	$postString = 'action=inviteCode&app=search';
	$response = dfopen(SEARCH_URL, 0, $postString, '', false);
	echo $response;
}


?>
