<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_search {

	function index_hot() {
		global $my_search_data, $my_siteid, $my_sitekey;
		if (!is_array($my_search_data)) {
			$my_search_data = unserialize($my_search_data);
		}

		$res = '';
		if(!empty($my_search_data['status']) && !empty($my_siteid) && !empty($my_sitekey)) {
			//$res = '<iframe frameborder="0" scrolling="no" class="manyou-search-api-form" style="height: 50px; width: 99%" src="my_search.php?mod=form"></iframe>';
		}

		return $res;
	}

	function post_bottom() {
		global $db, $tablepre, $tid, $fid;
		$timestamp = time();
		$pid = intval($_POST['pid']);
		// 编辑帖子
		if ($pid && submitcheck('editsubmit')) {
			$row = $db->fetch_array($db->query("SELECT first, authorid FROM {$tablepre}posts WHERE pid = $pid"));
			if ($_POST['delete']) {
				include_once DISCUZ_ROOT . './plugins/search/common.php';
				if ($row['first']) {
					$threads = array(
									 array('tid' => $tid,
										   'fid' => $fid,
										   'uid' => $row['authorid']
										  ),
									);
					plugin_search_delete_threads($threads);
				} else {
					$posts = array(
								   array('pid' => $pid,
										 'tid' => $tid,
										 'fid' => $fid,
										 'uid' => $row['authorid']
										),
								  );
					plugin_search_delete_posts($posts);
				}
			} else {
				$db->query("REPLACE INTO {$tablepre}postlogs (pid, fid, tid, uid, action, dateline) VALUES ('$pid', '$fid', '$tid', '$row[authorid]', 'update', '$timestamp')", 'UNBUFFERED');
			}
		}
		return ;
	}

	function topicadmin_top() {
		global $db, $tablepre, $action;

		$timestamp = time();
		$fid = $_POST['fid'];
		$tid = $_POST['tid'];
		if ($action == 'split' && submitcheck('modsubmit')) {
			$nos = $_POST['split'];
			$sql = "REPLACE INTO {$tablepre}postlogs (pid, fid, tid, action, dateline) VALUES ";
			if ($nos) {
				$nos = explode(',', $nos);
				sort($nos);
				$maxno = $nos[count($nos) - 1];
				$query = $db->query("SELECT pid FROM {$tablepre}posts WHERE tid='$tid' AND invisible='0' ORDER BY dateline LIMIT $maxno");
				$i = 1;
				$pids = array();
				while($post = $db->fetch_array($query)) {
					if (in_array($i, $nos)) {
						$sql .= "('" . $post['pid'] . "', '$fid', '$tid', 'split', '$timestamp'),";
					}
					$i++;
				}

				if ($i > 1) {
					$db->query(trim($sql, ', '));
				}

				$sql = sprintf("REPLACE INTO {$tablepre}threadlogs (tid, fid, action, dateline)
								VALUES ('%d', '%d', '%s', '%d')", $tid, $fid, 'split', $timestamp);
				$db->query($sql);
			}
		}
		return ;
	}

	function topicadmin_bottom() {
		global $db, $tablepre, $action;

		$timestamp = time();
		if ($action && submitcheck('modsubmit')) {
			$fid = $_POST['fid'];
			$tid = $_POST['tid'];
			$otherId = 0;

			$defactions = array('delete', 'moderate');
			if ($action == 'split') {
				return ;
			} elseif($action == 'merge') {
				$act = 'merge';
				$tids = array($_POST['othertid']); // 确保被合并的主题最后一次操作是merge
				$otherId = $tid;
			} elseif($action == 'banpost') { // 屏蔽帖子
				$act = $_POST['banned'] ? 'ban' : 'unban';
				$pids = $_POST['topiclist'];
			} elseif($action == 'warn') { // 警告帖子
				$act = $_POST['warned'] ? 'warn' : 'unwarn';
				$pids = $_POST['topiclist'];
			} elseif($action == 'delpost') { // 删除帖子
				$act = 'delete';
				$pids = $_POST['topiclist'];
			} elseif (in_array($action, $defactions)) {
				$act_map = array('stick' => 'sticky',
								 'digest' => 'digest',
								 'highlight' => 'highlight',
								 'bump' => 'bump',
								 'down' => 'down',
								 'move' => 'move',
								 'delete' => 'delete',
								);
				if (!$_POST['operations']) {
					return false;
				}
				// 同一次操作如果有多个动作以最后一个动作为准
				$act = array_pop($_POST['operations']);
				$act = $act_map[$act];

				if ($act) {
					$tids = $_POST['moderate'];
					// 移动主题
					if ($act == 'move') {
						$otherId = $_POST['moveto'];
					}
				}
			}

			if ($tids && is_array($tids)) {

				$authors = array();
				$query = $db->query("SELECT tid, authorid FROM {$tablepre}threads WHERE tid IN (" .  implode(',', $tids) . ")");
				while ($author = $db->fetch_array($query)) {
					$authors[$author['tid']] = $author['authorid'];
				}

				$threads = array();
				$sql = "REPLACE INTO {$tablepre}threadlogs (tid, fid, otherid, action, uid, dateline) VALUES ";
				foreach ($tids as $tid) {
					$sql .= "('$tid', '$fid', '$otherId', '$act', '" . $authors[$tid] . "', '$timestamp'), ";
					$threads[$tid] = array('tid' => $tid,
										   'fid' => $fid,
										   'uid' => $authors[$tid],
										  );
				}
				$sql = trim($sql, ', ');

				if ($act == 'delete') {
					include_once DISCUZ_ROOT . './plugins/search/common.php';
					plugin_search_delete_threads($threads);
				} else {
					$db->query($sql, 'UNBUFFERED');
				}
			} elseif ($tid && $pids) {

				$authors = array();
				$query = $db->query("SELECT pid, authorid FROM {$tablepre}posts WHERE pid IN (" .  implode(',', $pids) . ")");
				while ($author = $db->fetch_array($query)) {
					$authors[$author['pid']] = $author['authorid'];
				}

				$posts = array();
				$sql = "REPLACE INTO {$tablepre}postlogs (pid, fid, tid, uid, action, dateline) VALUES ";
				foreach ($pids as $pid) {
					$sql .= "('$pid', '$fid', '$tid', '" . $authors[$pid] . "', '$act', '$timestamp'), ";
					$posts[$pid] = array('pid' => $pid,
										 'tid' => $tid,
										 'fid' => $fid,
										 'uid' => $authors[$pid],
										);
				}
				$sql = trim($sql, ', ');
				if ($act == 'delete') {
					include_once DISCUZ_ROOT . './plugins/search/common.php';
					plugin_search_delete_posts($posts);
				} else {
					$db->query($sql, 'UNBUFFERED');
				}
			}
		}

		return ;
	}
}
