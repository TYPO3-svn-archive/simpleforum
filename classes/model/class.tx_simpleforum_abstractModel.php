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
 * classes/model/class.tx_simpleforum_abstractModel.php
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
abstract class tx_simpleforum_abstractModel {
	protected $extKey			= 'simpleforum';
	protected $scriptRelPath	= 'classes/model/class.tx_simpleforum_abstractModel.php';
	protected $limitStart = 0;
	protected $requestData = array();

	/**
	 * Model data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Persited model data (in database)
	 *
	 * @var array
	 */
	protected $persistedData = array();

	/**
	 * Gets data from database
	 *
	 * @param array $data: array(column => data)
	 * @return object
	 */
	public function findBy($data) {
		$this->requestData = $data;
		$where = array();
		foreach ($data as $col => $val) {
			$where[] = $col . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($val, $this->table);
		}
		$where = implode(' AND ', $where);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, $where, '', '', $this->limitStart . ',1');
		if (!$res) return false;

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (empty($row)) return false;
		$this->data = $this->persistedData = $row;
		return self;
	}

	/**
	 * Gets next data set in database
	 *
	 * @param array $data: array(column => data)
	 * @return object
	 */
	public function findNext($data=NULL) {
		if ($data !== Null) $this->requestData = $data;
		$this->limitStart++;
		return $this->findBy($this->requestData);
	}

	/**
	 * Saves data in database
	 *
	 * @return object
	 */
	public function save() {
		$diff = array_diff_assoc($this->data, $this->persistedData);
		if (!empty($diff)) {
			if (intVal($this->data['uid']) > 0) {
				unset($diff['uid']);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table, 'uid=' . $this->data['uid'], $diff);
				$this->persistedData = $this->data;
			} else {
				$this->data['crdate'] = mktime();
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->table, $this->data);
				$this->data['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
				$this->persistedData = $this->data;
			}

			$refindex = t3lib_div::makeInstance('t3lib_refindex');
			$refindex->updateRefIndexTable($this->table, $this->data['uid']);
		}

		return self;
	}

	/**
	 * Getter function for model data
	 *
	 * @param string $name: name of requested property
	 * @return void
	 */
	public function __get($name) {
		if (method_exists($this, 'get' . ucfirst($name))) {
			return call_user_func(array($this, 'get' . ucfirst($name)));
		} elseif (method_exists($this, 'is' . ucfirst($name))) {
			return call_user_func(array($this, 'is' . ucfirst($name)));
		} elseif (method_exists($this, 'has' . ucfirst($name))) {
			return call_user_func(array($this, 'has' . ucfirst($name)));
		} elseif (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
	}

	/**
	 * Setter function for model data
	 *
	 * @param string $name: set object propertie
	 * @param string $value: given value for propertie
	 * @return void
	 */
	public function __set($name, $value) {
		if (method_exists($this, 'set' . ucfirst($name))) {
			call_user_func(array($this, 'set' . ucfirst($name)), $value);
		} elseif (array_key_exists($name, $this->data)) {
			$this->data[$name] = $value;
		}
	}


	/**
	 * Wrapper method for findByX, where X is a table field
	 *
	 * @param string $name name of called function
	 * @param array $parameter parameters passed to the function
	 * @return mixed
	 */
	public function __call($name, $parameter) {
		if (substr($name, 0, 6) == 'findBy' && strlen($name) > 6) {
			$field = strtolower(substr($name, 6));
			return $this->findBy(array($field => $parameter[0]));
		} else {
			throw new Exception('Call to undefined method ' . $name . ' of object ' . get_class($this));
		}
	}

	/**
	 * Returns an array of all matching objects
	 *
	 * @param string $where where clause, defaults to 'deleted=0 AND hidden=0'
	 * @return void
	 */
	public function findAll($addWhere='', $orderBy='', $limit='') {
		$this->setWhere();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table, $this->where . $addWhere, '', $orderBy == '' ? $this->orderBy : $orderBy, $limit);
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

	/**
	 * Set object data
	 *
	 * @param array $data data to be set
	 * @param boolean $isPersisted set to true, if data is already persisted
	 * @return void
	 */
	public function injectData(array $data, $isPersisted=false) {
		if (empty($this->data)) {
			$this->data = $data;
			if ($isPersisted) $this->persistedData = $data;
		}
	}

	/**
	 * Returns number of rows to be shown
	 *
	 * @param string $addWhere
	 * @return integer row count
	 */
	public function getRowCount($addWhere='') {
		$this->setWhere();
		list($count) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(uid) as count', $this->table, $this->where . $addWhere);
		return intVal($count);

	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_abstractModel.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/model/class.tx_simpleforum_abstractModel.php']);
}
?>