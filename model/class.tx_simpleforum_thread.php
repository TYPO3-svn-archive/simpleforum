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
 * model/class.tx_simpleforum_thread.php
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
class tx_simpleforum_thread {
	var $prefixId		= 'tx_simpleforum_thread';		// Same as class name
	var $scriptRelPath	= 'model/class.tx_simpleforum_thread.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.
	var $dbTable		= 'tx_simpleforum_threads';

	/**
	 * Data of thread
	 *
	 * @var array
	 */
	protected $row;

	/**
	 * Original Data of thread
	 *
	 * @var array
	 */
	protected $origArray = array();

	protected $statistics = array();

	/**
	 * Creates thread object
	 *
	 * @param	mixed		$data: initial thread data or uid of existing thread
	 */
	function __construct($data) {
		$GLOBALS['TYPO3_DB']->debugOutput = true;
		if (is_array($data)) {
			$this->row = $data;
		} elseif (intVal($data) > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->dbTable, 'uid=' . intVal($data));
			if ($res) {
				$this->row = $this->origArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			}
		}
	}

	/**
	 * Writes/Updates threadinformation to database
	 *
	 */
	public function updateDatabase() {
		$diff = array_diff_assoc($this->row, $this->origArray);
		if (!empty($diff)) {
			$this->row['tstamp'] = $diff['tstamp'] = mktime();
			if (intVal($this->row['uid']) > 0) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->dbTable, 'uid=' . $this->getUid(), $this->row);
				$this->origArray = $this->row;
			} else {
				$this->row['crdate'] = $diff['crdate'] = mktime();
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->dbTable, $diff);
				$this->setUid($GLOBALS['TYPO3_DB']->sql_insert_id());
				$this->origArray = $this->row;
			}
		}
	}

	function isEmpty() {
		return empty($this->row);
	}

	function isLocked() {
		return ($this->row['locked'] == 1);
	}


	function getFid() {
		return intVal($this->row['fid']);
	}

	/**
	 * Returns uid of thread
	 *
	 * @return	integer
	 */
	public function getUid() {
		return $this->row['uid'];
	}

	/**
	 * Set uid of thread
	 *
	 * @param	integer		$uid: uid of photo db record
	 */
	protected function setUid($uid) {
		$this->row['uid'] = $uid;
	}

	public function getDeleted() {
		return ($this->row['deleted'] == 1 ? true : false);
	}

	public function delete() {
		$this->row['deleted'] = 1;
	}

	public function restore() {
		$this->row['deleted'] = 0;
	}


	/**
	 * Returns topic of thread
	 *
	 * @return	string
	 */
	public function getTopic() {
		return $this->row['topic'];
	}

	/**
	 * Set topic of thread
	 *
	 * @param	string		$topic: topic of thread
	 */
	public function setTopic($topic) {
		$this->row['topic'] = $topic;
	}

	/**
	 * Returns author of photo
	 * If it is a fe_user the uid is returned if not the name of the author
	 *
	 * @return	mixed
	 */
	public function getAuthor() {
		return $this->row['author'];
	}

	/**
	 * Set author of thread
	 *
	 * @param	mixed		$author: (integer: fe_users_uid / string: author name)
	 */
	public function setAuthor($author) {
		if (t3lib_div::testInt($author)) {
			$this->row['author'] = $author;
		} else {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'fe_users', 'username=\'' . $author . '\'');
			if (!empty($rows) && is_array($rows)) {
				$this->row['author'] = intVal($rows[0]['uid']);
			}
		}
	}

	/**
	 * Returns description of thread
	 *
	 * @return	string
	 */
	public function getDescription() {
		return $this->row['description'];
	}

	/**
	 * Set description of thread
	 *
	 * @param	string		$description: description of thread
	 */
	public function setDescription($description) {
		$this->row['description'] = $description;
	}


	public function getStatistics($name) {
		$pattern = array('user', 'lastpost', 'postnumber');
		if (!in_array($name, $pattern)) return false;

		$columns = array('user' => 'author', 'lastpost' => 'crdate', 'postnumber' => 'COUNT(uid)');

		if (!isset($this->statistics[$name])) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($columns[$name], 'tx_simpleforum_posts', 'tid=' . $this->row['uid'] . ' AND deleted=0 AND hidden=0 AND approved=1', '', 'crdate DESC', '1');
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$this->statistics[$name] = $row[0];
			}
		}

		return $this->statistics[$name];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_thread.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_thread.php']);
}
?>