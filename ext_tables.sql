#
# Table structure for table 'tx_simpleforum_forums'
#
CREATE TABLE tx_simpleforum_forums (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	topic tinytext NOT NULL,
	description tinytext NOT NULL,
	postnumber int(11) DEFAULT '0' NOT NULL,
	lastposttime tinytext NOT NULL,
	lastpostuser int(11) DEFAULT '0' NOT NULL,
	lastpostusername tinytext NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_simpleforum_threads'
#
CREATE TABLE tx_simpleforum_threads (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fid int(11) DEFAULT '0' NOT NULL,
	topic tinytext NOT NULL,
	replysnumber tinytext NOT NULL,
	replyslast tinytext NOT NULL,
	replyslastname tinytext NOT NULL,
	replyslastuid tinytext NOT NULL,
	authorname tinytext NOT NULL,
	author int(11) DEFAULT '0' NOT NULL,
	locked tinyint(3) DEFAULT '0' NOT NULL,
	usergroup tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_simpleforum_posts'
#
CREATE TABLE tx_simpleforum_posts (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	tid int(11) DEFAULT '0' NOT NULL,
	author int(11) DEFAULT '0' NOT NULL,
	message text NOT NULL,
	approved tinyint(3) DEFAULT '0' NOT NULL,
	remote_addr varchar(255) DEFAULT '' NOT NULL,
	doublepostcheck varchar(32) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);