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
	var $pi_checkCHash = true;
	var $ts;		//TimeStamp
	var $smilieApi;
	var $users;		//cached user-information
	var $forums;	//cached forum-information
	var $threads;	//cached thread-information
	var $posts;		//cached post-information
	var $isAdmin = false;
	var $continue = true;
	var $sorting = array();


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

		$this->processSubmission();

		//UPDATE extension "cache"
		if ($this->continue && $this->piVars['updateAll'] == 1) {
			$this->updateAll();
		}

		$check = md5($this->piVars['type'] . $this->piVars['id'] . $this->piVars['adminAction'] . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		if($this->continue && $this->isAdmin && t3lib_div::inList('edit,delete,lock,unlock,move,hide', $this->piVars['adminAction']) && ($check == $this->piVars['chk'])) {
			$content = $this->admin();
		}

		$content = $this->dispatcher();

		return $this->pi_wrapInBaseClass($content);
	}


	function init() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_initPIflexForm();
		$this->fetchConfigValue('introtext');

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

		$this->role = array(
			'0' => 'not logged in',
			'1' => 'logged in',
			'2' => 'admin',
		);
	}


	function dispatcher() {

		//admin actions
		if (isset($this->piVars['action'])) {
			$admin = new $this->model['admin'];
			$admin->start($this->conf, $this->piVars);
		}

		//Output
		if (intVal($this->piVars['tid']) > 0) {
			$content = $this->postlist(intVal($this->piVars['tid']));
		} elseif (intVal($this->piVars['fid']) > 0) {
			$content = $this->threadlist(intVal($this->piVars['fid']));
		} else {
			$content = $this->forumlist();
		}

		return $content;
	}

	function forumlist() {
		$this->getForums();

	}



	function getForums() {
		$where = 'hidden=0 AND deleted=0 AND (starttime<'.mktime().' OR starttime = 0) AND (endtime>'.mktime().' OR endtime=0)';
		$forums = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_simpleforum_forums', $where, 'sorting');

		$this->forums = array();
		foreach ($forums as $forum) {
			$this->forums[$forum['uid']] = new $this->model['forum']($forum);
			$this->sorting['forums'][] = $forum['uid'];
		}
	}

	function getThreads(tx_simpleforum_forum &$forum) {
		$where = 'hidden=0 AND deleted=0 AND (starttime<'.mktime().' OR starttime = 0) AND (endtime>'.mktime().' OR endtime=0) AND fid='.$forum->getUid();
		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_simpleforum_threads', $where, 'lastpost DESC');

		$this->threads = array();
		foreach ($threads as $thread) {
			$this->threads[$thread['uid']] = new $this->model['thread']($thread);
			$this->sorting['threads'][] = $thread['uid'];
		}
	}

	function getPosts(tx_simpleforum_thread &$thread) {
		$where = 'approved=1 AND hidden=0 AND deleted=0 AND tid='.$thread->getUid();
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_simpleforum_posts', $where, 'crdate ASC');

		$this->posts = array();
		foreach ($posts as $post) {
			$this->posts[$post['uid']] = new $this->model['post']($post);
			$this->sorting['posts'][] = $post['uid'];
		}
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
	 * Updates extension internal cache
	 *
	 * @return	void
	 */
	function updateAll() {
		$forums = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, crdate, topic, description, threadnumber, lastpost, lastpostuser, lastpostusername',
				'tx_simpleforum_forums', 'hidden=0 AND deleted=0');
		if (!is_array($forums)) $forums = array();

		foreach ($forums as $forum) {
			$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,tstamp,crdate,fid,topic,postnumber,lastpost,lastpostusername,lastpostuser,authorname,author,locked,usergroup',
				'tx_simpleforum_threads', 'hidden=0 AND deleted=0', 'lastpost DESC');
			if (!is_array($threads)) $threads = array();

			foreach ($threads as $thread) {
				$this->thread_update($thread['uid']);
			}

			$this->forum_update($forum['uid']);
		}
	}

	/**
	 * Initiates configuration values and sets additionalHeaderData
	 *
	 * @return	void
	 */
	function init_dep() {


		$this->ts = mktime();


		$this->isAdmin = (t3lib_div::inList($GLOBALS['TSFE']->fe_user->user['usergroup'], $this->conf['adminGroup']));
		if (intval($this->piVars['noadmin']) == 1) $this->isAdmin = false;
	}




	/**
	 * Processes form submissions.
	 *
	 * @return	void
	 */
	function processSubmission() {
		if ($this->piVars['reply']['submit'] && $this->processSubmission_validate()) {

			if (isset($this->piVars['reply']['title'])) {
				$threadRecord = $this->thread_createRecord();

				// Insert  record
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_simpleforum_threads', $threadRecord);
				$this->piVars['reply']['tid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			}


			$postRecord = $this->post_createRecord();

			// Insert  record
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_simpleforum_posts', $postRecord);
			$postUid = $GLOBALS['TYPO3_DB']->sql_insert_id();

			// Update reference index. This will show in theList view that someone refers to external record.
			$refindex = t3lib_div::makeInstance('t3lib_refindex');
			/* @var $refindex t3lib_refindex */
			$refindex->updateRefIndexTable('tx_simpleforum_posts', $postUid);
			$refindex->updateRefIndexTable('tx_simpleforum_threads', intVal($this->piVars['reply']['tid']));
			$refindex->updateRefIndexTable('tx_simpleforum_forums', intVal($this->piVars['reply']['fid']));
			$this->thread_update(intVal($this->piVars['reply']['tid']));
			$this->forum_update(intVal($this->piVars['reply']['fid']));
		}

	}

	/**
	 * Validates submitted form. Errors are collected in <code>$this->formValidationErrors</code>
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
	function thread_createRecord() {
		if (isset($this->piVars['reply']['title'])) {
			// Create record
			$record = array(
				'pid' => intVal($this->conf['storagePid']),
				'fid' => intVal($this->piVars['reply']['fid']),
				'topic' => $this->piVars['reply']['title'],
				'postnumber' => 0,
				'lastpost' => time(),
				'lastpostusername' => $GLOBALS['TSFE']->fe_user->user['username'],
				'lastpostuser' => $GLOBALS['TSFE']->fe_user->user['uid'],
				'authorname' => $GLOBALS['TSFE']->fe_user->user['username'],
				'author' => $GLOBALS['TSFE']->fe_user->user['uid'],
				'crdate' => time(),
				'tstamp' => time(),
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
	function post_createRecord() {
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
			$record['crdate'] = $record['tstamp'] = time();
			return $record;
		} else {
			return false;
		}
	}


	/**
	 * Updates cached information of a single thread
	 *
	 * @param	integer		$threadId: id of thread to be updated
	 * @return	void
	 */
	function thread_update($threadId) {
		$where = 'hidden=0 AND deleted=0 AND approved=1 AND tid='.intVal($threadId);
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,crdate,author',
			'tx_simpleforum_posts', $where, 'crdate DESC');

		if (is_array($posts)) {
			$user = $this->data_user($posts[0]['author']);

			$record = array(
				'postnumber' => count($posts),
				'lastpost' => $posts[0]['crdate'],
				'lastpostusername' => $user['username'],
				'lastpostuser' => $user['uid'],
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.intVal($threadId),$record);
		}
	}

	/**
	 * Updates cached information of a single forum
	 *
	 * @param	integer		$forumId: id of forum to be updated
	 * @return	void
	 */
	function forum_update($forumId) {
		$where = 'hidden=0 AND deleted=0 AND postnumber>0 AND fid='.intVal($forumId);
		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, postnumber, lastpost, lastpostusername, lastpostuser',
			'tx_simpleforum_threads', $where, 'lastpost DESC');

		if (is_array($threads)) {
			$record = array(
				'threadnumber' => count($threads),
				'lastpost' => $threads[0]['lastpost'],
				'lastpostusername' => $threads[0]['lastpostusername'],
				'lastpostuser' => $threads[0]['lastpostuser'],
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_forums', 'uid='.intVal($forumId),$record);
		}
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php']);
}

?>