<?php
if (!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS `cdb_postlogs` (
  `pid` int(10) unsigned NOT NULL DEFAULT '0',
  `tid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `fid` smallint(6) unsigned NOT NULL DEFAULT '0',
  `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `action` char(10) NOT NULL DEFAULT '',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`, `tid`),
  KEY `i_fid` (`fid`),
  KEY `i_uid` (`uid`),
  KEY `i_dateline` (`dateline`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `cdb_threadlogs` (
 `tid` mediumint(8) unsigned NOT NULL DEFAULT '0',
 `fid` smallint(6) unsigned NOT NULL DEFAULT '0',
 `uid` mediumint(8) unsigned NOT NULL DEFAULT '0',
 `otherid` smallint(6) unsigned NOT NULL DEFAULT '0',
 `action` char(10) NOT NULL,
 `expiry` int(10) unsigned NOT NULL DEFAULT '0',
 `dateline` int(10) unsigned NOT NULL DEFAULT '0',
 PRIMARY KEY (`tid`,`fid`,`uid`),
 KEY `i_dateline` (`dateline`)
) ENGINE=MyISAM;
EOF;

runquery($sql);

@include_once DISCUZ_ROOT.'./include/cache.func.php';
updatecache();

$finish = TRUE;

?>
