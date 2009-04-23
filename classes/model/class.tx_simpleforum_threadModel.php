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
 * classes/model/class.tx_simpleforum_threadModel.php
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
class tx_simpleforum_threadModel extends tx_simpleforum_abstractModel {
	protected $scriptRelPath	= 'classes/model/class.tx_simpleforum_threadModel.php';
	protected $table			= 'tx_simpleforum_threads';
	protected $where			= '';
	protected $statistics		= array();

	public function setWhere() {
		$temp = array(
			$this->table . '.hidden=0',
			$this->table . '.deleted=0',
			'(' . $this->table . '.starttime<'.mktime().' OR ' . $this->table . '.starttime=0)',
			'(' . $this->table . '.endtime>'.mktime().' OR ' . $this->table . '.endtime=0)',
		);
		$this->where = implode(' AND ', $temp);
	}

	public function isEmpty() {
		return empty($this->data);
	}
	public function isLocked() {
		return ($this->data['locked'] == 1);
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
	 * Set author of thread
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

	public function getStatistics($name) {
		$pattern = array('user', 'lastpost', 'postnumber');
		if (!in_array($name, $pattern)) return false;

		$columns = array('user' => 'author', 'lastpost' => 'crdate', 'postnumber' => 'COUNT(uid)');

		if (!isset($this->statistics[$name])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($columns[$name], 'tx_simpleforum_posts', 'tid=' . $this->data['uid'] . ' AND deleted=0 AND hidden=0 AND approved=1', '', 'crdate DESC', '1');
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$this->statistics[$name] = $row[0];
			}
		}

		return $this->statistics[$name];
	}

/**
	 * Returns an array of all matching objects
	 *
	 * @param string $where where clause, defaults to 'deleted=0 AND hidden=0'
	 * @return void
	 */
	public function findAll($addWhere='', $limit) {
		$this->setWhere();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_simpleforum_threads.*,MAX(tx_simpleforum_posts.crdate) AS lastpost',
						'tx_simpleforum_threads,tx_simpleforum_posts', $this->where . $addWhere . ' AND tx_simpleforum_threads.uid=tid', 'tx_simpleforum_threads.uid', 'lastpost DESC', $limit);
		if (!$res) return false;

		$result = array();
		$name = get_class($this);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$tempObject = new $name();
			$tempObject->injectData($row, true);
			$result[] = $tempObject;
		}
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_threadModel.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_threadModel.php']);
}
?>