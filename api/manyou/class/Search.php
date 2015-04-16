<?php
/**
 * $Id: Search.php 126 2011-09-21 16:50:07Z zhouguoqiang $
 */

class Search {

	function getUserGroupPermissions($userGroupIds) {
		if (!$userGroupIds) {
			return new APIResponse(array());
		}
		$result = $this->_getUserGroupPermissions($userGroupIds);
		return new APIResponse($result);
	}

	function _getUserGroupPermissions($userGroupIds) {
		global $tablepre, $db;
		$fields = array(
						'groupid' => 'userGroupId',
						'grouptitle' => 'userGroupName',
						'readaccess'	=> 'readPermission',
						'allowvisit'	=> 'allowVisit',
						'allowsearch'	=> 'searchLevel',
						);
		$userGroups = array();
		$query = $db->query("SELECT * FROM {$tablepre}usergroups WHERE groupid IN (" . implode(',', $userGroupIds) . ')');
		while($row = $db->fetch_array($query)) {
			foreach($row as $k => $v) {
				if (array_key_exists($k, $fields)) {
					if ($k == 'allowsearch') {
						switch ($v) {
							case '1' :
								$v = 'subject';
								break;
							case '2':
								$v = 'fulltext';
								break;
							case '0':
							default:
								$v = 'none';
						}
					}
					$userGroups[$row['groupid']][$fields[$k]] = $v;
				}
				$userGroups[$row['groupid']]['forbidForumIds'] = array();
				$userGroups[$row['groupid']]['allowForumIds'] = array();
				$userGroups[$row['groupid']]['specifyAllowForumIds'] = array();
			}
		}

		$query = $db->query(sprintf('SELECT * FROM %sforumfields', $tablepre));
		while($row = $db->fetch_array($query)) {
			$allowViewGroupIds = array();
			if ($row['viewperm']) {
				$allowViewGroupIds = explode("\t", $row['viewperm']);
			}
			foreach($userGroups as $gid => $_v) {
				if ($row['password']) {
					$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
					continue;
				}
				$perm = unserialize($row['formulaperm']);
				if(is_array($perm)) {
					if ($perm[0] || $perm[1] || $perm['users']) {
						$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
						continue;
					}
				}
				if (!$allowViewGroupIds) {
					$userGroups[$gid]['allowForumIds'][] = $row['fid'];
				} elseif (!in_array($gid, $allowViewGroupIds)) {
					$userGroups[$gid]['forbidForumIds'][] = $row['fid'];
				} elseif (in_array($gid, $allowViewGroupIds)) {
					$userGroups[$gid]['allowForumIds'][] = $row['fid'];
					$userGroups[$gid]['specifyAllowForumIds'][] = $row['fid'];
				}
			}
		}
		//print_r($userGroups);exit;

		// hash
		foreach($userGroups as $k => $v) {
			ksort($v);
			$userGroups[$k]['sign'] = md5(serialize($v));
		}
		return $userGroups;
	}

	function _convertPost($row) {
		$result = array();
		$map = array('pid' => 'pId',
						'tid'	=> 'tId',
						'fid'	=> 'fId',
						'authorid'	=> 'authorId',
						'author'	=> 'authorName',
						'useip'	=> 'authorIp',
						'anonymous'	=> 'isAnonymous',
						'subject'	=> 'subject',
						'message'	=> 'content',
				//		'invisible'	=> 'approveStatus',
						'htmlon'	=> 'isHtml',
						'attachment'	=> 'isAttached',
						'rate'	=> 'rate',
						'ratetimes'	=> 'rateTimes',
//						'status'	=> 'status',
						'dateline'	=> 'createdTime',
						'first'		=> 'isThread',
					   );
		$map2 = array(
					  'bbcodeoff'	=> 'isBbcode',
					  'smileyoff'	=> 'isSmiley',
					  'parseurloff'	=> 'isParseUrl',
					 );
		foreach($row as $k => $v) {
			if (array_key_exists($k, $map)) {
				if ($k == 'dateline') {
					$result[$map[$k]] = date('Y-m-d H:i:s', $v);
					continue;
				}

				if (in_array($k, array('htmlon', 'attachment', 'first', 'anonymous'))) {
					$v = $v ? true : false;
				}

				$result[$map[$k]] = $v;
			} elseif (array_key_exists($k, $map2)) {
				$result[$map2[$k]] = $v ? false : true;
			}
		}
		$result['isWarned'] = $result['isBanned'] = false;
		if ($row['status'] & 1) {
			$result['isBanned'] = true;
		}
		if ($row['status'] & 2) {
			$result['isWarned'] = true;
		}
		return $result;
	}

	function getUpdatedPosts($num, $lastPostIds = array()) {
		global $tablepre, $db;

		if ($lastPostIds) {
			$db->query("DELETE FROM {$tablepre}postlogs WHERE pid IN (" . implode($lastPostIds, ', ') . ")");
		}

		$result = array();

		$totalNum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}postlogs");
		if (!$totalNum) {
			return new APIResponse($result);
		}
		$result['totalNum'] = $totalNum;

		$sql = "SELECT * FROM {$tablepre}postlogs
				 ORDER BY dateline 
				LIMIT $num";
		$query = $db->query($sql);
		$pIds = $deletePosts = $updatePostIds = array();
		$unDeletePosts  = array();
		$posts = array();
		while($post = $db->fetch_array($query)) {
			$pIds[] = $post['pid'];
			if ($post['action'] == 'delete') {
				$deletePosts[$post['pid']] = array('pId' => $post['pid'],
										 'action' => $post['action'],
										 'updated' => date('Y-m-d H:i:s', $post['dateline']),
										);
			} else {
				$unDeletePosts[$post['pid']] = array('pId' => $post['pid'],
													 'action' => $post['action'],
										 			'updated' => date('Y-m-d H:i:s', $post['dateline']),
													);
			}
		}

		if ($pIds) {
			if ($unDeletePosts) {
				$posts = $this->_getPosts(array_keys($unDeletePosts));
				foreach($unDeletePosts as $pId => $updatePost) {
					if ($posts[$pId]) {
						$unDeletePosts[$pId] = array_merge($updatePost, $posts[$pId]);
					} else {
						$unDeletePosts[$pId]['pId'] = 0;
					}
				}
			}
		}
		$result['data'] = $deletePosts + $unDeletePosts;
		$result['ids']['post'] = $pIds;
		return new APIResponse($result);
	}

	function removePostLogs($pIds) {
		global $tablepre, $db;
		if (!$pIds) {
			return new APIResponse(false);
		}
		$db->query("DELETE FROM {$tablepre}postlogs WHERE pid IN (" . implode($pIds, ', ') . ")");
		return new APIResponse(true);
	}

	function getPosts($pIds) {
		global $db, $tablepre;
		$tIds = $authors = array();
		$posts = $this->_getPosts($pIds);
		if ($posts) {
			foreach($posts as $post) {
				$authors[$post['authorId']][] = $post['pId'];
				if ($post['isThread']) {
					$tIds[$post['pId']] = $post['tId'];
				}
			}
			if ($tIds) {
				$threads = $this->_getThreads($tIds);
				foreach($posts as $pId => $post) {
					if ($post['isThread']) {
						$tId = $tIds[$pId];
						$posts[$pId]['threadInfo'] = $threads[$tId];
					}
				}
			}

			// 过滤被禁止发言/禁止访问或被删除的用户的帖子
			$authorids = array_keys($authors);
			if ($authorids) {
				$banuids= $uids = array();
				$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
				while ($author = $db->fetch_array($query)) {
					$uids[$author['uid']] = $author['uid'];
					if ($author['groupid'] == 4 || $author['groupid'] == 5) {
						$banuids[] = $author['uid'];
					}
				}
				$deluids = array_diff($authorids, $uids);
				foreach($deluids as $deluid) {
					// 游客
					if (!$deluid) {
						continue;
					}
					foreach($authors[$deluid] as $pid) {
						$posts[$pid]['authorStatus'] = 'delete';
					}
				}
				foreach($banuids as $banuid) {
					foreach($authors[$banuid] as $pid) {
						$posts[$pid]['authorStatus'] = 'ban';
					}
				}
			}
		}
		return new APIResponse($posts);
	}

	function _getPosts($pIds) {
		global $tablepre, $db;
		$query = $db->query("SELECT * FROM {$tablepre}posts
							 WHERE invisible = '0'
							 AND pid In (" . implode($pIds, ', ') . ")"
						   );
		$result = array();
		while($post = $db->fetch_array($query)) {
			$result[$post['pid']] = $this->_convertPost($post);
		}
		
		return $result;
	}


	function getNewPosts($num, $fromPostId = 0) {
		global $tablepre, $db, $lang;

		$result = $authors = array();

		$sql = "SELECT * FROM {$tablepre}posts 
				WHERE pid > $fromPostId
				 ORDER BY pid ASC 
				LIMIT $num";
		$query = $db->query($sql);
		while($post = $db->fetch_array($query)) {
			$result['maxPid'] = $post['pid'];
			if ($post['invisible'] == 0) {
				$authors[$post['authorid']][] = $post['pid'];
				$result['data'][$post['pid']] = $this->_convertPost($post);
				if ($post['first']) {
					$result['data'][$post['pid']]['threadInfo'] = $this->_getThread($post['tid']);
				}
			}
		}

		// 过滤被禁止发言/禁止访问或被删除的用户的帖子
		$authorids = array_keys($authors);
		if ($authorids) {
			$banuids= $uids = array();
			$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
			while ($author = $db->fetch_array($query)) {
				$uids[$author['uid']] = $author['uid'];
				if ($author['groupid'] == 4 || $author['groupid'] == 5) {
					$banuids[] = $author['uid'];
				}
			}
			$deluids = array_diff($authorids, $uids);
			foreach($deluids as $deluid) {
				// 游客
				if (!$deluid) {
					continue;
				}
				foreach($authors[$deluid] as $pid) {
					$result['data'][$pid]['authorStatus'] = 'delete';
				}
			}
			foreach($banuids as $banuid) {
				foreach($authors[$banuid] as $pid) {
					$result['data'][$pid]['authorStatus'] = 'ban';
				}
			}
		}

		return new APIResponse($result);
	}

	function getAllPosts($num, $pid = 0, $orderType = 'ASC') {
		global $tablepre, $db, $lang;
		$pid = intval($pid);

		$result = $authors = array();

		$sql = "SELECT * FROM {$tablepre}posts
				WHERE pid > $pid
				ORDER BY pid $orderType
				LIMIT $num";
		$query = $db->query($sql);
		$tIds = array();
		while($post = $db->fetch_array($query)) {
			$result['maxPid'] = $post['pid'];
			if ($post['invisible'] == 0) {
				$authors[$post['authorid']][] = $post['pid'];
				$result['data'][$post['pid']] = $this->_convertPost($post);
				if ($post['first']) {
					$tIds[$post['pid']] = $post['tid'];
				}
			}
		}

		if ($tIds) {
			$threads = $this->_getThreads($tIds);
			foreach($tIds as $pId => $tId) {
				$result['data'][$pId]['threadInfo'] = $threads[$tId];
			}
		}

		// 过滤被禁止发言/禁止访问或被删除的用户的帖子
		$authorids = array_keys($authors);
		if ($authorids) {
			$banuids= $uids = array();
			$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
			while ($author = $db->fetch_array($query)) {
				$uids[$author['uid']] = $author['uid'];
				if ($author['groupid'] == 4 || $author['groupid'] == 5) {
					$banuids[] = $author['uid'];
				}
			}
			$deluids = array_diff($authorids, $uids);
			foreach($deluids as $deluid) {
				// 游客
				if (!$deluid) {
					continue;
				}
				foreach($authors[$deluid] as $pid) {
					$result['data'][$pid]['authorStatus'] = 'delete';
				}
			}
			foreach($banuids as $banuid) {
				foreach($authors[$banuid] as $pid) {
					$result['data'][$pid]['authorStatus'] = 'ban';
//					unset($result['data'][$pid]);
				}
			}
		}

		return new APIResponse($result);
	}

	function _convertThread($row) {
		$result = array();
		$map = array(
					'tid'	=> 'tId',
					'fid'	=> 'fId',
					'authorid'	=> 'authorId',
					'author'	=> 'authorName',
					'special'	=> 'specialType',
					'price'	=> 'price',
					'subject'	=> 'subject',
					'readperm'	=> 'readPermission',
					'lastposter'	=> 'lastPoster',
					'views'	=> 'viewNum',
					'displayorder'	=> 'stickLevel',
					'highlight'	=> 'isHighlight',
					'digest'	=> 'digestLevel',
					'rate'	=> 'isRated',
					'attachment'	=> 'isAttached',
					'moderated'	=> 'isModerated',
					'closed'	=> 'isClosed',
					'supe_pushstatus'	=> 'supeSitePushStatus',
					'recommends'	=> 'recommendTimes',
					'recommend_add'	=> 'recommendSupportTimes',
					'recommend_sub'	=> 'recommendOpposeTimes',
					'heats'		=> 'heats',
					'pid'		=> 'pId',
					);
		$map2 = array(
					'dateline'	=> 'createdTime',
					'lastpost'	=> 'lastPostedTime',
					);
		foreach($row as $k => $v) {
			if (array_key_exists($k, $map)) {
				if ($k == 'special') {
					switch($v) {
						case 1:
							$v = 'poll';
							break;
						case 2:
							$v = 'trade';
							break;
						case 3:
							$v = 'reward';
							break;
						case 4:
							$v = 'activity';
							break;
						case 5:
							$v = 'debate';
							break;
						case 127:
							$v = 'plugin';
							break;
						default:
							$v = 'normal';
					}
				}

				if ($k == 'displayorder') {
					switch($v) {
						case 1:
							$v = 'board';
							break;
						case 2:
							$v = 'group';
							break;
						case 3:
							$v = 'global';
							break;
						case 0:
						default:
							$v = 'none';
					}
				}

				if (in_array($k, array('highlight', 'rate', 'attachment', 'moderated', 'closed'))) {
					$v = $v ? true : false;
				}
				$result[$map[$k]] = $v;
			} elseif (array_key_exists($k, $map2)) {
				$result[$map2[$k]] = date('Y-m-d H:i:s', $v);
			}
		}
		return $result;
	}

	function getUpdatedThreads($num, $lastThreadIds = array(), $lastForumIds = array(), $lastUserIds = array()) {
		global $tablepre, $db;

		if ($lastThreadIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE tid IN (" . implode($lastThreadIds, ', ') . ")");
		}
		if ($lastForumIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE fid IN (" . implode($lastForumIds, ', ') . ") AND tid = 0");
		}
		if ($lastUserIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE uid IN (" . implode($lastUserIds, ', ') . ") AND tid = 0");
		}

		$result = array();

		$totalNum = $db->result_first("SELECT COUNT(*) FROM {$tablepre}threadlogs");
		if (!$totalNum) {
			return new APIResponse($result);
		}
		$result['totalNum'] = $totalNum;

		$tIds = $deleteThreads = $updateThreadIds = $otherLogs = $ids = array();
		$unDeleteThreads  = array();
		$threads = array();
		$sql = "SELECT * FROM {$tablepre}threadlogs 
				 ORDER BY dateline 
				LIMIT $num";
		$query = $db->query($sql);

		$otherActions = array('mergeforum', 'banuser', 'unbanuser', 'deluser', 'delforum');
		while($thread = $db->fetch_array($query)) {
			$tIds[] = $thread['tid'];
			if ($thread['action'] == 'delete') {
				$ids['thread'][] = $thread['tid'];
				$deleteThreads[$thread['tid']] = array('tId' => $thread['tid'],
										 'action' => 'delete',
									 	'updated' => date('Y-m-d H:i:s', $thread['dateline']),
										);
			} elseif (in_array($thread['action'], array('banuser', 'unbanuser', 'deluser'))) {
				$ids['user'][] = $thread['uid'];
				$expiry = 0;
				if ($thread['expiry']) {
					$expiry = date('Y-m-d H:i:s', $thread['expiry']);
				}
				$otherLogs[] = array('uId' => $thread['uid'],
									 'isDeletePost' => $thread['otherid'],
									 'action' => $thread['action'],
									 'expiry' => $expiry,
									 'updated' => date('Y-m-d H:i:s', $thread['dateline']),
									);
			} elseif (in_array($thread['action'], array('mergeforum', 'delforum'))) {
				$ids['forum'][] = $thread['fid'];
				$otherLogs[] = array('fId' => $thread['fid'],
									 'otherId' => $thread['otherid'],
									 'action' => $thread['action'],
									 'updated' => date('Y-m-d H:i:s', $thread['dateline']),
									);
			//  合并主题后，tid为消失的“旧”主题id，需要单独处理
			} elseif (in_array($thread['action'], array('merge'))) {
				$ids['thread'][] = $thread['tid'];
				$otherLogs[] = array('tId' => $thread['tid'],
									 'otherId' => $thread['otherid'],
									 'action' => $thread['action'],
									 'updated' => date('Y-m-d H:i:s', $thread['dateline']),
									);
			} else {
				$ids['thread'][] = $thread['tid'];
				$unDeleteThreads[$thread['tid']] = array('tId' => $thread['tid'],
													 'action'  => $thread['action'],
													 'otherId' => $thread['otherid'],
									 				'updated' => date('Y-m-d H:i:s', $thread['dateline']),
													);
			}
		}

		if ($tIds) {
			if ($unDeleteThreads) {
				$threads = $this->_getThreads(array_keys($unDeleteThreads));
				foreach($unDeleteThreads as $tId => $updateThread) {
					if ($threads[$tId]) {
						$unDeleteThreads[$tId] = array_merge($threads[$tId], $updateThread);
					} else {
						$unDeleteThreads[$tId]['tId'] = 0;
					}
				}
			}
		}
		$result['data'] = $deleteThreads +  $unDeleteThreads + $otherLogs;
		$result['ids'] = $ids;
		return new APIResponse($result);
	}
	
	function removeThreadLogs($lastThreadIds = array(), $lastForumIds = array(), $lastUserIds = array()) {
		global $tablepre, $db;

		if ($lastThreadIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE tid IN (" . implode($lastThreadIds, ', ') . ")");
		}
		if ($lastForumIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE fid IN (" . implode($lastForumIds, ', ') . ") AND tid = 0");
		}
		if ($lastUserIds) {
			$db->query("DELETE FROM {$tablepre}threadlogs WHERE uid IN (" . implode($lastUserIds, ', ') . ") AND tid = 0");
		}

		return new APIResponse(true);
	}

	function getThreads($tIds) {
		global $db, $tablepre;
		$result = $this->_getThreads($tIds);

		if ($result) {
			foreach($result as $thread) {
				$authors[$thread['authorId']][] = $thread['tId'];
			}
		}

		// 过滤被禁止发言/禁止访问或被删除的用户的主题 (暂不考虑匿名主题)
		$authorids = array_keys($authors);
		if ($authorids) {
			$banuids= $uids = array();
			$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
			while ($author = $db->fetch_array($query)) {
				$uids[$author['uid']] = $author['uid'];
				if ($author['groupid'] == 4 || $author['groupid'] == 5) {
					$banuids[] = $author['uid'];
				}
			}
			$deluids = array_diff($authorids, $uids);
			foreach($deluids as $deluid) {
				if (!$deluid) { // 游客
					continue;
				}
				foreach($authors[$deluid] as $tid) {
					$result[$tid]['authorStatus'] = 'delete';
				}
			}
			foreach($banuids as $banuid) {
				foreach($authors[$banuid] as $tid) {
					$result[$tid]['authorStatus'] = 'ban';
				}
			}
		}
		return new APIResponse($result);
	}

	function _getThread($tId) {
		$result = $this->_getThreads(array($tId));
		return $result[$tId];
	}

	function _getThreads($tIds) {
		global $tablepre, $db;

		$threadPosts = $this->_getThreadPosts($tIds);

		$query = $db->query("SELECT * FROM {$tablepre}threads
							 WHERE tid IN (" . implode($tIds, ', ') . ") AND displayorder >= 0"
						   );
		$result = array();
		while($thread = $db->fetch_array($query)) {
			$thread['pid'] = $threadPosts[$thread['tid']]['pId'];
			$result[$thread['tid']] = $this->_convertThread($thread);
		}
		return $result;
	}

	function _getThreadPosts($tIds) {
		global $tablepre, $db;
		$query = $db->query("SELECT * FROM {$tablepre}posts
							 WHERE tid IN (" . implode($tIds, ', ') . ") AND first = 1 AND invisible = '0'"
						   );
		$result = array();
		while($post = $db->fetch_array($query)) {
			$result[$post['tid']] = $this->_convertPost($post);
		}
		return $result;
	}

	function getNewThreads($num, $fromThreadId = 0) {
		global $tablepre, $db;

		$result = $authors = array();

		$sql = "SELECT * FROM {$tablepre}threads 
			WHERE tid > $fromThreadId 
			ORDER BY tid ASC 
			LIMIT $num";
		$query = $db->query($sql);
		$tIds = array();
		while($thread = $db->fetch_array($query)) {
			$result['maxTid'] = $thread['tid'];
			if ($thread['displayorder'] >= 0) {
				$authors[$thread['authorid']][] = $thread['tid'];
				$tIds[] = $thread['tid'];
				$result['data'][$thread['tid']] = $this->_convertThread($thread);
			}
		}

		$threadPosts = $this->_getThreadPosts($tIds);
		foreach($result['data'] as $tId => $v) {
			$result['data'][$tId]['pId'] = $threadPosts[$tId]['pId'];
		}

		// 过滤被禁止发言/禁止访问或被删除的用户的主题 (暂不考虑匿名主题)
		$authorids = array_keys($authors);
		if ($authorids) {
			$banuids= $uids = array();
			$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
			while ($author = $db->fetch_array($query)) {
				$uids[$author['uid']] = $author['uid'];
				if ($author['groupid'] == 4 || $author['groupid'] == 5) {
					$banuids[] = $author['uid'];
				}
			}
			$deluids = array_diff($authorids, $uids);
			foreach($deluids as $deluid) {
				if (!$deluid) { // 游客
					continue;
				}
				foreach($authors[$deluid] as $tid) {
					$result['data'][$tid]['authorStatus'] = 'delete';
				}
			}
			foreach($banuids as $banuid) {
				foreach($authors[$banuid] as $tid) {
					$result['data'][$tid]['authorStatus'] = 'ban';
				}
			}
		}

		return new APIResponse($result);
	}

	function getAllThreads($num, $tid = 0, $orderType = 'ASC') {
		global $tablepre, $db;
		$tid = intval($tid);

		$result = $authors = array();

		$sql = "SELECT * FROM {$tablepre}threads 
				WHERE tid > $tid
				ORDER BY tid $orderType 
				LIMIT $num ";
		$query = $db->query($sql);
		$tIds = array();
		while($thread = $db->fetch_array($query)) {
			if ($thread['displayorder'] >= 0) {
				$authors[$thread['authorid']][] = $thread['tid'];
				$tIds[] = $thread['tid'];
				$result['data'][$thread['tid']] = $this->_convertThread($thread);
			}
		}

		// thread's pId
		if ($tIds) {
			$threadPosts = $this->_getThreadPosts($tIds);
			foreach($result['data'] as $tId => $v) {
				$result['data'][$tId]['pId'] = $threadPosts[$tId]['pId'];
			}
		}

		// 过滤被禁止发言/禁止访问或被删除的用户的主题 (暂不考虑匿名主题)
		$authorids = array_keys($authors);
		if ($authorids) {
			$banuids= $uids = array();
			$query = $db->query("SELECT uid, username, groupid FROM {$tablepre}members WHERE uid IN (" . implode($authorids, ', ') . ')');
			while ($author = $db->fetch_array($query)) {
				$uids[$author['uid']] = $author['uid'];
				if ($author['groupid'] == 4 || $author['groupid'] == 5) {
					$banuids[] = $author['uid'];
				}
			}
			$deluids = array_diff($authorids, $uids);
			foreach($deluids as $deluid) {
				if (!$deluid) { // 游客
					continue;
				}
				foreach($authors[$deluid] as $tid) {
					$result['data'][$tid]['authorStatus'] = 'delete';
				}
			}
			foreach($banuids as $banuid) {
				foreach($authors[$banuid] as $tid) {
					$result['data'][$tid]['authorStatus'] = 'ban';
				}
			}
		}

		return new APIResponse($result);
	}

	function _convertForum($row) {
		$result = array();
		$map = array(
					'fid'	=> 'fId',
					'fup'	=> 'pId',
					'name'	=> 'fName',
					'type'	=> 'type',
					'displayorder'	=> 'displayOrder',
					);
		foreach($row as $k => $v) {
			if (array_key_exists($k, $map)) {
				$result[$map[$k]] = $v;
			}
		}
		$result['sign'] = md5(serialize($result));
		return $result;
	}

	function _getForums($fIds = array()) {
		global $tablepre, $db;

		if ($fIds) {
			$where = ' AND fid IN (' . implode(',', $fIds) . ')';
		}

		$result = array();

		$sql = "SELECT COUNT(*) FROM {$tablepre}forums
				WHERE true $where";
		$result['totalNum'] = $db->result_first($sql);

		$sql = "SELECT * FROM {$tablepre}forums
				WHERE true $where 
				ORDER BY fid ";
		$query = $db->query($sql);
		while($forum = $db->fetch_array($query)) {
			$result['data'][$forum['fid']] = $this->_convertForum($forum);
		}

		if ($fIds) {
			$result['sign'] = null;
		} else {
			$result['sign'] = md5(serialize($result['data']));
		}
		return $result;
	}

	function getForums($fIds = array()) {
		$result = $this->_getForums($fIds);
		return new APIResponse($result);
	}

	function removePosts($pIds = array()) {
		global $tablepre, $db;
		if ($pIds && is_array($pIds)) {
			$where = ' pid IN (' . implode(',', $pIds) . ')';
			$query = $db->query("SELECT first, tid, pid FROM {$tablepre}posts WHERE " . $where);
			$delteThreadIds = array();
			$updateThreadIds = array();
			while ($post = $db->fetch_array($query)) {
				// 主题贴
				if ($post['first']) {
					$deleteThreadIds[$post['tid']] = $post['tid'];
				} else {
					$updateThreadIds[$post['tid']]++;
				}
			}

			if (!$deleteThreadIds  && !$updateThreadIds) {
				return new APIResponse(false);
			}

			$res = $db->query("DELETE FROM {$tablepre}posts WHERE " . $where);
			if ($deleteThreadIds) {
				$db->query("DELECT FROM {$tablepre}posts WHERE tid IN (" . implode(', ', $deleteThreadIds) . ')');
				$db->query("DELECT FROM {$tablepre}threads WHERE tid IN (" . implode(', ', $deleteThreadIds) . ')');
			}

			if ($updateThreadIds) {
				$sql = "UPDATE {$tablepre}threads ";
				foreach ($updateThreadIds as $tId => $num) {
					$db->query($sql . ' SET replies = replies - ' . $num . ' WHERE tid = ' . $tId);
				}
			}
			return new APIResponse($res);
		}

		return new APIResponse(false);
	}

	function setConfig($data) {

		global $_DCACHE;
		$searchData = $_DCACHE['settings']['my_search_data'];
		if (!is_array($searchData)) {
			$searchData = unserialize($searchData);
		} elseif (!$searchData) {
			$searchData = array();
		}

		foreach($data as $k => $v) {
			$searchData[$k] = $v;
		}

		require_once libfile('function/cloud');
		$searchData = addslashes(serialize(dstripslashes($searchData)));
		inserttable('settings', array('variable' => 'my_search_data', 'value' => $searchData), 0, true);

		@chdir(DISCUZ_ROOT);
		require_once libfile('function/cache');
		updatecache('settings');

		return new APIResponse(true);
	}

	function getConfig($keys) {

		global $_DCACHE;
		$searchData = $_DCACHE['settings']['my_search_data'];
		if (!is_array($searchData)) {
			$searchData = unserialize($searchData);
		} elseif (!$searchData) {
			$searchData = array();
		}

		$maps = array(
			//		'hotWords' => 'srchhotkeywords',
					);
		$confs = array();
		foreach($keys as $key) {
			if ($fieldName = $maps[$key]) {
				$confs[$key] = $GLOBALS[$fieldName];
			} elseif (isset($searchData[$key])) {
				$confs[$key] = $searchData[$key];
			}
		}
		return new APIResponse($confs);
	}

	function setHotWords($data, $method = 'append', $limit = 0) {

		global $_DCACHE;
		$searchData = $_DCACHE['settings']['my_search_data'];
		if (!is_array($searchData)) {
			$searchData = unserialize($searchData);
		} elseif (!$searchData) {
			$searchData = array();
		}

		$srchhotkeywords = array();
		if ($searchData['hotWords']) {
			$srchhotkeywords = $searchData['hotWords'];
		}
		$newHotWords = array();
		foreach($data as $k => $v) {
			$newHotWords[] = addslashes(dstripslashes($v));
		}

		switch ($method) {
			case 'overwrite':
				$hotWords = $newHotWords;
				break;
			case 'prepend':
				$hotWords = array_merge($newHotWords, $srchhotkeywords);
				break;
			case 'append':
				$hotWords = array_merge($srchhotkeywords, $newHotWords);
				break;
		}

		if ($limit) {
			$hotWords = array_slice($hotWords, 0, $limit);
		}
		$hotWords = array_unique($hotWords);

		$hotWords = implode("\n", $hotWords);

		$searchData['hotWords'] = $hotWords;
		$searchData = addslashes(serialize(dstripslashes($searchData)));
		require_once libfile('function/cloud');
		inserttable('settings', array('variable' => 'my_search_data', 'value' => $searchData), 0, true);
		@chdir(DISCUZ_ROOT);
		require_once libfile('function/cache');
		updatecache('settings');

		return new APIResponse(true);
	}
}

?>
