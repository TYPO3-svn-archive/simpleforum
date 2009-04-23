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
 * classes/views/helper/class.tx_simpleforum_breadcrumbViewHelper.php
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
class tx_simpleforum_breadcrumbViewHelper {
	protected $extKey			= 'simpleforum';
	protected $scriptRelPath	= 'classes/views/helper/class.tx_simpleforum_breadcrumbViewHelper.php';

	/**
	 * @var tx_simpleforum_forumModel
	 */
	protected $forum;

	/**
	 * @var tx_simpleforum_threadModel
	 */
	protected $thread = null;

	/**
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	public function setForum(tx_simpleforum_forumModel $forum) {
		$this->forum = $forum;
	}

	public function setThread(tx_simpleforum_threadModel $thread) {
		$this->thread = $thread;
	}

	public function __construct() {
		$this->pObj = tx_simpleforum_pObj::getInstance();
	}

	public function render() {
		$temp[] = $this->pObj->cObj->typoLink(
			htmlspecialchars($this->pObj->conf['title']),
			array('parameter'=>$GLOBALS['TSFE']->id, 'useCacheHash' => true));

		if ($this->thread === null) {
			$temp[] = tx_simpleforum_linkViewHelper::toForum($this->forum);
		} else {
			$temp[] = tx_simpleforum_linkViewHelper::toForum($this->forum);
			$temp[] = tx_simpleforum_linkViewHelper::toThread($this->thread);
		}

		$content = implode(' &gt;&gt ', $temp);
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_breadcrumbViewHelper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_breadcrumbViewHelper.php']);
}
?>