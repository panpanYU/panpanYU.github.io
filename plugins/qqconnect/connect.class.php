<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: connect.class.php 82 2011-09-20 07:52:16Z houdelei $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_qqconnect_base {

	function init() {
		global $connect, $bbclosed;
		include_once template('qqconnect:module');
		if(!is_array($connect)) {
			$connect = (array)unserialize($connect);
		}
		if(!$connect['allow'] || $bbclosed || defined('IN_MOBILE')) {
			$this->allow = false;
			return;
		}
		$this->allow = $connect['allow'];
	}

	function common_base() {
		global $connect_setting, $discuz_uid, $_DCOOKIE, $db, $tablepre, $boardurl, $inajax;

		if (!isset($connect_setting)) {
			$connect_setting['api_url'] = 'http://openapi.qzone.qq.com';
			$connect_setting['connect_api_url'] = 'http://api.discuz.qq.com';
			$connect_setting['url'] = 'http://connect.discuz.qq.com';

			// QZone公共分享页面URL
			$connect_setting['qzone_public_share_url'] = 'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey';
			$connect_setting['weibo_public_share_url'] = 'http://v.t.qq.com/share/share.php';
			$connect_setting['referer'] = !$inajax && CURSCRIPT != 'member' ? $GLOBALS['BASESCRIPT'].($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '') : dreferer();

			// 微薄公共分享Appkey
			$connect_setting['weibo_public_appkey'] = 'ce7fb946290e4109bdc9175108b6db3a';

			// 新版Connect登录本地代理页
			$connect_setting['login_url'] = $boardurl.'connect.php?mod=login&op=init&referer='.urlencode($connect_setting['referer'] ? $connect_setting['referer'] : 'index.php');

			// 新版Connect本地Callback代理页
			$connect_setting['callback_url'] = $boardurl.'connect.php?mod=login&op=callback';

			// 更换QQ号登录本地代理页
			$connect_setting['change_qq_url'] = $boardurl.'connect.php?mod=login&op=change';

			// QC授权项对应关系
			$connect_setting['auth_fields'] = array(
											'is_user_info' => 1,
											'is_feed' => 2,
										   );
		}

		if($discuz_uid) {
			$member = $db->result_first("SELECT conisbind FROM {$tablepre}members WHERE uid='$discuz_uid'");
			dsetcookie('connect_is_bind', $member['conisbind'], 31536000);
			if(!$member['conisbind'] && $_DCOOKIE['connect_login']) {
				$_DCOOKIE['connect_login'] = 0;
				dsetcookie('connect_login');
			}
		}
	}

}

class plugin_qqconnect extends plugin_qqconnect_base {

	var $allow = false;

	function plugin_qqconnect() {
		$this->init();
		$this->common();
	}

	function common() {
		$this->common_base();
	}

	function global_header() {
	}

	function global_footer() {
		global $inshowmessage, $_DCOOKIE;

		if(!$this->allow) {
			return;
		}

		if(!empty($inshowmessage) || empty($_DCOOKIE['connect_js_name'])) {
			return;
		}

		if($_DCOOKIE['connect_js_name'] == 'user_bind') {
			require_once DISCUZ_ROOT.'./include/connect.func.php';
			$params = array('openid' => $_DCOOKIE['connect_uin']);
			return connect_user_bind_js($params);
		}
	}

	function _viewthread_share_method_output() {
		global $boardurl, $connect_setting, $connect, $groupid, $_DCACHE, $connect_post;

		require_once DISCUZ_ROOT.'./include/connect.func.php';

		// debug 走站外分享弹出新窗口
		// debug 第一页、一楼才有分享按钮
		if($GLOBALS['postlist']) {
			$firstpid = array_shift(array_keys($GLOBALS['postlist']));
		}
		if($GLOBALS['page'] == 1 && $firstpid && $GLOBALS['postlist'][$firstpid]['invisible'] == 0) {

			$connect_setting['thread_url'] = trim($boardurl, '/').$GLOBALS['temp'];

			// 公共分享页面URI
			$connect_setting['qzone_share_api'] = $connect_setting['qzone_public_share_url'].'?url='.urlencode($connect_setting['thread_url']);
			$connect_setting['pengyou_share_api'] = $connect_setting['qzone_public_share_url'].'?to=pengyou&url='.urlencode($connect_setting['thread_url']);
			$connect_setting['weibo_share_api'] = $connect_setting['weibo_public_share_url'];

			$first_post = daddslashes($GLOBALS['postlist'][$firstpid]);

			// 设置微博appkey
			$connect_setting['weibo_appkey'] = $connect_setting['weibo_public_appkey'];
			if($this->allow && $connect['mblog_app_key']) {
				$connect_setting['weibo_appkey'] = $connect['mblog_app_key'];
			}

			// 站外分享图片权限：有权限查看图片的用户
			include_once DISCUZ_ROOT.'./forumdata/cache/cache_usergroups.php';
			if ($_DCACHE['usergroups'][$groupid]['allowgetattach'] && $GLOBALS['thread']['price'] == 0) {
				if ($first_post['message']) {
					$connect_post['html_content'] = connect_parse_bbcode($first_post['message'], $first_post['fid'], $first_post['pid'], $first_post['htmlon'], $attach_images);
					if($attach_images && is_array($attach_images)) {
						$attach_images = array_slice($attach_images, 0, 3);
						$share_images = array();
						foreach ($attach_images as $attach_image) {
							$share_images[] = urlencode($attach_image);
						}
						$connect_post['share_images'] = implode('|', $share_images);
						unset($share_images);
					}
				}
			}
			return tpl_viewthread_share_method().$extrajs;
		}
	}

	function viewthread_useraction_output() {
		if(!$this->allow) {
			return;
		}
		return $this->_viewthread_share_method_output();
	}

}
?>
