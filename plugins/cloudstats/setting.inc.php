<?php
$extrahead .= '<script type="text/javascript"> var _speedMark = new Date(); </script>';

$_msgforward = (array)@unserialize($msgforward);
$_msgforward['messages'] = array_diff($_msgforward['messages'], array('post_reply_succeed', 'post_newthread_succeed'));
$msgforward = serialize($_msgforward);
$_DCACHE['settings']['msgforward'] = $msgforward;

class cloudstats_js {

	var $discuzParams = array();
	var $virtualDomain = '';
	var $extraParams = array();

	function _makejs() {
		global $inajax;

		$dzjs = $this->_makedzjs();
		$return = '';
		if(!$inajax) {
			$return = '&nbsp;&nbsp;<span id="tcss"></span><script type="text/javascript" src="http://tcss.qq.com/ping.js?v=1'.VERHASH.'" charset="utf-8"></script>';
		}
		$return .= '<script type="text/javascript" reload="1">setcookie("stats_qc_reg"); setcookie("stats_qc_login"); pgvMain('.$dzjs.');</script>';
		return $return;
	}

	function _makedzjs() {
		global $db, $tablepre, $discuz_user, $discuz_uid, $_DCACHE, $page, $tid, $fid, $charset, $inajax, $timestamp;


		$this->discuzParams['r2'] = $_DCACHE['settings']['my_siteid'];
		$this->discuzParams['ui'] = $discuz_uid ? $discuz_uid : 0;
		$this->discuzParams['rt'] = 'forum';

		 if ($discuz_uid) {
			  $yesterday =  $timestamp - $timestamp % 86400;
			  $reginfo = $GLOBALS['_DCOOKIE']['cloudstats_reginfo'];
			  list($uid, $regdate) = explode('|', $reginfo);;

			  if (!$reginfo || $discuz_uid != $uid) {
				   $regdate = $db->result_first("SELECT regdate FROM {$tablepre}members WHERE uid = '$discuz_uid'");
				   dsetcookie('cloudstats_reginfo', "$discuz_uid|$regdate");
			  }

			  if ($regdate < $yesterday) {
				   $this->discuzParams['ty'] = 2;
			  }
		 }


		if(CURSCRIPT) {
			$this->discuzParams['md'] = CURSCRIPT;
		}

		if($fid) {
			$this->discuzParams['fi'] = $fid;
		}

		if($tid) {
			$this->discuzParams['ti'] = $tid;
		}

		if($page) {
			$this->discuzParams['pn'] = $page;
		} else {
			$this->discuzParams['pn'] = 1;
		}

		$qq = intval($_COOKIE['stats_qc_reg']);
		$qq .= $discuz_uid?'1':'0';

		if($discuz_uid && $GLOBALS['_DCOOKIE']['connect_is_bind']) {
			 $qq .= ($qclogin = intval($_COOKIE['stats_qc_login']))?$qclogin:1;
		} else {
			 $qq .= '0';
		}

		$this->discuzParams['qq'] = $qq;

		$cloudstatpost = $GLOBALS['_DCOOKIE']['cloudstatpost'];
		dsetcookie('cloudstatpost');
		$cloudstatpost = explode('D', $cloudstatpost, 6);
		if($cloudstatpost[0] == 'thread') {
			$this->discuzParams['nt'] = 1;
			$this->discuzParams['ui'] = $cloudstatpost[1];
			$this->discuzParams['fi'] = $cloudstatpost[2];
			$this->discuzParams['ti'] = $cloudstatpost[3];
			$subject = $cloudstatpost[5];
			$_charset = $charset ? $charset : $dbcharset;

			if($_charset && 'GBK' != strtoupper($_charset)) {
				 require_once DISCUZ_ROOT.'./include/chinese.class.php';

				 $chs = new Chinese($_charset, 'GBK');
				 $subject = $chs->convert($subject);
			}
			$this->extraParams[] = "tn=" . urlencode($subject);
		} elseif($cloudstatpost[0] == 'post') {
			$this->discuzParams['nt'] = 2;
			$this->discuzParams['ui'] = $cloudstatpost[1];
			$this->discuzParams['fi'] = $cloudstatpost[2];
			$this->discuzParams['ti'] = $cloudstatpost[3];
			$this->discuzParams['pi'] = $cloudstatpost[4];
		}

		$cloudstaticon = isset($_DCACHE['settings']['cloud_staticon']) ? intval($_DCACHE['settings']['cloud_staticon']) : 1;
		if ($cloudstaticon && !$inajax) {
			if ($cloudstaticon > 4 && $cloudstaticon < 9) {
                $cloudstaticon = 1;
            } elseif ($cloudstaticon < 5) {
                $cloudstaticon += 10;
            }
			$this->discuzParams['logo'] = $cloudstaticon;
		}

		return $this->_response_format(array(
			'discuzParams' => $this->discuzParams,
			'extraParams' => implode(';', $this->extraParams)
		));
	}

	function _response_format($result) {
		if(function_exists('json_encode')) {
			$json = json_encode($result);
		} else {
			$json = $this->_array2json($result);
		}
		return $json;
	}

	function _array2json($array) {
		$piece = array();
		foreach ($array as $k => $v) {
			$piece[] = $k . ':' . $this->_php2json($v);
		}

		if ($piece) {
			$json = '{' . implode(',', $piece) . '}';
		} else {
			$json = '[]';
		}
		return $json;
	}

	function _php2json($value) {
		if (is_array($value)) {
			return $this->_array2json($value);
		}
		if (is_string($value)) {
			$value = str_replace(array("\n", "\t"), array(), $value);
			$value = addslashes($value);
			return '"'.$value.'"';
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if (is_null($value)) {
			return 'null';
		}

		return $value;
	}
}

$cloudstats = new cloudstats_js();
$statcode = $cloudstats->_makejs() . $statcode;