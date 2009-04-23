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
 * classes/model/class.tx_simpleforum_forumModel.php
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
class tx_simpleforum_forumModel extends tx_simpleforum_abstractModel {
	protected $scriptRelPath	= 'classes/model/class.tx_simpleforum_forumModel.php';
	protected $table			= 'tx_simpleforum_forums';
	protected $where			= '';
	protected $orderBy			= 'sorting ASC';
	protected $statistics 		= array();

	public function setWhere() {
		$temp = array(
			$this->table . '.hidden=0',
			$this->table . '.deleted=0',
			'(' . $this->table . '.starttime<'.mktime().' OR ' . $this->table . '.starttime=0)',
			'(' . $this->table . '.endtime>'.mktime().' OR ' . $this->table . '.endtime=0)',
		);
		$this->where = implode(' AND ', $temp);
	}

	function isEmpty() {
		return empty($this->data);
	}

	public function isDeleted() {
		return ($this->data['deleted'] == 1);
	}
	public function delete() {
		$this->data['deleted'] = 1;
	}
	public function restore() {
		$this->data['deleted'] = 0;
	}

	public function isHidden() {
		return ($this->data['hidden'] == 1);
	}
	public function hide() {
		$this->data['hidden'] = 1;
	}
	public function unhide() {
		$this->data['hidden'] = 0;
	}

	public function getStatistics($name) {
		$pattern = array('user', 'lastpost', 'threadnumber');
		if (!in_array($name, $pattern)) return false;

		$columns = array('user' => 'author', 'lastpost' => 'crdate', 'threadnumber' => 'COUNT(uid)');
		$groupBy = array('user' => '', 'lastpost' => '', 'threadnumber' => 'tid');

		if (!isset($this->statistics[$name])) {
			switch ($name) {
				CASE 'threadnumber':
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($columns[$name], 'tx_simpleforum_threads', 'fid=' . $this->data['uid'] . ' AND deleted=0 AND hidden=0', '', 'crdate DESC', '1');
					break;
				default:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($columns[$name], 'tx_simpleforum_posts', 'tid IN (SELECT uid FROM tx_simpleforum_threads WHERE fid=' . $this->data['uid'] . ') AND deleted=0 AND hidden=0 AND approved=1', $groupBy[$name], 'crdate DESC', '1');
			}
			if ($res) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$this->statistics[$name] = $row[0];
			}
		}

		return $this->statistics[$name];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_forumModel.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_forumModel.php']);
}
?>