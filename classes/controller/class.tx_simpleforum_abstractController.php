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
 * classes/controller/class.tx_simpleforum_abstractController.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * Interface for all controllers of tx_simpleforum
 *
 * @author Peter Schuster
 */
interface tx_simpleforum_controllerInterface {

	/**
	 * Main controller method
	 *
	 * @return void
	 */
	public function main();
}

/**
 * Abstract class for simpleforum controllers
 * implements tx_simpleforum_controllerInterface
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
abstract class tx_simpleforum_abstractController implements tx_simpleforum_controllerInterface {
	protected $extKey			= 'simpleforum';

	/**
	 * Array of parameters for caching output
	 *
	 * @var array
	 */
	public $cacheParams;

	/**
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	public function __construct() {
		$this->pObj = tx_simpleforum_pObj::getInstance();
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/controller/class.tx_simpleforum_abstractController.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/controller/class.tx_simpleforum_abstractController.php']);
}
?>