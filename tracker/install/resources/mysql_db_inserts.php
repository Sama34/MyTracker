<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mysql_db_inserts.php 3 2009-08-03 15:11:27Z Tomm $
|	> Installer originally written by MyBB Group © 2009 (mybboard.net)
+--------------------------------------------------------------------------
*/

$time = time();

// Default Category "None" and "Another Category"
$inserts[] = "INSERT INTO mybb_tracker_categories (catid, catname, disporder, forgroups) VALUES ('1', 'None', '1','2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_categories (catid, catname, disporder, forgroups) VALUES ('2', 'Another Category', '1','2,3,4,5,6');";

// Default Priorities
$inserts[] = "INSERT INTO mybb_tracker_priorities (priorid, priorityname, disporder, priorstyle, forgroups) VALUES ('1', 'None', '1', 'background:#E1FFE1;', '2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_priorities (priorid, priorityname, disporder, priorstyle, forgroups) VALUES ('2', 'Low', '2', 'background:#F5FAC3;', '2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_priorities (priorid, priorityname, disporder, priorstyle, forgroups) VALUES ('3', 'Medium', '3', 'background:#F8DD9E;', '2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_priorities (priorid, priorityname, disporder, priorstyle, forgroups) VALUES ('4', 'High', '4', 'background:#F0A05A;', '2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_priorities (priorid, priorityname, disporder, priorstyle, forgroups) VALUES ('5', 'Critical', '5', 'background:#DC8C8C;', '3,4,6');";

// Default Stages
$inserts[] = "INSERT INTO mybb_tracker_stages (stageid, stagename, disporder) VALUES ('1', 'Planning', '1');";
$inserts[] = "INSERT INTO mybb_tracker_stages (stageid, stagename, disporder) VALUES ('2', 'Pre-Alpha', '2');";
$inserts[] = "INSERT INTO mybb_tracker_stages (stageid, stagename, disporder) VALUES ('3', 'Alpha', '3');";
$inserts[] = "INSERT INTO mybb_tracker_stages (stageid, stagename, disporder) VALUES ('4', 'Beta', '4');";
$inserts[] = "INSERT INTO mybb_tracker_stages (stageid, stagename, disporder) VALUES ('5', 'Stable', '5');";

// Default Statuses
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('1', 'New', '1', '2,3,4,5,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('2', 'Browser Quirk', '2', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('3', 'Can''t Reproduce', '3', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('4', 'Confirmed', '4', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('5', 'Duplicate', '5', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('6', 'Not a Bug', '6', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('7', 'Waiting for Feedback', '7', '3,4,6');";
$inserts[] = "INSERT INTO mybb_tracker_status (statid, statusname, disporder, forgroups) VALUES ('8', 'Added to Future Version', '8', '3,4,6');";

// Add Cache tables
$inserts[] = "INSERT INTO mybb_datacache (title, cache) VALUES ('trackerversion', 'a:0:{}');";
$inserts[] = "INSERT INTO mybb_datacache (title, cache) VALUES ('trackernews', 'a:0:{}');";

// A default Project
// We're not adding default Issues (yet) as you can't delete them!
$inserts[] = "INSERT INTO mybb_tracker_projects (proid, name, description, parent, stage, disporder, active, created, allowfeats, num_issues, num_features, lastpost, lastposter, lastposteruid, lastpostissid, lastpostsubject) VALUES ('1', 'Example Project', 'An example Project.', '0', '1', '1', '1', '{$time}', '1', '0', '0', '', '', '', '', '');";

// Add in the template groups
$inserts[] = "INSERT INTO mybb_templategroups (gid, prefix, title) VALUES (NULL, 'mytracker', '<lang:group_mytracker>');";
$inserts[] = "INSERT INTO mybb_templategroups (gid, prefix, title) VALUES (NULL, 'mytrackercp', '<lang:group_mytrackercp>');";

// Alter templates, users and usergroups table
$inserts[] = "ALTER TABLE mybb_templates ADD myversion VARCHAR(20) NOT NULL DEFAULT '0'";
$inserts[] = "ALTER TABLE mybb_usergroups ADD canmodtrack INT(1) NOT NULL DEFAULT '0'";
$inserts[] = "ALTER TABLE mybb_users ADD developer INT(1) NOT NULL DEFAULT '0'";

// Update the Administrators group to be moderators
$inserts[] = "UPDATE mybb_usergroups SET canmodtrack = '1' WHERE cancp = '1'";
?>