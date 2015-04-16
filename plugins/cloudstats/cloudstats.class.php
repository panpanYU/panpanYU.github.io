<?php

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_cloudstats {

	function post_stats_output($tpl) {

		 global $post, $pid, $tid, $fid, $discuz_uid;

		 $stats = join('D', array($discuz_uid, $fid, $tid, $pid, $_POST['subject']));
		 if ($tpl['message'] == 'post_newthread_succeed') {
			  dsetcookie('cloudstatpost', 'threadD' . $stats, 86400);
		 } elseif ($tpl['message'] == 'post_reply_succeed') {
			  dsetcookie('cloudstatpost', 'postD' . $stats, 86400);
		 }

		 return '';
	}
}

?>