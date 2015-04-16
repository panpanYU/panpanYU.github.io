<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id: Photo.php 2 2011-09-07 06:49:01Z yexinhao $
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Photo extends MyBase {

	/**
	 * 创建相册
	 * @param integer $uId 用户Id
	 * @param string  $name 相册名称
	 * @param string  $privacy 相册限制
	 * @param string  $passwd 查看相册时的密码
	 * @param string  $friends 允许查看相册的好友Id
	 * @return integer 相册Id
	 */
	function createAlbum($uId, $name, $privacy, $passwd = null, $friendIds = null) {
		return new APIResponse(0);
	}

	/**
	 * 更新相册
	 * @param integer $uId 用户Id
	 * @param intger  $aId 相册Id
	 * @param string  $name 相册名称
	 * @param string  $privacy 相册限制
	 * @param string  $passwd 查看相册时的密码
	 * @param string  $friends 允许查看相册的好友Id
	 * @param integer $coverId 相册封面Id
	 * @return boolean
	 */
	function updateAlbum($uId, $aId, $name = null, $privacy = null, $passwd = null, $friendIds = null, $coverId = null) {
		return new APIResponse(0);
	}

	/**
	 * 移除相册
	 *
	 * @param integer $uId 用户Id
	 * @param integer $aId 相册Id
	 * @param string  $action 动作
	 * @param integer $targetAlbumId 目标相册Id
	 * @return boolean
	 */
	function removeAlbum($uId, $aId, $action = null , $targetAlbumId = null) {
		return new APIResponse(0);
	}

	/**
	 * 获取用户的相册列表
	 *
	 * @param integer $uId 用户Id
	 * @return array
	 */
	function getAlbums($uId) {
		return new APIResponse(0);
	}

	//note todo 测试远程模式上传的图片
	/**
	 * 上传照片
	 *
	 * @param integer $uId 用户Id
	 * @param integer $aId 相册Id
	 * @param string  $fileName 文件名
	 * @param string  $fileType 文件类型
	 * @param integer $fileSize 文件大小
	 * @param string  $data 照片数据
	 * @param string  $caption 照片说明
	 * @return array
	 */
	function upload($uId, $aId, $fileName, $fileType, $fileSize, $data, $caption = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * 获取照片信息
	 *
	 * @param integer $uId 用户Id
	 * @param integer $aId 相册Id
	 * @param array   $pIds 图片Id列表
	 * @return array
	 */
	function get($uId, $aId, $pIds = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * 更新一张照片
	 * @param integer $uId 用户Id
	 * @param integer $aId 相册Id
	 * @param string  $fileName 文件名
	 * @param string  $fileType 文件类型
	 * @param integer $fileSize 文件大小
	 * @param string  $caption 照片说明
	 * @param string  $data 照片数据
	 *
	 */
	function update($uId, $pId, $aId, $fileName = null, $fileType = null, $fileSize = null, $caption = null, $data = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * 删除照片
	 *
	 * @param integer $uId 用户Id
	 * @param array   $pIds 照片Id列表
	 * @return array
	 */
	function remove($uId, $pIds) {
		$result = array();
		return new APIResponse($result);
	}

}

?>