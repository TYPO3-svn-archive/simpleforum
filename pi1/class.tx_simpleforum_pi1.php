<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Peter Schuster <typo3@peschuster.de>
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


/**
 * Plugin 'Forum' for the 'simpleforum' extension.
 *
 * @author	Peter Schuster <typo3@peschuster.de>
 * @package	TYPO3
 * @subpackage	tx_simpleforum
 */
class tx_simpleforum_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_simpleforum_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_simpleforum_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'simpleforum';	// The extension key.
	var $pi_checkCHash = true;
	var $ts;	//TimeStamp
	var $smilieApi;
	var $users;  //cached user-information
	var $forums;  //cached forum-information
	var $threads;  //cached thread-information

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;

		$this->init();

		$GLOBALS['TYPO3_DB']->debugOutput = true;

		$this->processSubmission();

		if (empty($this->piVars['tid']) && empty($this->piVars['fid'])) {
			$content = $this->forums();

		} elseif (!empty($this->piVars['tid'])) {
			$content = $this->posts($this->piVars['tid']);

		} elseif (!empty($this->piVars['fid'])) {
			$content = $this->threads($this->piVars['fid']);

		}

		return $this->pi_wrapInBaseClass($content);
	}

	function init() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->ts = mktime();
		$this->templateCode = $this->cObj->fileResource('EXT:simpleforum/res/template.tmpl');


		$key = 'tx_simpleforum_' . md5($this->templateCode);
		if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$headerParts = $this->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
			if ($headerParts) {
				$headerParts = $this->cObj->substituteMarker($headerParts, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath('simpleforum'));
				$GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
			}
		}
	}

	function forums() {
		$template = $this->cObj->getSubpart($this->templateCode, '###FORUMLIST###');

		$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0)';
		$forums = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, crdate, topic, description, postnumber, lastposttime, lastpostuser, lastpostusername',
			'tx_simpleforum_forums', $where, 'sorting');
		if (!is_array($forums)) $forums = array();

		$marker = array(
			'###LABEL_TITLE###' => htmlspecialchars($this->conf['title']),
			'###LABEL_THREADNUMBER###' => $this->pi_getLL('L_ThreadNumber'),
			'###LABEL_LASTPOST###' => $this->pi_getLL('L_LastPost'),
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($forums as $forum) {

			$linkUser = $this->linkToUser($forum['lastpostuser'],$forum['lastpostusername']);

			$linkForum = $this->pi_linkToPage(
				$forum['topic'],
				$GLOBALS['TSFE']->id,'',
				array($this->prefixId.'[fid]'=>$forum['uid'])
			);

			$marker = array(
				'###ALTID###' => $i,
				'###FORUM_TITLE###' => $linkForum,
				'###FORUM_DESCRIPTION###' => $forum['description'],
				'###THREADNUMBER###' => intVal($forum['postnumber']),
				'###LASTPOST_DATETIME###' => $this->lastModString($forum['lastposttime']),
				'###LASTPOST_USER###' => $linkUser,
			);

			$temp['dataRows'][] = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $marker);
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		return $content;
	}

	function threads($forumId) {
		$template = $this->cObj->getSubpart($this->templateCode, '###THREADLIST###');

		$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND fid='.$forumId;
		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,tstamp,crdate,fid,topic,replysnumber,replyslast,replyslastname,replyslastuid,authorname,author,locked,usergroup',
			'tx_simpleforum_threads', $where, 'replyslast DESC');
		if (!is_array($threads)) $threads = array();

		$forum = $this->data_forum($forumId);

		$breadcrumb = $this->breadcrumb(array('fid'=>$forumId));

		$marker = array(
			'###THREADTITLE###' => $forum['topic'],
			'###BREADCRUMB###' => $breadcrumb,
			'###NAVBAR###' => '',
			'###LABEL_TOPIC###' => $this->pi_getLL('L_Topic'),
			'###LABEL_REPLYS###' => $this->pi_getLL('L_Replys'),
			'###LABEL_AUTHOR###' => $this->pi_getLL('L_Author'),
			'###LABEL_LASTPOST###' => $this->pi_getLL('L_LastPost'),
			'###PAGEBROWSER###' => '',
			'###NEWTHREADFORM###' => $this->form(array('fid' => $forum['uid'])),
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($threads as $thread) {
			$linkUser = $this->linkToUser($thread['replyslastuid'],$thread['replyslastname']);
			$linkAuthor = $this->linkToUser($thread['author'],$thread['authorname']);

			$linkThread = $this->pi_linkToPage(
			$thread['topic'],
			$GLOBALS['TSFE']->id,'',
			array($this->prefixId.'[tid]'=>$thread['uid'])
			);

			$markerSub = array(
				'###ALTID###' => $i,
				'###SPECIALICON###' => '',
				'###THREADTITLE###' => $linkThread,
				'###AUTHOR###' => $linkAuthor,
				'###POSTSNUMBER###' => intVal($thread['replysnumber']),
				'###LASTPOST_DATETIME###' => $this->lastModString($thread['replyslast']),
				'###LASTPOST_USER###' => $linkUser,
			);

			$temp['dataRows'][] = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	function posts($threadId) {
		$template = $this->cObj->getSubpart($this->templateCode, '###MESSAGELIST###');

		$where = 'hidden=0 AND deleted=0 AND tid='.$threadId;
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,tstamp,crdate,author,message,approved',
			'tx_simpleforum_posts', $where, 'crdate ASC');
		if (!is_array($posts)) $posts = array();

		$thread = $this->data_thread($threadId);
		$breadcrumb = $this->breadcrumb(array('tid'=>$threadId));

		$marker = array(
			'###THREADTITLE###' => $thread['topic'],
			'###BREADCRUMB###' => $breadcrumb,
			'###NAVBAR###' => '',
			'###LABEL_AUTHOR###' => $this->pi_getLL('L_Author'),
			'###LABEL_MESSAGE###' => $this->pi_getLL('L_Message'),
			'###PAGEBROWSER###' => '',
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		// Load Smilie-API (if available)
		if (t3lib_extMgm::isLoaded('smilie')) {
			require_once(t3lib_extMgm::extPath('smilie').'class.tx_smilie.php');
			$this->smilieApi = t3lib_div::makeInstance('tx_smilie');
		}



		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($posts as $post) {
			$linkAuthor = $this->linkToUser($post['author']);
			$user = $this->data_user($post['author']);
			$message = nl2br($this->smilieApi ? $this->smilieApi->replaceSmilies($post['message']) : $post['message']);

			$markerSub = array(
				'###ALTID###' => $i,
				'###AUTHOR###' => $linkAuthor,
				'###AUTHOR_IMAGE###' => $this->generateUserImage($user['image'], 45, 63, $user['username'], $this->conf['altUserIMG']),
				'###DATETIME###' => strftime($this->conf['strftime'], $post['crdate']),
				'###MESSAGE###' => $message,
			);

			$temp['dataRows'][] = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteSubpart($content, '###REPLYBOX###', $this->form(array('tid' => $thread['uid'], 'fid' => $thread['fid'])));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	function form($conf = array()) {
		$content = '';
		if ($conf['tid']) {
			$thread = $this->data_thread(intVal($conf['tid']));
			$addClause = ($thread['locked'] != 1);
			$template = $this->cObj->getSubpart($this->templateCode, '###REPLYBOX###');
		} else {
			$forum = $this->data_forum(intVal($conf['fid']));
			$addClause = true;
			$template = $this->cObj->getSubpart($this->templateCode, '###NEWTHREAD###');
		}

		if ($GLOBALS['TSFE']->loginUser && $addClause) {


			$actionLink = $this->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'exclude' => 'cHash,no_cache',
			),
				'additionalParams' => '&no_cache=1',
				'useCacheHash' => false,
			));

			$marker = array(
				'###FID###' => intVal($conf['fid']),
				'###TID###' => intVal($conf['tid']),
				'###LABEL_REPLYTO###' => $this->pi_getLL('L_ReplyTo'),
				'###THREADTITLE###' => $thread['topic'],
				'###L_SUBMIT###' => $this->pi_getLL('L_Submit'),
				'###ACTION_URL###' => $actionLink,
				'###L_NEW_THREAD###' => $this->pi_getLL('L_NewThread'),
				'###FORUMTITLE###' => $forum['topic'],
				'###L_THREADTITLE###' => $this->pi_getLL('L_ThreadTitle'),
				'###V_THREADTITLE###' => '',
				'###V_MESSAGE###' => '',
			);

			$content = $this->cObj->substituteMarkerArray($template, $marker);
		}
		return $content;
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
			$refindex->updateRefIndexTable('tx_simpleforum_posts', intVal($this->piVars['reply']['tid']));
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

	function thread_createRecord() {
		if (isset($this->piVars['reply']['title'])) {
			// Create record
			$record = array(
				'pid' => intVal($this->conf['storagePid']),
				'fid' => intVal($this->piVars['reply']['fid']),
				'topic' => $this->piVars['reply']['title'],
				'replysnumber' => 0,
				'replyslast' => time(),
				'replyslastname' => $GLOBALS['TSFE']->fe_user->user['username'],
				'replyslastuid' => $GLOBALS['TSFE']->fe_user->user['uid'],
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


	function thread_update($threadId) {
		$where = 'hidden=0 AND deleted=0 AND tid='.intVal($threadId);
		$posts = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,crdate,author',
			'tx_simpleforum_posts', $where, 'crdate DESC');

		if (is_array($posts)) {
			$user = $this->data_user($posts[0]['author']);

			$record = array(
				'replysnumber' => count($posts),
				'replyslast' => $posts[0]['crdate'],
				'replyslastname' => $user['username'],
				'replyslastuid' => $user['uid'],
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.intVal($threadId),$record);
		}
	}

	function forum_update($forumId) {
		$where = 'hidden=0 AND deleted=0 AND fid='.intVal($forumId);
		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, replysnumber, replyslast, replyslastname, replyslastuid',
			'tx_simpleforum_threads', $where, 'replyslast DESC');

		if (is_array($threads)) {
			$record = array(
				'postnumber' => count($threads),
				'lastposttime' => $threads[0]['replyslast'],
				'lastpostusername' => $threads[0]['replyslastname'],
				'lastpostuser' => $threads[0]['replyslastuid'],
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_forums', 'uid='.intVal($forumId),$record);
		}
	}



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

	function breadcrumb($conf) {
		$temp[] = $this->pi_linkToPage(htmlspecialchars($this->conf['title']),$GLOBALS['TSFE']->id);

		if ($conf['tid']) {
			$thread = $this->data_thread($conf['tid']);
			$temp[] = $this->linkToForum($thread['fid']);
			$temp[] = $this->linkToThread($conf['tid']);
		} elseif ($conf['fid']) {
			$temp[] = $this->linkToForum($conf['fid']);
		}

		$content = implode(' &gt;&gt ', $temp);
		return $content;
	}

	function linkToUser($userId, $username='') {
		if ($username == '') {
			$user = $this->data_user($userId);
			$username = $user['username'];
		}
		$content = $this->pi_linkToPage(
		$username,
		$this->conf['profilePID'],'',
		array('tx_feuser_pi1[uid]'=>$userId)
		);
		return $content;
	}

	function linkToForum($forumId, $topic='') {
		if ($topic == '') {
			$forum = $this->data_forum($forumId);
			$topic = $forum['topic'];
		}
		$content = $this->pi_linkToPage(
		$topic,
		$GLOBALS['TSFE']->id,'',
		array($this->prefixId.'[fid]'=>$forum['uid'])
		);
		return $content;
	}

	function linkToThread($threadId, $topic='') {
		if ($topic == '') {
			$thread = $this->data_thread($threadId);
			$topic = $thread['topic'];
		}
		$content = $this->pi_linkToPage(
		$topic,
		$GLOBALS['TSFE']->id,'',
		array($this->prefixId.'[tid]'=>$thread['uid'])
		);
		return $content;
	}

	function data_user($userId) {
		if (!is_array($this->users[$userId])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,name,first_name,last_name,showname,image',
				'fe_users', 'uid='.intVal($userId));
			if ($res) {
				$this->users[$userId] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$user = $this->users[$userId];
			} else {
				$user = false;
			}
		} elseif (is_array($this->users[$userId])) {
			$user = $this->users[$userId];
		}
		return $user;
	}

	function data_forum($forumId) {
		if (!is_array($this->forums[$forumId])) {
			$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND uid='.intVal($forumId);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, crdate, topic, description, postnumber, lastposttime, lastpostuser, lastpostusername',
				'tx_simpleforum_forums', $where);
			if ($res) {
				$this->forums[$forumId] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$forum = $this->forums[$forumId];
			} else {
				$forum = false;
			}
		} elseif (is_array($this->forums[$forumId])) {
			$forum = $this->forums[$forumId];
		}
		return $forum;
	}

	function data_thread($threadId) {
		if (!is_array($this->threads[$threadId])) {
			$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND uid='.intVal($threadId);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,tstamp,crdate,fid,topic,replysnumber,replyslast,replyslastname,replyslastuid,authorname,author,locked,usergroup',
				'tx_simpleforum_threads', $where);
			if ($res) {
				$this->threads[$threadId] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$thread = $this->threads[$threadId];
			} else {
				$thread = false;
			}
		} elseif (is_array($this->threads[$threadId])) {
			$thread = $this->threads[$threadId];
		}
		return $thread;
	}

	function generateUserImage($image,$width,$height,$altText,$altIMG='') {
		if (!($this->cObj)) $this->cObj = t3lib_div::makeInstance('tslib_cObj');
		if ($height == '') {
			if (!empty($image)) {
				$imgConf['file'] = 'uploads/tx_srfeuserregister/'.$image;
			} else {
				$imgConf['file'] = $altIMG;
			}
			$imgConf['file.']['width'] = intval($width);
			$imgConf['file.']['format'] = 'jpg';
			$imgConf['file.']['quality'] = '90';
		} else {
			$imgConf['file'] = 'GIFBUILDER';
			$imgConf['file.']['XY'] = intval($width).','.intval($height);
			$imgConf['file.']['format'] = 'jpg';
			$imgConf['file.']['quality'] = '90';
			$imgConf['file.']['10'] = 'IMAGE';
			if (!empty($image)) {
				$imgConf['file.']['10.']['file'] = 'uploads/tx_srfeuserregister/'.$image;
			} else {
				$imgConf['file.']['10.']['file'] = $altIMG;
			}
			$imgConf['file.']['10.']['file.']['quality'] = '90';
			$imgConf['file.']['10.']['file.']['maxW'] = $width;
		}
		$imgConf['altText'] = $altText;
		$myImage = $this->cObj->cObjGetSingle('IMAGE',$imgConf);
		return $myImage;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/pi1/class.tx_simpleforum_pi1.php']);
}

?>