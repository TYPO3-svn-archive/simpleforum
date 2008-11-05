<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');
$TCA["tx_simpleforum_forums"] = array (
	"ctrl" => array (
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
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, starttime, endtime, topic, description, postnumber, lastposttime, lastpostuser, lastpostusername",
	)
);

$TCA["tx_simpleforum_threads"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads',
		'label'     => 'topic',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_simpleforum_threads.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, starttime, endtime, fid, topic, replysnumber, replyslast, replyslastname, replyslastuid, authorname, author, locked, usergroup",
	)
);

$TCA["tx_simpleforum_posts"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts',
		'label'     => 'tid',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => "ORDER BY crdate",
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_simpleforum_posts.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "hidden, tid, author, message, approved",
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(array('LLL:EXT:simpleforum/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');


t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","Forum");
?>