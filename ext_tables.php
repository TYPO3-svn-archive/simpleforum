<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA['tx_simpleforum_forums'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_forums',
		'label'     => 'topic',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_tx_simpleforum_forums.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, starttime, endtime, topic, description, usergroup',
	)
);

$TCA['tx_simpleforum_threads'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads',
		'label'     => 'topic',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'res/icons/icon_tx_simpleforum_threads.gif',
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, starttime, endtime, fid, topic, author, locked, usergroup',
	)
);

$TCA['tx_simpleforum_posts'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts',
		'label'     => 'message',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_simpleforum_posts.gif',
		'typeicon_column' => 'approved',
		'typeicons' => array(
			'0' => t3lib_extMgm::extRelPath($_EXTKEY) . 'res/icons/icon_tx_simpleforum_posts_not_approved.png',
			'1' => t3lib_extMgm::extRelPath($_EXTKEY) . 'res/icons/icon_tx_simpleforum_posts.gif',
		),
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, tid, author, message, approved',
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_forum']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_widget']='layout,select_key';

t3lib_extMgm::addPlugin(array('LLL:EXT:simpleforum/locallang_db.xml:tt_content.list_type_forum', $_EXTKEY.'_forum'),'list_type');
t3lib_extMgm::addPlugin(array('LLL:EXT:simpleforum/locallang_db.xml:tt_content.list_type_widget', $_EXTKEY.'_widget'),'list_type');

t3lib_extMgm::addStaticFile('simpleforum', 'static/', 'simpleforum');

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_forum']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_forum', 'FILE:EXT:simpleforum/flexform_ds_forum.xml');
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_widget']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_widget', 'FILE:EXT:simpleforum/flexform_ds_widget.xml');
?>