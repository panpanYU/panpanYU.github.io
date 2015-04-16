<?php

/**
 *	  [Discuz!] (C)2001-2099 Comsenz Inc.
 *	  This is NOT a freeware, use is subject to license terms
 *
 *	  $Id: cloud_stats.php 118 2011-09-21 15:04:47Z liuwenxue $
 */

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$anchor = in_array($anchor, array('base', 'summary')) ? $anchor : 'summary';
$current = array($anchor => 1);

$statsnav = array();
$statsnav[0] = array('cloud_stats_summary', 'cloud&operation=stats&anchor=summary', $current['summary']);
$statsnav[1] = array('cloud_stats_setting', 'cloud&operation=stats&anchor=base', $current['base']);

if(!$_G['inajax']) {
	cpheader();
}

if($anchor == 'base') {

	if(!submitcheck('statssubmit')) {
		shownav('cloud', 'cloud_stats');
		showsubmenu('cloud_stats', $statsnav);
		showtips('cloud_stats_tips');
		showformheader('cloud&operation=stats&anchor=base');
		showhiddenfields(array('operation' => $operation));
		showtableheader('cloud_stats_icon_set');

		$myicon = $db->result_first("SELECT value FROM {$tablepre}settings WHERE variable = 'cloud_staticon'");
		if ($myicon === false || in_array($myicon, array('5', '6', '7', '8'))){
			$myicon = 1;
		}

		$checkicon[$myicon] = ' checked';

		$icons = '<table style="margin-bottom: 3px; margin-top:3px;width:350px"><tr><td>';
		for($i=1;$i<=11;$i++) {
			if ($i < 5) {
				$icons .= '<input class="radio" type="radio" id="stat_icon_'.$i.'" name="settingnew[cloud_staticon]" value="'.$i.'"'.$checkicon[$i].' /><label for="stat_icon_'.$i.'">&nbsp;<img src="http://tcss.qq.com/icon/toss_1'.$i.'.gif" /></label>&nbsp;&nbsp;';
				if ($i % 4 == 0) {
					$icons .= '</td></tr><tr><td>';
				}
			}elseif ($i < 9) {
				 continue;
			} elseif ($i < 11) {
				$icons .= '<input class="radio" type="radio" id="stat_icon_'.$i.'" name="settingnew[cloud_staticon]" value="'.$i.'"'.$checkicon[$i].' /><label for="stat_icon_'.$i.'">&nbsp;'.$lang['cloud_stats_icon_word'.$i].'</label>&nbsp;&nbsp;';
			} else {
				$icons .= '</td></tr><tr><td><input class="radio" type="radio" id="stat_icon_'.$i.'" name="settingnew[cloud_staticon]" value="0"'.$checkicon[0].' /><label for="stat_icon_'.$i.'">&nbsp;'.$lang['cloud_stats_icon_none'].'</label></td></tr>';
			}
		}
		$icons .= '</table>';
		showsetting('', '', '', $icons);

		showsubmit('statssubmit', 'submit');
		showtablefooter();
		showformfooter();

	} else {

		$settingnew['cloud_staticon'] = intval($settingnew['cloud_staticon']);

		$db->query("REPLACE INTO {$tablepre}settings (`variable`, `value`) VALUES ('cloud_staticon', '$settingnew[cloud_staticon]')");
		updatecache('settings');

		cloud_cpmsg('setting_update_succeed', 'action=cloud&operation='.$operation.(!empty($_G['gp_anchor']) ? '&anchor='.$_G['gp_anchor'] : ''), 'succeed');
	}
} elseif ($anchor == 'summary') {
	$statsDomain = 'http://ta.qq.com';
	$signUrl = generateSiteSignUrl(array('v' => 2));

	headerLocation($statsDomain.'/statsSummary/?'.$signUrl);
}

?>