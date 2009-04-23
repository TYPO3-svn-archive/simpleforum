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
 * classes/views/class.tx_simpleforum_formView.php
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
class tx_simpleforum_formView extends tx_simpleforum_abstractView {
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_formView.php';	// Path to this script relative to the extension dir.
	protected $extKey			= 'simpleforum';	// The extension key.

	/**
	 * @var tx_simpleforum_forumModel
	 */
	protected $forum;

	/**
	 * @var tx_simpleforum_threadModel
	 */
	protected $thread;

	public function setForum(tx_simpleforum_forumModel $forum) {
		$this->forum = $forum;
	}

	public function setThread(tx_simpleforum_threadModel $thread) {
		$this->thread = $thread;
	}

	/**
	 * Returns form. The type of the form is based on the conf array
	 *
	 * @param	array		$conf: array with configuration
	 * @return	string		HTML output
	 */
	function render() {
		$content = '';
		if ($this->thread === null) {
			$threadOpen = true;
			$template = $this->pObj->cObj->getSubpart($this->templateCode, '###NEWTHREAD###');
		} else {
			$threadOpen = !$this->thread->isLocked();
			$template = $this->pObj->cObj->getSubpart($this->templateCode, '###REPLYBOX###');
		}
		if ($this->pObj->auth->isAdmin) $threadOpen = true;

		if ($GLOBALS['TSFE']->loginUser && $threadOpen) {

			$actionLink = $this->pObj->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'exclude' => 'cHash,no_cache',
				),
				'additionalParams' => '&no_cache=1',
				'useCacheHash' => false,
			));

			$marker = array(
				'###FID###' => $this->forum->uid,
				'###TID###' => ($this->thread === null ? '' : $this->thread->uid),
				'###LABEL_REPLYTO###' => $this->pObj->getLL('L_ReplyTo'),
				'###THREADTITLE###' => ($this->thread === null ? '' : $this->thread->topic),
				'###L_SUBMIT###' => $this->pObj->getLL('L_Submit'),
				'###ACTION_URL###' => $actionLink,
				'###L_NEW_THREAD###' => $this->pObj->getLL('L_NewThread'),
				'###FORUMTITLE###' => $this->forum->topic,
				'###L_THREADTITLE###' => $this->pObj->getLL('L_ThreadTitle'),
				'###V_THREADTITLE###' => '',
				'###V_MESSAGE###' => '',
			);

			$content = $this->pObj->cObj->substituteMarkerArray($template, $marker);
		} elseif ($GLOBALS['TSFE']->loginUser && !$threadOpen) {
			$content = $this->pObj->getLL('message_threadLocked');
		} elseif (!$GLOBALS['TSFE']->loginUser && $thread !== null) {
			$content = $this->pObj->getLL('message_loginForReply');
		} else {
			$content = $this->pObj->getLL('message_loginForThread');
		}
		//$this->initRTE();
		//$content .= $RTEItem = $this->RTEObj->drawRTE($this, 'tx_simpleforum_posts', 'message', $row, $PA, $specConf, $thisConfig, $RTEtypeVal, '', $thePidValue);
		return $content;
	}

	function initRTE() {

		if(!$this->RTEObj && $this->conf['RTEenabled']=1 && t3lib_extMgm::isLoaded('rtehtmlarea')) {
		require_once(t3lib_extMgm::extPath('rtehtmlarea').'pi2/class.tx_rtehtmlarea_pi2.php');
			$this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');
		} elseif (!$this->RTEObj && $this->conf['RTEenabled']=1 && t3lib_extMgm::isLoaded('tinymce_rte')) {
			require_once(t3lib_extMgm::extPath('tinymce_rte').'pi1/class.tx_tinymce_rte_pi1.php');
			$this->RTEObj = t3lib_div::makeInstance('tx_tinymce_rte_pi1');
		} else {
			$this->RTEObj = 0;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_formView.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_formView.php']);
}

?>