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
	var $posts;  //cached post-information
	var $isAdmin = false;
	var $continue = true;

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

		if ($this->continue) {
			if (empty($this->piVars['tid']) && empty($this->piVars['fid'])) {
				$content = $this->forums();

			} elseif (!empty($this->piVars['tid'])) {
				$content = $this->posts($this->piVars['tid']);

			} elseif (!empty($this->piVars['fid'])) {
				$content = $this->threads($this->piVars['fid']);
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Updates extensin internal cache
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
	 * Initiates configuration values ans set additionalHeaderData
	 *
	 * @return	void
	 */
	function init() {
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		$this->pi_initPIflexForm();
		$this->conf['introtext'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'introtext', 'sDEF');

		$this->ts = mktime();
		$this->templateCode = $this->cObj->fileResource('EXT:simpleforum/res/template.tmpl');

		//Replace 'EXT:simpleforum/' in conf
		$list = array('lockedIcon', 'adminIcon');
		foreach ($list as $l) {
			$this->conf[$l] = str_replace('EXT:simpleforum/', t3lib_extMgm::siteRelPath('simpleforum'), $this->conf[$l]);
		}

		$key = 'tx_simpleforum_' . md5($this->templateCode);
		if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$headerParts = $this->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
			if ($headerParts) {
				$headerParts = $this->cObj->substituteMarker($headerParts, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath('simpleforum'));
				$GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
			}
		}

		$this->isAdmin = (t3lib_div::inList($GLOBALS['TSFE']->fe_user->user['usergroup'], $this->conf['adminGroup']));
		if (intval($this->piVars['noadmin']) == 1) $this->isAdmin = false;
	}

	function admin() {
		$content = '<h1>' . $this->pi_getLL('contextmenu_' . $this->piVars['adminAction']) . '</h1>';
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
						$this->pi_getLL('message_delete'),
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
						$this->pi_getLL('message_hide'),
						array_merge($arrYes, array($this->prefixId.'[adminAction]' => 'hide')),
						$arrNo
					);
				}
				break;
		}
		$this->updateAll();
		return $content;
	}

	function admin_move_form($arrYes, $arrNo) {
		switch ($this->piVars['type']) {
			CASE 'post':
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,topic', 'tx_simpleforum_threads', 'deleted=0 AND hidden=0', '', 'lastpost DESC');
				$options = array();
				foreach ($rows as $row) {
					$options[$row['uid']] = $row['topic'];
				}
				$message = $this->pi_getLL('message_move_posts');
				break;
			CASE 'thread':
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,topic', 'tx_simpleforum_forums', 'deleted=0 AND hidden=0', '', 'lastpost DESC');
				$options = array();
				foreach ($rows as $row) {
					$options[$row['uid']] = $row['topic'];
				}
				$message = $this->pi_getLL('message_move_thread');
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

	function admin_alert($message, $yes, $no) {
		$template = $this->cObj->getSubpart($this->templateCode, '###ALERT###');

		$links['yes'] = $this->pi_linkToPage($this->pi_getLL('yes'),$GLOBALS['TSFE']->id, '',$yes);
		$links['no'] = $this->pi_linkToPage($this->pi_getLL('no'),$GLOBALS['TSFE']->id, '',$no);

		$marker = array(
			'###MESSAGE###' => $message,
			'###YES###' => $links['yes'],
			'###NO###' => $links['no'],
		);
		$this->continue = false;

		$content = $this->cObj->substituteMarkerArray($template, $marker);
		return $content;
	}

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
		$links['no'] = $this->pi_linkToPage($this->pi_getLL('no'),$GLOBALS['TSFE']->id, '',$no);

		$marker = array(
			'###MESSAGE###' => $message,
			'###ACTIONURL###' => $actionUrl,
			'###L_SUBMIT###' => $this->pi_getLL('L_Submit'),
			'###NO###' => $links['no'],
		);
		$this->continue = false;

		$content = $this->cObj->substituteMarkerArray($template, $marker);
		return $content;
	}

	function admin_edit($type, $id, $content) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('message'=>$content));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('topic'=>$content));
				break;
		}
	}

	function admin_lock($type, $id) {
		switch ($type) {
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('locked'=>1));
				break;
		}
	}

	function admin_unlock($type, $id) {
		switch ($type) {
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('locked'=>0));
				break;
		}
	}

	function admin_hide($type, $id) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('approved'=>0));
				break;
		}
	}

	function admin_move($type, $id, $pid) {
		switch ($type) {
			CASE 'post':
				$post = $this->data_post($id);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'tid='.$post['tid'].' AND crdate>='.$post['crdate'], array('tid'=>$pid));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('fid'=>$pid));
				break;
		}
	}

	function admin_delete($type, $id) {
		switch ($type) {
			CASE 'post':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_posts', 'uid='.$id, array('deleted'=>1));
				break;
			CASE 'thread':
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_simpleforum_threads', 'uid='.$id, array('deleted'=>1));
				break;
		}
	}


	/**
	 * Returns forums
	 *
	 * @return	string		HTML output
	 */
	function forums() {
		$template = $this->cObj->getSubpart($this->templateCode, '###FORUMLIST###');

		$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0)';
		$forums = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, crdate, topic, description, threadnumber, lastpost, lastpostuser, lastpostusername',
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

			$marker = array (
				'###ALTID###' => $i,
				'###FORUM_TITLE###' => $linkForum,
				'###FORUM_DESCRIPTION###' => $forum['description'],
				'###THREADNUMBER###' => intVal($forum['threadnumber']),
				'###LASTPOST_DATETIME###' => $this->lastModString($forum['lastpost']),
				'###LASTPOST_USER###' => $linkUser,
			);

			$temp['dataRows'][] = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $marker);
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));

		$marker = array(
			'###INTROTEXT###' => '<p class="introtext">'.$this->conf['introtext'].'</p>',
		);
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	/**
	 * Returns threadlist
	 *
	 * @param	integer		$forumId: id of forum to be shown
	 * @return	string		HTML output
	 */
	function threads($forumId) {
		$template = $this->cObj->getSubpart($this->templateCode, '###THREADLIST###');

		$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND fid='.$forumId;
		$threads = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,tstamp,crdate,fid,topic,postnumber,lastpost,lastpostusername,lastpostuser,authorname,author,locked,usergroup',
			'tx_simpleforum_threads', $where, 'lastpost DESC');
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
			if (intVal($thread['postnumber']) == 0) continue;
			$linkUser = $this->linkToUser($thread['lastpostuser'],$thread['lastpostusername']);
			$linkAuthor = $this->linkToUser($thread['author'],$thread['authorname']);

			$linkThread = $this->pi_linkToPage(
				$thread['topic'],
				$GLOBALS['TSFE']->id,'',
				array($this->prefixId.'[tid]'=>$thread['uid'])
			);
			$thread['postnumber'] = intVal($thread['postnumber']);
			if ($thread['postnumber'] > 0) $thread['postnumber']--;

			$specialIcon = '';
			if (intVal($thread['locked']) == 1) {
				$specialIcon = '<img src="' . $this->conf['lockedIcon'] . '" />';
			}

			$markerSub = array(
				'###ALTID###' => $i,
				'###SPECIALICON###' => $specialIcon,
				'###THREADTITLE###' => $linkThread,
				'###AUTHOR###' => $linkAuthor,
				'###POSTSNUMBER###' => $thread['postnumber'],
				'###LASTPOST_DATETIME###' => $this->lastModString($thread['lastpost']),
				'###LASTPOST_USER###' => $linkUser,
			);

			$rowContent = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);

			$rowContent = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);

			$confArr = array(
					'template' => $this->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
					'id' => $thread['uid'],
					'show' => array('delete','move'),
					'type' => 'thread',
					'leftright' => 'left'
			);
			if ($thread['locked'] == 1) {
				$confArr['show'][] = 'unlock';
			} else {
				$confArr['show'][] = 'lock';
			}
			$adminMenu = $this->adminMenu($confArr);
			$rowContent = $this->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu);

			$temp['dataRows'][] = $rowContent;
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	/**
	 * Returns post list
	 *
	 * @param	integer		$threadId: id of thread to be shown
	 * @return	string		HTML output
	 */
	function posts($threadId) {
		$template = $this->cObj->getSubpart($this->templateCode, '###MESSAGELIST###');

		$where = 'approved=1 AND hidden=0 AND deleted=0 AND tid='.$threadId;
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

			$rowContent = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);

			$confArr = array(
					'template' => $this->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
					'id' => $post['uid'],
					'show' => array('delete','hide','move'),
					'type' => 'post',
					'leftright' => 'right'
			);
			$adminMenu = $this->adminMenu($confArr);
			$rowContent = $this->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu);

			$temp['dataRows'][] = $rowContent;
			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteSubpart($content, '###REPLYBOX###', $this->form(array('tid' => $thread['uid'], 'fid' => $thread['fid'])));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	/**
	 * Returns form. The type of the form is based on the conf array
	 *
	 * @param	array		$conf: array with configuration
	 * @return	string		HTML output
	 */
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
	 * Returns breadcrumb menu (forum internal)
	 *
	 * @param	array		$conf: array with thread-/forum-id
	 * @return	string		HTML output
	 */
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

	function adminMenu($conf=array()) {
		if ($this->isAdmin) {
			$content = '<a href="#" onclick="txSimpleForumAdminMenu(event, '.$conf['id'].'); return false;" title="'.$this->pi_getLL('adminMenuTitle').'"><img src="' . $this->conf['adminIcon'] . '" /></a>';

			$items = array(
				'delete' => array('icon' => 'res/cross.png'),
				'edit' => array('icon' => 'res/pencil.png'),
				'hide' => array('icon' => 'res/eye.png'),
				'move' => array('icon' => 'res/table_go.png'),
				'lock' => array('icon' => 'res/lock.png'),
				'unlock' => array('icon' => 'res/lock_open.png'),
			);

			$content .= $this->adminMenu_getMenu(array('items' => $items, 'show'=>$conf['show'], 'id'=>$conf['id'], 'type' => $conf['type'], 'leftright' => $conf['leftright']));
		}
		return $this->cObj->substituteMarker($conf['template'], '###ADMINICONS###', $content);
	}

	function adminMenu_getMenu($conf) {
		$contextMenu = $this->cObj->getSubpart($this->cObj->fileResource('EXT:simpleforum/res/contextmenu.html'),'###CONTEXTMENU###');
		$marker = array(
			'###UID###' => $conf['id'],
			'###LEFTRIGHT###' => $conf['leftright'],
		);

		$rowTemplate = $this->cObj->getSubpart($contextMenu, '###ROW###');
		foreach ($conf['show'] as $label) {
			$urlParameter = array(
				'no_cache' => 1,
				'tx_simpleforum_pi1[adminAction]' => $label,
				'tx_simpleforum_pi1[type]' => $conf['type'],
				'tx_simpleforum_pi1[id]' => $conf['id'],
				'tx_simpleforum_pi1[chk]' => md5($conf['type'] . $conf['id'] . $label . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
			);
			foreach ($urlParameter as $k => $v) {
				$urlParameterStr .= '&'.$k.'='.$v;
			}
			$url = $this->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'exclude' => 'cHash,no_cache',
				),
				'additionalParams' => $urlParameterStr,
				'useCacheHash' => false,
			));
			$subMarker = array(
				'###ICON###' => t3lib_extMgm::siteRelPath('simpleforum') . $conf['items'][$label]['icon'],
				'###LABEL###' => $this->pi_getLL('contextmenu_'.$label),
				'###URL###' => $url,
			);
			$rows[] = $this->cObj->substituteMarkerArray($rowTemplate, $subMarker);
		}
		$contextMenu = $this->cObj->substituteSubpart($contextMenu, '###ROW###', implode('', $rows));
		$contextMenu = $this->cObj->substituteMarkerArray($contextMenu, $marker);

		$content = $contextMenu;
		return $content;
	}

	/**
	 * Returns link to a user
	 *
	 * @param	integer		$userId: id of user to point at
	 * @param	string		$username: username (optional)
	 * @return	string		HTML output
	 */
	function linkToUser($userId, $username='') {
		if ($username == '') {
			$user = $this->data_user($userId);
			$username = $user['username'];
		}
		$content = $this->pi_linkToPage(
			$username,
			$this->conf['profilePID'],'',
			array($this->conf['profileParam']=>$userId)
		);
		return $content;
	}

	/**
	 * Returns link to a single forum
	 *
	 * @param	integer		$forumId: id of forum
	 * @param	string		$topic: forum title (optional)
	 * @return	string		HTML output
	 */
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

	/**
	 * Returns link to a single thread
	 *
	 * @param	integer		$threadId: id of thread
	 * @param	string		$topic: thread title (optional)
	 * @return	string		HTML output
	 */
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


	/**
	 * Returns data of a single user and caches it at $this->users
	 * When data of this user is already cached in $this->users this
	 * data is taken and no new databasequery is executed.
	 *
	 * @param	integer		$userId: id of user
	 * @return	array		user information
	 */
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

	/**
	 * Returns data of a single forum and caches it at $this->forums
	 * When data of this forum is already cached in $this->forums this
	 * data is taken and no new databasequery is executed.
	 *
	 * @param	integer		$forumId: id of forum
	 * @return	array		forum information
	 */
	function data_forum($forumId) {
		if (!is_array($this->forums[$forumId])) {
			$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND uid='.intVal($forumId);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, crdate, topic, description, threadnumber, lastpost, lastpostuser, lastpostusername',
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

	/**
	 * Returns data of a single thread and caches it at $this->threads
	 * When data of this thread is already cached in $this->threads this
	 * data is taken and no new databasequery is executed.
	 *
	 * @param	integer		$threadId: id of thread
	 * @return	array		thread information
	 */
	function data_thread($threadId) {
		if (!is_array($this->threads[$threadId])) {
			$where = 'hidden=0 AND deleted=0 AND (starttime<'.$this->ts.' OR starttime = 0) AND (endtime>'.$this->ts.' OR endtime=0) AND uid='.intVal($threadId);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,tstamp,crdate,fid,topic,postnumber,lastpost,lastpostusername,lastpostuser,authorname,author,locked,usergroup',
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

	/**
	 * Returns data of a single post and caches it at $this->posts
	 * When data of this post is already cached in $this->posts this
	 * data is taken and no new databasequery is executed.
	 *
	 * @param	integer		$postId: id of post
	 * @return	array		post information
	 */
	function data_post($postId) {
		if (!is_array($this->posts[$postId])) {
			$where = 'hidden=0 AND deleted=0 AND uid='.intVal($postId);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,tstamp,crdate,deleted,hidden,tid,author,message,approved,remote_addr,doublepostcheck',
				'tx_simpleforum_posts', $where);
			if ($res) {
				$this->posts[$postId] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$post = $this->posts[$postId];
			} else {
				$post = false;
			}
		} elseif (is_array($this->posts[$postId])) {
			$post = $this->posts[$postId];
		}
		return $post;
	}


	/**
	 * Generates user image and returns it
	 *
	 * @param	string		$image: image name (in folder 'uploads/tx_srfeuserregister/')
	 * @param	string		$width: image width
	 * @param	string		$height: image height (if set to '' it is calculated automaticly)
	 * @param	string]		$altText: text for alt tag
	 * @param	string		$altIMG: path to alternative image
	 * @return	string		HTML output
	 */
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