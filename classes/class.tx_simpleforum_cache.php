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

	public $content	= '';
	var $hasCache		= false;

	/**
	 *
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->pObj = tx_simpleforum_pObj::getInstance($conf);

		$this->checkArray = array(
			'fid' => intVal($this->pObj->piVars['fid']),
			'tid' => intVal($this->pObj->piVars['tid']),
			'isAdmin' => $this->pObj->auth->isAdmin,
			'loginState' => $GLOBALS['TSFE']->loginUser,
			'page' => $this->pObj->piVars['page'],
			'controller' => $this->pObj->conf['controller'],
		);
	}


	/**
	 * Reads cached data from database
	 *
	 * @return void
	 */
	public function fetchCache() {
		$hash = md5(@serialize($this->checkArray).$this->pObj->cObj->data['uid']);
		$where = array(
			'ce_uid=' . $this->pObj->cObj->data['uid'],
			'hash=\'' . $hash . '\'',
			'tstamp>' . (mktime() - intVal($this->pObj->conf['cache_expires'])),
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'cache_txsimpleforum', implode(' AND ', $where));
		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if (!empty($row)) $this->hasCache = true;
			$this->content = $row['content'];
		}
	}

	/**
	 * Writes content into database cache
	 *
	 * @param string $content content to be chached
	 * @param array $arr array with forum and thread id values
	 * @return void
	 */
	public function setCache($content, $arr) {
		$hash = md5(@serialize($this->checkArray).$this->pObj->cObj->data['uid']);

		$data = array(
			'hash' => $hash,
			'ce_uid' => $this->pObj->cObj->data['uid'],
			'fid' => intVal($arr['fid']),
			'tid' => intVal($arr['tid']),
			'page' => intVal($this->piVars['page']),
			'tstamp' => mktime(),
			'content' => $content,
		);

		$this->deleteCacheSingle($data['hash']);
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_txsimpleforum', $data);
		$this->content = $content;
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

	public function getContent() {
		$content = $this->content;
		$content = $this->setDateStrings($content);
		return $content;
	}

	protected function setDateStrings($content) {
		$pattern = '%%%##%%(\d+)%%##%%%';
		$matches = array();
		preg_match_all('/' . $pattern . '/', $content, $matches);
		foreach ($matches[1] as $key => $date) {
			$content = str_replace($matches[0][$key], tx_simpleforum_dateViewHelper::lastModString($date), $content);
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_cache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_cache.php']);
}

?>