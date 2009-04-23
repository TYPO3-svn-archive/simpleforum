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
 * classes/views/class.tx_simpleforum_threadsView.php
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
class tx_simpleforum_threadsView extends tx_simpleforum_abstractView {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_threadsView';
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_threadsView.php';

	/**
	 * Threads
	 *
	 * @var array
	 */
	protected $threads;

	/**
	 * @var tx_simpleforum_forumModel
	 */
	protected $forum;

	public function setThreads(array $threads) {
		$this->threads = $threads;
	}

	public function setForum(tx_simpleforum_forumModel $forum) {
		$this->forum = $forum;
	}

	/**
	 * (non-PHPdoc)
	 * @see classes/views/tx_simpleforum_abstractViewInterface#render()
	 */
	public function render() {
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###THREADLIST###');

		$breadcrumb = new tx_simpleforum_breadcrumbViewHelper();
		$breadcrumb->setForum($this->forum);

		$adminMenu = new tx_simpleforum_adminMenuViewHelper();

		$form = new tx_simpleforum_formView();
		$form->setForum($this->forum);

		$marker = array(
			'###THREADTITLE###' => $this->forum->topic,
			'###BREADCRUMB###' => $breadcrumb->render(),
			'###LABEL_TOPIC###' => $this->pObj->getLL('L_Topic'),
			'###LABEL_REPLYS###' => $this->pObj->getLL('L_Replys'),
			'###LABEL_AUTHOR###' => $this->pObj->getLL('L_Author'),
			'###LABEL_LASTPOST###' => $this->pObj->getLL('L_LastPost'),
			'###PAGEBROWSER###' => $this->getListGetPageBrowser(),
			'###NEWTHREADFORM###' => $form->render(),
		);
		$temp['titleRow'] = $this->pObj->cObj->substituteMarkerArray($this->pObj->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		$temp['dataTemplate'] = $this->pObj->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($this->threads as $thread) {
			if (intVal($thread->getStatistics('postnumber')) == 0) continue;

			$specialIcon = '';
			if ($thread->isLocked()) {
				$specialIcon = '<img src="' . $this->pObj->conf['lockedIcon'] . '" />';
			}

			$markerSub = array(
				'###ALTID###' => $i,
				'###SPECIALICON###' => $specialIcon,
				'###THREADTITLE###' => tx_simpleforum_linkViewHelper::toThread($thread),
				'###AUTHOR###' => tx_simpleforum_linkViewHelper::toUser(tx_simpleforum_userModel::getInstance($thread->author)),
				'###POSTSNUMBER###' => ($thread->getStatistics('postnumber')-1),
				'###LASTPOST_DATETIME###' => tx_simpleforum_dateViewHelper::wrapDateString($thread->getStatistics('lastpost')),
				'###LASTPOST_USER###' => tx_simpleforum_linkViewHelper::toUser(tx_simpleforum_userModel::getInstance($thread->getStatistics('user'))),
			);

			$rowContent = $this->pObj->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);


			$confArr = array(
				'template' => $this->pObj->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
				'id' => $thread->uid,
				'show' => array('delete','move'),
				'type' => 'thread',
				'leftright' => 'left'
			);
			if ($thread->isLocked()) {
				$confArr['show'][] = 'unlock';
			} else {
				$confArr['show'][] = 'lock';
			}
			$adminMenu->setConf($confArr);
			$temp['dataRows'][] = $this->pObj->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu->render());

			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->pObj->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->pObj->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->pObj->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_threadsView.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_threadsView.php']);
}
?>