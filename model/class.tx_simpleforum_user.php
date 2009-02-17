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
 * model/class.tx_simpleforum_user.php
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
class tx_simpleforum_user {
	var $scriptRelPath	= 'model/class.tx_simpleforum_user.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.

	protected static $singleton = array();

	protected $row = array();
	protected $origRow = array();

	protected function __construct($userId) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,username,name,first_name,last_name,showname,image',
				'fe_users', 'uid='.$userId);
		if ($res) {
			$this->row = $this->origRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		}
	}

	public static function getInstance($userId) {
		$userId = intVal($userId);
		if (!isset(self::$singleton[$userId])) {
			$className = t3lib_div::makeInstanceClassName('tx_simpleforum_user');
			self::$singleton[$userId] = new $className($userId);
		}
		return self::$singleton[$userId];
	}

	public function __get($name) {
		if (isset($this->row[$name])) {
			return $this->row[$name];
		}
	}

	public function __set($name, $value) {
		if (isset($this->row[$name])) {
			$this->row[$name] = $value;
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_user.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_user.php']);
}

?>