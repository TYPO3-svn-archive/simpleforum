<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_simpleforum_forums'] = array (
	'ctrl' => $TCA['tx_simpleforum_forums']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,topic,description,postnumber,lastposttime,lastpostuser'
	),
	'feInterface' => $TCA['tx_simpleforum_forums']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'topic' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_forums.topic',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_forums.description',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'usergroup' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_forums.usergroup',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_groups',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 10,
			)
		),
		'threadnumber' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpost' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpostuser' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpostusername' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, topic, description, threadnumber, lastpost, lastpostuser, lastpostusername, usergroup')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime')
	)
);



$TCA['tx_simpleforum_threads'] = array (
	'ctrl' => $TCA['tx_simpleforum_threads']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fid,topic,replysnumber,replyslast,replyslastname,replyslastuid,authorname,author,locked,usergroup'
	),
	'feInterface' => $TCA['tx_simpleforum_threads']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads.fid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_simpleforum_forums',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'topic' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads.topic',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'postnumber' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpost' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpostusername' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'lastpostuser' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'authorname' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'author' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads.author',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'locked' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads.locked',
			'config' => Array (
				'type' => 'check',
			)
		),
		'usergroup' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_threads.usergroup',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_groups',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 10,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, fid, topic, replysnumber, replyslast, replyslastname, replyslastuid, authorname, author, locked, usergroup')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, endtime')
	)
);



$TCA['tx_simpleforum_posts'] = array (
	'ctrl' => $TCA['tx_simpleforum_posts']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,tid,author,message,approved'
	),
	'feInterface' => $TCA['tx_simpleforum_posts']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'tid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts.tid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_simpleforum_threads',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'author' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts.author',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'message' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts.message',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'approved' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts.approved',
			'config' => Array (
				'type' => 'check',
				'default' => 1,
			)
		),
		'remote_addr' => array(
			'label' => 'LLL:EXT:simpleforum/locallang_db.xml:tx_simpleforum_posts.remote_addr',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim,required,is_in',
				'is_in' => '0123456789.',
			),
		),
		'doublepostcheck' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, tid, author, message, remote_addr, approved')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>