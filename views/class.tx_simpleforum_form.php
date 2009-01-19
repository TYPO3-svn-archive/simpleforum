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
 * views/class.tx_simpleforum_form.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * [DESCRIPTION]
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_form extends tslib_pibase {
	var $prefixId		= 'tx_simpleforum_form';		// Same as class name
	var $scriptRelPath	= 'views/class.tx_simpleforum_form.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.


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
			$threadOpen = ($thread['locked'] != 1);
			$template = $this->cObj->getSubpart($this->templateCode, '###REPLYBOX###');
		} else {
			$forum = $this->data_forum(intVal($conf['fid']));
			$threadOpen = true;
			$template = $this->cObj->getSubpart($this->templateCode, '###NEWTHREAD###');
		}

		if ($GLOBALS['TSFE']->loginUser && $threadOpen) {

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
		} elseif ($GLOBALS['TSFE']->loginUser && !$threadOpen) {
			$content = $this->pi_getLL('message_threadLocked');
		} elseif (!$GLOBALS['TSFE']->loginUser && $conf['tid']) {
			$content = $this->pi_getLL('message_loginForReply');
		} else {
			$content = $this->pi_getLL('message_loginForThread');
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_form.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_form.php']);
}

?>