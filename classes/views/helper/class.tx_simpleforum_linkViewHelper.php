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
 * classes/views/helper/class.tx_simpleforum_linkViewHelper.php
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
class tx_simpleforum_linkViewHelper {
	protected $extKey			= 'simpleforum';
	protected $scriptRelPath	= 'classes/views/helper/class.tx_simpleforum_linkViewHelper.php';

	/**
	 * Returns link to a user
	 *
	 * @param	integer		$userId: id of user to point at
	 * @param	string		$username: username (optional)
	 * @return	string		HTML output
	 */
	public static function toUser(tx_simpleforum_userModel $user) {
		$pObj = tx_simpleforum_pObj::getInstance();
		if ($user->username == '') {
			$content = '<em>anonym</em>';
		} elseif ($user->deleted == 1) {
			$content = '<em>' . $user->username . '</em>';
		} else {
			$content = $pObj->cObj->typoLink(
				$user->username,
				array(
					'parameter' => $pObj->conf['profilePID'],
					'useCacheHash' => true,
					'additionalParams' => '&'.$pObj->conf['profileParam'].'='.$user->uid
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
	public static function toForum(tx_simpleforum_forumModel $forum) {
		$pObj = tx_simpleforum_pObj::getInstance();
		$content = $pObj->cObj->typoLink(
			$forum->topic,
			array(
				'parameter' => $GLOBALS['TSFE']->id,
				'useCacheHash' => true,
				'additionalParams' => t3lib_div::implodeArrayForUrl($pObj->prefixId, array('fid'=>$forum->uid)),
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
	public static function toThread(tx_simpleforum_threadModel $thread) {
		$pObj = tx_simpleforum_pObj::getInstance();
		$content = $pObj->cObj->typoLink(
			$thread->topic,
			array(
				'parameter' => $GLOBALS['TSFE']->id,
				'useCacheHash' => true,
				'additionalParams' => t3lib_div::implodeArrayForUrl($pObj->prefixId, array('tid'=>$thread->uid)),
			)
		);
		return $content;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_linkViewHelper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_linkViewHelper.php']);
}
?>