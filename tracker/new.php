<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: new.php 16 2009-10-07 12:18:45Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/new.php');
$templatelist = "codebuttons, mytracker_new_modoptions, mytracker_new_issueoptions, mytracker_new_content, mytracker_new";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

// No way, José! No guests (or banned) allowed!
if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
{
	error_no_permission();
}

if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
{
	$moderator = true;
}
else
{
	$moderator = false;
}

// New issue
if($mybb->input['action'] == "newissue")
{
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		// Build the error array... there could be troublllle, aheaaaaaad...
		$errors = array();
		
		// Check if this issue hasn't already been posted
		$threshold = TIME_NOW - 60;
		$query = $db->simple_select("tracker_issues", "issid", "uid = '".$mybb->user['uid']."' AND subject = '".$db->escape_string($mybb->input['subject'])."' AND dateline >= '".$threshold."'", array("limit" => 1));
		if($db->num_rows($query))
		{
			$issid = $db->fetch_field($query, "issid");
			$lang->new_already_posted = $lang->sprintf($lang->new_already_posted, "<a href=\"".get_issue_url($issid)."\">{$lang->dash_lower_issue} #".$issid."</a>.");
			error($lang->new_already_posted);
		}
		// Has the user posted in the last {flood} minutes?
		if(($mybb->settings['postfloodcheck'] == 1 && $mybb->settings['postfloodsecs'] > 0) && $moderator == false)
		{
			$threshold = TIME_NOW - $mybb->settings['postfloodsecs'];
			$query = $db->simple_select("tracker_issues", "issid", "uid = '".$mybb->user['uid']."' AND dateline >= '".$threshold."'");
			if($db->num_rows($query))
			{
				error($lang->new_quick_posted);
			}
		}
		// Is the subject and description long enough?
		$mybb->input['subject'] = trim($mybb->input['subject']);
		if(!$mybb->input['subject'])
		{
			$errors[] = $lang->new_no_subject;
		}
		if($mybb->input['subject'] && my_strlen($mybb->input['subject']) < 5)
		{
			$errors[] = $lang->new_short_subject;
		}
		$mybb->input['message'] = trim($mybb->input['message']);
		if(!$mybb->input['message'])
		{
			$errors[] = $lang->new_no_message;
		}
		if($mybb->input['message'] && my_strlen($mybb->input['message']) < 10)
		{
			$errors[] = $lang->new_short_message;
		}

		// Essential information; Please note - OCD.
		// Check for a project
		$query = $db->simple_select("tracker_projects", "COUNT(proid)", "proid = '".intval($mybb->input['project'])."'");
		if(!$db->num_rows($query))
		{
			$errors[] = $lang->new_no_project;
		}	

		// If there's errors, display inline
		if(count($errors) > 0)
		{
			// Sort out the input values
			$mybb->input['message'] = htmlspecialchars_uni($mybb->input['message']);
			$mybb->input['subject'] = htmlspecialchars_uni($mybb->input['subject']);
			
			$errors = "<td colspan=\"2\">".inline_error($errors)."</td>\n</tr>\n<tr>";
		}
		else
		{
			// If no value, select defaults
			if(intval($mybb->input['status']) <= 0)
			{
				$mybb->input['status'] = 1;
			}
			if(intval($mybb->input['priority']) <= 0)
			{
				$mybb->input['priority'] = 1;
			}
			if(intval($mybb->input['assignee']) <= 0)
			{
				$mybb->input['assignee'] = '';
				$mybb->input['assignname'] = '';
			}
			if(intval($mybb->input['category']) <= 0)
			{
				$mybb->input['category'] = 1;
			}
			if(intval($mybb->input['complete']) <= 0)
			{
				$mybb->input['complete'] = 0;
			}
			$query = $db->simple_select("tracker_projects", "COUNT(proid)", "proid = '".intval($mybb->input['priority'])."' AND parent != '0'"); // Is a project, but not a parent
			if(!$db->num_rows($query))
			{
				$mybb->input['version'] = 0;
			}
			// The Issue array
			$insert_array1 = array(
				"projid" => intval($mybb->input['project']),
				"subject" => $db->escape_string($mybb->input['subject']),
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid'],
				"views" => '0',
				"replies" => '0',
				"closed" => '',
				"visible" => '1',
				"allowcomments" => '1',
				"status" => intval($mybb->input['status']),
				"priority" => intval($mybb->input['priority']),
				"assignee" => intval($mybb->input['assignee']),
				"category" => intval($mybb->input['category']),
				"complete" => intval($mybb->input['complete']),
				"version" => intval($mybb->input['version'])
			);
			if($insert_array1['assignee'] > 0)
			{
				$query = $db->simple_select("users", "username", "uid = '".$insert_array1['assignee']."'");
				$username = $db->fetch_field($query, "username");
				$insert_array1['assignname'] = $db->escape_string($username);
			}
			else
			{
				$insert_array1['assignname'] = "None";
			}

			// Issuesposts Array
			$insert_array2 = array(
				"issid" => $issue_id,
				"projid" => $insert_array1['projid'],
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"message" => $db->escape_string($mybb->input['message']),
				"ipaddress" => $mybb->user['ipaddress'],
				"visible" => 1,
				"posthash" => $db->escape_string($mybb->input['my_post_key'])
			);

			// Insert Issues data
			$issue_id = $db->insert_query("tracker_issues", $insert_array1);

			// Insert Issuesposts data
			$insert_array2['issid'] = $issue_id;
			$post_id = $db->insert_query("tracker_issuesposts", $insert_array2);

			// If only everyone used mySQL 5...
			// Quickly update the firstpost of the issue
			$db->update_query("tracker_issues", array("firstpost" => $post_id), "issid = '".$issue_id."'");

			// Projects array
			$query = $db->simple_select("tracker_projects", "num_issues", "proid = '".$insert_array1['projid']."'");
			$num_issues = $db->fetch_field($query, "num_issues") + 1;
			$update_array = array(
				"num_issues" => $num_issues,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid'],
				"lastpostissid" => $issue_id,
				"lastpostsubject" => $insert_array1['subject']
			);
			$db->update_query("tracker_projects", $update_array, "proid = '".$insert_array1['projid']."'");

			// Activity array
			$insert_array3 = array(
				"action" => 3,
				"issid" => $issue_id,
				"feature" => 0,
				"content" => $lang->new_act_info = $lang->sprintf($lang->new_act_info, $db->escape_string($mybb->user['username'])),
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"visible" => 1
			);
			$db->insert_query("tracker_activity", $insert_array3);
			
			// All done! Redirect...
			redirect("".get_issue_url($issue_id)."", $lang->new_issue_redir);
		}
	}
	
	// Add breadcrumbs!
	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($lang->new_bread);
	
	if($mybb->input['project'])
	{
		$query = $db->simple_select("tracker_projects", "proid, name", "proid = '".intval($mybb->input['project'])."'", array("limit" => 1));
		if(!$db->num_rows($query))
		{
			$mybb->input['project'] = '0';
		}
		else
		{
			$project = $db->fetch_array($query);
			$project['link'] = "&amp;project=".$project['proid']."";
			$mybb->input['project'] = intval($mybb->input['project']);
			$back_url = get_project_link($mybb->input['project']);
		}
	}

	if(!$back_url)
	{
		$back_url = "./"; // Back to the Dashboard
	}

	if($mybb->settings['bbcodeinserter'] != 0 && (!$mybb->user['uid'] || $mybb->user['showcodebuttons'] != 0))
	{
		$codebuttons = build_mycode_inserter();
	}

	$projects = get_tracker_projects();
	$categories = get_tracker_categories($mybb->user['usergroup']);
	$priorities = get_tracker_priorities($mybb->user['usergroup']);
	$statuses = get_tracker_statuses($mybb->user['usergroup']);
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$assignees = get_tracker_assignees();
		$pc_complete = get_tracker_complete();

		$versions = get_tracker_versions();
		if($versions)
		{
			// Build row for the versions
			$issue_versions = "<tr>\n<td class=\"trow2\" valign=\"top\"><strong>{$lang->iss_versions}a</strong></td>";
			$issue_versions .= "<td class=\"trow2\">{$versions}</td>\n</tr>";
		}

		eval("\$mod_options = \"".$templates->get("mytracker_new_modoptions")."\";");
	}

	// It's a new issue we're making
	eval("\$new_options = \"".$templates->get("mytracker_new_issueoptions")."\";");
	$form_url = "new.php?action=newissue".$project['link']."&amp;processed=1";
	$type = "issue";

	$lang->post_new = $lang->new_post_issue;

	$menu = output_nav_tabs($sub_tabs, 'newbug');
	eval("\$content = \"".$templates->get("mytracker_new_content")."\";");
	eval("\$new_index = \"".$templates->get("mytracker_new")."\";");

	output_page($new_index);
}
elseif($mybb->input['action'] == "newidea")
{
	if($mybb->settings['ideasys'] == 0)
	{
		// The idea sysmtem is off, show error
		error($lang->idea_sys_off);
	}

	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		// Build the error array... there could be troublllle, aheaaaaaad...
		$errors = array();
		
		// Check if this feature hasn't already been posted
		$query = $db->simple_select("tracker_features", "featid", "uid = '".$mybb->user['uid']."' AND subject = '".$db->escape_string($mybb->input['subject'])."'", array("limit" => 1));
		if($db->num_rows($query))
		{
			$fid = $db->fetch_field($query, "featid");
			$lang->new_already_posted_feature = $lang->sprintf($lang->new_already_posted_feature, "<a href=\"".get_feature_url($fid)."\">{$lang->dash_lower_issue} #".$fid."</a>.");
			error($lang->new_already_posted_feature);
		}
		// Has the user posted in the last {flood} minutes?
		if(($mybb->settings['postfloodcheck'] == 1 && $mybb->settings['postfloodsecs'] > 0) && $moderator == false)
		{
			$threshold = TIME_NOW - $mybb->settings['postfloodcheck'];
			$query = $db->simple_select("tracker_features", "featid", "uid = '".$mybb->user['uid']."' AND dateline >= '".$threshold."'", array("limit" => 1, "order_by" => 'dateline', "order_dir" => 'DESC'));
			if($db->num_rows($query))
			{
				error($lang->new_quick_posted);
			}
		}
		// Is the subject and description long enough?
		$mybb->input['subject'] = trim($mybb->input['subject']);
		if(!$mybb->input['subject'])
		{
			$errors[] = $lang->new_no_subject;
		}
		if($mybb->input['subject'] && my_strlen($mybb->input['subject']) < 5)
		{
			$errors[] = $lang->new_short_subject;
		}
		$mybb->input['message'] = trim($mybb->input['message']);
		if(!$mybb->input['message'])
		{
			$errors[] = $lang->new_no_message;
		}
		if($mybb->input['message'] && my_strlen($mybb->input['message']) < 10)
		{
			$errors[] = $lang->new_short_message;
		}

		// Essential information; Please note - OCD.
		// Check for a project
		$query = $db->simple_select("tracker_projects", "proid, active, allowfeats", "proid = '".intval($mybb->input['project'])."'");
		if(!$db->num_rows($query))
		{
			$errors[] = $lang->new_no_project;
		}
		else
		{
			$project = $db->fetch_array($query);
			if($project['allowfeats'] == 0 || ($project['active'] == 0 && $moderator == false))
			{
				// Project isn't active, or doesn't allow features
				error($lang->idea_sys_off_project);
			}
		}

		// If there's errors, display inline
		if(count($errors) > 0)
		{
			// Sort out the input values
			$mybb->input['message'] = htmlspecialchars_uni($mybb->input['message']);
			$mybb->input['subject'] = htmlspecialchars_uni($mybb->input['subject']);
			
			$errors = "<td colspan=\"2\">".inline_error($errors)."</td>\n</tr>\n<tr>";
		}
		else
		{
			// The Feature array
			$insert_array1 = array(
				"projid" => intval($mybb->input['project']),
				"subject" => $db->escape_string($mybb->input['subject']),
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid'],
				"views" => '0',
				"replies" => '0',
				"closed" => '',
				"visible" => '1',
				"allowcomments" => '1',
				"status" => '1',
				"votesfor" => '1',
				"votesagainst" => '0'
			);

			// Featuresposts Array
			$insert_array2 = array(
				"featid" => $issue_id,
				"projid" => $insert_array1['projid'],
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"message" => $db->escape_string($mybb->input['message']),
				"ipaddress" => $mybb->user['ipaddress'],
				"visible" => 1,
				"posthash" => $db->escape_string($mybb->input['my_post_key'])
			);

			// Insert Features data
			$feature_id = $db->insert_query("tracker_features", $insert_array1);

			// Insert Featuresposts data
			$insert_array2['featid'] = $feature_id;
			$post_id = $db->insert_query("tracker_featuresposts", $insert_array2);

			// If only everyone used mySQL 5...
			// Quickly update the firstpost of the issue
			$db->update_query("tracker_features", array("firstpost" => $post_id), "featid = '".$feature_id."'");

			// Projects array
			$query = $db->simple_select("tracker_projects", "num_features", "proid = '".$insert_array1['projid']."'");
			$num_features = $db->fetch_field($query, "num_features") + 1;
			$update_array = array(
				"num_features" => $num_features,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid'],
				"lastpostissid" => $feature_id,
				"lastpostsubject" => $insert_array1['subject']
			);
			$db->update_query("tracker_projects", $update_array, "proid = '".$insert_array1['projid']."'");

			// Activity array
			$insert_array3 = array(
				"action" => 3,
				"issid" => $feature_id,
				"feature" => 1,
				"content" => $lang->new_idea_info = $lang->sprintf($lang->new_idea_info, $db->escape_string($mybb->user['username'])),
				"uid" => $mybb->user['uid'],
				"username" => $db->escape_string($mybb->user['username']),
				"dateline" => TIME_NOW,
				"visible" => 1
			);
			$db->insert_query("tracker_activity", $insert_array3);
			
			// All done! Redirect...
			redirect("".get_feature_url($feature_id)."", $lang->new_idea_redir);
		}
	}

	// Add breadcrumbs!
	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($lang->new_bread);
	
	if($mybb->input['project'])
	{
		$query = $db->simple_select("tracker_projects", "proid, name", "proid = '".intval($mybb->input['project'])."'", array("limit" => 1));
		if(!$db->num_rows($query))
		{
			$mybb->input['project'] = '0';
		}
		else
		{
			$project = $db->fetch_array($query);
			$project['link'] = "&amp;project=".$project['proid']."";
			$mybb->input['project'] = intval($mybb->input['project']);
			$back_url = get_project_link($mybb->input['project']);
		}
	}

	if(!$back_url)
	{
		$back_url = "./"; // Back to the Dashboard
	}

	if($mybb->settings['bbcodeinserter'] != 0 && (!$mybb->user['uid'] || $mybb->user['showcodebuttons'] != 0))
	{
		$codebuttons = build_mycode_inserter();
	}

	$projects = get_tracker_projects();

	$form_url = "new.php?action=newidea".$project['link']."&amp;processed=1";
	$type = "idea";

	$menu = output_nav_tabs($sub_tabs, 'ideas');
	eval("\$content = \"".$templates->get("mytracker_newidea_content")."\";");
	eval("\$new_index = \"".$templates->get("mytracker_newidea")."\";");

	output_page($new_index);
}
?>