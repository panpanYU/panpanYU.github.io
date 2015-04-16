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
	 * �������
	 * @param integer $uId �û�Id
	 * @param string  $name �������
	 * @param string  $privacy �������
	 * @param string  $passwd �鿴���ʱ������
	 * @param string  $friends ����鿴���ĺ���Id
	 * @return integer ���Id
	 */
	function createAlbum($uId, $name, $privacy, $passwd = null, $friendIds = null) {
		return new APIResponse(0);
	}

	/**
	 * �������
	 * @param integer $uId �û�Id
	 * @param intger  $aId ���Id
	 * @param string  $name �������
	 * @param string  $privacy �������
	 * @param string  $passwd �鿴���ʱ������
	 * @param string  $friends ����鿴���ĺ���Id
	 * @param integer $coverId ������Id
	 * @return boolean
	 */
	function updateAlbum($uId, $aId, $name = null, $privacy = null, $passwd = null, $friendIds = null, $coverId = null) {
		return new APIResponse(0);
	}

	/**
	 * �Ƴ����
	 *
	 * @param integer $uId �û�Id
	 * @param integer $aId ���Id
	 * @param string  $action ����
	 * @param integer $targetAlbumId Ŀ�����Id
	 * @return boolean
	 */
	function removeAlbum($uId, $aId, $action = null , $targetAlbumId = null) {
		return new APIResponse(0);
	}

	/**
	 * ��ȡ�û�������б�
	 *
	 * @param integer $uId �û�Id
	 * @return array
	 */
	function getAlbums($uId) {
		return new APIResponse(0);
	}

	//note todo ����Զ��ģʽ�ϴ���ͼƬ
	/**
	 * �ϴ���Ƭ
	 *
	 * @param integer $uId �û�Id
	 * @param integer $aId ���Id
	 * @param string  $fileName �ļ���
	 * @param string  $fileType �ļ�����
	 * @param integer $fileSize �ļ���С
	 * @param string  $data ��Ƭ����
	 * @param string  $caption ��Ƭ˵��
	 * @return array
	 */
	function upload($uId, $aId, $fileName, $fileType, $fileSize, $data, $caption = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * ��ȡ��Ƭ��Ϣ
	 *
	 * @param integer $uId �û�Id
	 * @param integer $aId ���Id
	 * @param array   $pIds ͼƬId�б�
	 * @return array
	 */
	function get($uId, $aId, $pIds = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * ����һ����Ƭ
	 * @param integer $uId �û�Id
	 * @param integer $aId ���Id
	 * @param string  $fileName �ļ���
	 * @param string  $fileType �ļ�����
	 * @param integer $fileSize �ļ���С
	 * @param string  $caption ��Ƭ˵��
	 * @param string  $data ��Ƭ����
	 *
	 */
	function update($uId, $pId, $aId, $fileName = null, $fileType = null, $fileSize = null, $caption = null, $data = null) {
		$result = array();
		return new APIResponse($result);
	}

	/**
	 * ɾ����Ƭ
	 *
	 * @param integer $uId �û�Id
	 * @param array   $pIds ��ƬId�б�
	 * @return array
	 */
	function remove($uId, $pIds) {
		$result = array();
		return new APIResponse($result);
	}

}

?>