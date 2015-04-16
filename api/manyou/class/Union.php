<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Union.php 47 2011-09-14 08:24:01Z songlixin $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Union extends MyBase {

	function addAdvs($advs) {

		$result = array();
		if (is_array($advs)) {
			foreach($advs as $advid => $adv) {
				$data = $this->_addAdv($adv);
				if($data === true) {
					$result['succeed'][$advid] = $advid;
				} else {
					$result['failed'][$advid] = $data;
				}
			}

			updatecache('advs');
			updatecache('settings');
		} else {
			$result['errMessage'] = 'no adv';
		}
		return new APIResponse($result);
	}

	function _addAdv($adv) {
		global $_G, $tablepre;

		foreach($adv as $k => $v) {
			$_G['gp_'.$k] = $v;
		}

		$type = $_G['gp_type'];
		$advnew = $_G['gp_advnew'];

		$parameters = !empty($_G['gp_parameters']) ? $_G['gp_parameters'] : array();
		$advnew['targets'] = '';

		$advnew['starttime'] = $advnew['starttime'] ? strtotime($advnew['starttime']) : 0;
		$advnew['endtime'] = $advnew['endtime'] ? strtotime($advnew['endtime']) : 0;

		if(!$advnew['title']) {
			return 'err_2';
		} elseif(strlen($advnew['title']) > 50) {
			return 'err_3';
		} elseif(!$advnew['style']) {
			return 'err_4';
		} elseif($advnew['endtime'] && ($advnew['endtime'] <= TIMESTAMP || $advnew['endtime'] <= $advnew['starttime'])) {
			return 'err_6';
		} elseif(($advnew['style'] == 'code' && !$advnew['code']['html'])
			|| ($advnew['style'] == 'text' && (!$advnew['text']['title'] || !$advnew['text']['link']))
			|| ($advnew['style'] == 'image' && (!$_FILES['advnewimage'] && !$_G['gp_advnewimage'] || !$advnew['image']['link']))
			|| ($advnew['style'] == 'flash' && (!$_FILES['advnewflash'] && !$_G['gp_advnewflash'] || !$advnew['flash']['width'] || !$advnew['flash']['height']))) {
				return 'err_7';
			}

		$GLOBALS['db']->query("INSERT INTO {$tablepre}advertisements (`available`, `type`) VALUES
										(1, '$type')");
		$advid = $GLOBALS['db']->insert_id();

		if($advnew['style'] == 'image' || $advnew['style'] == 'flash') {
			$advnew[$advnew['style']]['url'] = $_G['gp_advnew'.$advnew['style']];
		}

		foreach($advnew[$advnew['style']] as $key => $val) {
			$advnew[$advnew['style']][$key] = stripslashes($val);
		}

		$advnew['displayorder'] = isset($advnew['displayorder']) ? implode("\t", $advnew['displayorder']) : '';
		$advnew['code'] = $this->_encodeadvcode($advnew);

		$advnew['parameters'] = addslashes(serialize(array_merge(is_array($parameters) ? $parameters : array(), array('style' => $advnew['style']), $advnew['style'] == 'code' ? array() : $advnew[$advnew['style']], array('html' => $advnew['code']), array('displayorder' => $advnew['displayorder']))));

		if ($type == 'footerbanner') {
			preg_match('/width\:(\d+).+?height\:(\d+)/i', $advnew['code'], $res);
			$advnew['code'] = "<div style='width:{$res[1]}px; margin:0 auto;'>{$advnew['code']}</div>";
			$advnew['code'] = addslashes($advnew['code']);
		} else {
			$advnew['code'] = addslashes($advnew['code']);
		}

		$GLOBALS['db']->query("UPDATE {$tablepre}advertisements SET title='$advnew[title]', targets='$advnew[targets]', parameters='$advnew[parameters]', code='$advnew[code]', starttime='$advnew[starttime]', endtime='$advnew[endtime]' WHERE advid='$advid'");


		require_once DISCUZ_ROOT . 'include/cache.func.php';
		if($type == 'intercat') {
			updatecache('advs_index');
		} elseif(in_array($type, array('thread', 'interthread'))) {
			updatecache('advs_viewthread');
		} elseif($type == 'text') {
			updatecache(array('advs_index', 'advs_forumdisplay', 'advs_viewthread'));
		} else {
			updatecache(array('settings', 'advs_archiver', 'advs_register', 'advs_index', 'advs_forumdisplay', 'advs_viewthrea    d'));
		}

		return true;
	}

	function _encodeadvcode($advnew) {
		switch($advnew['style']) {
		case 'code':
			$advnew['code'] = $advnew['code']['html'];
			break;
		case 'text':
			$advnew['code'] = '<a href="'.$advnew['text']['link'].'" target="_blank" '.($advnew['text']['size'] ? 'style="font-size: '.$advnew['text']['size'].'"' : '').'>'.$advnew['text']['title'].'</a>';
			break;
		case 'image':
			$advnew['code'] = '<a href="'.$advnew['image']['link'].'" target="_blank"><img src="'.$advnew['image']['url'].'"'.($advnew['image']['height'] ? ' height="'.$advnew['image']['height'].'"' : '').($advnew['image']['width'] ? ' width="'.$advnew['image']['width'].'"' : '').($advnew['image']['alt'] ? ' alt="'.$advnew['image']['alt'].'"' : '').' border="0"></a>';
			break;
		case 'flash':
			$advnew['code'] = '<embed width="'.$advnew['flash']['width'].'" height="'.$advnew['flash']['height'].'" src="'.$advnew['flash']['url'].'" type="application/x-shockwave-flash" wmode="transparent"></embed>';
			break;
		}
		return $advnew['code'];
	}
}

?>