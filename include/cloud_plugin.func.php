<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cloud.func.php 51 2011-09-14 09:37:01Z yexinhao $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

// 安装插件函数
function _installplugin($identifier, $repair = 0) {
    global $tablepre, $importtxt, $db, $_DCACHE;
    
	if(empty($identifier)) {
		return false;
	}
	// 已经安装过
	$plugin = $db->fetch_first("SELECT name, pluginid FROM {$tablepre}plugins WHERE identifier='{$identifier}' LIMIT 1");
	// 如果插件已经安装，则不操作
	if ($plugin) {
	    return true;    
	}
	//print_r($plugin);exit;
	// xml 文件是否存在
	$xmlfile = 'discuz_plugin_'.$identifier.'.xml';
	$importfile = DISCUZ_ROOT.'./plugins/'.$identifier.'/'.$xmlfile;
	if(!file_exists($importfile)) {
		return false;
	}
	$importtxt = @implode('', file($importfile));
	
	$pluginarray = _importdata($importtxt);

	// 安装语言包
	$langexists = FALSE;
	if(!empty($pluginarray['language'])) {
		@mkdir('./forumdata/plugins/', 0777);
		$file = DISCUZ_ROOT.'./forumdata/plugins/'.$pluginarray['plugin']['identifier'].'.lang.php';
		if($fp = @fopen($file, 'wb')) {
			$scriptlangstr = !empty($pluginarray['language']['scriptlang']) ? "\$scriptlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['scriptlang']) : '';
			$templatelangstr = !empty($pluginarray['language']['templatelang']) ? "\$templatelang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['templatelang']) : '';
			$installlangstr = !empty($pluginarray['language']['installlang']) ? "\$installlang['".$pluginarray['plugin']['identifier']."'] = ".langeval($pluginarray['language']['installlang']) : '';
			fwrite($fp, "<?php\n".$scriptlangstr.$templatelangstr.$installlangstr.'?>');
			fclose($fp);
		}
		$langexists = TRUE;
	}
	
	if(!empty($pluginarray['intro']) || $langexists) {
		$pluginarray['plugin']['modules'] = unserialize(stripslashes($pluginarray['plugin']['modules']));
		if(!empty($pluginarray['intro'])) {
			require_once DISCUZ_ROOT.'./include/discuzcode.func.php';
			$pluginarray['plugin']['modules']['extra']['intro'] = discuzcode(stripslashes(strip_tags($pluginarray['intro'])), 1, 0);
		}
		$langexists && $pluginarray['plugin']['modules']['extra']['langexists'] = 1;
		$pluginarray['plugin']['modules'] = addslashes(serialize($pluginarray['plugin']['modules']));
	}
	$sql1 = $sql2 = $comma = '';
	foreach($pluginarray['plugin'] as $key => $val) {
		if($key == 'directory') {
			$val .= (!empty($val) && substr($val, -1) != '/') ? '/' : '';
		} elseif($key == 'available') {
		    // 修复的时候不开启
		    if ($repair == 1) {
		        $val = 0;
		    } else {
		        $val = 1;
		    }
		}
		$sql1 .= $comma.$key;
		$sql2 .= $comma.'\''.$val.'\'';
		$comma = ',';
	}
	// 维持 puginid 不变
	if ($plugin) {
	    $db->query("REPLACE INTO {$tablepre}plugins (pluginid, $sql1) VALUES ($plugin[pluginid], $sql2)");
	} else {
	    $db->query("REPLACE INTO {$tablepre}plugins ($sql1) VALUES ($sql2)");    
	}	
	$pluginid = $db->insert_id();

	foreach(array('hooks', 'vars') as $pluginconfig) {
	    // 删除旧的插件配置信息
	    $db->query("DELETE FROM {$tablepre}plugin$pluginconfig WHERE pluginid = {$pluginid}");
		if(is_array($pluginarray[$pluginconfig])) {
			foreach($pluginarray[$pluginconfig] as $config) {
				$sql1 = 'pluginid';
				$sql2 = '\''.$pluginid.'\'';
				foreach($config as $key => $val) {
					$sql1 .= ','.$key;
					$sql2 .= ',\''.$val.'\'';
				}
				$db->query("INSERT INTO {$tablepre}plugin$pluginconfig ($sql1) VALUES ($sql2)");
			}
		}
	}
	
	// 安装数据库
	$filename = $pluginarray['installfile'];
	
	if(file_exists($langfile = DISCUZ_ROOT.'./forumdata/plugins/'.$identifier.'.lang.php')) {
		@include $langfile;
	}
	
	if(!empty($filename) && preg_match('/^[\w\.]+$/', $filename)) {
		$filename = DISCUZ_ROOT.'./plugins/'.$identifier.'/'.$filename;
		
		if(file_exists($filename)) {
			include_once $filename;
		}
	}
	return true;
}

// 运行sql机制
if(!function_exists('runquery')) {
    function runquery($sql) {
    	global $dbcharset, $tablepre, $db;
    
    	$sql = str_replace("\r", "\n", str_replace(array(' cdb_', ' {tablepre}', ' `cdb_'), array(' '.$tablepre, ' '.$tablepre, ' `'.$tablepre), $sql));
    	$ret = array();
    	$num = 0;
    	foreach(explode(";\n", trim($sql)) as $query) {
    		$queries = explode("\n", trim($query));
    		foreach($queries as $query) {
    			$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
    		}
    		$num++;
    	}
    	unset($sql);
    
    	foreach($ret as $query) {
    		$query = trim($query);
    		if($query) {
    
    			if(substr($query, 0, 12) == 'CREATE TABLE') {
    				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
    				$db->query(createtable($query, $dbcharset));
    
    			} else {
    				$db->query($query);
    			}
    
    		}
    	}
    }
}

// 建表
if(!function_exists('createtable')) {
    function createtable($sql, $dbcharset) {
    	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
    	$type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
    	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).
    	(mysql_get_server_info() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
    }
}
// 建立语言包
if(!function_exists('langeval')) {
    function langeval($array) {
    	$return = array();
    	$array = langvalstripslashes($array);
    	return var_export($array, 1).";\n\n";
    }
}
// 建立语言包
if(!function_exists('langvalstripslashes')) {
    function langvalstripslashes($string) {
    	if(is_array($string)) {
    		foreach($string as $key => $val) {
    			$string[$key] = langvalstripslashes($val);
    		}
    	} else {
    		$string = stripslashes($string);
    	}
    	return $string;
    }
}

// xml2aray
function _importdata($importtxt, $addslashes = 1) {

	include_once DISCUZ_ROOT.'./include/xml.class.php';
	$xmldata = xml2array($importtxt);
	if (!is_array($xmldata) || !$xmldata) {
	    return array();    
	}
	if ($xmldata['Title'] != 'Discuz! Plugin') {
	    return array();    
	}
	$xmldata = exportarray($xmldata['Data'], 0);
	if($addslashes) {
		$xmldata = daddslashes($xmldata, 1);
	}
	return $xmldata;
}

// 处理xml数组
if(!function_exists('exportarray')) {
    function exportarray($array, $method) {
    	$tmp = $array;
    	if($method) {
    		foreach($array as $k => $v) {
    			if(is_array($v)) {
    				$tmp[$k] = exportarray($v, 1);
    			} else {
    				$uv = unserialize($v);
    				if($uv && is_array($uv)) {
    					$tmp['__'.$k] = exportarray($uv, 1);
    					unset($tmp[$k]);
    				} else {
    					$tmp[$k] = $v;
    				}
    			}
    		}
    	} else {
    		foreach($array as $k => $v) {
    			if(is_array($v)) {
    				if(substr($k, 0, 2) == '__') {
    					$tmp[substr($k, 2)] = serialize(exportarray($v, 0));
    					unset($tmp[$k]);
    				} else {
    					$tmp[$k] = exportarray($v, 0);
    				}
    			} else {
    				$tmp[$k] = $v;
    			}
    		}
    	}
    	return $tmp;
    }
}

?>