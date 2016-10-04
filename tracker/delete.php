<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: delete.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/issues.php');
$templatelist = "mytracker_issue_timeline, mytracker_issue_comments, mytracker_issue_button_edit, mytracker_issue_button_visibleoff, mytracker_issue_newcomment, mytracker_issue_content, mytracker_issue, mytracker_project_nocontent, mytracker_issue_all";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

add_breadcrumb($mybb->settings['trackername'], "./");

if($mybb->input['issue'])
{
	$mybb->input['issue'] = intval($mybb->input['issue']);
	$query = $db->query("
		SELECT i.issid, i.subject, i.uid, p.proid, p.name, p.active, p.num_issues FROM
		".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_projects p ON (p.proid=i.projid)
		WHERE i.issid = '".$mybb->input['issue']."'
	");
	
	if(!$db->num_rows($query))
	{
		error($lang->iss_no_issue);
	}
	else
	{
		$issue = $db->fetch_array($query);
	}
	
	// Find out if they can delete this issue or not
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$moderator = true;
	}
	elseif($mybb->user['uid'] == $issue['uid'])
	{
		if($issue['active'] == 0)
		{
			// User is an author, but project is hidden now
			$moderator = false;
		}
		else
		{
			// Otherwise allow them to delete Issues
			$moderator = true;
		}
	}
	else
	{
		$moderator = false;
	}
	
	if($moderator == false)
	{
		// If they aren't allowed to delete, then no_permission!
		error_no_permission();
	}
	
	if($mybb->input['action'] == "do_delete" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
	
		// Deleting an Issue
		$db->delete_query("tracker_activity", "issid = '".$issue['issid']."'");
		$db->delete_query("tracker_issues", "issid = '".$issue['issid']."'");
		$db->delete_query("tracker_issuesposts", "issid = '".$issue['issid']."'");
		$db->delete_query("tracker_issuesread", "issid = '".$issue['issid']."'");
	
		// Update the Project last post
		update_project_lastpost($issue);
	
		// Optimize the tables, because we're nice
		$db->optimize_table("tracker_activity");
		$db->optimize_table("tracker_issues");
		$db->optimize_table("tracker_issuesposts");
		$db->optimize_table("tracker_issuesread");
		$db->optimize_table("tracker_projects");
		redirect(get_project_link($issue['proid']), $lang->deleted_issue);
	}
	
	$issue['subject'] = htmlspecialchars_uni($issue['subject']);
	$issue['name'] = htmlspecialchars_uni($issue['name']);
	
	add_breadcrumb($issue['name'], get_project_link($issue['proid']));
	add_breadcrumb("#".$issue['issid']." &raquo; ".$issue['subject'], get_issue_url($issue['issid']));
	add_breadcrumb($lang->delete_issue);
	
	eval("\$delete_index = \"".$templates->get("mytracker_issue_delete")."\";");
	output_page($delete_index);
}
elseif($mybb->input['feature'])
{
	$mybb->input['feature'] = intval($mybb->input['feature']);
	$query = $db->query("
		SELECT f.featid, f.subject, f.uid, p.proid, p.name, p.active, p.num_features FROM
		".TABLE_PREFIX."tracker_features f
		LEFT JOIN ".TABLE_PREFIX."tracker_projects p ON (p.proid=f.projid)
		WHERE f.featid = '".$mybb->input['feature']."'
	");
	
	if(!$db->num_rows($query))
	{
		error($lang->fea_no_feature);
	}
	else
	{
		$feature = $db->fetch_array($query);
	}

	// Find out if they can delete this issue or not
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$moderator = true;
	}
	elseif($mybb->user['uid'] == $issue['uid'])
	{
		if($feature['active'] == 0)
		{
			// User is an author, but project is hidden now
			$moderator = false;
		}
		else
		{
			// Otherwise allow them to delete Issues
			$moderator = true;
		}
	}
	else
	{
		$moderator = false;
	}

	if($moderator == false)
	{
		// If they aren't allowed to delete, then no_permission!
		error_no_permission();
	}

	if($mybb->input['action'] == "do_delete" && $mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
	
		// Deleting an Issue
		$db->delete_query("tracker_activity", "issid = '".$feature['featid']."' AND feature = '1'");
		$db->delete_query("tracker_features", "featid = '".$feature['featid']."'");
		$db->delete_query("tracker_featuresposts", "featid = '".$feature['featid']."'");
		$db->delete_query("tracker_featuresread", "featid = '".$feature['featid']."'");
		$db->delete_query("tracker_featuresvotes", "featid = '".$feature['featid']."'");
	
		// Update the Project last post
		update_project_lastpost_f($feature);
	
		// Optimize the tables, because we're nice
		$db->optimize_table("tracker_activity");
		$db->optimize_table("tracker_features");
		$db->optimize_table("tracker_featuresposts");
		$db->optimize_table("tracker_featuresread");
		$db->optimize_table("tracker_featuresvotes");
		$db->optimize_table("tracker_projects");

		redirect(get_project_link($feature['proid']), $lang->deleted_feature);
	}
	
	$feature['subject'] = htmlspecialchars_uni($feature['subject']);
	$feature['name'] = htmlspecialchars_uni($feature['name']);
	
	add_breadcrumb($feature['name'], get_project_link($feature['proid']));
	add_breadcrumb("#".$feature['featid']." &raquo; ".$feature['subject'], get_feature_url($feature['featid']));
	add_breadcrumb($lang->delete_feature);
	
	eval("\$delete_index = \"".$templates->get("mytracker_feature_delete")."\";");
	output_page($delete_index);
}
?>