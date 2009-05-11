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
 * classes/class.tx_simpleforum_data.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * Handling of submitted data
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_data {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_data';
	protected $scriptRelPath	= 'classes/class.tx_simpleforum_data.php';

	/**
	 * @var tx_simpleforum_pObj
	 */
	protected $pObj;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor
	 *
	 * @param array $data
	 * @return void
	 */
	public function __construct(array $data) {
		$this->data = $data;

		$this->pObj = tx_simpleforum_pObj::getInstance();
	}

	/**
	 * Processes form submissions.
	 *
	 * @return	void
	 */
	public function processSubmission() {
		$valid = $this->processSubmission_validate();
		if ($this->data['reply']['submit'] && $valid) {

			if (isset($this->data['reply']['title'])) {
				$thread = new tx_simpleforum_threadModel();
				$thread->injectData(array(
					'pid' => intVal($this->pObj->getConf('storagePid')),
					'fid' => intVal($this->data['reply']['fid']),
					'topic' => $this->data['reply']['title'],
					'author' => $GLOBALS['TSFE']->fe_user->user['uid'],
				));
				$thread->save();
			}

			$post = new tx_simpleforum_postModel();
			$post->injectData($this->dataNewPost());
			if($thread) $post->tid = $thread->uid;
			$post->save();

			if (!$thread) {
				$thread = new tx_simpleforum_threadModel();
				$thread->findByUid($post->tid);
			}

			$this->pObj->cache->deleteCacheForum($thread->fid);
			$this->pObj->cache->deleteCacheThread($thread->uid);

			// Update reference index
			$refindex = t3lib_div::makeInstance('t3lib_refindex');
			$refindex->updateRefIndexTable('tx_simpleforum_posts', $post->uid);
			$refindex->updateRefIndexTable('tx_simpleforum_threads', $post->tid);
			$refindex->updateRefIndexTable('tx_simpleforum_forums', $thread->fid);
		}

	}

	/**
	 * Validates submitted form.
	 *
	 * @return	boolean		true, if form is ok.
	 */
	protected function processSubmission_validate() {
		$errorCount = 0;

		// trim all
		foreach ($this->data as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $key2s => $value2s) {
					if (!is_array($value2s)) {
						$this->data[$key][$key2s] = trim($value2s);
					}
				}
			} else {
				$this->data[$key] = trim($value);
			}
		}
		// Check required fields first
		if (!$this->data['reply']['message']) {
			$errorCount++;
		}
		if (!$this->data['reply']['title'] && !$this->data['reply']['tid']) {
			$errorCount++;
		}

		if (!empty($this->data['reply']['homepage'])) {
			$errorCount++;
		}

		// Check whether user is logged in
		if (!$GLOBALS['TSFE']->loginUser) {
			$errorCount++;
		}


		return ($errorCount == 0);
	}




	/**
	 * Creates record array for new post
	 *
	 * @return	array		new post array
	 */
	protected function dataNewPost() {
		$isApproved = 1;

		// Create record
		$record = array(
			'pid' => intVal($this->pObj->getConf('storagePid')),
			'tid' => intVal($this->data['reply']['tid']),
			'author' => $GLOBALS['TSFE']->fe_user->user['uid'],
			'message' => $this->data['reply']['message'],
			'remote_addr' => t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'approved' => $isApproved,
		);

		// Check for double post
		$double_post_check = md5(implode(',', $record));
		if ($this->pObj->getConf('preventDuplicatePosts')) {
			list($info) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t', 'tx_simpleforum_posts',
						'hidden=0 AND deleted=0 AND crdate>=' . (time() - 60*60) . ' AND doublepostcheck=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($double_post_check, 'tx_simpleforum_posts'));
		} else {
			$info['t'] = 0;
		}

		if ($info['t'] == 0) {
			$record['doublepostcheck'] = $double_post_check;
			return $record;
		} else {
			return false;
		}
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_data.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_data.php']);
}
?>