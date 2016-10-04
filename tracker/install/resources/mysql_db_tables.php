<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009  Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mysql_db_tables.php 3 2009-08-03 15:11:27Z Tomm $
|	> Installer originally written by MyBB Group  2009 (mybboard.net)
+--------------------------------------------------------------------------
*/
$tables[] = "CREATE TABLE mybb_tracker_activity (
  actid int(10) NOT NULL AUTO_INCREMENT,
  action smallint(5) unsigned NOT NULL,
  issid int(10) NOT NULL,
  feature tinyint(1) unsigned NOT NULL DEFAULT '0',
  content text NOT NULL,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(80) NOT NULL,
  dateline bigint(30) unsigned NOT NULL DEFAULT '0',
  visible int(1) unsigned NOT NULL,
  PRIMARY KEY (actid),
  KEY uid (uid),
  KEY action (action),
  KEY issid (issid)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_categories (
  catid int(5) unsigned NOT NULL AUTO_INCREMENT,
  catname varchar(80) NOT NULL,
  disporder int(3) unsigned NOT NULL DEFAULT '0',
  forgroups text NOT NULL,
  PRIMARY KEY (catid),
  KEY disporder (disporder)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_features (
  featid int(10) unsigned NOT NULL AUTO_INCREMENT,
  projid smallint(5) unsigned NOT NULL DEFAULT '0',
  subject varchar(120) NOT NULL DEFAULT '',
  icon smallint(5) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(80) NOT NULL DEFAULT '',
  dateline bigint(30) NOT NULL DEFAULT '0',
  firstpost int(10) NOT NULL DEFAULT '0',
  lastpost int(10) NOT NULL DEFAULT '0',
  lastposter varchar(120) NOT NULL DEFAULT '',
  lastposteruid int(10) unsigned NOT NULL DEFAULT '0',
  views int(100) unsigned NOT NULL DEFAULT '0',
  replies int(100) unsigned NOT NULL DEFAULT '0',
  closed varchar(30) NOT NULL DEFAULT '',
  visible int(1) NOT NULL DEFAULT '1',
  allowcomments int(1) NOT NULL DEFAULT '1',
  status smallint(5) unsigned NOT NULL DEFAULT '1',
  votesfor int(11) NOT NULL DEFAULT '0',
  votesagainst int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (featid),
  KEY uid (uid),
  KEY projid (projid),
  KEY dateline (dateline),
  KEY lastpost (lastpost),
  KEY firstpost (firstpost),
  KEY subject (`subject`)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_featuresposts (
  featpid int(10) unsigned NOT NULL AUTO_INCREMENT,
  featid int(10) unsigned NOT NULL DEFAULT '0',
  projid smallint(5) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(120) NOT NULL,
  dateline bigint(30) NOT NULL DEFAULT '0',
  message text NOT NULL,
  ipaddress varchar(30) NOT NULL DEFAULT '',
  edituid int(10) unsigned NOT NULL DEFAULT '0',
  edituser varchar(120) NOT NULL,
  edittime int(10) NOT NULL DEFAULT '0',
  visible int(1) NOT NULL DEFAULT '0',
  posthash varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (featpid),
  KEY issid (featid,uid),
  KEY uid (uid),
  KEY visible (visible),
  KEY dateline (dateline),
  FULLTEXT KEY message (message)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_featuresread (
  featid int(10) unsigned NOT NULL,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  KEY uid (uid),
  KEY featid (featid),
  KEY dateline (dateline)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_featuresvotes (
  fvid int(10) unsigned NOT NULL AUTO_INCREMENT,
  featid int(10) unsigned NOT NULL,
  uid int(10) unsigned NOT NULL,
  `for` tinyint(1) NOT NULL DEFAULT '0',
  `against` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (fvid),
  KEY featid (featid),
  KEY uid (uid),
  KEY `for` (`for`),
  KEY `against` (`against`)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_issues (
  issid int(10) unsigned NOT NULL AUTO_INCREMENT,
  projid smallint(5) unsigned NOT NULL DEFAULT '0',
  subject varchar(120) NOT NULL DEFAULT '',
  icon smallint(5) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(80) NOT NULL DEFAULT '',
  dateline bigint(30) NOT NULL DEFAULT '0',
  firstpost int(10) NOT NULL DEFAULT '0',
  lastpost int(10) NOT NULL DEFAULT '0',
  lastposter varchar(120) NOT NULL DEFAULT '',
  lastposteruid int(10) unsigned NOT NULL DEFAULT '0',
  views int(100) NOT NULL DEFAULT '0',
  replies int(100) NOT NULL DEFAULT '0',
  closed varchar(30) NOT NULL DEFAULT '',
  visible int(1) NOT NULL DEFAULT '1',
  allowcomments int(1) NOT NULL DEFAULT '1',
  status smallint(5) unsigned NOT NULL DEFAULT '1',
  priority smallint(5) unsigned NOT NULL DEFAULT '0',
  assignee int(10) unsigned NOT NULL DEFAULT '0',
  assignname varchar(80) NOT NULL,
  category smallint(5) unsigned NOT NULL DEFAULT '0',
  complete smallint(3) unsigned NOT NULL DEFAULT '0',
  version varchar(35) NOT NULL,
  PRIMARY KEY (issid),
  KEY projid (projid),
  KEY subject (subject),
  KEY uid (uid),
  KEY firstpost (firstpost),
  KEY lastpost (lastpost)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_issuesposts (
  isspid int(10) unsigned NOT NULL AUTO_INCREMENT,
  issid int(10) unsigned NOT NULL DEFAULT '0',
  projid smallint(5) unsigned NOT NULL DEFAULT '0',
  uid int(10) unsigned NOT NULL DEFAULT '0',
  username varchar(80) NOT NULL DEFAULT '',
  dateline bigint(30) NOT NULL DEFAULT '0',
  message text NOT NULL,
  ipaddress varchar(30) NOT NULL DEFAULT '',
  edituid int(10) unsigned NOT NULL DEFAULT '0',
  edituser varchar(80) NOT NULL DEFAULT '',
  edittime int(10) NOT NULL DEFAULT '0',
  visible int(1) NOT NULL DEFAULT '0',
  posthash varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (isspid),
  KEY issid (issid,uid),
  KEY uid (uid),
  KEY visible (visible),
  KEY dateline (dateline),
  FULLTEXT KEY message (message)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_issuesread (
  issid int(10) unsigned NOT NULL,
  uid int(10) unsigned NOT NULL DEFAULT '0',
  dateline int(10) unsigned NOT NULL DEFAULT '0',
  KEY uid (uid),
  KEY issid (issid),
  KEY dateline (dateline)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_priorities (
  priorid int(10) unsigned NOT NULL AUTO_INCREMENT,
  priorityname varchar(120) NOT NULL DEFAULT '',
  disporder smallint(5) NOT NULL,
  priorstyle text NOT NULL,
  forgroups text NOT NULL,
  PRIMARY KEY (priorid),
  KEY disporder (disporder)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_projects (
  proid int(5) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(120) NOT NULL DEFAULT '',
  description text NOT NULL,
  parent smallint(5) unsigned NOT NULL DEFAULT '0',
  stage smallint(5) unsigned NOT NULL DEFAULT '0',
  disporder smallint(5) unsigned NOT NULL DEFAULT '0',
  active tinyint(1) unsigned NOT NULL DEFAULT '1',
  created int(10) NOT NULL DEFAULT '0',
  allowfeats int(1) unsigned NOT NULL DEFAULT '1',
  num_issues int(10) unsigned NOT NULL DEFAULT '0',
  num_features int(10) unsigned NOT NULL DEFAULT '0',
  lastpost int(10) unsigned NOT NULL DEFAULT '0',
  lastposter varchar(120) NOT NULL DEFAULT '',
  lastposteruid int(10) unsigned NOT NULL DEFAULT '0',
  lastpostissid int(10) unsigned NOT NULL DEFAULT '0',
  lastpostsubject varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (proid),
  KEY disporder (disporder),
  KEY active (active),
  KEY lastpost (lastpost)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_projectsread (
  proid int(10) unsigned NOT NULL,
  uid int(10) unsigned NOT NULL,
  dateline int(10) NOT NULL,
  KEY uid (uid),
  KEY proid (proid),
  KEY dateline (dateline)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_stages (
  stageid int(10) unsigned NOT NULL AUTO_INCREMENT,
  stagename varchar(120) NOT NULL DEFAULT '',
  disporder smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (stageid),
  KEY disporder (disporder)
) ENGINE=MyISAM;";

$tables[] = "CREATE TABLE mybb_tracker_status (
  statid int(10) unsigned NOT NULL AUTO_INCREMENT,
  statusname varchar(120) NOT NULL DEFAULT '',
  disporder smallint(5) unsigned NOT NULL DEFAULT '0',
  forgroups text NOT NULL,
  PRIMARY KEY (statid),
  KEY disporder (disporder)
) ENGINE=MyISAM;";
?>