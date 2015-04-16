<?php
/*
	[Discuz!] (C)2001-2099 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: CloudClient.php 61 2011-09-15 09:03:50Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once DISCUZ_ROOT.'./include/cloud.func.php';

/**
 * ��ƽ̨�ӿڿͻ����࣬Discuz! ͨ�������з���������ƽ̨�ӿ�
 */
class Cloud_Client {

	/**
	 * cloudApiIp
	 *
	 * @ignore
	 */
	var $cloudApiIp = '';

	/**
	 * sId
	 *
	 * @ignore
	 */
	var $sId = 0;

	/**
	 * sKey
	 *
	 * @ignore
	 */
	var $sKey = '';

	/**
	 * url
	 *
	 * @ignore
	 */
	var $url = '';

	/**
	 * format
	 *
	 * @ignore
	 */
	var $format = '';

	/**
	 * ts
	 *
	 * @ignore
	 */
	var $ts = 0;

	/**
	 * debug
	 *
	 * @ignore
	 */
	var $debug = false;

	/**
	 * errno
	 *
	 * @ignore
	 */
	var $errno = 0;

	/**
	 * errmsg
	 *
	 * @ignore
	 */
	var $errmsg = '';

	/**
	 * Discuz! ��ƽ̨�ͻ��˹��캯��
	 *
	 * @param integer $sId վ�� ID�����û�п�Ϊ��
	 * @param string $sKey վ��ͨ�� Key�����û�п�Ϊ��
	 *
	 */
	function Cloud_Client($sId = 0, $sKey = '') {

			$this->sId = intval($sId);
			$this->sKey = $sKey;
			$this->url = 'http://api.discuz.qq.com/site.php';
			$this->format = 'php';
			$this->ts = time();
	}

	/**
	 * _callMethod
	 * ���󷽷�
	 *
	 * @ignore
	 * @param  mixed $method
	 * @param  mixed $args
	 * @return void
	 */
	function _callMethod($method, $args) {
		$this->errno = 0;
		$this->errmsg = '';
		$url = $this->url;

		$params = array();
		$params['sId'] = $this->sId;
		$params['method'] = $method;
		$params['format'] = strtoupper($this->format);

		$params['sig'] = $this->_generateSig($params, $method, $args);
		$params['ts'] = $this->ts;

		$data = $this->_createPostString($params, $args, true);
		list($errno, $result) = $this->_postRequest($url, $data);
		if ($this->debug) {
			$this->_message('receive data ' . htmlspecialchars($result) . "\n\n");
		}

		if (!$errno && $result) {
			$result = @unserialize($result);
			if(is_array($result) && array_key_exists('result', $result)) {
				if ($result['errCode']) {
					$this->errno = $result['errCode'];
					$this->errmsg = $result['errMessage'];
					// throw new Restful_Exception($result['errMessage'], $result['errCode']);
					return false;
				} else {
					return $result['result'];
				}
			} else {
				return $this->_unknowErrorMessage();
			}
		} else {
			return $this->_unknowErrorMessage();
		}
	}

	function _unknowErrorMessage() {
		$this->errno = 1;
		$this->errmsg = 'An unknown error occurred. May be DNS Error. ';
		return false;
	}

	/**
	 * _generateSig
	 * sig���ɷ���
	 *
	 * @ignore
	 * @param  mixed $params
	 * @param  mixed $method
	 * @param  mixed $args
	 * @return void
	 */
	function _generateSig(&$params, $method, $args) {
		$str = $this->_createPostString($params, $args, true);
		if ($this->debug) {
			$this->_message('sig string: ' . $str . '|' . $this->sKey . '|' . $this->ts . "\n\n");
		}

		return md5(sprintf('%s|%s|%s', $str, $this->sKey, $this->ts));
	}

	/**
	 * _createPostString
	 * ����sig �� post ���ݴ�
	 *
	 * @ignore
	 * @param  mixed $params
	 * @param  mixed $args
	 * @param  mixed $isEncode
	 * @return void
	 */
	function _createPostString($params, $args, $isEncode = false) {
		ksort($params);
		$str = '';
		foreach ($params as $k=>$v) {
			$str .= $k . '=' . $v . '&';
		}

		ksort($args);
		$str .= $this->_buildArrayQuery($args, 'args', $isEncode);
		return $str;
	}

	/**
	 * _postRequest
	 *
	 * @ignore
	 * @param  string $url
	 * @param  string $data
	 * @return result
	 */
	function _postRequest($url, $data, $ip = '') {
		if ($this->debug) {
			$this->_message('post params: ' . $data. "\n\n");
		}

		$ip = $this->cloudApiIp;

		$result = $this->_fsockopen($url, 4096, $data, '', false, $ip, 5);
		return array(0, $result);
	}

	/**
	 * _fsockopen
	 * @ignore
	 * @params string $url URL
	 * @params integer $limit �������ݳ�������
	 * @params string $post POST����
	 * @params string $cookie cookie����
	 * @params string $ip HOST��Ӧ��IP��ַ
	 * @params integer $timeout ��ʱʱ��
	 * @params boolean $block ģ�鷽ʽ
	 *
	 */
	function _fsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
		return dfopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block);
	}

	/**
	 * _message
	 *
	 * @ignore
	 * @param  mixed $msg
	 * @return void
	 */
	function _message($msg) {
		echo $msg;
	}

	/**
	 * _buildArrayQuery
	 * ��������HTTP QUERY����
	 *
	 * @ignore
	 * @param  mixed $data
	 * @param  string $key
	 * @param  mixed $isEncode
	 * @return void
	 */
	function _buildArrayQuery($data, $key = '', $isEncode = false) {
		return buildArrayQuery($data, $key, $isEncode);
	}

	/**
	 * Discuz! ����ƽ̨�ӿڣ�ע��վ����Ϣ
	 *
	 * @param string $sName վ������
	 * @param string $sSiteKey ��Ʒ Key (վ��Ψһ ID)
	 * @param string $sUrl վ��url ������ http://��ͷ.
	 * @param string $sCharset վ��ʹ���ַ��� utf8 gbk big5
	 * @param string $sTimeZone վ��ʹ��ʱ��
	 * @param string $sUCenterUrl վ�� UCenter ��ַ
	 * @param string $sLanguage վ��ʹ������
	 * @param string $sProductType (Discuz!, Discuz!X ...) ��Ʒ����
	 * @param string $sProductVersion (7.2, 1.5 ...) ��Ʒ�汾
	 * @param integer $sTimestamp վ�������ʱ��
	 * @param string $sApiVersion վ����ƽ̨�ͻ��˰汾
	 * @param integer $sSiteUid վ����վ���ϵ�Uid
	 *
	 * @throws Cloud_Site_Exception
	 *  + 1 ������һ��δ֪�����������ύ����
	 *  + 2 Ŀǰ���񲻿��á�
	 *  + 3 IP ��ַ����ֹ��
	 *  + 100 ��Ч�Ĳ���
	 *  + 101 ��Ч�� API Key��
	 *  + 102 ��Ч��ǩ��ֵ
	 *
	 *  + 111 xxx������ʽ���Ϸ�.
	 *  + 112 վ�� Url �ظ�
	 *  + 114 վ���Ʒ Key �ظ�
	 *
	 * @return array ����վ�� ID ��վ�� Key
	 *  + sId վ�� ID
	 *  + sKey վ��ͨ�� Key
	 *
	 * @source
	 * <code>
	 *
	 * $cloudClient = new Cloud_Client();
	 * $cloudInfo = $cloudClient->register($sName, $sSiteKey, $sUrl, $sCharset,
	 *                                     $sTimeZone, $sUCenterUrl, $sLanguage,
	 *                                     $sProductType, $sProductVersion
	 *                                     $sTimestamp, $sApiVersion, $sSiteUid);
	 * echo $cloudInfo['sId'];
	 * echo $cloudInfo['sKey'];
	 *
	 * </code>
	 *
	 */
	function register($sName, $sSiteKey, $sUrl, $sCharset,
					  $sTimeZone, $sUCenterUrl, $sLanguage,
					  $sProductType, $sProductVersion,
					  $sTimestamp, $sApiVersion, $sSiteUid, $sProductRelease) {

		return $this->_callMethod('site.register', array('sName' => $sName,
														 'sSiteKey' => $sSiteKey,
														 'sUrl' => $sUrl,
														 'sCharset' => $sCharset,
														 'sTimeZone' => $sTimeZone,
														 'sUCenterUrl' => $sUCenterUrl,
														 'sLanguage' => $sLanguage,
														 'sProductType' => $sProductType,
														 'sProductVersion' => $sProductVersion,
														 'sTimestamp' => $sTimestamp,
														 'sApiVersion' => $sApiVersion,
														 'sSiteUid' => $sSiteUid,
														 'sProductRelease' => $sProductRelease
												   )
								  );
	}

	/**
	 * Discuz!����ƽ̨�ӿ�ͬ��վ����Ϣ
	 *
	 * @param string $sName վ������
	 * @param string $sSiteKey ��Ʒ Key (վ��ΨһID)
	 * @param string $sUrl վ�� Url ������ http:// ��ͷ.
	 * @param string $sCharset վ��ʹ���ַ��� utf8 gbk big5
	 * @param string $sTimeZone վ��ʹ��ʱ��
	 * @param string $sUCenterUrl վ�� UCenter ��ַ
	 * @param string $sLanguage վ��ʹ������
	 * @param string $sProductType (Discuz!, Discuz!X ...) ��Ʒ����
	 * @param string $sProductVersion (7.2, 1.5 ...) ��Ʒ�汾
	 * @param integer $sTimestamp վ�������ʱ��
	 * @param string $sApiVersion վ����ƽ̨�ͻ��˰汾
	 * @param integer $sSiteUid վ����վ���ϵ�Uid
	 *
	 * @throws Cloud_Site_Exception
	 *  + 1 ������һ��δ֪�����������ύ����
	 *  + 2 Ŀǰ���񲻿��á�
	 *  + 3 IP ��ַ����ֹ��
	 *  + 100 ��Ч�Ĳ���
	 *  + 101 ��Ч�� API Key��
	 *  + 102 ��Ч��ǩ��ֵ
	 *
	 *  + 111 xxx������ʽ���Ϸ�
	 *  + 121 ��ǰվ�㲻����
	 *
	 * @return boolean true | false ���³ɹ�|ʧ��
	 *
	 * @source
	 * <code>
	 *
	 * $cloudClient = new Cloud_Client($sId, $sKey);
	 * $cloudSync = $cloudClient->sync($sName, $sSiteKey, $sUrl, $sCharset,
	 *                                 $sTimeZone, $sUCenterUrl, $sLanguage,
	 *                                 $sProductType, $sProductVersion
	 *                                 $sTimestamp, $sApiVersion, $sSiteUid);
	 * if($cloudSync) {
	 *  // do something
	 * }
	 *
	 * </code>
	 */
	function sync($sName, $sSiteKey, $sUrl, $sCharset,
				  $sTimeZone, $sUCenterUrl, $sLanguage,
				  $sProductType, $sProductVersion,
				  $sTimestamp, $sApiVersion, $sSiteUid, $sProductRelease) {

		return $this->_callMethod('site.sync', array('sId' => $this->sId,
													 'sName' => $sName,
													 'sSiteKey' => $sSiteKey,
													 'sUrl' => $sUrl,
													 'sCharset' => $sCharset,
													 'sTimeZone' => $sTimeZone,
													 'sUCenterUrl' => $sUCenterUrl,
													 'sLanguage' => $sLanguage,
													 'sProductType' => $sProductType,
													 'sProductVersion' => $sProductVersion,
													 'sTimestamp' => $sTimestamp,
													 'sApiVersion' => $sApiVersion,
													 'sSiteUid' => $sSiteUid,
													 'sProductRelease' => $sProductRelease
													 )
								  );
	}

	/**
	 * Discuz! ����ƽ̨�ӿ���������վ�� ID ��վ�� Key
	 *
	 * @throws Cloud_Site_Exception
	 *  + 1 ������һ��δ֪�����������ύ����
	 *  + 2 Ŀǰ���񲻿��á�
	 *  + 3 IP ��ַ����ֹ��
	 *  + 100 ��Ч�Ĳ���
	 *  + 101 ��Ч�� API Key��
	 *  + 102 ��Ч��ǩ��ֵ
	 *
	 *  + 111 xxx������ʽ���Ϸ�.
	 *  + 121 ��ǰվ�㲻����
	 *
	 * @return array ����վ�� ID ��վ��ͨ�� Key
	 *  + sId վ�� ID
	 *  + sKey վ��ͨ�� Key
	 *
	 * @source
	 * <code>
	 *
	 * $cloudClient = new Cloud_Client($sId, $sKey);
	 * $cloudReset = $cloudClient->resetKey();
	 * echo $cloudReset['sId'];
	 * echo $cloudReset['sKey'];
	 *
	 * </code>
	 */
	function resetKey() {

		return $this->_callMethod('site.resetKey', array('sId' => $this->sId));
	}

	function resume($sUrl, $sCharset, $sProductType, $sProductVersion) {

		return $this->_callMethod('site.resume', array(
																			   'sUrl' => $sUrl,
																			   'sCharset' => $sCharset,
																			   'sProductType' => $sProductType,
																			   'sProductVersion' => $sProductVersion
																			   )
												 );
	}

	function QQGroupMiniportal($topic, $normal, $gIds = array()) {

		return $this->_callMethod('qqgroup.miniportal', array('topic' => $topic, 'normal' => $normal, 'gIds' => $gIds));
	}

	function connectSync($qzoneLikeQQ, $mblogQQ) {

		return $this->_callMethod('connect.sync', array('qzoneLikeQQ' => $qzoneLikeQQ, 'mblogFollowQQ' => $mblogQQ));
	}

}

class Discuz_Cloud_Client {

	/**
	 * debug
	 *
	 * @ignore
	 */
	var $debug = false;

	/**
	 * errno
	 *
	 * @ignore
	 */
	var $errno = 0;

	/**
	 * errmsg
	 *
	 * @ignore
	 */
	var $errmsg = '';

	var $Client = null;

	var $my_status = false;
	var $cloud_status = false;

	var $siteId = '';
	var $siteKey = '';
	var $siteName = '';
	var $uniqueId = '';
	var $siteUrl = '';
	var $charset = '';
	var $timeZone = 0;
	var $UCenterUrl = '';
	var $language = '';
	var $productType = '';
	var $productVersion = '';
	var $productRelease = '';
	var $timestamp = 0;
	var $apiVersion = '';
	var $siteUid = 0;

	function Discuz_Cloud_Client($debug = false) {
		global $_DCACHE;
		loadcache('settings');

		require_once DISCUZ_ROOT.'./discuz_version.php';

		$this->my_status = !empty($_DCACHE['settings']['my_status']) ? $_DCACHE['settings']['my_status'] : '';
		$this->cloud_status = !empty($_DCACHE['settings']['cloud_status']) ? $_DCACHE['settings']['cloud_status'] : '';

		$this->siteId = !empty($_DCACHE['settings']['my_siteid']) ? $_DCACHE['settings']['my_siteid'] : '';
		$this->siteKey = !empty($_DCACHE['settings']['my_sitekey']) ? $_DCACHE['settings']['my_sitekey'] : '';
		$this->siteName = !empty($_DCACHE['settings']['bbname']) ? $_DCACHE['settings']['bbname'] : '';
		$uniqueidsql = sprintf("SELECT value FROM %s WHERE variable='%s'", $GLOBALS['tablepre'].'settings', 'siteuniqueid');
		$this->uniqueId = $siteuniqueid = $GLOBALS['db']->result_first($uniqueidsql);
		$this->siteUrl = $GLOBALS['boardurl'];
		$this->charset = !empty($GLOBALS['charset']) ? $GLOBALS['charset'] : 'GBK';
		$this->timeZone = !empty($_DCACHE['settings']['timeoffset']) ? $_DCACHE['settings']['timeoffset'] : '';
		$this->UCenterUrl = defined('UC_API') ? rtrim(UC_API, '/').'/' : '';
		$this->language = $GLOBALS['language'] ? $GLOBALS['language'] : 'zh_CN';
		$this->productType = 'DISCUZ';
		$this->productVersion = defined('DISCUZ_VERSION') ? DISCUZ_VERSION : '';
		$this->productRelease = defined('DISCUZ_RELEASE') ? DISCUZ_RELEASE : '';
		$this->timestamp = time();

		$this->apiVersion = cloud_get_api_version();

		$this->siteUid = $GLOBALS['discuz_uid'];

		$this->Client = new Cloud_Client($this->siteId, $this->siteKey);

		if ($debug) {
			$this->Client->debug = true;
			$this->debug = true;
		}

		if ($_DCACHE['settings']['cloud_api_ip']) {
			$this->setCloudIp($_DCACHE['settings']['cloud_api_ip']);
		}

	}

	function register() {

		$data = $this->Client->register($this->siteName, $this->uniqueId, $this->siteUrl, $this->charset,
										$this->timeZone, $this->UCenterUrl, $this->language,
										$this->productType, $this->productVersion,
										$this->timestamp, $this->apiVersion, $this->siteUid, $this->productRelease);

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

	function sync() {

		$data = $this->Client->sync($this->siteName, $this->uniqueId, $this->siteUrl, $this->charset,
									$this->timeZone, $this->UCenterUrl, $this->language,
									$this->productType, $this->productVersion,
									$this->timestamp, $this->apiVersion, $this->siteUid, $this->productRelease);

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

	function resetKey() {

		$data = $this->Client->resetKey();

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

	function resume() {

		// �޸�����ʹ��UTF-8����
		$data = $this->Client->resume($this->siteUrl, 'UTF-8', $this->productType, $this->productVersion);

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

	function setCloudIp($ip) {
		$this->Client->cloudApiIp = $ip;
	}

	function QQGroupMiniportal($topic, $normal, $gIds = array()) {

		$data = $this->Client->QQGroupMiniportal($topic, $normal, $gIds);

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

	function connectSync($qzoneQQ, $mblogQQ) {
		$data = $this->Client->connectSync($qzoneQQ, $mblogQQ);

		$this->errno = $this->Client->errno;
		$this->errmsg = $this->Client->errmsg;

		return $data;
	}

}

?>