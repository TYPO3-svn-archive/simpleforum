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
 * classes/views/class.tx_simpleforum_abstractView.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * Interface for view classes
 *
 * @author Peter Schuster
 */
interface tx_simpleforum_abstractViewInterface {

	/**
	 * Renders view
	 *
	 * @return string HTML output
	 */
	public function render();
	}

/**
 * Abstract view class
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
abstract class tx_simpleforum_abstractView implements tx_simpleforum_abstractViewInterface {
	protected $extKey			= 'simpleforum';
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_abstractView.php';

	/**
	 *
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	/**
	 * Constructs view object
	 *
	 * @return void
	 */
	public function __construct() {
		$this->pObj = tx_simpleforum_pObj::getInstance();

		if (!$this->pObj->conf['templateFile']) $this->pObj->conf['templateFile'] = 'EXT:simpleforum/res/template.tmpl';
		$this->templateCode = $this->pObj->cObj->fileResource($this->pObj->conf['templateFile']);
		$this->templateCode = $this->pObj->cObj->substituteMarker($this->templateCode, '###SITE_REL_PATH###', t3lib_extMgm::siteRelPath('simpleforum'));

		//Replace 'EXT:simpleforum/' in conf
		$list = array('lockedIcon', 'adminIcon');
		foreach ($list as $l) $this->pObj->conf[$l] = str_replace('EXT:simpleforum/', t3lib_extMgm::siteRelPath('simpleforum'), $this->pObj->conf[$l]);

		$key = 'tx_simpleforum_' . md5($this->templateCode);
		if (!isset($GLOBALS['TSFE']->additionalHeaderData[$key])) {
			$headerParts = $this->pObj->cObj->getSubpart($this->templateCode, '###HEADER_ADDITIONS###');
			if ($headerParts) $GLOBALS['TSFE']->additionalHeaderData[$key] = $headerParts;
		}
	}

	protected function getListGetPageBrowser() {
		$numberOfPages = $this->pObj->numberOfPages;

		// Get default configuration
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagebrowse_pi1.'];

		// Modify this configuration
		$conf += array(
				'pageParameterName' => $this->pObj->prefixId . '|page',
				'numberOfPages' => intval($numberOfPages/$this->pObj->conf['pageSize']) +
						(($numberOfPages % $this->pObj->conf['pageSize']) == 0 ? 0 : 1),
		);

		// Get page browser
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		/* @var $cObj tslib_cObj */
		$cObj->start(array(), '');
		return $cObj->cObjGetSingle('USER', $conf);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_abstractView.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_abstractView.php']);
}
?>