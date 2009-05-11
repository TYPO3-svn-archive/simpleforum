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
 * classes/class.tx_simpleforum_dispatcher.php
 *
 * $Id$
 *
 * @author Peter Schuster <typo3@peschuster.de>
 */

/**
 * Dispatcher
 *
 * @author Peter Schuster <typo3@peschuster.de>
 * @package TYPO3
 * @subpackage simpleforum
 */
class tx_simpleforum_dispatcher {
	protected $extKey			= 'simpleforum';
	protected $prefixId			= 'tx_simpleforum_dispatcher';
	protected $scriptRelPath	= 'classes/class.tx_simpleforum_dispatcher.php';

	/**
	 *
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 *
	 * @var tx_simpleforum_pObj
	 */
	public $pObj;

	/**
	 * Classnames
	 *
	 * @var array
	 */
	public $className = array();

	/**
	 * class contructor
	 *
	 * @return void
	 */
	public function __construct() {
		spl_autoload_register(array($this, 'autoloader'));
	}

	public function dispatch($content, $conf) {
		$GLOBALS['TYPO3_DB']->debugOutput = true;

		$this->init($conf);

		$postData = t3lib_div::_POST($this->pObj->prefixId);
		if (!empty($postData)) {
			$dataController = new tx_simpleforum_data($postData);
			$dataController->processSubmission();
		}

		$this->pObj->cache->fetchCache();
		if (!$this->pObj->cache->hasCache || $this->pObj->conf['no_cache'] == 1) {
			$controller = t3lib_div::makeInstance('tx_simpleforum_' . $this->pObj->conf['controller'] . 'Controller');
			$content = $controller->main();
			$this->pObj->cache->setCache($content, $controller->cacheParams);
		}
		$content = $this->pObj->cache->getContent();


		$GLOBALS['TSFE']->additionalHeaderData['tx_simpleforum'] = '';
		return '<div class="' . str_replace('_', '-', $this->pObj->prefixId) . '">' . $content . '</div>';
	}

	/**
	 * Initiates classes and configuration values
	 *
	 * @return void
	 */
	public function init(array $conf) {
		$this->resolveClassNames();

		$this->pObj = tx_simpleforum_pObj::getInstance($conf, $this->cObj);
		$this->pObj->auth = t3lib_div::makeInstance('tx_simpleforum_auth');
		$this->pObj->cache = t3lib_div::makeInstance('tx_simpleforum_cache');

	}

	/**
	 * Resolves class names (xclass)
	 * @return void
	 */
	protected function resolveClassNames() {
		$classes = array('forumModel', 'threadModel', 'postModel', 'userModel', 'admin', 'auth', 'cache', 'pObj');
		foreach ($classes as $name) {
			$className = 'tx_simpleforum_' . $name;
			while(class_exists('ux_' . $className)) {
				$className = 'ux_' . $className;
			}
			$this->className[$name] = $className;
		}
	}

	/**
	 * Autoload php files of this extension on request
	 *
	 * @param string $class: class name
	 * @return void
	 */
	protected function autoloader($class) {
		$classParts = explode('_', $class);
		if ($classParts[1] !== 'simpleforum') return false;

		$extPath = t3lib_extMgm::extPath($this->extKey);
		$filename = 'class.' . $class . '.php';

		if (substr($class, -10, 10) == 'Controller') {
			$path = $extPath . 'classes/controller/' . $filename;
		} elseif (substr($class, -5, 5) == 'Model') {
			$path = $extPath . 'classes/model/' . $filename;
		} elseif (substr($class, -4, 4) == 'View') {
			$path = $extPath . 'classes/views/' . $filename;
		} elseif (substr($class, -10, 10) == 'ViewHelper') {
			$path = $extPath . 'classes/views/helper/' . $filename;
		} else {
			$path = $extPath . 'classes/' . $filename;
		}

		if (@file_exists($path)) {
			require_once($path);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_dispatcher.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_dispatcher.php']);
}
?>