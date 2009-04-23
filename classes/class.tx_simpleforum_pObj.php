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
 * classes/class.tx_simpleforum_pObj.php
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
 *
 * @singleton
 */
class tx_simpleforum_pObj {
	public $prefixId			= 'tx_simpleforum';
	protected $extKey			= 'simpleforum';
	protected $scriptRelPath	= 'classes/class.tx_simpleforum_pObj.php';

	public $numberOfPages;

	/**
	 *
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * TypoScript Configuration
	 *
	 * @var array
	 */
	public $conf;

	/**
	 * Configuration value
	 * (merged flexform and typoscript)
	 *
	 * @var array
	 */
	public $prioConf;

	/**
	 *
	 * @var array
	 */
	public $piVars;

	/**
	 *
	 * @var tx_simpleforum_cache
	 */
	public $cache;

	/**
	 *
	 * @var tx_simpleforum_auth
	 */
	public $auth;

	/**
	 *
	 * @var tx_simpleforum_admin
	 */
	public $admin;

	/**
	 *
	 * @var instance of pObj
	 */
	protected static $instance = NULL;

	/**
	 * Constructor (protected -> singelton)
	 *
	 * @return void
	 */
	protected function __construct($conf, $cObj) {
		$this->cObj = $cObj;
		$this->conf = $conf;
		$this->conf = array_merge($this->conf, (array)$GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId . '.']);

		$this->initLL();
		$this->initPiVars();
		$this->initflexForm();
	}

	/**
	 * Returns an instance of pObj
	 *
	 * @return tx_simpleforum_pObj
	 */
	public static function getInstance($conf=array(), $cObj=null) {
		if (self::$instance === NULL) {
			self::$instance = new self($conf, $cObj);
		}
		return self::$instance;
	}

	/**
	 * Initiates flexform values
	 *
	 * @return void
	 */
	public function initflexForm() {
			// Converting flexform data into array:
		if (!is_array($this->cObj->data['pi_flexform']) && $this->cObj->data['pi_flexform']) {
			$this->cObj->data['pi_flexform'] = t3lib_div::xml2array($this->cObj->data['pi_flexform']);
			if (!is_array($this->cObj->data['pi_flexform'])) $this->cObj->data['pi_flexform']=array();
		}
	}

	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	protected function getFFvalue($fieldName,$sheet='sDEF',$lang='lDEF',$value='vDEF') {
		$T3FlexForm_array = $this->cObj->data['pi_flexform'];
		$sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
		if (is_array($sheetArray))	{
			return $this->getFFvalueFromSheetArray($sheetArray,explode('/',$fieldName),$value);
		}
	}

	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @see getFFvalue()
	 */
	protected function getFFvalueFromSheetArray($sheetArray,$fieldNameArr,$value)	{
		$tempArr=$sheetArray;
		foreach($fieldNameArr as $k => $v)	{
			if (t3lib_div::testInt($v))	{
				if (is_array($tempArr))	{
					$c=0;
					foreach($tempArr as $values)	{
						if ($c==$v)	{
							#debug($values);
							$tempArr=$values;
							break;
						}
						$c++;
					}
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		return $tempArr[$value];
	}

	/**
	 * Returns prioritized configuration value (felxform overwrites typoscript conf)

	 * @param	string		$param:	Parameter name
	 * @return	mixed		configuration value
	 */
	function getConf($param) {
		if (strchr($param, '.')) {
			list($section, $param) = explode('.', $param, 2);
		}
		$value = trim($this->getFFvalue($param, ($section ? 's' . ucfirst($section) : 'sDEF')));

		if ($section) {
			if (!is_null($value) && $value != '') {
				$this->prioConf[$section . '.'][$param] = $value;
			} else {
				$this->prioConf[$section . '.'][$param] = $this->conf[$section . '.'][$param];
			}
			return $this->prioConf[$section . '.'][$param];

		} else {
			if (!is_null($value) && $value != '') {
				$this->prioConf[$param] = $value;
			} else {
				$this->prioConf[$param] = $this->conf[$param];
			}
			return $this->prioConf[$param];

		}
	}

	/**
	 * Initiates piVars
	 *
	 * @return void
	 */
	public function initPiVars() {
		if ($this->prefixId)	{
			$this->piVars = t3lib_div::GParrayMerged($this->prefixId);
			$this->piVars = $this->piVars + (array)t3lib_div::_GP('tx_simpleforum');

			if ($this->pi_checkCHash && count($this->piVars))	{
				$GLOBALS['TSFE']->reqCHash();
			}
		}
		if (is_array($this->conf['_DEFAULT_PI_VARS.']))	{
			$this->piVars = t3lib_div::array_merge_recursive_overrule($this->conf['_DEFAULT_PI_VARS.'],(array)$this->piVars);
		}
	}

	/**
	 * Loads ll values
	 * @return void
	 */
	public function initLL() {
		if ($GLOBALS['TSFE']->config['config']['language'])	{
			$this->LLkey = $GLOBALS['TSFE']->config['config']['language'];
			if ($GLOBALS['TSFE']->config['config']['language_alt'])	{
				$this->altLLkey = $GLOBALS['TSFE']->config['config']['language_alt'];
			}
		}
		$basePath = t3lib_extMgm::extPath($this->extKey, 'locallang.xml');
		$LLcontent = t3lib_div::readLLfile($basePath,$this->LLkey);
		$this->LOCAL_LANG = array_merge((array)$this->LOCAL_LANG,$LLcontent);
	}

	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 *
	 * @param	string		The key from the LOCAL_LANG array for which to return the value.
	 * @param	string		Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
	 * @param	boolean		If true, the output label is passed through htmlspecialchars()
	 * @return	string		The value from LOCAL_LANG.
	 */
	public function getLL($key,$alt='',$hsc=FALSE)	{
		// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
		if (isset($this->LOCAL_LANG[$this->LLkey][$key]))	{
			$word = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->LLkey][$key], $this->LOCAL_LANG_charset[$this->LLkey][$key]);
		} elseif ($this->altLLkey && isset($this->LOCAL_LANG[$this->altLLkey][$key]))	{
			$word = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->altLLkey][$key], $this->LOCAL_LANG_charset[$this->altLLkey][$key]);
		} elseif (isset($this->LOCAL_LANG['default'][$key]))	{
			$word = $this->LOCAL_LANG['default'][$key];	// No charset conversion because default is english and thereby ASCII
		} else {
			$word = $this->LLtestPrefixAlt.$alt;
		}

		$output = $this->LLtestPrefix.$word;
		if ($hsc)	$output = htmlspecialchars($output);

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_pObj.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simpleforum/classes/class.tx_simpleforum_pObj.php']);
}
?>