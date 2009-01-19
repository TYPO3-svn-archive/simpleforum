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
 * model/class.tx_simpleforum_forum.php
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
class tx_simpleforum_forum {
	var $prefixId		= 'tx_simpleforum_forum';		// Same as class name
	var $scriptRelPath	= 'model/class.tx_simpleforum_forum.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.
	var $dbTable		= 'tx_simpleforum_forums';

	/**
	 * Data of forum
	 *
	 * @var array
	 */
	protected $row;

	/**
	 * Original Data of forum
	 *
	 * @var array
	 */
	protected $origArray = array();

	/**
	 * Creates forum object
	 *
	 * @param	mixed		$data: initial forum data or uid of existing forum
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
	 * Writes/Updates Foruminformation to database
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


	/**
	 * Returns uid of forum
	 *
	 * @return	integer
	 */
	public function getUid() {
		return $this->row['uid'];
	}

	/**
	 * Set uid of forum
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


	public function getHidden() {
		return ($this->row['hidden'] == 1 ? true : false);
	}
	public function hide() {
		$this->row['hidden'] = 1;
	}
	public function unhide() {
		$this->row['hidden'] = 0;
	}

	//pid 	sorting

	/**
	 * Returns topic of forum
	 *
	 * @return	string
	 */
	public function getTopic() {
		return $this->row['topic'];
	}

	/**
	 * Set topic of forum
	 *
	 * @param	string		$topic: topic of forum
	 */
	public function setTopic($topic) {
		$this->row['topic'] = $topic;
	}

	/**
	 * Returns description of forum
	 *
	 * @return	string
	 */
	public function getDescription() {
		return $this->row['description'];
	}

	/**
	 * Set description of forum
	 *
	 * @param	string		$description: description of forum
	 */
	public function setDescription($description) {
		$this->row['description'] = $description;
	}


	public function setStarttime($starttime) {
		$this->row['starttime'] = intVal($starttime);
	}
	public function getStarttime() {
		return $this->row['starttime'];
	}
	public function setEndtime($endtime) {
		$this->row['endtime'] = intVal($endtime);
	}
	public function getEndtime() {
		return $this->row['endtime'];
	}


	public function updateReferences() {
		//threadnumber 	lastpost 	lastpostuser 	lastpostusername
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_forum.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/model/class.tx_simpleforum_forum.php']);
}
?>