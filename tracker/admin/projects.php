<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: projects.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/projects.php');
$templatelist = "mytrackercp_mainmenu, mytrackercp_projects_edit, mytrackercp_projects_projectlist, mytrackercp_projects_index, mytrackercp_projects_new, mytrackercp_projects_delete";

chdir(dirname(dirname(dirname(__FILE__))));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

add_breadcrumb($mybb->settings['trackername'], "../");
add_breadcrumb($lang->trackercp, "./");

// Verify that we can access the CP
if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
{
	$ismod = true;
}
else
{
	error_no_permission(); // Global; if not a mod, they can't do anything past here
}

// We have more options for the projects section
$extra_options = array(
	"\t\t<li class=\"np\"><div class=\"margin_left\"><a href=\"./projects.php?action=new\">{$lang->new_project}</a></div></li>",
	"\t\t<li class=\"md\"><div class=\"margin_left\"><a href=\"./projects.php?action=managedevs\">{$lang->manage_devs}</a></div></li>"
);

// This is used to remove the "np" and "md" in the above code later on
$empty_array = array(
	"",
	""
);

// Saving Project Orders
if($mybb->input['action'] == "save_orders")
{
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		foreach($mybb->input['disporder'] as $key => $value)
		{
			$update_array = array(
				"disporder" => intval($value)
			);
			$db->update_query("tracker_projects", $update_array, "proid ='".intval($key)."'");
		}
		$success_message = $lang->updated_orders;
	}
	$mybb->input['action'] = ''; // Reset, because we're doing inline message
}

if($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	if($mybb->request_method == "post" && $mybb->input['action'] == "do_new")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();
		
		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->short_name;
		}
		// Users can create projects without a description, but clean whitespace
		$mybb->input['description'] = trim($mybb->input['description']);
		
		if(count($errors) > 0)
		{
			// Show inline error
			$messages = inline_error($errors);
		}
		else
		{
			// Carrying on...
			$query = $db->simple_select("tracker_projects", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_projects)"); // The next display order number
			$last_order = $db->fetch_field($query, "disporder");
			
			$insert_array = array(
				"name" => $db->escape_string($mybb->input['name']),
				"description" => $db->escape_string($mybb->input['description']),
				"stage" => intval($mybb->input['stage']),
				"active" => intval($mybb->input['active']),
				"allowfeats" => intval($mybb->input['features']),
				"disporder" => ++$last_order,
				"parent" => '0',
				"created" => TIME_NOW,
				"num_issues" => '0',
				"num_features" => '0',
				"lastpost" => '0',
				"lastposter" => '',
				"lastposteruid" => '0',
				"lastpostissid" => '0',
				"lastpostsubject" => ''
			);
			$db->insert_query("tracker_projects", $insert_array);
			redirect("/tracker/admin/projects.php", $lang->created_project);
		}
		
		// If we've tried to post, show the posted options instead
		$value_1 = $mybb->input['name'];
		$value_2 = $mybb->input['description'];
		$project['stage'] = $mybb->input['stage'];
		$project_stages = get_tracker_stages();
		if($mybb->input['active'] == 1)
		{
			$value_3 = "checked=\"checked\"";
		}
		else
		{
			$value_4 = "checked=\"checked\"";
		}
		if($mybb->input['features'] == 1)
		{
			$value_5 = "checked=\"checked\"";
		}
		else
		{
			$value_6 = "checked=\"checked\"";
		}
		
	}
	
	add_breadcrumb($lang->new_project);
	
	// Set a few defaults and get the stages
	if(!$value_3 || !$value_4)
	{
		$value_3 = "checked=\"checked\""; // Set "Active" to yes
	}
	if(!$value_5 || !$value_6)
	{
		$value_5 = "checked=\"checked\""; // Set "Allow Features" to yes
	}
	$project_stages = get_tracker_stages();

	// Replace values in the extra options menu
	$search_array = array(
		"np",
		"md",
		$lang->new_project
	);
	$replace_array = array(
		"active",
		"",
		"&raquo {$lang->new_project}"
	);
	$extra_options = str_replace($search_array, $replace_array, $extra_options);
	$project_options = implode("\n", $extra_options);
	eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");

	eval("\$trackercp_projects_new = \"".$templates->get("mytrackercp_projects_new")."\";");
	output_page($trackercp_projects_new);
}

if($mybb->input['action'] == "managedevs")
{
	// We're updating group permissions
	if($mybb->input['do'] == "group_mod")
	{
		$change_group = intval($mybb->input['group']);
		if($change_group)
		{
			$query = $db->simple_select("usergroups", "canmodtrack, cancp", "gid = '".$change_group."'");
			if($db->num_rows($query))
			{
				$group_info = $db->fetch_array($query);
				if($group_info['canmodtrack'] == 1 && $group_info['cancp'] != 1)
				{
					$db->update_query("usergroups", array("canmodtrack" => 0), "gid = '".$change_group."'");
					$cache->update_usergroups();
					redirect("/tracker/admin/projects.php?action=managedevs", $lang->group_updated);
				}
				elseif($group_info['canmodtrack'] == 0 && $group_info['cancp'] != 1)
				{
					$db->update_query("usergroups", array("canmodtrack" => 1), "gid = '".$change_group."'");
					$cache->update_usergroups();
					redirect("/tracker/admin/projects.php?action=managedevs", $lang->group_updated);
				}
				elseif($group_info['cancp'] == 1)
				{
					// Display failed message
					error($lang->no_edit_admin);
				}
			}
			else
			{
				error($lang->no_groups);
			}
		}
		else
		{
			error($lang->no_groups);
		}
	}
	
	// Adding a Developer
	if($mybb->request_method == "post" && $mybb->input['do'] == "adddev")
	{
		$query = $db->simple_select("users", "uid", "username = '".$db->escape_string($mybb->input['username'])."'");
		if($db->num_rows($query))
		{
			// Update the developer info
			$db->update_query("users", array("developer" => '1'), "username = '".$db->escape_string($mybb->input['username'])."'");
			redirect("/tracker/admin/projects.php?action=managedevs", $lang->added_dev);
		}
		else
		{
			error($lang->no_find_dev);
		}
	}
	
	// Removing a Developer
	if($mybb->input['do'] == "remdev")
	{
		$developer = intval($mybb->input['dev']);
		$query = $db->simple_select("users", "uid", "uid = '".$developer."'");
		if($db->num_rows($query))
		{
			$db->update_query("users", array("developer" => '0'), "uid = '".$developer."'");
			
			// Remove the "assigned" tasks for this user
			$db->update_query("tracker_issues", array("assignee" => '0', "assignname" => 'None'), "assignee = '".$developer."'"); 
			redirect("/tracker/admin/projects.php?action=managedevs", $lang->removed_dev);
		}
		else
		{
			error($lang->no_find_dev);
		}
	}
	
	// Get the list of groups for the forum
	$query = $db->simple_select("usergroups", "gid, title, namestyle, canmodtrack, cancp", "", array("order_by" => "gid", "order_dir" => "ASC"));
	$total = $db->num_rows($query);
	$group_list = '';
	while($group = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$group_name = htmlspecialchars_uni($group['title']);
		$group_format = str_replace("{username}", $group_name, $group['namestyle']);
		
		if($group['canmodtrack'])
		{
			$status = "<img src=\"./images/tick.gif\" alt=\"\" />";
			if(!$group['cancp'])
			{
				$allow_deny = $lang->deny;
			}
			else
			{
				$allow_deny = '--';
			}
		}
		else
		{
			$status = "<img src=\"./images/cross.gif\" alt=\"\" />";
			if(!$group['cancp'])
			{
				$allow_deny = $lang->allow;
			}
			else
			{
				$allow_deny = '--';
			}
		}
		$group_list .= "<tr>\n<td class=\"{$bgcolor}\">".$group_format."</td>\n<td class=\"{$bgcolor}\" align=\"center\" width=\"75\">{$status}</td><td align=\"center\" class=\"{$bgcolor}\"><a href=\"projects.php?action=managedevs&amp;do=group_mod&amp;group=".$group['gid']."\">{$allow_deny}</a></td>\n</tr>";
	}

	// We're grabbing developers here!
	$query = $db->simple_select("users", "uid, username", "developer = '1'", array("order_by" => "uid", "order_dir" => "ASC"));
	while($developer = $db->fetch_array($query))
	{
		$link = get_user_url($developer['uid']);
		$developer['username'] = htmlspecialchars_uni($developer['username']);
		$developer_list .= "<tr>\n<td class=\"{$bgcolor}\"><a href=\"../".$link."\">".$developer['username']."</a></td>\n<td class=\"{$bgcolor}\" align=\"center\" width=\"75\"><a href=\"projects.php?action=managedevs&amp;do=remdev&amp;dev=".$developer['uid']."\">{$lang->remove_dev}</a></td>\n</tr>";
	}

	// Replace values in the extra options menu
	$search_array = array(
		"np",
		"md",
		$lang->manage_devs
	);
	$replace_array = array(
		"",
		"active",
		"&raquo {$lang->manage_devs}"
	);
	$extra_options = str_replace($search_array, $replace_array, $extra_options);
	$project_options = implode("\n", $extra_options);
	eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");

	eval("\$trackercp_projects_managedevs = \"".$templates->get("mytrackercp_projects_managedevs")."\";");
	output_page($trackercp_projects_managedevs);
}

if($mybb->input['action'] == "delete" || $mybb->input['action'] == "do_delete")
{
	$mybb->input['project'] = intval($mybb->input['project']);
	$query = $db->simple_select("tracker_projects", "proid, name", "proid = '".$mybb->input['project']."'");
	if(!$db->num_rows($query))
	{
		error($lang->unknown_project);
	}
	else
	{
		$project = $db->fetch_array($query);
	}
	if($mybb->request_method == "post" && $mybb->input['action'] == "do_delete")
	{
		verify_post_check($mybb->input['my_post_key']);

		// We're deleting pretty much everything you can think of here...
		// Generate a list of features and issues we'll be deleting
		$query = $db->simple_select("tracker_features", "featid", "projid = '".$mybb->input['project']."'");
		while($feature = $db->fetch_array($query))
		{
			$featurecache[$feature['featid']] = $feature['featid'];
		}
		$query = $db->simple_select("tracker_issues", "issid", "projid = '".$mybb->input['project']."'");
		while($issue = $db->fetch_array($query))
		{
			$issuecache[$issue['issid']] = $issue['issid'];
		}

		// Implode to a list
		if(is_array($featurecache))
		{
			$featurecache = implode(", ", $featurecache);
		}
		if(is_array($issuecache))
		{
			$issuecache = implode(", ", $issuecache);
		}

		// Start deleting the project - but only if there's things in the array
		if($issuecache)
		{
			$db->delete_query("tracker_activity", "issid IN ({$issuecache}) AND feature = '0'"); // Deleting issues from the activity
			$db->delete_query("tracker_activity", "issid IN ({$featurecache}) AND feature = '1'"); // Deleting features from the activity
			$db->delete_query("tracker_issuesread", "issid IN ({$issuecache})");
		}
		if($featurecache)
		{
			$db->delete_query("tracker_featuresread", "featid IN ({$featurecache})");
			$db->delete_query("tracker_featuresvotes", "featid IN ({$featurecache})");
		}

		// Features
		$db->delete_query("tracker_features", "projid = '".$mybb->input['project']."'");
		$db->delete_query("tracker_featuresposts", "projid = '".$mybb->input['project']."'");

		// Issues
		$db->delete_query("tracker_issues", "projid = '".$mybb->input['project']."'");
		$db->delete_query("tracker_issuesposts", "projid = '".$mybb->input['project']."'");

		// Project
		$db->delete_query("tracker_projects", "proid = '".$mybb->input['project']."'");
		$db->delete_query("tracker_projectsread", "proid = '".$mybb->input['project']."'");

		redirect("/tracker/admin/projects.php", $lang->project_deleted);
	}

	eval("\$trackercp_projects_delete = \"".$templates->get("mytrackercp_projects_delete")."\";");
	output_page($trackercp_projects_delete);
}

if($mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit")
{
	$mybb->input['project'] = intval($mybb->input['project']);
	$query = $db->simple_select("tracker_projects", "*", "proid = '".$mybb->input['project']."'");
	if(!$db->num_rows($query))
	{
		error($lang->unknown_project);
	}
	else
	{
		$project = $db->fetch_array($query);
	}

	$project['name'] = htmlspecialchars_uni($project['name']); // Clean name!

	// We're posting edits to a project
	if($mybb->request_method == "post" && $mybb->input['action'] == "do_edit")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();
		
		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->short_name;
		}
		// Users can create projects without a description, but clean whitespace
		$mybb->input['description'] = trim($mybb->input['description']);
		
		if(count($errors) > 0)
		{
			// Show inline error
			$messages = inline_error($errors);
		}
		else
		{
			// Carrying on...
			$update_array = array(
				"name" => $db->escape_string($mybb->input['name']),
				"description" => $db->escape_string($mybb->input['description']),
				"stage" => intval($mybb->input['stage']),
				"active" => intval($mybb->input['active']),
				"allowfeats" => intval($mybb->input['features'])
			);
			$db->update_query("tracker_projects", $update_array, "proid = '".$project['proid']."'");
			redirect("/tracker/admin/projects.php", $lang->saved_project);
		}
	}
	
	// Trail...
	$this_breadcrumb = $lang->edit_project = $lang->sprintf($lang->edit_project, $project['name']);
	add_breadcrumb($lang->projects, "projects.php");
	add_breadcrumb($this_breadcrumb);
	
	// Figure out the statistics
	$lang->stats_info_1 = $lang->sprintf($lang->stats_info_1, my_number_format($project['num_issues']));
	if($project['allowfeats'] == 1)
	{
		$lang->stats_info_2 = $lang->sprintf($lang->stats_info_2, my_number_format($project['num_features']))."."; // End with fullstop
	}
	else
	{
		$lang->stats_info_2 = '.'; // Remove so it won't be seen in the string
	}
	$query = $db->query("
		SELECT
		(SELECT COUNT(DISTINCT(uid)) FROM ".TABLE_PREFIX."tracker_issuesposts WHERE projid = '".$project['proid']."') AS issuepost_users,
		(SELECT COUNT(DISTINCT(uid)) FROM ".TABLE_PREFIX."tracker_featuresposts WHERE projid = '".$project['proid']."') AS ideaspost_users
	");
	$user_stats = $db->fetch_array($query);

	// Posted comments
	// This isn't accurate, as the firstpost of an issue is included here, but it's just an idea of users!
	if(($user_stats['issuepost_users'] + $user_stats['ideaspost_users']) == 1)
	{
		$lang->stats_info_3 = $lang->sprintf($lang->stats_info_3, '1', $lang->person, $lang->has);
	}
	else
	{
		$lang->stats_info_3 = $lang->sprintf($lang->stats_info_3, my_number_format(($user_stats['issuepost_users'] + $user_stats['ideaspost_users'])), $lang->people, $lang->have);
	}

	$query = $db->simple_select("tracker_issues", "COUNT(issid) AS closed_issues", "complete = '100'");
	$project['closed_issues'] = $db->fetch_field($query, "closed_issues");

	// Now that we know how many issues have been closed, display a percentage and the project time
	if($project['num_issues'])
	{
		$sum = round(($project['closed_issues'] / $project['num_issues']) * 100);
		$project['percent'] = $sum;
	}
	else
	{
		$project['percent'] = 0; // There is no issues, so no %
	}

	// Ohhh yeah... relative time...
	$project['time'] = relative_time($project['created']);
	$lang->stats_info_4 = $lang->sprintf($lang->stats_info_4, $project['percent'], $project['time']); 
	
	// Project Options
	if($mybb->request_method == "post")
	{
		// If we've tried to post, show the posted options instead
		$value_1 = $mybb->input['name'];
		$value_2 = $mybb->input['description'];
		$project['stage'] = $mybb->input['stage'];
		$project_stages = get_tracker_stages();
		if($mybb->input['active'] == 1)
		{
			$value_3 = "checked=\"checked\"";
		}
		else
		{
			$value_4 = "checked=\"checked\"";
		}
		if($mybb->input['features'] == 1)
		{
			$value_5 = "checked=\"checked\"";
		}
		else
		{
			$value_6 = "checked=\"checked\"";
		}
	}
	else
	{
		$value_1 = $project['name'];
		$value_2 = $project['description'];
		$project_stages = get_tracker_stages();
		if($project['active'] == 1)
		{
			$value_3 = "checked=\"checked\"";
		}
		else
		{
			$value_4 = "checked=\"checked\"";
		}
		if($project['allowfeats'] == 1)
		{
			$value_5 = "checked=\"checked\"";
		}
		else
		{
			$value_6 = "checked=\"checked\"";
		}
	}
	
	
	// Replace values in the extra options menu
	$search_array = array(
		"np",
		"md",
	);
	$extra_options = str_replace($search_array, $empty_array, $extra_options);
	$project_options = implode("\n", $extra_options);
	$projects_active = "active"; // We're on the projects page (and not doing anything)!
	$p_raquo = "&raquo ";
	eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");

	eval("\$trackercp_projects_edit = \"".$templates->get("mytrackercp_projects_edit")."\";");
	output_page($trackercp_projects_edit);
}

if(!$mybb->input['action'])
{
	// We're on just the projects page
	add_breadcrumb($lang->projects);
	$query = $db->simple_select("tracker_projects", "proid, name, description, disporder", "", array("order_by" => "proid", "order_dir" => "ASC"));
	while($project = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$project_link = "../".get_project_link($project['proid']);
		eval("\$project_list .= \"".$templates->get("mytrackercp_projects_projectlist")."\";");
	}
	
	// Replace values in the extra options menu
	$search_array = array(
		"np",
		"md",
	);
	$extra_options = str_replace($search_array, $empty_array, $extra_options);
	$project_options = implode("\n", $extra_options);
	$projects_active = "active"; // We're on the projects page (and not doing anything)!
	$p_raquo = "&raquo ";
	eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");
	
	// Is there a message to display?
	if($success_message)
	{
		$messages = "<div id=\"flash_message\" class=\"success\">{$success_message}</div>";
	}
	elseif($error_message)
	{
		$messages = "<div id=\"flash_message\" class=\"error\">{$error_message}</div>";
	}
	
	eval("\$trackercp_projects_index = \"".$templates->get("mytrackercp_projects_index")."\";");
	output_page($trackercp_projects_index);
}
?>
