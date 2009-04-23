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
 * classes/controller/class.tx_simpleforum_widgetController.php
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
class tx_simpleforum_widgetController extends tx_simpleforum_abstractController {
	protected $prefixId			= 'tx_simpleforum_widgetController';
	protected $scriptRelPath	= 'classes/controller/class.tx_simpleforum_widgetController.php';

	/**
	 * @var tx_simpleforum_abstractView
	 */
	protected $view;


	public function main() {
		$this->controllerDispatcher();

		$content = $this->view->render();
		return $content;
	}

	protected function indexAction() {
		$forum = new tx_simpleforum_forumModel();
		$forums = $forum->findAll();

		$this->view = new tx_simpleforum_forumsView();
		$this->view->setForums($forums);
	}

	protected function forumAction($forumUid) {
		$forum = new tx_simpleforum_forumModel();
		$forum->findByUid($forumUid);

		$thread = new tx_simpleforum_threadModel();
		$this->pObj->numberOfPages = $thread->getRowCount(' AND fid=' . $forumUid);
		$threads = $thread->findAll(' AND fid=' . $forumUid, $this->pObj->piVars['page']*$this->pObj->conf['pageSize'] . ',' . $this->pObj->conf['pageSize']);

		$this->view = new tx_simpleforum_threadsView();
		$this->view->setForum($forum);
		$this->view->setThreads($threads);
	}

	protected function threadAction($threadUid) {
		$thread = new tx_simpleforum_threadModel();
		$thread->findByUid($threadUid);
		$forum = new tx_simpleforum_forumModel();
		$forum->findByUid($thread->fid);

		$post = new tx_simpleforum_postModel();

		$this->pObj->numberOfPages = $post->getRowCount(' AND tid=' . $threadUid);
		$posts = $post->findAll(' AND tid=' . $threadUid, '', $this->pObj->piVars['page']*$this->pObj->conf['pageSize'] . ',' . $this->pObj->conf['pageSize']);

		$this->view = new tx_simpleforum_postsView();
		$this->view->setForum($forum);
		$this->view->setThread($thread);
		$this->view->setPosts($posts);
	}

	protected function controllerDispatcher() {
		if (intVal($this->pObj->piVars['tid']) > 0) {
			$this->threadAction(intVal($this->pObj->piVars['tid']));
		} elseif (intVal($this->pObj->piVars['fid']) > 0) {
			$this->forumAction(intVal($this->pObj->piVars['fid']));
		} else {
			$this->indexAction();
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/controller/class.tx_simpleforum_widgetController.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/controller/class.tx_simpleforum_widgetController.php']);
}
?>