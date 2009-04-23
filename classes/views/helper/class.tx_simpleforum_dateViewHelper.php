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
 * classes/views/helper/class.tx_simpleforum_dateViewHelper.php
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
class tx_simpleforum_dateViewHelper {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_dateViewHelper';
	protected $scriptRelPath	= 'classes/views/class.tx_simpleforum_dateViewHelperHelper.php';

	/**
	 * Returns formarted string with 'last modified date'
	 *
	 * @param	integer		$lastModTs: timestamp on which calculation is based
	 * @return	string		formarted timespan/date
	 */
	public static function lastModString($lastModTs) {
		$pObj = tx_simpleforum_pObj::getInstance();

		$lastModTs = intVal($lastModTs);
		$diff = time() - $lastModTs;

		if ($diff < (60*60)) {
			//Angabe in Minuten
			$content = round(($diff/60),0);
			$content = $pObj->getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $pObj->getLL('minutes_single') : $pObj->getLL('minutes'));
		} elseif ($diff < ((60*60*24))) {
			//Angabe in Stunden
			$content = round(($diff/(60*60)),0);
			$content = $pObj->getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $pObj->getLL('hours_single') : $pObj->getLL('hours'));
		} elseif ($diff < ((60*60*24*5))) {
			//Angabe in Tagen
			$content = round(($diff/(60*60*24)),0);
			$content = $pObj->getLL('lastmod_pre') . ' ' . $content . ' ' . ($content == 1 ? $pObj->getLL('days_single') : $pObj->getLL('days'));
		} else {
			//Datum augeben
			$content = strftime($pObj->conf['strftime'], $lastModTs);
		}
		return $content;
	}

	public static function wrapDateString($date) {
		return '%%%##%%' . $date . '%%##%%%';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_dateViewHelper.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/views/helper/class.tx_simpleforum_dateViewHelper.php']);
}
?>