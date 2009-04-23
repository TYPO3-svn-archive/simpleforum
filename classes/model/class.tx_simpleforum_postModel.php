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
 * classes/model/class.tx_simpleforum_postModel.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(t3lib_extMgm::extPath('simpleforum', 'classes/model/class.tx_simpleforum_abstractModel.php'));

/**
 * [DESCRIPTION]
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_postModel extends tx_simpleforum_abstractModel {
	protected $scriptRelPath	= 'classes/model/class.tx_simpleforum_postModel.php';
	protected $table			= 'tx_simpleforum_posts';
	protected $where			= '';
	protected $orderBy			= 'crdate ASC';

	public function setWhere() {
		$this->where = 'approved=1 AND hidden=0 AND deleted=0';
	}

	function isEmpty() {
		return empty($this->data);
	}
	public function isDeleted() {
		return ($this->data['deleted'] == 1 ? true : false);
	}
	public function delete() {
		$this->data['deleted'] = 1;
	}
	public function restore() {
		$this->data['deleted'] = 0;
	}

	/**
	 * Set author of photo
	 *
	 * @param	mixed		$author: (integer: fe_users_uid / string: author name)
	 */
	public function setAuthor($author) {
		if (t3lib_div::testInt($author)) {
			$this->data['author'] = $author;
		} else {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'fe_users', 'username=\'' . $author . '\'');
			if (!empty($rows) && is_array($rows)) {
				$this->data['author'] = intVal($rows[0]['uid']);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_postModel.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_postModel.php']);
}
?>