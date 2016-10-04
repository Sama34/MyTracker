<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: timeline.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'tracker/comments.php');
$templatelist = "mytracker_issue_newcomment, mytracker_issue_comments, mytracker_comments_content, mytracker_comments, mytracker_timeline_activity, mytracker_timeline_content, mytracker_timeline";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
{
	error_no_permission();
}

$mybb->input['issue'] = intval($mybb->input['issue']);
$query = $db->query("
	SELECT i.*, pr.name AS project
	FROM ".TABLE_PREFIX."tracker_issues i
	LEFT JOIN ".TABLE_PREFIX."tracker_projects pr ON (i.projid=pr.proid)
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
}

// Check moderator
if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1)
{
	$moderator = true;
	$where = '';
}
else
{
	$moderator = false;
	$where = " AND visible = '1'";
}

// If this is invisible,
if($issue['visible'] == 0 && $moderator == false)
{
	error($lang->iss_no_issue);
}

// Approving/Unapproving Activities
if(($mybb->input['action'] == "unapprove" || $mybb->input['action'] == "approve") && $mybb->input['actid'])
{
	$actid = intval($mybb->input['actid']);
	if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
	{
		$query = $db->simple_select("tracker_activity", "visible", "actid = '".$actid."'", array("limit" => 1));
		if($db->num_rows($query))
		{
			$visible = $db->fetch_field($query, "visible");
			if($visible == 1)
			{
				$update = array(
					"visible" => 0
				);
				$issue['visible'] = 0;
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'");
			}
			else
			{
				$update = array(
					"visible" => 1
				);
				$issue['visible'] = 1;
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'");
			}
			if($mybb->input['req'] != "ajax")
			{
				redirect("".get_timeline_url($issue['issid'])."", $lang->iss_act_time_redirect);
			}
		}
	}
}

// Breadcrumbs
add_breadcrumb($mybb->settings['trackername'], "./");
add_breadcrumb($issue['project'], get_project_link($issue['projid']));
add_breadcrumb($lang->dash_lower_issue." #".$issue['issid'], get_issue_url($issue['issid']));
add_breadcrumb($lang->iss_timeline);

// Show all activity
$query = $db->query("
	SELECT a.*, u.avatar, u.avatartype
	FROM ".TABLE_PREFIX."tracker_activity a
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=a.uid)
	WHERE issid = '".$issue['issid']."' AND action != '1' AND feature = '0'".$where."
	ORDER BY dateline ASC
");
if($db->num_rows($query))
{
	while($activity = $db->fetch_array($query))
	{
		if($activity['visible'] == 0)
		{
			$bgcolor = "trow_shaded vis_border";
		}
		else
		{
			$bgcolor = '';
		}
		$user_link = get_user_url($activity['uid']);
		$activity['user'] = "<a href=\"".$user_link."\">".htmlspecialchars_uni($activity['username'])."</a>";
		$activity['time'] = my_date($mybb->settings['dateformat'], $activity['dateline']).", ".my_date($mybb->settings['timeformat'], $activity['dateline']);
		
		if($activity['avatar'])
		{
			$user_avatar = htmlspecialchars_uni($activity['avatar']);
			if($activity['avatartype'] == "upload")
			{
				$user_avatar = str_replace("./", "", $user_avatar);
				$avatar = "<img src=\"{$mybb->settings['bburl']}/".$user_avatar."\" alt=\"\" width=\"40\" height=\"40\" style=\"padding-top: 7px;\" />";
			}
			else
			{
				$avatar = "<img src=\"".$user_avatar."\" alt=\"\" width=\"40\" height=\"40\" style=\"padding-top: 7px;\" />";
			}
		}
		else
		{
			$avatar = "<img src=\"{$mybb->settings['bburl']}/images/default_avatar.gif\" alt=\"\" width=\"40\" height=\"40\" style=\"padding: 7px;\" />";
		}

		switch($activity['action'])
		{
			case 1:
				$lang->user_activity = $lang->dash_act_comment_ns;
				$activity['style'] = "act_comment";
				break;		
			case 2:
				$lang->issue_activity = $lang->iss_act_update;
				$activity['class'] = "act_update";
				break;
			case 3:
				$lang->issue_activity = $lang->iss_act_new;
				$activity['class'] = "act_newbug";
				break;
			case 4:
				$lang->issue_activity = $lang->iss_act_resolved;
				$activity['class'] = "act_resolved";
				break;
		}

		// Figure out the mod options
		$mod_actions = '';
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
		{
			if($activity['visible'] == 1)
			{
				$img = "rem_delete.png";
				$visible_lang = $lang->misc_invisible;
				$url = "timeline.php?issue=".$issue['issid']."&amp;action=unapprove&amp;actid=".$activity['actid']."";
			}
			else
			{
				$img = "rem_tick.png";
				$visible_lang = $lang->misc_visible;
				$url = "timeline.php?issue=".$issue['issid']."&amp;action=approve&amp;actid=".$activity['actid']."";
			}
			if($mybb->input['req'] == "ajax")
			{
				$url = "javascript:;";
			}
			$mod_actions .= " | <a href=\"".$url."\" name=\"jscript\" onclick=\"jQuery('#loading').show(); jQuery('#body').load('timeline.php?issue=".$issue['issid']."&amp;action=unapprove&amp;actid=".$activity['actid']."&amp;req=ajax', '', function(){ jQuery('#loading').hide(); });\"><img src=\"../images/tracker/".$img."\" alt=\"\" title=\"".$visible_lang."\" /></a>";
		}

		eval("\$issue_timeline .= \"".$templates->get("mytracker_timeline_activity")."\";");
	}
}
else
{
	$issue_timeline = "<tr><td>".$lang->dash_no_activity."</td></tr>";
}

$timeline_link = get_timeline_url($issue['issid']);
$comment_link = get_comments_url($issue['issid']);

// Extra tabs
$sub_tabs['issue'] = array(
	'title' => "".$lang->dash_lower_issue." #".$issue['issid']."",
	'link' => get_issue_url($issue['issid']),
);
$sub_tabs['comments'] = array(
	'title' => $lang->iss_timeline,
	'link' => $timelink_link,
	'description' => "".$lang->iss_timeline_for." ".$lang->dash_lower_issue." #".$issue['issid'].""
);

// Output content
$menu = output_nav_tabs($sub_tabs, 'comments', true);
if($mybb->input['req'] == "ajax")
{
	eval("\$timeline_index = \"".$templates->get("mytracker_timeline_content")."\";");// We're just wanting the content to change(!)
}
else
{
	eval("\$content = \"".$templates->get("mytracker_timeline_content")."\";");
	eval("\$timeline_index = \"".$templates->get("mytracker_timeline")."\";");
}

// Output the page
output_page($timeline_index);
?>