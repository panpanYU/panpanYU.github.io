<?php
/**
 * $Id: form.inc.php 134 2011-09-22 03:38:00Z zhouguoqiang $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if (!is_array($my_search_data)) {
	$my_search_data = unserialize($my_search_data);
}
if(empty($my_search_data['status']) || empty($my_siteid) || empty($my_sitekey)) {
	showmessage('search:no_open');
}


$hotwords = explode("\n", $my_search_data['hotWords']);
foreach($hotwords as $k => $v) {
	$hotwords[$k] = trim($v);
}
include template('my_search_form');
?>
