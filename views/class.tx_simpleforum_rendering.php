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
 * views/class.tx_simpleforum_rendering.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('simpleforum', 'views/class.tx_simpleforum_adminMenu.php'));
require_once(t3lib_extMgm::extPath('simpleforum', 'views/class.tx_simpleforum_form.php'));

/**
 * Class for rendering the outputdata
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_rendering {
	var $prefixId		= 'tx_simpleforum_pi1';
	var $scriptRelPath	= 'views/class.tx_simpleforum_lists.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.

	/**
	 * cObj
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	function start(&$conf, &$piVars, tslib_pibase &$pObj) {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pObj = &$pObj;
		$this->conf = &$conf;
		$this->piVars = &$piVars;
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
	}



	function forumlist(&$forums, $sorting=array()) {
		$template = $this->cObj->getSubpart($this->templateCode, '###FORUMLIST###');

		if (!is_array($forums)) $forums = array();
		if (empty($sorting)) foreach ($forums as $forum) $sorting[] = $forum->getUid();


		$marker = array(
			'###LABEL_TITLE###' => htmlspecialchars($this->conf['title']),
			'###LABEL_THREADNUMBER###' => $this->pObj->pi_getLL('L_ThreadNumber'),
			'###LABEL_LASTPOST###' => $this->pObj->pi_getLL('L_LastPost'),
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);


		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');

		$i = 1;
		foreach ($sorting as $forumUid) {
			$forum = $forums[$forumUid];

			$marker = array (
				'###ALTID###' => $i,
				'###FORUM_TITLE###' => $this->linkToForum($forum),
				'###FORUM_DESCRIPTION###' => $forum->getDescription(),
				'###THREADNUMBER###' => intVal($forum->getStatistics('threadnumber')),
				'###LASTPOST_DATETIME###' => $this->wrapDateString($forum->getStatistics('lastpost')),
				'###LASTPOST_USER###' => $this->linkToUser(tx_simpleforum_user::getInstance($forum->getStatistics('user'))),
			);

			$temp['dataRows'][] = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $marker);
			$i = ($i == 1 ? 2 : 1);
		}

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', (is_array($temp['dataRows']) ? $temp['dataRows'] : array())));

		if ($this->conf['introtext']) {
			$content = $this->cObj->substituteMarker($content,'###INTROTEXT###',$this->cObj->stdWrap($this->conf['introtext'], $this->conf['introtext.']));
		}
		return $content;
	}

	function threadlist(&$threads, &$forum, $sorting=array()) {
		$template = $this->cObj->getSubpart($this->templateCode, '###THREADLIST###');

		if (!is_array($threads)) $threads = array();
		if (empty($sorting)) foreach ($threads as $thread) $sorting[] = $thread->getUid();

		$breadcrumb = $this->breadcrumb($forum);

		$formClass = t3lib_div::makeInstanceClassName('tx_simpleforum_form');
		$this->form = new $formClass($this->pObj, $this->templateCode);


		$marker = array(
			'###THREADTITLE###' => $forum->getTopic(),
			'###BREADCRUMB###' => $breadcrumb,
			'###NAVBAR###' => '',
			'###LABEL_TOPIC###' => $this->pObj->pi_getLL('L_Topic'),
			'###LABEL_REPLYS###' => $this->pObj->pi_getLL('L_Replys'),
			'###LABEL_AUTHOR###' => $this->pObj->pi_getLL('L_Author'),
			'###LABEL_LASTPOST###' => $this->pObj->pi_getLL('L_LastPost'),
			'###PAGEBROWSER###' => $this->getListGetPageBrowser($this->pObj->numberOfPages),
			'###NEWTHREADFORM###' => $this->form->output($forum),
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($sorting as $threadUid) {
			$thread = $threads[$threadUid];
			if (intVal($thread->getStatistics('postnumber')) == 0) continue;

			$specialIcon = '';
			if ($thread->isLocked()) {
				$specialIcon = '<img src="' . $this->conf['lockedIcon'] . '" />';
			}

			$markerSub = array(
				'###ALTID###' => $i,
				'###SPECIALICON###' => $specialIcon,
				'###THREADTITLE###' => $this->linkToThread($thread),
				'###AUTHOR###' => $this->linkToUser(tx_simpleforum_user::getInstance($thread->getAuthor())),
				'###POSTSNUMBER###' => ($thread->getStatistics('postnumber')-1),
				'###LASTPOST_DATETIME###' => $this->wrapDateString($thread->getStatistics('lastpost')),
				'###LASTPOST_USER###' => $this->linkToUser(tx_simpleforum_user::getInstance($thread->getStatistics('user'))),
			);

			$rowContent = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);


			$confArr = array(
				'template' => $this->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
				'id' => $thread->getUid(),
				'show' => array('delete','move'),
				'type' => 'thread',
				'leftright' => 'left'
			);
			if ($thread->isLocked()) {
				$confArr['show'][] = 'unlock';
			} else {
				$confArr['show'][] = 'lock';
			}
			$adminMenu = $this->adminMenu($confArr);
			$temp['dataRows'][] = $this->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu);

			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}

	function postlist(&$posts, &$thread, &$forum, $sorting=array()) {
		$template = $this->cObj->getSubpart($this->templateCode, '###MESSAGELIST###');

		if (!is_array($posts)) $posts = array();
		if (empty($sorting)) foreach ($posts as $post) $sorting[] = $post->getUid();

		$breadcrumb = $this->breadcrumb($forum, $thread);

		$marker = array(
			'###THREADTITLE###' => $thread->getTopic(),
			'###BREADCRUMB###' => $breadcrumb,
			'###NAVBAR###' => '',
			'###LABEL_AUTHOR###' => $this->pObj->pi_getLL('L_Author'),
			'###LABEL_MESSAGE###' => $this->pObj->pi_getLL('L_Message'),
			'###PAGEBROWSER###' => $this->getListGetPageBrowser($this->pObj->numberOfPages),
		);
		$temp['titleRow'] = $this->cObj->substituteMarkerArray($this->cObj->getSubpart($template, '###TITLEROW###'), $marker);

		// Load Smilie-API (if available)
		if (t3lib_extMgm::isLoaded('smilie') && !$this->smilieApi) {
			require_once(t3lib_extMgm::extPath('smilie').'class.tx_smilie.php');
			$this->smilieApi = t3lib_div::makeInstance('tx_smilie');
		}

		$formClass = t3lib_div::makeInstanceClassName('tx_simpleforum_form');
		$this->form = new $formClass($this->pObj, $this->templateCode);

		$temp['dataTemplate'] = $this->cObj->getSubpart($template, '###DATAROW###');
		$i = 1;
		foreach ($sorting as $postUid) {
			$post = $posts[$postUid];

			$message = htmlspecialchars($post->getMessage());
			$message = nl2br($this->smilieApi ? $this->smilieApi->replaceSmilies($message) : $message);

			$user = tx_simpleforum_user::getInstance($post->getAuthor());

			$markerSub = array(
				'###ALTID###' => $i,
				'###AUTHOR###' => $this->linkToUser($user),
				'###AUTHOR_IMAGE###' => $this->userImage($user, 45, 63, $this->conf['altUserIMG']),
				'###DATETIME###' => strftime($this->conf['strftime'], $post->getCrdate()),
				'###MESSAGE###' => $message,
			);

			$rowContent = $this->cObj->substituteMarkerArray($temp['dataTemplate'], $markerSub);

			$confArr = array(
					'template' => $this->cObj->getSubpart($temp['dataTemplate'], '###ADMINMENU###'),
					'id' => $post->getUid(),
					'show' => array('delete','hide','move'),
					'type' => 'post',
					'leftright' => 'right'
			);
			$adminMenu = $this->adminMenu($confArr);
			$temp['dataRows'][] = $this->cObj->substituteSubpart($rowContent, '###ADMINMENU###', $adminMenu);

			$i = ($i == 1 ? 2 : 1);
		}
		if (!is_array($temp['dataRows'])) $temp['dataRows'] = array();

		$content = $this->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->cObj->substituteSubpart($content, '###DATAROW###', implode('', $temp['dataRows']));
		$content = $this->cObj->substituteSubpart($content, '###REPLYBOX###', $this->form->output($forum, $thread));
		$content = $this->cObj->substituteMarkerArray($content, $marker);
		return $content;
	}



	function adminMenu($conf) {

		if (!$this->adminMenuClass) {
			$adminMenuClass = t3lib_div::makeInstanceClassName('tx_simpleforum_adminMenu');
			$this->adminMenuClass = new $adminMenuClass();
			$this->adminMenuClass->start($this->conf, $this->piVars, $this->pObj);
		}
		return $this->adminMenuClass->output($conf);
	}


	/**
	 * Returns breadcrumb menu (forum internal)
	 *
	 * @param	array		$conf: array with thread-/forum-id
	 * @return	string		HTML output
	 */
	function breadcrumb($forum=null, $thread=null) {
		$temp[] = $this->cObj->typoLink(
			htmlspecialchars($this->conf['title']),
			array('parameter'=>$GLOBALS['TSFE']->id, 'useCacheHash' => true));

		if ($thread === null) {
			$temp[] = $this->linkToForum($forum);
		} else {
			$temp[] = $this->linkToForum($forum);
			$temp[] = $this->linkToThread($thread);
		}

		$content = implode(' &gt;&gt ', $temp);
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
			$content = $this->pObj->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pObj->pi_getLL('minutes_single') : $this->pObj->pi_getLL('minutes'));
		} elseif ($diff < ((60*60*24))) {
			//Angabe in Stunden
			$content = round(($diff/(60*60)),0);
			$content = $this->pObj->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pObj->pi_getLL('hours_single') : $this->pObj->pi_getLL('hours'));
		} elseif ($diff < ((60*60*24*5))) {
			//Angabe in Tagen
			$content = round(($diff/(60*60*24)),0);
			$content = $this->pObj->pi_getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $this->pObj->pi_getLL('days_single') : $this->pObj->pi_getLL('days'));
		} else {
			//Datum augeben
			$content = strftime($this->conf['strftime'], $lastModTs);
		}
		return $content;
	}

	/**
	 * Generates user image and returns it
	 *
	 * @param	tx_simpleforum_user		$user: fe_user
	 * @param	string		$width: image width
	 * @param	string		$height: image height (if set to '' it is calculated automaticly)
	 * @param	string		$altIMG: path to alternative image
	 * @return	string		HTML output
	 */
	function userImage(tx_simpleforum_user $user, $width, $height,$altIMG='') {
		if (!($this->cObj)) $this->cObj = t3lib_div::makeInstance('tslib_cObj');
		if ($height == '') {
			if ($user->image != '') {
				$imgConf['file'] = 'uploads/tx_srfeuserregister/'.$user->image;
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
			if ($user->image != '') {
				$imgConf['file.']['10.']['file'] = 'uploads/tx_srfeuserregister/'.$user->image;
			} else {
				$imgConf['file.']['10.']['file'] = $altIMG;
			}
			$imgConf['file.']['10.']['file.']['quality'] = '90';
			$imgConf['file.']['10.']['file.']['maxW'] = $width;
		}
		$imgConf['altText'] = $user->username;
		$myImage = $this->cObj->cObjGetSingle('IMAGE',$imgConf);
		return $myImage;
	}

	function wrapDateString($date) {
		return '%%%##%%' . $date . '%%##%%%';
	}


	/**
	 * Returns link to a user
	 *
	 * @param	integer		$userId: id of user to point at
	 * @param	string		$username: username (optional)
	 * @return	string		HTML output
	 */
	function linkToUser(tx_simpleforum_user $user) {
		if ($user->username == '') {
			$content = '<em>anonym</em>';
		} elseif ($user->deleted == 1) {
			$content = '<em>' . $user->username . '</em>';
		} else {
			$content = $this->cObj->typoLink(
				$user->username,
				array(
					'parameter' => $this->conf['profilePID'],
					'useCacheHash' => true,
					'additionalParams' => '&'.$this->conf['profileParam'].'='.$user->uid
				)
			);
		}
		return $content;
	}

	/**
	 * Returns link to a single forum
	 *
	 * @param	integer		$forumId: id of forum
	 * @param	string		$topic: forum title (optional)
	 * @return	string		HTML output
	 */
	function linkToForum(tx_simpleforum_forum $forum) {
		$content = $this->cObj->typoLink(
			$forum->getTopic(),
			array(
				'parameter' => $GLOBALS['TSFE']->id,
				'useCacheHash' => true,
				'additionalParams' => t3lib_div::implodeArrayForUrl($this->prefixId, array('fid'=>$forum->getUid())),
			)
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
	function linkToThread(tx_simpleforum_thread $thread) {
		$content = $this->cObj->typoLink(
			$thread->getTopic(),
			array(
				'parameter' => $GLOBALS['TSFE']->id,
				'useCacheHash' => true,
				'additionalParams' => t3lib_div::implodeArrayForUrl($this->prefixId, array('tid'=>$thread->getUid())),
			)
		);
		return $content;
	}


	function getListGetPageBrowser($numberOfPages) {
		// Get default configuration
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];

		// Modify this configuration
		$conf += array(
				'pageParameterName' => $this->prefixId . '|page',
				'numberOfPages' => intval($numberOfPages/$this->conf['pageSize']) +
						(($numberOfPages % $this->conf['pageSize']) == 0 ? 0 : 1),
		);

		// Get page browser
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		/* @var $cObj tslib_cObj */
		$cObj->start(array(), '');
		return $cObj->cObjGetSingle('USER', $conf);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_rendering.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_rendering.php']);
}

?>