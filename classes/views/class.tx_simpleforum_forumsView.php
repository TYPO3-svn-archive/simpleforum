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
 * classes/views/class.tx_simpleforum_forumsView.php
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
class tx_simpleforum_forumsView extends tx_simpleforum_abstractView {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_forumsView';
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_forumsView.php';

	/**
	 * Forums
	 *
	 * @var array
	 */
	protected $forums;

	public function setForums(array $forums) {
		$this->forums = $forums;
	}

	/**
	 * (non-PHPdoc)
	 * @see classes/views/tx_simpleforum_abstractViewInterface#render()
	 */
	public function render() {
		$template = $this->pObj->cObj->getSubpart($this->templateCode, '###FORUMLIST###');


		$marker = array(
			'###LABEL_TITLE###' => htmlspecialchars($this->pObj->conf['title']),
			'###LABEL_THREADNUMBER###' => $this->pObj->getLL('L_ThreadNumber'),
			'###LABEL_LASTPOST###' => $this->pObj->getLL('L_LastPost'),
		);
		$temp['titleRow'] = $this->pObj->cObj->substituteMarkerArray($this->pObj->cObj->getSubpart($template, '###TITLEROW###'), $marker);


		$temp['dataTemplate'] = $this->pObj->cObj->getSubpart($template, '###DATAROW###');

		$i = 1;
		foreach ($this->forums as $forum) {

			$marker = array (
				'###ALTID###' => $i,
				'###FORUM_TITLE###' => tx_simpleforum_linkViewHelper::toForum($forum),
				'###FORUM_DESCRIPTION###' => $forum->description,
				'###THREADNUMBER###' => intVal($forum->getStatistics('threadnumber')),
				'###LASTPOST_DATETIME###' => tx_simpleforum_dateViewHelper::wrapDateString($forum->getStatistics('lastpost')),
				'###LASTPOST_USER###' => tx_simpleforum_linkViewHelper::toUser(tx_simpleforum_userModel::getInstance($forum->getStatistics('user'))),
			);

			$temp['dataRows'][] = $this->pObj->cObj->substituteMarkerArray($temp['dataTemplate'], $marker);
			$i = ($i == 1 ? 2 : 1);
		}

		$content = $this->pObj->cObj->substituteSubpart($template, '###TITLEROW###', $temp['titleRow']);
		$content = $this->pObj->cObj->substituteSubpart($content, '###DATAROW###', implode('', (is_array($temp['dataRows']) ? $temp['dataRows'] : array())));

		if ($this->pObj->getConf('introtext')) {
			$content = $this->pObj->cObj->substituteMarker($content,'###INTROTEXT###',$this->pObj->cObj->stdWrap($this->pObj->getConf('introtext'), $this->pObj->conf['introtext.']));
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_forumsView.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/class.tx_simpleforum_forumsView.php']);
}
?>