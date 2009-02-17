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
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_simpleforum_forums.gif',
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
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_simpleforum_threads.gif',
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
			'0' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_simpleforum_posts_not_approved.png',
			'1' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_simpleforum_posts.gif',
		),
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'hidden, tid, author, message, approved',
	)
);

$TCA['cache_txsimpleforum'] = array (
	'hideTable'	=> 1,
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';

t3lib_extMgm::addPlugin(array('LLL:EXT:simpleforum/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/','Forum');

$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:simpleforum/flexform_ds_pi1.xml');

?>