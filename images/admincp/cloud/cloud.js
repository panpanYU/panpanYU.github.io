
/**
* 开通云平台介绍时的提交事件
*/
function submitForm() {

	if (dialogHtml == '') {
		dialogHtml = $('siteInfo').innerHTML;
		$('siteInfo').innerHTML = '';
	}

	cloudShowWindow('open_cloud', dialogHtml, 'html');

	$('fwin_open_cloud').style.top = '80px';
	$('cloud_api_ip').value = cloudApiIp;

	return false;
}

/**
* 显示浮动窗口
* k		浮窗的key
* url		浮窗请求的url
* mode		get和post两种方式
* cache		是否缓存浮窗		0:否 1:是 -1强制浏览器更新
*/
function cloudShowWindow(k, url, mode, cache, menuv) {
	mode = isUndefined(mode) ? 'get' : mode;
	cache = isUndefined(cache) ? 1 : cache;
	var menuid = 'fwin_' + k;
	var menuObj = $(menuid);
	var drag = null;
	var loadingst = null;
	var hidedom = '';

	if(disallowfloat && disallowfloat.indexOf(k) != -1) {
		if(BROWSER.ie) url += (url.indexOf('?') != -1 ?  '&' : '?') + 'referer=' + escape(location.href);
		location.href = url;
		doane();
		return;
	}

	var fetchContent = function() {
		if(mode == 'get') {
			menuObj.url = url;
			url += (url.search(/\?/) > 0 ? '&' : '?') + 'infloat=yes&handlekey=' + k;
			url += cache == -1 ? '&t='+(+ new Date()) : '';
			ajaxget(url, 'fwin_content_' + k, null, '', '', function() {initMenu();show();});
		} else if(mode == 'post') {
			menuObj.act = $(url).action;
			ajaxpost(url, 'fwin_content_' + k, '', '', '', function() {initMenu();show();});
		}
		if(parseInt(BROWSER.ie) != 6) {
			loadingst = setTimeout(function() {showDialog('', 'info', '<img src="' + IMGDIR + '/loading.gif"> 请稍候...')}, 500);
		}
	};
	var initMenu = function() {
		clearTimeout(loadingst);
		var objs = menuObj.getElementsByTagName('*');
		var fctrlidinit = false;
		for(var i = 0; i < objs.length; i++) {
			if(objs[i].id) {
				objs[i].setAttribute('fwin', k);
			}
			if(objs[i].className == 'flb' && !fctrlidinit) {
				if(!objs[i].id) objs[i].id = 'fctrl_' + k;
				drag = objs[i].id;
				fctrlidinit = true;
			}
		}
	};
	var show = function() {
		hideMenu('fwin_dialog', 'dialog');
		v = {'mtype':'win','menuid':menuid,'duration':3,'pos':'00','zindex':JSMENU['zIndex']['win'],'drag':typeof drag == null ? '' : drag,'cache':cache};
		for(k in menuv) {
			v[k] = menuv[k];
		}
		showMenu(v);
	};

	if(!menuObj) {
		menuObj = document.createElement('div');
		menuObj.id = menuid;
		menuObj.className = 'fwinmask';
		menuObj.style.display = 'none';
		$('append_parent').appendChild(menuObj);
		evt = ' style="cursor:move" onmousedown="dragMenu($(\'' + menuid + '\'), event, 1)" ondblclick="hideWindow(\'' + k + '\')"';
		if(!BROWSER.ie) {
			hidedom = '<style type="text/css">object{visibility:hidden;}</style>';
		}
		menuObj.innerHTML = hidedom + '<table cellpadding="0" cellspacing="0" class="fwin"><tr><td class="t_l"></td><td class="t_c"' + evt + '></td><td class="t_r"></td></tr><tr><td class="m_l"' + evt + ')">&nbsp;&nbsp;</td><td class="m_c" id="fwin_content_' + k + '">'
			+ '</td><td class="m_r"' + evt + '"></td></tr><tr><td class="b_l"></td><td class="b_c"' + evt + '></td><td class="b_r"></td></tr></table>';
		if(mode == 'html') {
			$('fwin_content_' + k).innerHTML = url;
			initMenu();
			show();
		} else {
			fetchContent();
		}
	} else if((mode == 'get' && (url != menuObj.url || cache != 1)) || (mode == 'post' && $(url).action != menuObj.act)) {
		fetchContent();
	} else {
		show();
	}
	doane();
}

/**
* 开通云平台介绍信息回调函数
*/
function dealHandle(msg) {

	getMsg = true;

	if (msg['status'] == 'error') {
		$('loadinginner').innerHTML = '<font color="red">' + msg['content'] + '</font>';
		return;
	}

	$('loading').style.display = 'none';
	$('mainArea').style.display = '';

	if(cloudStatus == 'upgrade') {
		$('title').innerHTML = msg['cloudIntroduction']['upgrade_title'];
		$('msg').innerHTML = msg['cloudIntroduction']['upgrade_content'];
	} else {
		$('title').innerHTML = msg['cloudIntroduction']['open_title'];
		$('msg').innerHTML = msg['cloudIntroduction']['open_content'];
	}

	if (msg['navSteps']) {
		$('nav_steps').innerHTML = msg['navSteps'];
	}

	if (msg['protocalUrl']) {
		$('protocal_url').href = msg['protocalUrl'];
	}

	if (msg['cloudApiIp']) {
		cloudApiIp = msg['cloudApiIp'];
	}

	if (msg['manyouUpdateTips']) {
		$('manyou_update_tips').innerHTML = msg['manyouUpdateTips'];
	}
}

/**
* 开通云平台介绍信息时超时
*/
function expiration() {

	if(!getMsg) {
		$('loadinginner').innerHTML = '<font color="red">' + expirationText + '</font>';
		clearTimeout(expirationTimeout);
	}
}

/**
* 诊断工具中API IP的回调函数
*/
function apiCallback(apiIps) {

	if (typeof apiIps == 'undefined' || typeof apiIps == 'null' || !apiIps) {
		return false;
	}

	if (apiIps.errorCode) {
		return false;
	}

	if (!apiIps.result || !apiIps.result.cloud_api_ip || !apiIps.result.manyou_api_ip || !apiIps.result.qzone_api_ip) {
		return false;
	}

	if (!$('cloud_tbody_api_test') || !$('cloud_tbody_manyou_test') || !$('cloud_tbody_qzone_test')) {
		return false;
	}

	var cloudAPIIPs = apiIps.result.cloud_api_ip;
	var manyouAPIIPs = apiIps.result.manyou_api_ip;
	var QzoneAPIIPs = apiIps.result.qzone_api_ip;

	// 云平台接口IP测试
	ajaxShowAPIStatus(1, cloudAPIIPs);

	// 漫游接口IP测试
	ajaxShowAPIStatus(2, manyouAPIIPs);

	// Qzone接口IP测试
	ajaxShowAPIStatus(3, QzoneAPIIPs);

}

/**
* 诊断工具中输出其他接口IP的测试结果(修正IE innerHTML问题)
*/
function ajaxShowAPIStatus(apiType, ips) {

	var apiType = parseInt(apiType);

	for(i in ips) {
		var apiIp = ips[i].ip;
		var apiDescription = ips[i].description;
		var apiTr = document.createElement('tr');

		var apiTdFirst = document.createElement('td');
		apiTdFirst.className = 'td24';
		if (!apiType || apiType == 1) {
			apiTdFirst.innerHTML = '<strong>云平台其他接口测试</strong>';
		} else if (apiType == 2) {
			apiTdFirst.innerHTML = '<strong>漫游其他接口测试</strong>';
		} else if (apiType == 3) {
			apiTdFirst.innerHTML = '<strong>QQ互联接口测试</strong>';
		}

		var apiTdSecond = document.createElement('td');
		apiTdSecond.innerHTML = '<div id="_doctor_apitest_' + apiType + '_' + apiIp + '">&nbsp;</div>';

		apiTr.appendChild(apiTdFirst);
		apiTr.appendChild(apiTdSecond);

		if (!apiType || apiType == 1) {
			$('cloud_tbody_api_test').appendChild(apiTr);
		} else if (apiType == 2) {
			$('cloud_tbody_manyou_test').appendChild(apiTr);
		} else if (apiType == 3) {
			$('cloud_tbody_qzone_test').appendChild(apiTr);
		}
	}

	for(i in ips) {
		var apiIp = ips[i].ip;
		var apiDescription = ips[i].description;
		ajaxget('admincp.php?action=cloud&operation=doctor&op=apitest&api_type=' + apiType + '&api_ip=' + encodeURI(apiIp) + '&api_description=' + encodeURI(apiDescription), '_doctor_apitest_' + apiType + '_' + apiIp);
	}

}