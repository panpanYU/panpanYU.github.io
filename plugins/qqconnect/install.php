<?php
/*
   [Discuz!] (C)2001-2011 Comsenz Inc.
   This is NOT a freeware, use is subject to license terms

   $Id: install.php 74 2011-09-16 02:12:51Z songlixin $
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF
CREATE TABLE IF NOT EXISTS cdb_member_connect (
	`uid` mediumint(8) unsigned NOT NULL default '0',
	`conuin` char(40) NOT NULL default '',
	`conuinsecret` char(16) NOT NULL default '',
	`conopenid` char(32) NOT NULL default '',
	`conisfeed` tinyint(1) unsigned NOT NULL default '0',
	`conispublishfeed` tinyint(1) unsigned NOT NULL default '0',
	`conispublisht` tinyint(1) unsigned NOT NULL default '0',
	`conisregister` tinyint(1) unsigned NOT NULL default '0',
	`conisqzoneavatar` tinyint(1) unsigned NOT NULL default '0',
	PRIMARY KEY  (`uid`),
	KEY `conuin` (`conuin`),
	KEY `conopenid` (`conopenid`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS cdb_connect_memberbindlog (
	mblid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	uid mediumint(8) unsigned NOT NULL DEFAULT '0',
	uin char(40) NOT NULL,
	`type` tinyint(1) NOT NULL DEFAULT '0',
	dateline int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (mblid),
	KEY uid (uid),
	KEY uin (uin),
	KEY dateline (dateline)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS cdb_uin_black (
	uin char(40) NOT NULL,
	uid mediumint(8) unsigned NOT NULL DEFAULT '0',
	dateline int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (uin),
	UNIQUE KEY uid (uid)
) TYPE=MyISAM;

REPLACE INTO cdb_settings VALUES ('regconnect', '1');
REPLACE INTO cdb_settings VALUES ('connect', 'a:19:{s:5:"allow";s:1:"1";s:4:"feed";a:2:{s:5:"allow";s:1:"1";s:5:"group";s:1:"0";}s:1:"t";a:2:{s:5:"allow";s:1:"1";s:5:"group";s:1:"0";}s:10:"like_allow";s:1:"1";s:7:"like_qq";s:0:"";s:10:"turl_allow";s:1:"1";s:7:"turl_qq";s:0:"";s:8:"like_url";s:0:"";s:17:"register_birthday";s:1:"0";s:15:"register_gender";s:1:"0";s:17:"register_uinlimit";s:0:"";s:21:"register_rewardcredit";s:1:"1";s:18:"register_addcredit";s:0:"";s:16:"register_groupid";s:1:"0";s:18:"register_regverify";s:1:"1";s:15:"register_invite";s:1:"1";s:10:"newbiespan";s:0:"";s:9:"turl_code";s:0:"";s:13:"mblog_app_key";s:0:"";}');

EOF;

runquery($sql);

$result = $db->fetch_first("DESCRIBE {$tablepre}members conisbind");
if(!$result) {
	$member_sql = "ALTER TABLE `cdb_members` ADD `conisbind` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX (`conisbind`);";
	runquery($member_sql);
}

$finish = true;

?>
