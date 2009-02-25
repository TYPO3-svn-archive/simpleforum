<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Peter Schuster <typo3@peschuster.de>
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

require_once(PATH_tslib.'class.tslib_pibase.php');

require_once(t3lib_extMgm::extPath('simpleforum', 'model/class.tx_simpleforum_forum.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'model/class.tx_simpleforum_thread.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'model/class.tx_simpleforum_post.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'model/class.tx_simpleforum_user.php'));

require_once(t3lib_extMgm::extPath('simpleforum', 'classes/class.tx_simpleforum_admin.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'classes/class.tx_simpleforum_auth.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'classes/class.tx_simpleforum_cache.php'));

require_once(t3lib_extMgm::extPath('simpleforum', 'views/class.tx_simpleforum_rendering.php'));


/**
 * Plugin 'Forum' for the 'simpleforum' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_simpleforum
 */
class tx_simpleforum_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_simpleforum_pi1';					// Same as class name
	var $scriptRelPath = 'pi1/class.tx_simpleforum_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'simpleforum';							// The extension key.
	//var $pi_checkCHash = true;
	var $smilieApi;
	var $isAdmin = false;
	var $continue = true;
	var $sorting = array();

	var $forums = array();
	var $threads = array();
	var $posts = array();


	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->init();

		$GLOBALS['TYPO3_DB']->debugOutput = true;

		$check = md5($this->piVars['type'] . $this->piVars['id'] . $this->piVars['adminAction'] . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		if($this->isAdmin && t3lib_div::inList('edit,delete,lock,unlock,move,hide', $this->piVars['adminAction']) && ($check == $this->piVars['chk'])) {
			$content = $this->admin();
		}

		$content = $this->dispatcher();

		$this->clearMem();

		return $this->pi_wrapInBaseClass($content);
	}

	protected function clearMem() {
		// Data Arrays
		unset($this->forums);
		unset($this->threads);
		unset($this->posts);

		// Global Vars
		unset($this->conf);

		// Classes
		unset($this->auth);
		unset($this->view);
		unset($this->cache);
	}

	function init() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_initPIflexForm();
		$this->fetchConfigValue('introtext');
		$this->conf['pageSize'] = 20;
		$this->startRecord = 0;
		$this->piVars['page'] = intVal($this->piVars['page']);

		if (!$this->conf['templateFile']) $this->conf['templateFile'] = 'EXT:simpleforum/res/template.tmpl';
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
		$this->templateCode = $this->cObj->substituteMarker($this->templateCode, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath('simpleforum'));

		//Replace 'EXT:simpleforum/' in conf
		$list = array('lockedIcon', 'adminIcon');
		foreach ($list as $l) $this->conf[$l] = str_replace('EXT:simpleforum/', t3lib_extMgm::siteRelPath('simpleforum'), $this->conf[$l]);

		$key = 'tx_simpleforum_' . md5($this->templateCode);
		if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$headerParts = $this->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
			if ($headerParts) $GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
		}

		$this->model = array(
			'forum' => t3lib_div::makeInstanceClassName('tx_simpleforum_forum'),
			'thread' => t3lib_div::makeInstanceClassName('tx_simpleforum_thread'),
			'post' => t3lib_div::makeInstanceClassName('tx_simpleforum_post'),
			'user' => t3lib_div::makeInstanceClassName('tx_simpleforum_user'),
			'admin' => t3lib_div::makeInstanceClassName('tx_simpleforum_admin'),
			'auth' => t3lib_div::makeInstanceClassName('tx_simpleforum_auth'),
		);

		$this->auth = new $this->model['auth']();
		$this->auth->start($this->conf, $this->piVars, $this);

		$this->view = new tx_simpleforum_rendering();
		$this->view->start($this->conf, $this->piVars, $this);

		$this->parentCE = $this->cObj->data['uid'];

		$this->cache = new tx_simpleforum_cache();
		$this->cache->start($this->conf, $this->piVars, $this);
	}


	function dispatcher() {
		$continue = true;
		$content = '';

		/**
		 * ADMIN ACTIONS
		 */
		if (isset($this->piVars['action']) || isset($this->piVars['adminAction'])) {
			$admin = new $this->model['admin'];
			$admin->start($this->conf, $this->piVars, $this);

			$content .= $admin->dispatch();
			$continue = $admin->continue;
		}
		if (!$continue) return $content;



		/*
		 * PROCESS SUBMITTED DATA
		 */
		$this->processSubmission();



		/*
		 * RETURN FORUM DATA
		 */
		$this->cache->getCache();
		if (!$this->cache->hasCache) {

			if (intVal($this->piVars['tid']) > 0) {
				$dataContent = $this->postlist(intVal($this->piVars['tid']));
			} elseif (intVal($this->piVars['fid']) > 0) {
				$dataContent = $this->threadlist(intVal($this->piVars['fid']));
			} else {
				$dataContent = $this->forumlist();
			}

			$this->cache->setCache($dataContent, array('fid'=>$this->piVars['fid'], 'tid'=>$this->piVars['tid']));
			$content .= $dataContent;
		} else {
			$content .= $this->cache->cached_data;
		}

		$content = $this->afterCacheSubstitution($content);

		return $content;
	}

	function forumlist() {
		$this->getForums();

		$content = $this->view->forumlist($this->forums, $this->sorting['forums']);
		return $content;
	}

	function threadlist($forumId) {
		$forum = new $this->model['forum']($forumId);

		$this->initPagebrowserThreads($forum);
		$this->getThreads($forum);

		$content = $this->view->threadlist($this->threads, $forum, $this->sorting['threads']);
		return $content;
	}

	function postlist($threadId) {
		$thread = new $this->model['thread']($threadId);
		$forum = new $this->model['forum']($thread->getFid());

		$this->initPagebrowserPosts($thread);
		$this->getPosts($thread);

		$content = $this->view->postlist($this->posts, $thread, $forum, $this->sorting['posts']);
		return $content;
	}



	function getForums() {
		$where = array(
			'hidden=0',
			'deleted=0',
			'(starttime<'.mktime().' OR starttime = 0)',
			'(endtime>'.mktime().' OR endtime=0)'
		);
		$forums = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_simpleforum_forums', implode(' AND ', $where), '', 'sorting');

		$this->forums = array();
		foreach ($forums as $forum) {
			$this->forums[$forum['uid']] = new $this->model['forum']($forum);
			$this->sorting['forums'][] = $forum['uid'];
		}
	}

	function getThreadsWhere(tx_simpleforum_forum &$forum) {
		$where = array(
			'tx_simpleforum_threads.hidden=0',
			'tx_simpleforum_threads.deleted=0',
			'(tx_simpleforum_threads.starttime<'.mktime().' OR tx_simpleforum_threads.starttime=0)',
			'(tx_simpleforum_threads.endtime>'.mktime().' OR tx_simpleforum_threads.endtime=0)',
			'tx_simpleforum_threads.fid='.$forum->getUid(),
		);
		return implode(' AND ', $where);
	}

	function getThreads(tx_simpleforum_forum &$forum) {

		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'tx_simpleforum_threads.*,MAX(tx_simpleforum_posts.crdate) AS lastpost',
						'tx_simpleforum_threads,tx_simpleforum_posts', $this->getThreadsWhere($forum) . ' AND tx_simpleforum_threads.uid=tid', 'tx_simpleforum_threads.uid', 'lastpost DESC', $this->startRecord . ',' . $this->conf['pageSize']);

		$this->threads = array();
		foreach ($threads as $thread) {
			$this->threads[$thread['uid']] = new $this->model['thread']($thread);
			$this->sorting['threads'][] = $thread['uid'];
		}
	}

	function getPosts(tx_simpleforum_thread &$thread) {
		$where = 'approved=1 AND hidden=0 AND deleted=0 AND tid='.$thread->getUid();
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_simpleforum_posts', $where, '', 'crdate ASC', $this->startRecord . ',' . $this->conf['pageSize']);

		$this->posts = array();
		foreach ($posts as $post) {
			$this->posts[$post['uid']] = new $this->model['post']($post);
			$this->sorting['posts'][] = $post['uid'];
		}
	}



	function initPagebrowserThreads(tx_simpleforum_forum &$forum) {
		$where = $this->getThreadsWhere($forum);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid)',
					'tx_simpleforum_threads', $where);
		if ($res) {
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$this->numberOfPages = $temp[0];
		}

		$rpp = $this->conf['pageSize'];
		$this->startRecord = $rpp*intval($this->piVars['page']);
	}

	function initPagebrowserPosts(tx_simpleforum_thread &$thread) {
		$where = 'approved=1 AND hidden=0 AND deleted=0 AND tid='.$thread->getUid();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('COUNT(uid)',
					'tx_simpleforum_posts', $where);
		if ($res) {
			$temp = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$this->numberOfPages = $temp[0];
		}

		$rpp = $this->conf['pageSize'];
		$this->startRecord = $rpp*intval($this->piVars['page']);
	}


	function afterCacheSubstitution($content) {

		$content = $this->replaceDateStrings($content);

		$marker = array(
			'###ADMINICONS###' => '',
		);

		//if ($this->auth->isAdmin) $maker['###ADMINICONS###'] = $this->view->adminMenu();

		foreach($marker as $label => $value) $content = str_replace($label, $value, $content);

		return $content;
	}

	function replaceDateStrings($content) {
		$pattern = '%%%##%%(\d+)%%##%%%';
		$matches = array();
		preg_match_all('/' . $pattern . '/', $content, $matches);
		foreach ($matches[1] as $key => $date) {
			$content = str_replace($matches[0][$key], $this->lastModString($date), $content);
		}
		return $content;
	}

	/**
	 * Returns formarted string with 'last modified date'
	 *
	 * @param	integer		$lastModTs: timestamp on which calculation is based
	 * @return	string		formarted timespan/date
	 */
	function lastModString($lastModTs) {
		$lastModTs = intVal($lastModTs);
		$diff = time() - $lastModTs;

		if ($diff < (60*60)) {
			//Angabe in Minuten
			$content = round(($diff/60),0);
			$content = $this->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pi_getLL('minutes_single') : $this->pi_getLL('minutes'));
		} elseif ($diff < ((60*60*24))) {
			//Angabe in Stunden
			$content = round(($diff/(60*60)),0);
			$content = $this->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pi_getLL('hours_single') : $this->pi_getLL('hours'));
		} elseif ($diff < ((60*60*24*5))) {
			//Angabe in Tagen
			$content = round(($diff/(60*60*24)),0);
			$content = $this->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pi_getLL('days_single') : $this->pi_getLL('days'));
		} else {
			//Datum augeben
			$content = strftime($this->conf['strftime'], $lastModTs);
		}
		return $content;
	}




	/**
	 *
	 * HELPER FUNCTIONS
	 *
	 */

	/**
	 * Fetches configuration value from flexform. If value exists, value in
	 * <code>$this->conf</code> is replaced with this value.
	 *
	 * @author	Dmitry Dulepov <dmitry@typo3.org>
	 * @param	string		$param:	Parameter name. If <code>.</code> is found, the first part is section name, second is key (applies only to $this->conf)
	 * @return	void
	 */
	function fetchConfigValue($param) {
		if (strchr($param, '.')) {
			list($section, $param) = explode('.', $param, 2);
		}
		$value = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], $param, ($section ? 's' . ucfirst($section) : 'sDEF')));
		if (!is_null($value) && $value != '') {
			if ($section) {
				$this->conf[$section . '.'][$param] = $value;
			}
			else {
				$this->conf[$param] = $value;
			}
		}
	}




	/**
	 * Processes form submissions.
	 *
	 * @return	void
	 */
	function processSubmission() {
		if ($this->piVars['reply']['submit'] && $this->processSubmission_validate()) {

			if (isset($this->piVars['reply']['title'])) {
				$thread = new $this->model['thread']($this->dataNewThread());
				$thread->updateDatabase();
			}

			$post = new $this->model['post']($this->dataNewPost());
			if($thread) $post->setTid($thread->getUid());
			$post->updateDatabase();

			if (!$thread) $thread = new $this->model['thread']($post->getTid());

			$this->cache->deleteCacheForum($thread->getFid());
			$this->cache->deleteCacheThread($thread->getUid());

			// Update reference index
			$refindex = t3lib_div::makeInstance('t3lib_refindex');
			$refindex->updateRefIndexTable('tx_simpleforum_posts', $post->getUid());
			$refindex->updateRefIndexTable('tx_simpleforum_threads', intVal($this->piVars['reply']['tid']));
			$refindex->updateRefIndexTable('tx_simpleforum_forums', intVal($this->piVars['reply']['fid']));
		}

	}

	/**
	 * Validates submitted form.
	 *
	 * @return	boolean		true, if form is ok.
	 */
	function processSubmission_validate() {
		$errorCount = 0;

		// trim all
		foreach ($this->piVars as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $key2s => $value2s) {
					if (!is_array($value2s)) {
						$this->piVars[$key][$key2s] = trim($value2s);
					}
				}
			} else {
				$this->piVars[$key] = trim($value);
			}
		}
		// Check required fields first
		if (!$this->piVars['reply']['message']) {
			$errorCount++;
		}
		if (!$this->piVars['reply']['title'] && !$this->piVars['reply']['tid']) {
			$errorCount++;
		}

		if (!empty($this->piVars['reply']['homepage'])) {
			$errorCount++;
		}

		// Check whether user is logged in
		if (!$GLOBALS['TSFE']->loginUser) {
			$errorCount++;
		}


		return ($errorCount == 0);
	}


	/**
	 * Creates record array for new thread
	 *
	 * @return	array		new thread record
	 */
	function dataNewThread() {
		if (isset($this->piVars['reply']['title'])) {
			// Create record
			$record = array(
				'pid' => intVal($this->conf['storagePid']),
				'fid' => intVal($this->piVars['reply']['fid']),
				'topic' => $this->piVars['reply']['title'],
				'author' => $GLOBALS['TSFE']->fe_user->user['uid'],
			);
			return $record;
		} else {
			return false;
		}
	}

	/**
	 * Creates record array for new post
	 *
	 * @return	array		new post array
	 */
	function dataNewPost() {
		$isApproved = 1;

		// Create record
		$record = array(
			'pid' => intVal($this->conf['storagePid']),
			'tid' => intVal($this->piVars['reply']['tid']),
			'author' => $GLOBALS['TSFE']->fe_user->user['uid'],
			'message' => $this->piVars['reply']['message'],
			'remote_addr' => t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'approved' => $isApproved,
		);

		// Check for double post
		$double_post_check = md5(implode(',', $record));
		if ($this->conf['preventDuplicatePosts']) {
			list($info) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t', 'tx_simpleforum_posts',
						'hidden=0 AND deleted=0 AND crdate>=' . (time() - 60*60) . ' AND doublepostcheck=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($double_post_check, 'tx_simpleforum_posts'));
		} else {
			$info['t'] = 0;
		}

		if ($info['t'] == 0) {
			$record['doublepostcheck'] = $double_post_check;
			return $record;
		} else {
			return false;
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php']);
}

?>