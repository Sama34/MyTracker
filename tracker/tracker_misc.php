<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: tracker_misc.php 5 2010-02-22 16:21:44Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'tracker/tracker_misc.php');
$templatelist = "";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || ($mybb->user['uid'] == $issue['uid'] || $mybb->user['uid'] == $feature['uid']))
{
	$moderator = true;
}

// 1.0.2 bug fix
if(!isset($lang->no_edit_comment))
{
	$lang->no_edit_comment = "You do not have permission to edit this comment.";
}

if($mybb->input['action'] != '' && $mybb->input['action'] != "quick_edit")
{
	if($mybb->input['issue'])
	{
		$mybb->input['issue'] = intval($mybb->input['issue']);
		$query = $db->simple_select("tracker_issues", "issid", "issid = '".$mybb->input['issue']."'", array("limit" => 1));
		if(!$mybb->input['issue'] || !$db->num_rows($query))
		{
			facebox_error($lang->iss_no_issue);
		}
		else
		{
			$query = $db->simple_select("tracker_issues", "*", "issid = '".$mybb->input['issue']."'", array("limit" => 1));
			$issue = $db->fetch_array($query);
		}
	}
	elseif($mybb->input['feature'])
	{
		$mybb->input['feature'] = intval($mybb->input['feature']);
		$query = $db->simple_select("tracker_features", "featid", "featid = '".$mybb->input['feature']."'", array("limit" => 1));
		if(!$mybb->input['feature'] || !$db->num_rows($query))
		{
			facebox_error($lang->iss_no_issue);
		}
		else
		{
			$query = $db->simple_select("tracker_features", "*", "featid = '".$mybb->input['feature']."'", array("limit" => 1));
			$feature = $db->fetch_array($query);
		}
	}

	// Check if we can view this
	if(($issue['issid'] > 0 && $issue['visible'] == 0) || ($feature['featid'] > 0 && $feature['visible'] == 0))
	{
		if($moderator == false)
		{
			facebox_error($lang->iss_no_permission); // If it's invisible, and not able to moderate this issue, then block the user
		}
	}
}

// Making a facebox comment box!
if($mybb->input['action'] == "addcomment")
{
	if(!$mybb->user['uid'])
	{
		facebox_error($lang->iss_pleaselogin);
	}
	if($mybb->input['issue'])
	{
		eval("\$newcomment = \"".$templates->get("mytracker_issue_newcomment")."\";");
	}
	elseif($mybb->input['feature'])
	{
		// We need to check if they haven't posted just before this!
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
		{
			eval("\$newcomment = \"".$templates->get("mytracker_feature_newcomment")."\";");
		}
		else
		{
			$query = $db->simple_select("tracker_featuresposts", "uid", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'", array("order_by" => "dateline", "order_dir" => "DESC", "limit" => 1));
			$poster_uid = $db->fetch_field($query, "uid");
			if($poster_uid == $mybb->user['uid'])
			{
				facebox_error($lang->iss_lastcomment);
			}
		}
	}
	output_page($newcomment);
}

// Updating a comment via facebox? Holy crap!
if($mybb->input['action'] == "editcomment")
{
	if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1)
	{
		$extra_sql = '';
	}
	else
	{
		$extra_sql = "AND uid = '".$mybb->user['uid']."'";
	}

	if($mybb->input['feature'])
	{
		// Get the comment to edit
		$fpid = intval($mybb->input['pid']);
		$fid = intval($mybb->input['feature']);
		$query = $db->simple_select("tracker_featuresposts", "*", "featpid = '".$fpid."' AND featid = '".$fid."' {$extra_sql}", array("limit" => 1));

		if(!$db->num_rows($query))
		{
			// No query results?
			facebox_error($lang->no_edit_comment);
		}

		$comment = $db->fetch_array($query);
		$comment['message'] = htmlspecialchars_uni($comment['message']);

		if($mybb->input['all'])
		{
			$all = "<input name=\"all\" type=\"hidden\" value=\"1\" />";
		}
	
		eval("\$editcomment = \"".$templates->get("mytracker_feature_editcomment")."\";");
		output_page($editcomment);
	}
	elseif($mybb->input['issue'])
	{
		// Get the comment to edit
		$isspid = intval($mybb->input['pid']);
		$issid = intval($mybb->input['issue']);
		$query = $db->simple_select("tracker_issuesposts", "*", "isspid = '".$isspid."' AND issid = '".$issid."' {$extra_sql}", array("limit" => 1));

		if(!$db->num_rows($query))
		{
			// No query results?
			facebox_error($lang->no_edit_comment);
		}

		$comment = $db->fetch_array($query);
		$comment['message'] = htmlspecialchars_uni($comment['message']);
	
		if($mybb->input['all'])
		{
			$all = "<input name=\"all\" type=\"hidden\" value=\"1\" />";
		}

		eval("\$editcomment = \"".$templates->get("mytracker_issue_editcomment")."\";");
		output_page($editcomment);
	}
}

// Are we deleting a comment?
if($mybb->input['action'] == "deletecomment")
{
	if($mybb->input['issue'])
	{
		$pid = intval($mybb->input['pid']);
		$query = $db->simple_select("tracker_issuesposts", "isspid", "isspid = '".$pid."' AND issid = '".intval($mybb->input['issue'])."'", array("limit" => 1));
		if(!$pid || !$db->num_rows($query))
		{
			facebox_error($lang->iss_no_comment);
		}
		eval("\$deletecomment = \"".$templates->get("mytracker_issue_comments_delete")."\";");
	}
	elseif($mybb->input['feature'])
	{
		$pid = intval($mybb->input['pid']);
		$query = $db->simple_select("tracker_featuresposts", "featpid", "featpid = '".$pid."' AND featid = '".intval($mybb->input['feature'])."'", array("limit" => 1));
		if(!$pid || !$db->num_rows($query))
		{
			facebox_error($lang->iss_no_comment);
		}
		eval("\$deletecomment = \"".$templates->get("mytracker_feature_comments_delete")."\";");
	}
	output_page($deletecomment);
}

if($mybb->input['action'] == "do_quickedit" && $mybb->request_method == "post")
{
	redirect("".get_issue_url(1)."", $lang->iss_added_comment);
}

// 'Quick' edit a bug in fancy facebox
if($mybb->input['action'] == "quickedit")
{
	if($mybb->input['issue'])
	{
		$mybb->input['issue'] = intval($mybb->input['issue']);
		$query = $db->query("
			SELECT i.*, p.priorityname, p.priorstyle AS style, s.statusname
			FROM ".TABLE_PREFIX."tracker_issues i
			LEFT JOIN ".TABLE_PREFIX."tracker_priorities p ON (i.priority=p.priorid)
			LEFT JOIN ".TABLE_PREFIX."tracker_status s ON (i.status=s.statid)
			WHERE issid = ".$mybb->input['issue']."
			LIMIT 1
		");
		if(!$mybb->input['issue'] || !$db->num_rows($query))
		{
			facebox_error($lang->iss_no_issue);
		}
		else
		{
			$issue = $db->fetch_array($query);
		}
		// Check if we can view this
		if($issue['visible'] == 0)
		{
			if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $issue['uid'])
			{
				$moderator = true;
			}
			else
			{
				facebox_error($lang->iss_no_permission); // If it's invisible, and not able to moderate this issue, then block the user
			}
		}
	
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
				$issue_versions = "<br/><strong>{$lang->iss_versions}</strong><span style=\"position:absolute; left:125px; margin-top:2px;\">{$versions}</span>";
			}
		}
		
		// Strange bug here with styles and facebox...!
		// Yep, you guessed it... I'm crap at CSS - that's why I do PHP...!
		eval("\$quickedit = \"".$templates->get("mytracker_issue_quickedit")."\";");
		output_page($quickedit);
	}
	elseif($mybb->input['feature'])
	{
		$mybb->input['feature'] = intval($mybb->input['feature']);
		$query = $db->simple_select("tracker_features", "*", "featid = '".$mybb->input['feature']."'");
		if(!$mybb->input['feature'] || !$db->num_rows($query))
		{
			facebox_error($lang->fea_no_feature);
		}
		else
		{
			$feature = $db->fetch_array($query);
		}
		// Check if we can view this
		if($feature['visible'] == 0)
		{
			if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $feature['uid'])
			{
				$moderator = true;
			}
			else
			{
				facebox_error($lang->iss_no_permission); // If it's invisible, and not able to moderate, then block the user
			}
		}
	
		$query = $db->simple_select("tracker_featuresposts", "message", "featid = '".$feature['featid']."' AND featpid = '".$feature['firstpost']."'", array("limit" => 1));
		$feature['message'] = $db->fetch_field($query, "message");
		$feature['subject'] = htmlspecialchars_uni($feature['subject']);
		
		// Who was this suggested by?
		$user_link = get_user_url($feature['uid']);
		$idea_time = relative_time($feature['dateline']);
		$feature['username'] = htmlspecialchars_uni($feature['username']);
		$lang->misc_suggested = $lang->sprintf($lang->misc_suggested, $user_link, $feature['username'], $idea_time);

		eval("\$quickedit = \"".$templates->get("mytracker_feature_quickedit")."\";");
		output_page($quickedit);
	}
}

if($mybb->input['action'] == "edithistory")
{
	$query = $db->query("
		SELECT a.*, i.subject
		FROM ".TABLE_PREFIX."tracker_activity a
		LEFT JOIN ".TABLE_PREFIX."tracker_issues i ON (a.issid=i.issid)
		WHERE a.issid = ".intval($mybb->input['issue'])." AND actid = '".intval($mybb->input['activity'])."'
		LIMIT 1
	");
	
	if(!$db->num_rows($query))
	{
		facebox_error($lang->ed_no_hist);
	}
	else
	{
		$history = $db->fetch_array($query);
	}
	
	// Variables
	$history['subject'] = htmlspecialchars_uni($history['subject']);
	$history['author'] = "<a href=\"".get_user_url($history['uid'])."\">".htmlspecialchars_uni($history['username'])."</a>";
	$history['updated'] = my_date($mybb->settings['dateformat'], $history['dateline']).", ".my_date($mybb->settings['timeformat'], $history['dateline']);
	$lang->ed_his_author = $lang->sprintf($lang->ed_his_author, $history['author'], $history['updated']);
	
	eval("\$edithistory = \"".$templates->get("mytracker_misc_edithistory")."\";");
	output_page($edithistory);
}
?>