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
 * classes/class.tx_simpleforum_cache.php
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
class tx_simpleforum_cache {
	var $scriptRelPath	= 'classes/class.tx_simpleforum_cache.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.

	var $cached_data	= '';
	var $hasCache		= false;

	public function __construct() {

	}

	public function start(&$conf, &$piVars, &$pObj) {
		$this->piVars = &$piVars;
		$this->conf = &$conf;
		$this->pObj = &$pObj;
		$this->checkArray = array(
			'fid' => intVal($this->piVars['fid']),
			'tid' => intVal($this->piVars['tid']),
			'isAdmin' => $this->pObj->auth->isAdmin,
			'loginState' => $GLOBALS['TSFE']->loginUser,
		);
	}

	public function getCache() {
		$hash = md5(@serialize($this->checkArray).$this->pObj->parentCE);
		$where = array(
			'ce_uid=' . $this->pObj->parentCE,
			'hash=\'' . $hash . '\'',
			'tstamp>' . (mktime() - intVal($this->conf['cache_expires'])),
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'cache_txsimpleforum', implode(' AND ', $where));
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if (!empty($row)) $this->hasCache = true;
			$this->cached_data = $row['content'];
		}
	}

	public function setCache($content, $arr) {
		$hash = md5(@serialize($this->checkArray).$this->pObj->parentCE);

		$data = array(
			'hash' => $hash,
			'ce_uid' => $this->pObj->parentCE,
			'fid' => intVal($arr['fid']),
			'tid' => intVal($arr['tid']),
			'tstamp' => mktime(),
			'content' => $content,
		);

		$this->deleteCacheSingle($data['hash']);
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_txsimpleforum', $data);
	}

	public function deleteCacheForum($fid) {
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_txsimpleforum', 'tid=0 AND fid=' . $fid);
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_txsimpleforum', 'tid=0 AND fid=0');
	}

	public function deleteCacheThread($tid) {
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_txsimpleforum', 'tid=' . $tid);
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_txsimpleforum', 'tid=0 AND fid=0');
	}

	public function deleteCacheSingle($hash = '') {
		if ($hash == '') $hash = md5(@serialize($this->checkArray).$this->pObj->parentCE);
		$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_txsimpleforum', 'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'cache_txsimplefourm'));
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_cache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_cache.php']);
}

?>