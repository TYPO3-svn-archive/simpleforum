<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Peter Schuster <typo3@peschuster.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * classes/class.tx_simpleforum_admin.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */


/**
 * [DESCRIPTION]
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_admin {
	var $prefixId		= 'tx_simpleforum_pi1';
	var $scriptRelPath	= 'classes/class.tx_simpleforum_admin.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.

	/**
	 * cObj
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	var $continue		= true;

	function start($conf, $piVars, &$pObj) {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pObj = &$pObj;
		$this->conf = $conf;
		$this->piVars = $piVars;
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
	}


	/**
	 * Calls specific admin functions based on provieded GET/POST parameters
	 *
	 * @return	string		HTML output
	 */
	function dispatch() {
		$content = '<h1>' . $this->pObj->pi_getLL('contextmenu_' . $this->piVars['adminAction']) . '</h1>';
		$this->piVars['id'] = intVal($this->piVars['id']);

		$arrYes = array(
			$this->prefixId.'[type]' => $this->piVars['type'],
			$this->prefixId.'[id]' => $this->piVars['id'],
			$this->prefixId.'[chk]' => $this->piVars['chk'],
			$this->prefixId.'[do]' => md5($this->piVars['id'].$this->piVars['chk']),
			$this->prefixId.'[tid]' => $this->piVars['tid'],
			$this->prefixId.'[fid]' => $this->piVars['fid']
		);
		$arrNo = array(
			$this->prefixId.'[tid]' => $this->piVars['tid'],
			$this->prefixId.'[fid]' => $this->piVars['fid']
		);

		switch($this->piVars['adminAction']) {
			CASE 'edit':
				break;
			CASE 'delete':
				if (md5($this->piVars['id'].$this->piVars['chk']) == $this->piVars['do']) {
					$content .= $this->admin_delete($this->piVars['type'],$this->piVars['id']);
				} else {
					$content .=	$this->admin_alert(
						$this->pObj->pi_getLL('message_delete'),
						array_merge($arrYes, array($this->prefixId.'[adminAction]' => 'delete')),
						$arrNo
					);
				}
				break;
			CASE 'lock':
				$content .= $this->admin_lock($this->piVars['type'],$this->piVars['id']);
				break;
			CASE 'unlock':
				$content .= $this->admin_unlock($this->piVars['type'],$this->piVars['id']);
				break;
			CASE 'move':
				if (md5($this->piVars['id'].$this->piVars['chk']) == $this->piVars['do']) {
					$content .= $this->admin_move($this->piVars['type'],$this->piVars['id'], $this->piVars['moveselect']);
				} else {
					$content .= $this->admin_move_form($arrYes, $arrNo);
				}
				break;
			CASE 'hide':
				if (md5($this->piVars['id'].$this->piVars['chk']) == $this->piVars['do']) {
					$content .= $this->admin_hide($this->piVars['type'],$this->piVars['id']);
				} else {
					$content .= $this->admin_alert(
						$this->pObj->pi_getLL('message_hide'),
						array_merge($arrYes, array($this->prefixId.'[adminAction]' => 'hide')),
						$arrNo
					);
				}
				break;
		}
		return $content;
	}

	/**
	 * Returns form asking to which location thread/posts should be moved
	 *
	 * @param	array		$arrYes: array of urlParamters for "Submit"-Link
	 * @param	array		$arrNo: array of urlParamters for "cancel"-Link
	 * @return	string		HTML output
	 */
	function admin_move_form($arrYes, $arrNo) {
		switch ($this->piVars['type']) {
			CASE 'post':
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,topic', 'tx_simpleforum_threads', 'deleted=0 AND hidden=0', '', 'crdate DESC');
				$options = array();
				foreach ($rows as $row) {
					$options[$row['uid']] = $row['topic'];
				}
				$message = $this->pObj->pi_getLL('message_move_posts');
				break;
			CASE 'thread':
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,topic', 'tx_simpleforum_forums', 'deleted=0 AND hidden=0', '', 'crdate DESC');
				$options = array();
				foreach ($rows as $row) {
					$options[$row['uid']] = $row['topic'];
				}
				$message = $this->pObj->pi_getLL('message_move_thread');
				break;
		}

		$conf = array(
			'formtype' => 'select',
			'options' => $options,
		);
		return $this->admin_form(
			$conf,
			$message,
			array_merge($arrYes, array($this->prefixId.'[adminAction]' => 'move', 'no_cache' => 1)),
			$arrNo
		);
	}

	/**
	 * Shows message asking whether to continue or cancel process
	 *
	 * @param	string		$message: displayed message
	 * @param	array		$yes: array of urlParameters for "Continue"-Link
	 * @param	array		$no: array of urlParameters for "Cancel"-Link
	 * @return	string		HTML output
	 */
	function admin_alert($message, $yes, $no) {
		$template = $this->cObj->getSubpart($this->templateCode, '###ALERT###');

		$links['yes'] = $this->pObj->pi_linkToPage($this->pObj->pi_getLL('yes'),$GLOBALS['TSFE']->id, '',$yes);
		$links['no'] = $this->pObj->pi_linkToPage($this->pObj->pi_getLL('no'),$GLOBALS['TSFE']->id, '',$no);

		$marker = array(
			'###MESSAGE###' => $message,
			'###YES###' => $links['yes'],
			'###NO###' => $links['no'],
		);
		$this->continue = false;

		$content = $this->cObj->substituteMarkerArray($template, $marker);
		return $content;
	}

	/**
	 * Shows message asking whether to continue or cancel process
	 * and asking for further input
	 *
	 * @param	array		$conf: Konfiguration array(formtype, options)
	 * @param	string		$message: displayed message
	 * @param	array		$action: array of urlParameters for "Continue"-Link
	 * @param	array		$no: array of urlParameters for "Cancel"-Link
	 * @return	string		HTML output
	 */
	function admin_form($conf, $message, $action, $no) {
		$template = $this->cObj->getSubpart($this->templateCode, '###ALERTFORM###');

		switch($conf['formtype']) {
			CASE 'select':
				$selectTemplate = $this->cObj->getSubpart($template, '###SELECTBOX###');
				$optionTemplate = $this->cObj->getSubpart($selectTemplate, '###OPTIONS###');

				$rows = array();
				foreach ($conf['options'] as $value => $label) {
					$rows[] = $this->cObj->substituteMarkerArray($optionTemplate, array('###LABEL###' => $label, '###VALUE###' => $value));
				}
				$selectTemplate = $this->cObj->substituteSubpart($selectTemplate, '###OPTIONS###', implode('', $rows));
				$template = $this->cObj->substituteSubpart($template, '###SELECTBOX###', $selectTemplate);

				break;
			CASE 'text':
				break;
		}

		foreach ($action as $k => $v) {
			$urlParameterStr .= '&'.$k.'='.$v;
		}
		$actionUrl = $this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'addQueryString' => 1,
			'addQueryString.' => array(
				'exclude' => 'cHash,no_cache',
			),
			'additionalParams' => $urlParameterStr,
			'useCacheHash' => false,
		));
		$links['no'] = $this->pObj->pi_linkToPage($this->pObj->pi_getLL('no'),$GLOBALS['TSFE']->id, '',$no);

		$marker = array(
			'###MESSAGE###' => $message,
			'###ACTIONURL###' => $actionUrl,
			'###L_SUBMIT###' => $this->pObj->pi_getLL('L_Submit'),
			'###NO###' => $links['no'],
		);
		$this->continue = false;

		$content = $this->cObj->substituteMarkerArray($template, $marker);
		return $content;
	}

	/**
	 * Process edit of new post-content/thread-topic
	 *
	 * @param	string		$type: allowed: post/thread
	 * @param	integer		$id: uid of post/thread
	 * @param	string		$content: new content
	 * @return	void
	 */
	function admin_edit($type, $id, $content) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('message'=>$content));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('topic'=>$content));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				break;
		}
	}

	/**
	 * Process lock of thread
	 *
	 * @param	string		$type: allowed: thread
	 * @param	integer		$id: uid of thread
	 * @return	void
	 */
	function admin_lock($type, $id) {
		switch ($type) {
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('locked'=>1));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
		}
	}

	/**
	 * Process unlock of thread
	 *
	 * @param	string		$type: allowed: thread
	 * @param	integer		$id: uid of thread
	 * @return	void
	 */
	function admin_unlock($type, $id) {
		switch ($type) {
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('locked'=>0));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
		}
	}

	/**
	 * Process hide of post
	 *
	 * @param	string		$type: allowed: post
	 * @param	integer		$id: uid of post
	 * @return	void
	 */
	function admin_hide($type, $id) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('approved'=>0));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
		}
	}

	/**
	 * Process move of thread/posts
	 *
	 * @param	string		$type: allowed: post/thread
	 * @param	integer		$id: uid of post/thread
	 * @param	integer		$pid: uid of new parent-record
	 * @return	void
	 */
	function admin_move($type, $id, $pid) {
		switch ($type) {
			CASE 'post':
				$post = $this->data_post($id);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'tid='.$post['tid'].' AND crdate>='.$post['crdate'], array('tid'=>$pid));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('fid'=>$pid));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
		}
	}

	/**
	 * Process delete of post/thread
	 *
	 * CASE 'post':
	 * 		- delted is set to 1 in database record
	 * CASE 'thread':
	 * 		- delted is set to 1 in thread database record
	 * 		- all related post records which are already delted are set to 'hidden'
	 * 		- all related post records are set to 'deleted'
	 *
	 * By this enabling a thread again won't show all sepratedly deleted posts again,
	 * but only the ones which were visible when the thread got deleted
	 *
	 * @param	string		$type: allowed: thread/post
	 * @param	integer		$id: uid of thread/post
	 * @return	void
	 */
	function admin_delete($type, $id) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('deleted'=>1));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('deleted'=>1));
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'deleted=1 AND tid='.$id, array('hidden'=>1));
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'tid='.$id, array('deleted'=>1));
				$this->pObj->cache->deleteCacheForum(intVal($this->piVars['fid']));
				$this->pObj->cache->deleteCacheThread(intVal($this->piVars['tid']));
				break;
		}
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_admin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_admin.php']);
}

?>