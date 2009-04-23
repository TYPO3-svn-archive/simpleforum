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
 * classes/views/class.tx_simpleforum_adminMenuViewHelper.php
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
class tx_simpleforum_adminMenuViewHelper {
	var $prefixId		= 'tx_simpleforum_adminMenuViewHelper';		// Same as class name
	var $scriptRelPath	= 'classes/views/class.tx_simpleforum_adminMenuViewHelper.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.

	/**
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	/**
	 * @var array
	 */
	protected $formConf;

	public function __construct() {
		$this->pObj = tx_simpleforum_pObj::getInstance();
	}

	public function setConf(array $conf) {
		$this->formConf = $conf;
	}

	/**
	 * Returns admin context menu incl. wrap
	 *
	 * @param	array		$conf: configuration array
	 * @return	string		HTML output
	 */
	function render() {
		if ($this->pObj->auth->isAdmin) {
			$content = '<a href="#" onclick="txSimpleForumAdminMenu(event, '.$this->formConf['id'].'); return false;" title="'.$this->pObj->getLL('adminMenuViewTitle').'"><img src="' . $this->pObj->conf['adminIcon'] . '" /></a>';

			$items = array(
				'delete' => array('icon' => 'res/images/cross.png'),
				'edit' => array('icon' => 'res/images/pencil.png'),
				'hide' => array('icon' => 'res/images/eye.png'),
				'move' => array('icon' => 'res/images/table_go.png'),
				'lock' => array('icon' => 'res/images/lock.png'),
				'unlock' => array('icon' => 'res/images/lock_open.png'),
			);

			$content .= $this->getMenu(
				array(
					'items' => $items,
					'show'=>$this->formConf['show'],
					'id'=>$this->formConf['id'],
					'type' => $this->formConf['type'],
					'leftright' => $this->formConf['leftright']
				)
			);
			$content = $this->pObj->cObj->substituteMarker($this->formConf['template'], '###ADMINICONS###', $content);
		}
		return $content;
	}

	/**
	 * Returns plain admin context menu
	 *
	 * @param	array		$conf: configuration array
	 * @return	string		HTML output
	 */
	function getMenu($conf) {
		$contextMenu = $this->pObj->cObj->getSubpart($this->pObj->cObj->fileResource('EXT:simpleforum/res/contextmenu.html'),'###CONTEXTMENU###');
		$marker = array(
			'###UID###' => $conf['id'],
			'###LEFTRIGHT###' => $conf['leftright'],
		);

		$rowTemplate = $this->pObj->cObj->getSubpart($contextMenu, '###ROW###');
		foreach ($conf['show'] as $label) {
			$urlParameter = array(
				'no_cache' => 1,
				'adminAction' => $label,
				'type' => $conf['type'],
				'id' => $conf['id'],
				'chk' => md5($conf['type'] . $conf['id'] . $label . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
			);
			$url = $this->pObj->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'exclude' => 'cHash,no_cache',
				),
				'additionalParams' => t3lib_div::implodeArrayForUrl($this->pObj->prefixId, $urlParameter),
				'useCacheHash' => false,
			));
			$subMarker = array(
				'###ICON###' => t3lib_extMgm::siteRelPath('simpleforum') . $conf['items'][$label]['icon'],
				'###LABEL###' => $this->pObj->getLL('contextmenu_'.$label),
				'###URL###' => $url,
			);
			$rows[] = $this->pObj->cObj->substituteMarkerArray($rowTemplate, $subMarker);
		}
		$contextMenu = $this->pObj->cObj->substituteSubpart($contextMenu, '###ROW###', implode('', $rows));
		$contextMenu = $this->pObj->cObj->substituteMarkerArray($contextMenu, $marker);

		return $contextMenu;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_adminMenuView.Helperphp'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_adminMenuViewHelper.php']);
}

?>