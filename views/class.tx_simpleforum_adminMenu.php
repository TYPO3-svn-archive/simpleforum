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
 * views/class.tx_simpleforum_adminMenu.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * [DESCRIPTION]
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_adminMenu extends tslib_pibase {
	var $prefixId		= 'tx_simpleforum_adminMenu';		// Same as class name
	var $scriptRelPath	= 'views/class.tx_simpleforum_adminMenu.php';	// Path to this script relative to the extension dir.
	var $extKey			= 'simpleforum';	// The extension key.


	public function start(&$conf, &$piVars, &$pObj) {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pObj = &$pObj;
		$this->conf = &$conf;
		$this->piVars = &$piVars;
	}

	/**
	 * Returns admin context menu incl. wrap
	 *
	 * @param	array		$conf: configuration array
	 * @return	string		HTML output
	 */
	function output($conf=array()) {
		if ($this->pObj->auth->isAdmin) {
			$content = '<a href="#" onclick="txSimpleForumAdminMenu(event, '.$conf['id'].'); return false;" title="'.$this->pObj->pi_getLL('adminMenuTitle').'"><img src="' . $this->conf['adminIcon'] . '" /></a>';

			$items = array(
				'delete' => array('icon' => 'res/cross.png'),
				'edit' => array('icon' => 'res/pencil.png'),
				'hide' => array('icon' => 'res/eye.png'),
				'move' => array('icon' => 'res/table_go.png'),
				'lock' => array('icon' => 'res/lock.png'),
				'unlock' => array('icon' => 'res/lock_open.png'),
			);

			$content .= $this->adminMenu_getMenu(array('items' => $items, 'show'=>$conf['show'], 'id'=>$conf['id'], 'type' => $conf['type'], 'leftright' => $conf['leftright']));
		}
		return $this->cObj->substituteMarker($conf['template'], '###ADMINICONS###', $content);
	}

	/**
	 * Returns plain admin context menu
	 *
	 * @param	array		$conf: configuration array
	 * @return	string		HTML output
	 */
	function adminMenu_getMenu($conf) {
		$contextMenu = $this->cObj->getSubpart($this->cObj->fileResource('EXT:simpleforum/res/contextmenu.html'),'###CONTEXTMENU###');
		$marker = array(
			'###UID###' => $conf['id'],
			'###LEFTRIGHT###' => $conf['leftright'],
		);

		$rowTemplate = $this->cObj->getSubpart($contextMenu, '###ROW###');
		foreach ($conf['show'] as $label) {
			$urlParameter = array(
				'no_cache' => 1,
				'tx_simpleforum_pi1[adminAction]' => $label,
				'tx_simpleforum_pi1[type]' => $conf['type'],
				'tx_simpleforum_pi1[id]' => $conf['id'],
				'tx_simpleforum_pi1[chk]' => md5($conf['type'] . $conf['id'] . $label . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']),
			);
			foreach ($urlParameter as $k => $v) {
				$urlParameterStr .= '&'.$k.'='.$v;
			}
			$url = $this->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'exclude' => 'cHash,no_cache',
				),
				'additionalParams' => $urlParameterStr,
				'useCacheHash' => false,
			));
			$subMarker = array(
				'###ICON###' => t3lib_extMgm::siteRelPath('simpleforum') . $conf['items'][$label]['icon'],
				'###LABEL###' => $this->pObj->pi_getLL('contextmenu_'.$label),
				'###URL###' => $url,
			);
			$rows[] = $this->cObj->substituteMarkerArray($rowTemplate, $subMarker);
		}
		$contextMenu = $this->cObj->substituteSubpart($contextMenu, '###ROW###', implode('', $rows));
		$contextMenu = $this->cObj->substituteMarkerArray($contextMenu, $marker);

		return $contextMenu;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_adminMenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/views/class.tx_simpleforum_adminMenu.php']);
}

?>