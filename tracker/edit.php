<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: edit.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/edit.php');

$templatelist = "mytracker_edit_modoptions, codebuttons, mytracker_edit_content, mytracker_edit";
chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

if($mybb->input['issue'])
{
	// Basic stuff
	$mybb->input['issue'] = intval($mybb->input['issue']);
	$query = $db->query("
		SELECT i.*, p.priorityname, p.priorstyle AS style, s.statusname, pr.name AS project
		FROM ".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_priorities p ON (i.priority=p.priorid)
		LEFT JOIN ".TABLE_PREFIX."tracker_projects pr ON (i.projid=pr.proid)
		LEFT JOIN ".TABLE_PREFIX."tracker_status s ON (i.status=s.statid)
		WHERE issid = ".$mybb->input['issue']."
		LIMIT 1
	");

	if(!$mybb->input['issue'] || !$db->num_rows($query))
	{
		error($lang->iss_no_issue);
	}
	else
	{
		$issue = $db->fetch_array($query);
		$issue_url = get_issue_url($issue['issid']);
	}

	// Moderator or original author?
	if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $issue['uid'])
	{
		$moderator = true;
	}
	else
	{
		$moderator = false;
	}
	
	if(($mybb->input['action'] == "do_quickedit" || $mybb->input['action'] == "do_edit") && $mybb->request_method == "post")
	{
		if($moderator == false)
		{
			error_no_permission();
		}
		
		$mybb->input['message'] = trim($mybb->input['message']);
		$mybb->input['subject'] = trim($mybb->input['subject']);
		
		if(my_strlen($mybb->input['message']) < 5)
		{
			error($lang->ed_not_long);
		}
		if(my_strlen($mybb->input['subject']) < 5)
		{
			error($lang->ed_not_long_subject);
		}

		// Setup update array to insert into the database
		$update_array1 = array(
			"edituid" => $mybb->user['uid'],
			"edituser" => $db->escape_string($mybb->user['username']),
			"edittime" => TIME_NOW,
			"message" => $db->escape_string($mybb->input['message'])
		);
		$update_array2 = array(
			"category" => intval($mybb->input['category']),
			"priority" => intval($mybb->input['priority']),
			"status" => intval($mybb->input['status']),
			"subject" => $db->escape_string($mybb->input['subject'])
		);

		if($db->escape_string($issue['subject']) != $db->escape_string($mybb->input['subject']))
		{
			$update_array2['subject'] = $db->escape_string(trim($mybb->input['subject']));
		}
	
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
		{
			$update_array2['assignee'] = intval($mybb->input['assignee']);
			if($update_array2['assignee'] != $issue['assignee'])
			{
				$query = $db->simple_select("users", "username", "uid = '".$update_array2['assignee']."'", array("limit" => 1));
				if($db->num_rows($query))
				{
					$update_array2['assignname'] = $db->fetch_field($query, "username");
				}
				else
				{
					$update_array2['assignee'] = '0';
					$update_array2['assignname'] = 'None';
				}
			}
			$update_array2['version'] = intval($mybb->input['version']);
			$update_array2['complete'] = intval($mybb->input['complete']);
		}
		
		// We can hide the update here
		if($mybb->input['update'] == 1)
		{
			$update = true;
		}
		else
		{
			$update = false;
		}
		// Update_array1 = _issuesposts data; update_array2 = _issues data
		update_issue($update_array1, $update_array2, $update);
	}
	
	if(!$mybb->input['action'])
	{
		add_breadcrumb($mybb->settings['trackername'], "./");
		add_breadcrumb($issue['project'], get_project_link($issue['projid']));
		add_breadcrumb($lang->dash_lower_issue." #".$issue['issid'], get_issue_url($issue['issid']));
		add_breadcrumb($lang->dash_edit_issue." #".$issue['issid']);
		$query = $db->simple_select("tracker_issuesposts", "message", "issid = '".$issue['issid']."' AND isspid = '".$issue['firstpost']."'", array("limit" => 1));
		$issue['message'] = htmlspecialchars_uni($db->fetch_field($query, "message"));
		$issue['subject'] = htmlspecialchars_uni($issue['subject']);
	
		$categories = get_tracker_categories($mybb->user['usergroup']);
		$priorities = get_tracker_priorities($mybb->user['usergroup']);
		$statuses = get_tracker_statuses($mybb->user['usergroup']);
	
		// Mods only actions
		// Assigned to, complete and version
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
		{
			$assignees = get_tracker_assignees();
			$pc_complete = get_tracker_complete();
	
			// Does this issue's project have any "active" versions
			$versions = get_tracker_versions();
			if($versions)
			{
				$issue_versions = "<tr>\n<td class=\"trow2\" valign=\"top\"><strong>{$lang->iss_versions}</strong></td>";
				$issue_versions .= "<td class=\"trow2\">{$versions}</td>\n</tr>";
			}
	
			eval("\$mod_options = \"".$templates->get("mytracker_edit_modoptions")."\";");
		}
	
		// Editor Buttons
		if($mybb->settings['bbcodeinserter'] != 0 && $mybb->user['showcodebuttons'] != 0)
		{
			$codebuttons = build_mycode_inserter();
		}
	
		$sub_tabs['issue'] = array(
			'title' => "".$lang->dash_lower_issue." #".$issue['issid']."",
			'link' => get_issue_url($issue['issid'])
		);
		$sub_tabs['edissue'] = array(
			'title' => "".$lang->dash_edit_issue." #".$issue['issid']."",
			'link' => "edit.php?issue=".$issue['issid']."",
			'description' => $lang->iss_tab_editinfo = $lang->sprintf($lang->iss_tab_editinfo, $issue['issid'], $issue['subject'])
		);
	
		$menu = output_nav_tabs($sub_tabs, 'edissue');
		eval("\$content = \"".$templates->get("mytracker_edit_content")."\";");
		eval("\$edit_index = \"".$templates->get("mytracker_edit")."\";");
	
		output_page($edit_index);
	}
}
else
{
	$mybb->input['feature'] = intval($mybb->input['feature']);
	$query = $db->query("
		SELECT f.*, pr.name AS project
		FROM ".TABLE_PREFIX."tracker_features f
		LEFT JOIN ".TABLE_PREFIX."tracker_projects pr ON (f.projid=pr.proid)
		WHERE featid = ".$mybb->input['feature']."
		LIMIT 1
	");

	if(!$mybb->input['feature'] || !$db->num_rows($query))
	{
		error($lang->fea_no_feature);
	}
	else
	{
		$feature = $db->fetch_array($query);
		$feature_url = get_feature_url($feature['featid']);
		$query = $db->simple_select("tracker_featuresposts", "message", "featid = '".$feature['featid']."' AND featpid = '".$feature['firstpost']."'", array("limit" => 1));
		$feature['message'] = $db->fetch_field($query, "message");
	}

	// Moderator or original author?
	if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $feature['uid'])
	{
		$moderator = true;
	}
	else
	{
		$moderator = false;
	}
	
	// Saving Changes
	if(($mybb->input['action'] == "do_quickedit" || $mybb->input['action'] == "do_edit") && $mybb->request_method == "post")
	{
		if($moderator == false)
		{
			error_no_permission();
		}
		
		$mybb->input['message'] = trim($mybb->input['message']);
		$mybb->input['subject'] = trim($mybb->input['subject']);
		
		if(my_strlen($mybb->input['message']) < 5)
		{
			error($lang->ed_not_long);
		}
		if(my_strlen($mybb->input['subject']) < 5)
		{
			error($lang->ed_not_long_subject);
		}
		
		$update_array1 = array(
			"edituid" => $mybb->user['uid'],
			"edituser" => $db->escape_string($mybb->user['username']),
			"edittime" => TIME_NOW,
			"message" => $db->escape_string($mybb->input['message'])
		);
		$update_array2 = array(
			"subject" => $db->escape_string($mybb->input['subject'])
		);

		$act_array = array(
			"action" => 2,
			"issid" => $feature['featid'],
			"feature" => 1,
			"content" => $feature['featid'],
			"uid" => $mybb->user['uid'],
			"username" => $db->escape_string($mybb->user['username']),
			"dateline" => TIME_NOW
		);
		
		$db->update_query("tracker_featuresposts", $update_array1, "featid = '".$feature['featid']."' AND featpid = '".$feature['firstpost']."'");
		$db->update_query("tracker_features", $update_array2, "featid = '".$feature['featid']."'");
		$db->insert_query("tracker_activity", $act_array);
		
		redirect("".get_feature_url($feature['featid'])."", $lang->fea_redirect);
	}

	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($feature['project'], get_project_link($feature['projid']));
	add_breadcrumb($lang->dash_lower_feature." #".$feature['featid'], $feature_url);
	add_breadcrumb($lang->dash_edit_feature." #".$feature['featid']);

	// Editor Buttons
	if($mybb->settings['bbcodeinserter'] != 0 && $mybb->user['showcodebuttons'] != 0)
	{
		$codebuttons = build_mycode_inserter();
	}

	$sub_tabs['feature'] = array(
		'title' => "".$lang->dash_lower_feature." #".$feature['featid']."",
		'link' => $feature_url
	);
	$sub_tabs['edfeature'] = array(
		'title' => "".$lang->dash_edit_feature." #".$feature['featid']."",
		'link' => "edit.php?feature=".$feature['featid']."",
		'description' => $lang->fea_tab_editinfo = $lang->sprintf($lang->fea_tab_editinfo, $feature['featid'], $feature['subject'])
	);

	$menu = output_nav_tabs($sub_tabs, 'edfeature');
	eval("\$edit_index = \"".$templates->get("mytracker_feature_edit")."\";");

	output_page($edit_index);
}
?>