<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: functions_tracker.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/

//---------------------------------------------------
// Have we installed? Or active?
//---------------------------------------------------
$active_check = $cache->read("plugins");
if(!$active_check['active']['mytracker'])
{
	if($db->table_exists("tracker_issues") === true)
	{
		die("You need to activate the MyTracker plugin in the Admin CP before this can be used.");
	}
	else
	{
		die("MyTracker is not installed. Please check the install documents at <a href=\"http://resources.xekko.co.uk\">http://resources.xekko.co.uk</a>.");
	}
}

//---------------------------------------------------
// Are we using friendly URLs?
//---------------------------------------------------
if($mybb->settings['trackerseo'] == "yes" && ($mybb->settings['seourls'] == "yes" || ($mybb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1)))
{
	define('PROJECT_LIST_URL', "projects.html");
	define('PROJECT_URL', "project-{project}.html");
	define('PROJECT_VERSION', "project-{project}-versions.html");
	define('PROJECT_BUGS_URL', "project-{project}-issues.html");
	define('PROJECT_BUGS_PAGED', "project-{project}-issues-{page}.html");
	define('PROJECT_FEATURES_URL', "project-{project}-features.html");
	define('PROJECT_FEATURES_PAGED', "project-{project}-features-{page}.html");
	define('ISSUE_ALL_URL', "issues.html");
	define('ISSUE_LIST_URL', "issues-{page}.html");
	define('ISSUE_URL', "issue-{issue}.html");
	define('FEATURE_LIST_URL', "features.html");
	define('FEATURE_URL', "feature-{feature}.html");
	define('USER_URL', "../user-{uid}.html"); // Because we're in a directory!
	define('COMMENT_URL', "issue-{issue}-comments.html");
	define('FEATURE_COMMENT_URL', "feature-{feature}-comments.html");
	define('TIMELINE_URL', "issue-{issue}-timeline.html");
}
else
{
	define('PROJECT_LIST_URL', "projects.php");
	define('PROJECT_URL', "projects.php?project={project}");
	define('PROJECT_VERSION', "projects.php?project={project}&amp;view=versions");
	define('PROJECT_BUGS_URL', "projects.php?project={project}&view=issues");
	define('PROJECT_BUGS_PAGED', "projects.php?project={project}&view=issues&page={page}");
	define('PROJECT_FEATURES_URL', "projects.php?project={project}&view=features");
	define('PROJECT_FEATURES_PAGED', "projects.php?project={project}&view=features&page={page}");
	define('ISSUE_ALL_URL', "issues.php?issue=all");
	define('ISSUE_LIST_URL', "issues.php?issue=all&page={page}");
	define('ISSUE_URL', "issues.php?issue={issue}");
	define('FEATURE_LIST_URL', "features.php");
	define('FEATURE_URL', "features.php?feature={feature}");
	define('USER_URL', "../member.php?action=profile&uid={uid}");
	define('COMMENT_URL', "comments.php?issue={issue}");
	define('FEATURE_COMMENT_URL', "comments.php?feature={feature}");
	define('TIMELINE_URL', "timeline.php?issue={issue}");
}

//---------------------------------------------------
// Setup the tabs
//---------------------------------------------------
$sub_tabs['dashboard'] = array(
	'title' => $lang->dashboard,
	'link' => "./", // Reverts to /folder_name/
	'description' => $lang->dashboard_info
);
$sub_tabs['issues'] = array(
	'title' => $lang->issues,
	'link' => ISSUE_ALL_URL,
	'description' => $lang->issuelist_info
);
if($mybb->user['uid'])
{
	$sub_tabs['newbug'] = array(
		'title' => $lang->tracker_new_report,
		'link' => "new.php?action=newissue",
		'description' => $lang->newbug_info
	);
}

// Control Panel and a new idea - right
if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
{
	$sub_tabs['cpanel'] = array(
		'title' => "Control Panel",
		'link' => "./admin/",
		'align' => "right",
		'description' => $lang->cp_info
	);
}
if($mybb->settings['ideasys'] == 1 && (THIS_SCRIPT == "tracker/features.php" || THIS_SCRIPT == "tracker/projects.php" || THIS_SCRIPT == "tracker/new.php" && $mybb->input['action'] == "newidea"))
{
	$sub_tabs['ideas'] = array(
		'title' => $lang->tracker_ideas,
		'link' => "new.php?action=newidea",
		'description' => $lang->ideas_info,
		'align' => "right"
	);
}

//---------------------------------------------------
// Get the User Profile URL
// @param int The user ID
// @return string The URL to member.php
//---------------------------------------------------
function get_user_url($uid)
{
	$link = str_replace("{uid}", $uid, USER_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Issue URL
// @param int The issue ID
// @return string The URL to an issue
//---------------------------------------------------
function get_issue_url($issue)
{
	$link = str_replace("{issue}", $issue, ISSUE_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Feature URL
// @param int The feature ID
// @return string The URL to a  feature
//---------------------------------------------------
function get_feature_url($feature)
{
	$link = str_replace("{feature}", $feature, FEATURE_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Comments URL
// @param int The Issue ID
// @return string The URL to a the issue's comments
//---------------------------------------------------
function get_comments_url($issue)
{
	$link = str_replace("{issue}", $issue, COMMENT_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Feature Comments URL
// @param int The Feature ID
// @return string The URL to a the feature's comments
//---------------------------------------------------
function get_features_comments_url($feature)
{
	$link = str_replace("{feature}", $feature, FEATURE_COMMENT_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Timeline URL
// @param int The Issue ID
// @return string The URL to a the issue's timeline
//---------------------------------------------------
function get_timeline_url($issue)
{
	$link = str_replace("{issue}", $issue, TIMELINE_URL);
	return htmlspecialchars_uni($link);
}

//---------------------------------------------------
// Get the Project URL
// @param int The project ID
// @param string (Optional) Switch view to show bugs or features
// @param int (Optional) The page number of bugs/features
// @param int (Optional) A project version number (for Roadmap)
// @return string The URL to a project
//---------------------------------------------------
function get_project_link($project_id, $view='', $page=0, $version_id='')
{
	if($view == "issues")
	{
		if($page > 0)
		{
			$link = str_replace("{project}", $project_id, PROJECT_BUGS_PAGED);
			$link = str_replace("{page}", $page, $link);
		}
		else
		{
			$link = str_replace("{project}", $project_id, PROJECT_BUGS_URL);
		}		
		return htmlspecialchars_uni($link);
	}
	else if($view == "features")
	{
		if($page > 0)
		{
			$link = str_replace("{project}", $project_id, PROJECT_FEATURES_PAGED);
			$link = str_replace("{page}", $page, $link);
		}
		else
		{
			$link = str_replace("{project}", $project_id, PROJECT_FEATURES_URL);
		}
		return htmlspecialchars_uni($link);
	}
	else if($view == "versions")
	{
		$link = str_replace("{project}", $project_id, PROJECT_VERSION);
		$link = str_replace("{version}", $version_id, $link);
		return htmlspecialchars_uni($link);
	}
	else
	{
		$link = str_replace("{project}", $project_id, PROJECT_URL);
		return htmlspecialchars_uni($link);
	}	
}

function get_project_lightbulb($projects, $lastpost)
{
	global $mybb, $lang, $db, $project;

	// Fetch the last read date for this project	
	$query = $db->simple_select("tracker_projectsread", "dateline", "proid = '".intval($projects)."' AND uid = '".$mybb->user['uid']."'", array("limit" => 1));
	$project_read = $db->fetch_field($query, "dateline");

	if(!$project_read)
	{
		$project_read = $mybb->user['lastvisit'];
	}
 
	if($project['lastpost'] > $project_read && $project['lastpost'] != 0) 
	{
		$folder = "on";
		$altonoff = $lang->new_posts;
	}
	else
	{
		$folder = "off";
		$altonoff = $lang->no_new_posts;
	}

	return array(
		"folder" => $folder,
		"altonoff" => $altonoff
	);
}

function unviewable_projects($table_name)
{
	global $db, $mybb;
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		return false;
	}
	else
	{
		$query = $db->simple_select("tracker_projects", "proid", "active = '0'");	
		while($project = $db->fetch_array($query))
		{
			$projects[] = $project['proid'];
		}
		
		if(count($projects) > 0)
		{
			// Return a nice list of unviewable projects to limit the SQL to
			$denied_list = implode(",", $projects);
			$denied_sql = "AND ".$table_name." NOT IN (".$denied_list.")";
			return $denied_sql;
		}
		else
		{
			return '';
		}
	}
}

function unviewable_issues()
{
	global $db, $mybb;
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		return false;
	}
	else
	{
		$query = $db->simple_select("tracker_issues", "issid", "visible = '0'");	
		while($issue = $db->fetch_array($query))
		{
			$issues[] = $issue['issid'];
		}
		
		if(count($issues) > 0)
		{
			// Return a nice list of unviewable issues to limit the SQL to
			$denied_list = implode(",", $issues);
			$denied_sql = "AND issid NOT IN (".$denied_list.")";
			return $denied_sql;
		}
		else
		{
			return '';
		}
	}
}

function unviewable_features()
{
	global $db, $mybb;
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		return false;
	}
	else
	{
		$query = $db->simple_select("tracker_features", "featid", "visible = '0'");	
		while($feature = $db->fetch_array($query))
		{
			$features[] = $feature['featid'];
		}
		
		if(count($features) > 0)
		{
			// Return a nice list of unviewable features to limit the SQL to
			$denied_list = implode(",", $features);
			$denied_sql = "AND featid NOT IN (".$denied_list.")";
			return $denied_sql;
		}
		else
		{
			return '';
		}
	}
}

function get_hidden_issues($project)
{
	global $db;
	$query = $db->simple_select("tracker_issues", "issid", "visible = '0' AND projid = '".intval($project)."'");
	$hidden_issues = $db->num_rows($query);
	if(!$hidden_issues)
	{
		$hidden_issues = 0;
	}
	
	return $hidden_issues;
}

function get_hidden_features($project)
{
	global $db;
	$query = $db->simple_select("tracker_features", "featid", "visible = '0' AND projid = '".intval($project)."'");
	$hidden_features = $db->num_rows($query);
	if(!$hidden_features)
	{
		$hidden_features = 0;
	}
	
	return $hidden_features;
}

function update_project_lastpost($project)
{
	global $db;
	$query = $db->simple_select("tracker_issues", "issid, subject, lastpost, lastposter, lastposteruid", "projid = '".intval($project['proid'])."' AND visible = '1'", array("order_by" => "lastpost", "order_dir" => "DESC", "limit" => '1'));
	$lastpost_array = $db->fetch_array($query);
	
	$new_num_issues = $project['num_issues'] - 1;
	
	// Generate an array, and update the project
	$update_array = array(
		"num_issues" => $new_num_issues,
		"lastpost" => intval($lastpost_array['lastpost']),
		"lastposter" => $db->escape_string($lastpost_array['lastposter']),
		"lastposteruid" => intval($lastpost_array['lastpostuid']),
		"lastpostissid" => $lastpost_array['issid'],
		"lastpostsubject" => $db->escape_string($lastpost_array['subject'])
	);
	$db->update_query("tracker_projects", $update_array, "proid = '".intval($project['proid'])."'");
}

function update_project_lastpost_f($project)
{
	global $db;
	$query = $db->simple_select("tracker_features", "featid, subject, lastpost, lastposter, lastposteruid", "projid = '".intval($project['proid'])."' AND visible = '1'", array("order_by" => "lastpost", "order_dir" => "DESC", "limit" => '1'));
	$lastpost_array = $db->fetch_array($query);
	
	$new_num_features = $project['num_features'] - 1;
	
	// Generate an array, and update the project
	$update_array = array(
		"num_features" => $new_num_features,
		"lastpost" => intval($lastpost_array['lastpost']),
		"lastposter" => $db->escape_string($lastpost_array['lastposter']),
		"lastposteruid" => intval($lastpost_array['lastpostuid']),
		"lastpostissid" => $lastpost_array['issid'],
		"lastpostsubject" => $db->escape_string($lastpost_array['subject'])
	);
	$db->update_query("tracker_projects", $update_array, "proid = '".intval($project['proid'])."'");
}

function get_tracker_projects()
{
	global $db, $mybb, $project;

	// Build the query depending on permissions
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$query = $db->simple_select("tracker_projects", "proid, name", ""); // Main projects only
	}
	else
	{
		$query = $db->simple_select("tracker_projects", "proid, name", "parent = '0' AND active = '1'"); // Hide hidden projects from the list
	}
	
	$projects = "<select name=\"project\" id=\"project\">";
	while($project = $db->fetch_array($query))
	{
		if($mybb->input['project'] == $project['proid'])
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$projects .= "<option value=\"".$project['proid']."\"".$selected.">".$project['name']."</option>";
	}
	$projects .= "</select>";
	
	return $projects;
}

function get_tracker_categories($group)
{
	global $db, $issue;
	
	$query = $db->simple_select("tracker_categories", "catid, catname", "forgroups LIKE '%".$db->escape_string($group)."%'", array("order_by" => 'disporder', "order_dir" => 'ASC'));
	$categories = "<select name=\"category\" id=\"category\">";
	while($category = $db->fetch_array($query))
	{
		if($issue['category'] == $category['catid'])
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$categories .= "<option value=\"".$category['catid']."\"".$selected.">".$category['catname']."</option>";
	}
	$categories .= "</select>";
	
	return $categories;
}

function get_tracker_priorities($group, $search=false)
{
	global $db, $issue, $lang, $mybb;

	if($group != 0)
	{
		$where = "forgroups LIKE '%".$db->escape_string($group)."%'";
	}
	else
	{
		$where = '';
	}

	// Under pressure - dum dum do dum dum...
	$query = $db->simple_select("tracker_priorities", "priorid, priorityname, priorstyle", $where, array("order_by" => 'disporder', "order_dir" => 'ASC'));
	$priorities = "<select name=\"priority\" id=\"priority\">";
	$priorities .= "<optgroup label=\"".$lang->iss_priorities."\">";

	if($search == true)
	{
		$priorities .= "<option value=\"\">{$lang->misc_all_priors}</option>";
	}
	
	while($priority = $db->fetch_array($query))
	{
		if($issue['priority'] == $priority['priorid'])
		{
			$selected = " selected=\"selected\"";
		}
		elseif($search == true && intval($mybb->input['priority'] == $priority['priorid']))
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$priorities .= "<option value=\"".$priority['priorid']."\"".$selected." style=\"".$priority['priorstyle']."\">".$priority['priorityname']."</option>";
	}
	$priorities .= "</optgroup>";
	$priorities .= "</select>";
	
	return $priorities;
}

function get_tracker_statuses($group)
{
	global $db, $issue;
	
	$query = $db->simple_select("tracker_status", "statid, statusname", "forgroups LIKE '%".$db->escape_string($group)."%'", array("order_by" => 'disporder', "order_dir" => 'ASC'));
	$statuses = "<select name=\"status\" id=\"status\">";
	while($status = $db->fetch_array($query))
	{
		if($issue['status'] == $status['statid'])
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$statuses .= "<option value=\"".$status['statid']."\"".$selected.">".$status['statusname']."</option>";
	}
	$statuses .= "</select>";
	
	return $statuses;
}

function get_tracker_versions()
{
	global $db, $issue, $lang, $mybb;
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$where = '';
	}
	else
	{
		$where = " AND active = '1'";
	}
	$query = $db->simple_select("tracker_projects", "proid, name", "parent = '".$issue['projid']."' AND parent != '0'".$where."");
	if($db->num_rows($query))
	{
		$versions = "<select name=\"version\" id=\"version\">";
		// Provide a "none"
		$versions .= "<option value=\"0\">".$lang->iss_none."</option>";
		while($version = $db->fetch_array($query))
		{
			if($version['proid'] == $issue['version'])
			{
				$selected = " selected=\"selected\"";
			}
			else
			{
				$selected = '';
			}
			$versions .= "<option value=\"".$version['proid']."\"".$selected.">".$version['name']."</option>";
		}
		$versions .= "</select>";
		return $versions;
	}
	else
	{
		return false;
	}	
}

function get_tracker_assignees()
{
	global $db, $issue, $lang, $mybb;

	// Assignees are anybody who has been marked as a "developer"
	$query = $db->simple_select("users", "uid, username", "developer = '1'", array("order_by" => 'uid', "order_dir" => 'ASC'));
	if(!$db->num_rows($query))
	{
		$assignees = $lang->ed_no_devs;
		if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'])
		{
			$assignees .= " ".$lang->ed_add_dev;
		}
	}
	else
	{
	$assignees = "<select name=\"assignee\" id=\"assignee\">";
	$assignees.= "<option value=\"0\">None</option>";
		while($assignee = $db->fetch_array($query))
		{
			if($issue['assignee'] == $assignee['uid'])
			{
				$selected = " selected=\"selected\"";
			}
			else
			{
				$selected = '';
			}
			$assignees .= "<option value=\"".$assignee['uid']."\"".$selected.">".$assignee['username']."</option>";
		}
	$assignees .= "</select>";
	}
	
	return $assignees;
}

function get_tracker_complete()
{
	global $issue;
	
	$complete = "<select name=\"complete\" id=\"complete\">";
	for($i = 0; $i <= 10; $i++)
	{
		$num = $i * 10;
		if($issue['complete'] == $num)
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$complete .= "<option value=\"".$num."\"".$selected.">".$num."%</option>";
	}
	$complete .= "</select>";
	
	return $complete;
}

function get_tracker_stages()
{
	global $db, $project;
	$query = $db->simple_select("tracker_stages", "*");
	$stages = "<select name=\"stage\" id=\"stage\">";
	while($stage = $db->fetch_array($query))
	{
		if($stage['stageid'] == $project['stage'])
		{
			$selected = " selected=\"selected\"";
		}
		else
		{
			$selected = '';
		}
		$stages .= "<option value=\"".$stage['stageid']."\"".$selected.">".$stage['stagename']."</option>";
	}
	$stages .= "</select>";
	
	return $stages;
}

// Yes, a query in a loop... bad Tomm!
function get_closed_count($project)
{
	global $db, $mybb;
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$query = $db->simple_select("tracker_issues", "COUNT(issid) AS closedissues", "complete = '100' AND projid = '".$project."'");
	}
	else
	{
		$query = $db->simple_select("tracker_issues", "COUNT(issid) AS closedissues", "complete = '100' AND projid = '".$project."' AND visible = '1'");
	}
	return $db->fetch_field($query, "closedissues");
}

function facebox_error($message)
{
	global $lang;
	print("<span class=\"largetext\">{$lang->misc_error}</span><br /><div>".htmlspecialchars_uni($message)."</div>");
	exit;
}

function update_issue($data1, $data2, $update=true)
{
	global $db, $issue, $lang, $mybb;
	
	$query = $db->simple_select("tracker_issuesposts", "*", "issid = '".$issue['issid']."' AND isspid = '".$issue['firstpost']."'", array("limit" => 1));
	if(!$db->num_rows($query))
	{
		error($lang->ed_process_error);
	}
	else
	{
		$issue['message'] = $db->escape_string($db->fetch_field($query, "message"));
	}
	
	// Are we updating the subject? If yes, check the length isn't low
	if($data2['subject'] && my_strlen($data2['subject']) < 5)
	{
		error($lang->ed_not_long_subject);
	}

	// Figure out what's changed, and add it to the activity	
	$act_array = array(
		"action" => 2,
		"issid" => $issue['issid'],
		"feature" => 0,
		"uid" => $mybb->user['uid'],
		"username" => $db->escape_string($mybb->user['username']),
		"dateline" => TIME_NOW,
		"visible" => 1
	);

	if($data2['complete'] == "100" && $issue['complete'] != "100")
	{
		$act_array['action'] = 4; // We've resolved this issue!
	}

	// If "Show Update" is unchecked, then we make the activity invisible
	if($update == false)
	{
		$act_array['visible'] = 0;
	}

	// Add in the 'content'
	$content = '';
	if($data2['subject'] && $data2['subject'] != $issue['subject'])
	{
		$content .= "{$lang->ed_his_updated} {$lang->iss_subject}<br />";
	}
	if($data1['message'] != $issue['message'])
	{
		$content .= "{$lang->ed_his_updated} {$lang->iss_message}<br />";
		// Add in edit information if message has been changed
		$data1['edituid'] = $mybb->user['uid'];
		$data1['edittime'] = TIME_NOW;
	}
	if($data2['category'] != $issue['category'])
	{
		$query = $db->simple_select("tracker_categories", "catname", "catid = '".$issue['category']."'");
		$old_cat = $db->fetch_field($query, "catname");
		$query = $db->simple_select("tracker_categories", "catname", "catid = '".$data2['category']."'");
		$new_cat = $db->fetch_field($query, "catname");
		$content .= "{$lang->ed_his_updated} {$lang->iss_category}: ".htmlspecialchars_uni($old_cat)." &raquo; ".htmlspecialchars_uni($new_cat)."<br />";
	}
	if($data2['priority'] != $issue['priority'])
	{
		$query = $db->simple_select("tracker_priorities", "priorityname", "priorid = '".$issue['priority']."'");
		$old_prior = $db->fetch_field($query, "priorityname");
		$query = $db->simple_select("tracker_priorities", "priorityname", "priorid = '".$data2['priority']."'");
		$new_prior = $db->fetch_field($query, "priorityname");
		$content .= "{$lang->ed_his_updated} {$lang->iss_priority}: ".htmlspecialchars_uni($old_prior)." &raquo; ".htmlspecialchars_uni($new_prior)."<br />";
	}
	if($data2['status'] != $issue['status'])
	{
		$query = $db->simple_select("tracker_status", "statusname", "statid = '".$issue['status']."'");
		$old_status = $db->fetch_field($query, "statusname");
		$query = $db->simple_select("tracker_status", "statusname", "statid = '".$data2['status']."'");
		$new_status = $db->fetch_field($query, "statusname");
		$content .= "{$lang->ed_his_updated} {$lang->iss_status}: ".htmlspecialchars_uni($old_status)." &raquo; ".htmlspecialchars_uni($new_status)."<br />";
	}
	
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		if($data2['assignee'] != $issue['assignee'])
		{
			$content .= "{$lang->ed_his_updated} {$lang->iss_assign}: ".htmlspecialchars_uni($issue['assignname'])." &raquo; ".htmlspecialchars_uni($data2['assignname'])."<br />";
		}
		if($data2['version'] != $issue['version'])
		{
			$query = $db->simple_select("tracker_projects", "name", "proid = '".$issue['version']."'");
			$old_version = $db->fetch_field($query, "name");
			if(!$old_version)
			{
				$old_version = "<em>None</em>";
			}
			$query = $db->simple_select("tracker_projects", "name", "proid = '".$data2['version']."'");
			$new_version = $db->fetch_field($query, "name");
			$content .= "{$lang->ed_his_updated} {$lang->iss_versions}: ".htmlspecialchars_uni($old_version)." &raquo; ".htmlspecialchars_uni($new_version)."<br />";
		}
		if($data2['complete'] != $issue['complete'])
		{
			$content .= "{$lang->ed_his_updated} {$lang->iss_complete}: ".$issue['complete']."&#37; &raquo; ".$data2['complete']."&#37;<br />";
		}
	}

	$act_array['content'] = $db->escape_string($content);

	// Update the post in the database
	if(is_array($data1)) // Only update if the message is different
	{
		$db->update_query("tracker_issuesposts", $data1, "issid = '".$issue['issid']."' AND isspid = '".$issue['firstpost']."'");
	}
	// Update the issues in the database
	if(is_array($data2) && $act_array['content'] != '') // If there is no content, then nothing has changed!
	{
		$db->update_query("tracker_issues", $data2, "issid = '".$issue['issid']."'");
	}
	// Insert the activity data
	if($act_array['content'] != '')
	{
		$db->insert_query("tracker_activity", $act_array);
	}
	
	if($act_array['content'] != '')
	{
		redirect("".get_issue_url($issue['issid'])."", $lang->ed_complete);
	}
	else
	{
		redirect("".get_issue_url($issue['issid'])."", $lang->ed_no_change);
	}
}

/**
 * Allows use of ACP navigation tabs
 * This is a modified core MyBB function from ./admin/inc/class_page.php
 *
 * @param array Nested array of tabs containing possible keys of align, link_target, link, title.
 * @param string The name of the active tab. Corresponds with the key of each tab item.
 * @param bool (optional) Whether or not to display loading div container in description
 * @return string The list of tabs to be displayed
 **/
function output_nav_tabs($tabs=array(), $active='', $load=false)
{
	global $mybb;
	$tabber = "<tr><td colspan=\"3\"><div class=\"nav_tabs\">";
	$tabber .= "\n\t<ul>\n";
	foreach($tabs as $id => $tab)
	{
		$class = '';
		if($id == $active)
		{
			$class = ' active';
		}
		if($tab['align'] == "right")
		{
			$class .= " right";
		}
		if($tab['link_target'])
		{
			$target = " target=\"{$tab['link_target']}\"";
		}
			if($tab['onclick'] && $tab['onclick'] != "javascript:;" && $mybb->settings['use_xmlhttprequest'])
			{
				$onclick = " onclick=\"{$tab['onclick']}\"";
				if($mybb->input['process'] != 1) // If posting, don't do javascript... nasty stuff
				{
					$name = " name=\"jscript\"";
				}
			}
			else // If this isn't done, then this stuff inherits the previous tab's values
			{
				$onclick = '';
				$name = '';
			}
		$tabber .= "\t\t<li class=\"{$class}\"><a href=\"{$tab['link']}\"{$target}{$name}{$onclick}>{$tab['title']}</a></li>\n";
		$target = '';
	}
	$tabber .= "\t</ul>\n";
	if($tabs[$active]['description'])
	{
		if($load === true) // To have a valid source
		{
			$load_div = "<div id=\"loading\" style=\"float:right; display:none;\">Loading...</div>";
		}
		$tabber .= "\t<div class=\"tab_description\">{$load_div}{$tabs[$active]['description']}</div>\n"; // Note: May add in spinner image in future version...
	}
	$tabber .= "</div></td></tr><tr>";
return $tabber;
}

/**
 * Outputs the correct suffix for the number given
 * A quick function to make this easier for us OCD sufferers
 *
 * @param int Number to be suffixed
 * @param bool (optional) Return text with suffix?
 * @return string The correct suffix
 **/
function my_suffix($number, $text=false)
{
	$number = intval($number);
	if($number == 1)
	{
		$suffix = "";
		if($text)
		{
			$suffix .= " has";
		}
	}
	else
	{
		$suffix = "s";
		if($text)
		{
			$suffix .= " have";
		}
	}
	return $suffix;
}

/**
 * Display a relative time (1 month ago, 4 minutes ago etc.)
 *
 * @param int The UNIX timestamp to figure out if it is today or not
 * @return int Whether or not the given time is from today
 */
function istoday($timestamp)
{
	$now = gmdate("d", time());
	$stamp = gmdate("d", $timestamp);
	if($now == $stamp)
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

//---------------------------------------------------
// Please note: I did not write this!
// I've been using this for years, and have no idea where
// it comes from! Probably from http://uk.php.net/manual/en/function.time.php#89415
// or somewhere similar...
//---------------------------------------------------
/**
 * Display a relative time (1 month ago, 4 minutes ago etc.)
 * This works both ways (1 month to go, 4 minutes go go etc.) if $timestamp is greater than TIME_NOW
 *
 * @param int The UNIX timestamp to get the date from (or to)
 * @return string The relative time according to the given date
 */
function relative_time($timestamp)
{
	global $lang, $mybb;

	// Get the time/date formats from the settings
	$tformat = $mybb->settings['timeformat'];
	$dformat = $mybb->settings['dateformat'];

	$today = istoday($timestamp); // Been added today?
	// Different formats for the relative time
	$textret = "".$lang->today.", ";
	$ret1 = gmdate("".$tformat."", $timestamp);
	$ret2 = gmdate("".$dformat.", ".$tformat, $timestamp);

	$calc = TIME_NOW;
	$difference = $calc - $timestamp; // How many seconds have passed?

	// Master of Time... hmm, have a blast on Zelda when I get home tonight...!
	$periods = array($lang->second, $lang->minute, $lang->hour, $lang->day, $lang->week, $lang->month, $lang->years, $lang->decade);
	$lengths = array("60","60","24","7","4.35","12","10");

	if($difference >= 0)
	{
		// This was in the past - edited; needs to equal 0 unless the future is used...
		$ending = $lang->relative_ago;
	}
	else
	{
		// We're timewarping... agaaaaaaaaaain...
		$difference = -$difference;
		$ending = $lang->relative_to_go;
	}		

	// Loop through difference/lengths
	for($j = 0; $difference >= $lengths[$j]; $j++)
	{
		$difference /= $lengths[$j];
	}
	$difference = round($difference);

	// Display an extra 's' at the end for plural times, or show "moments ago"
	if($difference != 1)
	{
		$periods[$j].= $lang->relative_plural;
	}
	if($difference < 10)
	{
		$text = $lang->moments;
	}

	// Just cleaning up a few things for different times
	if($periods[$j] == "day")
	{
		$text = "".$lang->yesterday.", $ret1"; // Display 'yesterday'
	}
	else
	{
		$text = "$difference $periods[$j] $ending"; // Else display relative time
	}

	return $text;
}

//---------------------------------------------------
// OK, this is *really* cheating. We've taken the pagination
// script from MyBB, and messed around with it enough to
// make it possible for AJAX transition (if requested)
//---------------------------------------------------

/**
 * Generate a listing of page - pagination
 *
 * @param int The number of items
 * @param int The number of items to be shown per page
 * @param int The current page number
 * @param string The URL to have page numbers tacked on to (If {page} is specified, the value will be replaced with the page #)
 * @param string Whether we're specifically forcing a page
 * @bool (optional) Whether or not to turn URLs into javascript:; links (for AJAX pagination)
 * @return string The generated pagination
 */
function my_multipage($count, $perpage, $page, $url, $type='', $js=false)
{
	global $theme, $templates, $lang, $mybb;

	if($count <= $perpage)
	{
		return;
	}
	
	$url = str_replace("&amp;", "&", $url);
	$url = htmlspecialchars_uni($url);

	$pages = ceil($count / $perpage);

	if($page > 1)
	{
		$prev = $page-1;
		$page_url = my_fetch_page_url($url, $prev, $type, $js);
		eval("\$prevpage = \"".$templates->get("multipage_prevpage")."\";");
	}

	// Maximum number of "page bits" to show
	if(!$mybb->settings['maxmultipagelinks'])
	{
		$mybb->settings['maxmultipagelinks'] = 5;
	}

	$from = $page-floor($mybb->settings['maxmultipagelinks']/2);
	$to = $page+floor($mybb->settings['maxmultipagelinks']/2);

	if($from <= 0)
	{
		$from = 1;
		$to = $from+$mybb->settings['maxmultipagelinks']-1;
	}

	if($to > $pages)
	{
		$to = $pages;
		$from = $pages-$mybb->settings['maxmultipagelinks']+1;
		if($from <= 0)
		{
			$from = 1;
		}
	}

	if($to == 0)
	{
		$to = $pages;
	}

	if($from > 1)
	{
		$page_url = my_fetch_page_url($url, 1, $type, $js);
		eval("\$start = \"".$templates->get("multipage_start")."\";");
	}

	for($i = $from; $i <= $to; ++$i)
	{
		$page_url = my_fetch_page_url($url, $i, $type, $js);
		if($page == $i)
		{
			eval("\$mppage .= \"".$templates->get("multipage_page_current")."\";");
		}
		else
		{
			eval("\$mppage .= \"".$templates->get("multipage_page")."\";");
		}
	}

	if($to < $pages)
	{
		$page_url = my_fetch_page_url($url, $pages, $type, $js);
		eval("\$end = \"".$templates->get("multipage_end")."\";");
	}

	if($page < $pages)
	{
		$next = $page+1;
		$page_url = my_fetch_page_url($url, $next,$type,  $js);
		eval("\$nextpage = \"".$templates->get("multipage_nextpage")."\";");
	}
	$lang->multipage_pages = $lang->sprintf($lang->multipage_pages, $pages);
	eval("\$multipage = \"".$templates->get("multipage")."\";");

	return $multipage;
}

/**
 * Generate a page URL for use by the multipage function
 *
 * @param string The URL being passed
 * @param int The page number
 * @param bool (optional) Whether or not to turn the link into a javascript:; link
 */
function my_fetch_page_url($url, $page, $type='', $js=false)
{
	global $mybb, $project;
	
	// If no page identifier is specified we tack it on to the end of the URL
	if($mybb->input['req'] == "ajax")
	{
		$url = "javascript:;";
	}
	elseif(strpos($url, "{page}") === false)
	{
		if(strpos($url, "?") === false)
		{
			$url .= "?";
		}
		else
		{
			$url .= "&amp;";
		}
		$url .= "page=$page";
	}
	else
	{
		$url = str_replace("{page}", $page, $url);
	}

	// OK, we're really cheating here... change to javascript link, add onclick
	// =(
	// Officer, I swear I'm clean! I don't have javascript, so I just see normal links...
	$link = "jQuery('#loading').show(); jQuery('#proj_content').load('projects.php?project=".$project['proid']."&amp;req=ajax&amp;action=content&amp;view={$type}&amp;page=".$page."', '', function(){ jQuery('#loading').hide(); })";
	$url .= "\" name=\"jscript\" onclick=\"".$link."";
	
	return $url;
}
?>