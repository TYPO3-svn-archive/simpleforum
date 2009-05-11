<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_simpleforum_forums=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_simpleforum_threads=1
');
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_simpleforum_posts=1
');

function addTxSimpleforumPlugin($prefix = '') {
	global $TYPO3_LOADED_EXT;

	$pluginContent = trim('
plugin.tx_simpleforum'.$prefix.' = USER_INT
plugin.tx_simpleforum'.$prefix.' {
	includeLibs = '.$TYPO3_LOADED_EXT['simpleforum']['siteRelPath'].'classes/class.tx_simpleforum_dispatcher.php
	userFunc = tx_simpleforum_dispatcher->dispatch
}');

	t3lib_extMgm::addTypoScript('simpleforum', 'setup', '
# Setting simpleforum plugin TypoScript
'.$pluginContent);

	t3lib_extMgm::addTypoScript('simpleforum', 'setup', '
# Setting simpleforum plugin TypoScript
tt_content.list.20.simpleforum'.$prefix.' = < plugin.tx_simpleforum'.$prefix.'
', 43);
}

addTxSimpleforumPlugin('_forum');
addTxSimpleforumPlugin('_widget');

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearAllCache_additionalTables']['cache_simpleforum'] = 'cache_simpleforum';
?>