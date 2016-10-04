<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: index.php 8 2009-08-17 09:59:23Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/index.php');
$templatelist = "mytracker_index_projectlist, mytracker_index_latest_activity, mytracker_index_content, mytracker_index";

chdir(dirname(dirname(__FILE__))); // In case people have their own folder, get back to main dir
require_once "./global.php";
require_once "./inc/functions_tracker.php";

add_breadcrumb($mybb->settings['trackername'], "./");
add_breadcrumb($lang->dashboard);

$templatelist = "mytracker_project_issues_content, mytracker_project_nocontent, mytracker_project_issues, mytracker_project_content, mytracker_project";
$templates->cache($templatelist);

// Moderator Actions
if($mybb->input['action'] == "unapprove" || $mybb->input['action'] == "approve" && $mybb->input['actid'])
{
	$actid = intval($mybb->input['actid']);
	// Check if we can do this
	if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
	{
		// Check if the thread exists
		$query = $db->simple_select("tracker_activity", "visible", "actid = '".$actid."'", array("limit" => 1));
		if($db->num_rows($query))
		{
			$visible = $db->fetch_field($query, "visible");
			if($visible == 1)
			{
				$update = array(
					"visible" => 0
				);
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'"); // We're unapproving this here
			}
			else
			{
				$update = array(
					"visible" => 1
				);
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'"); // We're approving this now
			}
			// We've updated something - reload the page if not using AJAX, or AJAH, whatever it is you want to call it that makes it look fancy...
			if($mybb->input['req'] != "ajax")
			{
				redirect("./", $lang->dash_act_redirect);
			}
		}
	}
}

if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
{
	$query = $db->simple_select("tracker_projects", "*", "parent = '0'", array("order_by" => 'disporder', "order_dir" => 'ASC'));
	$moderator = true;
	$where = '';
	$where_2 = '';
}
else
{
	$query = $db->simple_select("tracker_projects", "*", "active = '1' AND parent = '0'", array("order_by" => 'disporder', "order_dir" => 'ASC'));
	$moderator = false;
	$where = "visible = '1'";
	$where_2 = "WHERE visible = '1'";
}
$project_count = $db->num_rows($query); // How many active "projects" have we got?
$total_count_issues = 0;
$total_count_features = 0;
while($project = $db->fetch_array($query))
{
	$bgcolor = alt_trow();
	$issue_count = 0;
	$feature_count = 0;
	// Keep a count of the stats
	$issue_count = $issue_count + $project['num_issues'];
	$feature_count = $feature_count + $project['num_features'];

	// If this user isn't a mod, then reduce the count
	if($moderator == false)
	{
		$hidden_issues = get_hidden_issues($project['proid']);
		$hidden_features = get_hidden_features($project['proid']);

		// Alter the counts
		$issue_count = $issue_count - $hidden_issues;
		$feature_count = $feature_count - $hidden_features;
		if($issue_count < 0)
		{
			$issue_count = 0;
		}
		if($feature_count < 0)
		{
			$feature_count = 0;
		}
	}
	
	$total_count_issues = $total_count_issues + $issue_count;
	if($project['allowfeats'])
	{
		$total_count_features = $total_count_features + $feature_count;
	}

	// Sort a few things out
	$project['name'] = htmlspecialchars_uni($project['name']);
	$project['description'] = htmlspecialchars_uni($project['description']);
	$project['link'] = get_project_link($project['proid']);
	$project['num_issues'] = my_number_format($project['num_issues']);

	$project['closed_issues'] = get_closed_count($project['proid']);

	// Figure out the fancy percentage
	if($project['num_issues'])
	{
		$sumage = round(($project['closed_issues'] / $project['num_issues']) * 100);
		$project['percent'] = $sumage;
		if($project['percent'] == 100)
		{
			$project['percentbar'] = 99;
		}
		elseif($project['num_issues'] && $project['percent'] <= 99)
		{
			$project['percentbar'] = $project['percent'];
		}
		else
		{
			$project['percentbar'] = 100;
		}
		$project['num_issues'] = my_number_format($project['num_issues']);
	}
	else
	{
		$project['percent'] = 0; // There is no issues, so why show it?
		$project['percentbar'] = 1; // No issues, but show a little skin...
	}

	// Show the Features for this project if the system is on
	if($mybb->settings['ideasys'])
	{
		if($mybb->settings['ideasys'] == 0 || $project['allowfeats'] == 0)
		{
			$feature_counts = "--";
		}
		elseif(!$project['num_features'])
		{
			$feature_counts = 0;
		}
		else
		{
			$feature_counts = $feature_count;
		}
		$project_features_cat = "<td class=\"tcat\" width=\"100\" align=\"center\"><span class=\"smalltext\"><strong>{$lang->dash_features}</strong></span></td>";
		$project_features = "<td class=\"{$bgcolor}\" align=\"center\">{$feature_counts}</td>";
	}

	// ZOMG - Lightbulbs!
	$lightbulb = get_project_lightbulb($project['proid'], $project['lastpost']);

	eval("\$project_list .= \"".$templates->get("mytracker_index_projectlist")."\";");
}

// Latest Activity
if($moderator == true) // Track Moderators and Developers can see all hidden activity
{
	// These can see ideas, regardless of the on/off setting
	$query = $db->simple_select("tracker_activity", "*", "", array("limit" => 10, "limit_start" => 0, "order_by" => 'dateline', "order_dir" => 'DESC'));
}
else
{
	//$query = $db->simple_select("tracker_activity", "*", "visible = '1'".$extra."", array("limit" => 10, "limit_start" => 0, "order_by" => 'dateline', "order_dir" => 'DESC'));
	// Build a better query so we can check to see if we can see the activities or not
	$query = $db->query("
		SELECT a.*, i.visible AS issue_vis, f.visible AS feature_vis FROM
		".TABLE_PREFIX."tracker_activity a
		LEFT JOIN ".TABLE_PREFIX."tracker_issues i ON (i.issid=a.issid AND a.feature='0')
		LEFT JOIN ".TABLE_PREFIX."tracker_features f ON (f.featid=a.issid AND a.feature='1') 
		WHERE a.visible = '1'
		ORDER BY a.dateline DESC
		LIMIT 0, 10
	");
}

$has = $lang->dash_act_has; // #42
$actual_count = 0;
if($db->num_rows($query))
{
	while($activity = $db->fetch_array($query))
	{
		if($activity['visible'] == 0)
		{
			$bgcolor = "trow_shaded";
		}
		else
		{
			$bgcolor = alt_trow(); // WIP: Strange bug here...
		}

		// Check for permissions
		if($moderator == false && ($activity['visible'] == 0 || $activity['issue_vis'] == 0 || $activity['feature_vis'] == 0))
		{
			if($activity['feature'] == 0 && $activity['issue_vis'] == 0)
			{
				continue;
			}
			elseif($activity['feature'] == 1 && $activity['feature_vis'] == 0)
			{
				continue;
			}
			elseif($activity['visible'] == 0)
			{
				continue;
			}
		}

		++$actual_count;
		$activity['user'] = "<a href=\"".get_user_url($activity['uid'])."\">".htmlspecialchars_uni($activity['username'])."</a>";
		$activity['time'] = my_date($mybb->settings['dateformat'], $activity['dateline'])." ".my_date($mybb->settings['timeformat'], $activity['dateline']);

		if(!$lang->dash_act_has)
		{
			// If we've produced proper English, Jeeves, make it a cup of tea...
			$lang->dash_act_has = $has; // #42
		}

		switch($activity['action'])
		{
			case 1: // It's a comment
				$activity['style'] = "act_comment";
				if($activity['feature'])
				{
					$link = get_feature_url($activity['issid']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_feature." #".intval($activity['issid'])."</a>"; 
				}
				else
				{
					$link = get_issue_url($activity['issid']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_issue." #".intval($activity['issid'])."</a>";
				}
				$lang->dash_act_has = '';
				$lang->user_activity = $lang->dash_act_comment." ".$link_text;
				$active_go = '';
				break;
			case 2: // An update!
				$activity['style'] = "act_update";
				if($activity['feature'])
				{
					$link = get_feature_url($activity['content']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_feature." #".intval($activity['issid'])."</a>"; 
					$active_go = '';
				}
				else
				{
					$link = get_issue_url($activity['issid']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_issue." #".intval($activity['issid'])."</a>";
					if($mybb->input['req'] == "ajax")
					{
						$url2 = "javascript:;";
					}
					else
					{
						$url2 = "";
					}
					$active_go = "<a href=\"".$url2."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=edithistory&amp;activity={$activity['actid']}&amp;issue={$activity['issid']}' });\">";
					$active_go .= "<img src=\"../images/tracker/bullet_go.png\" alt=\"{$lang->iss_in_show_changes}\" title=\"{$lang->iss_in_show_changes}\" style=\"vertical-align:middle;\" /></a>";
				}
				$lang->user_activity = $lang->dash_act_update." ".$link_text;				
				break;
			case 3: // New stuff...
				if($activity['feature'])
				{
					$link = get_feature_url($activity['issid']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_feature." #".intval($activity['issid'])."</a>";
					$type = $lang->dash_lower_feature;
					$activity['style'] = "act_newfeature";
				}
				else // A new bug - oh noes!
				{
					$link = get_issue_url($activity['issid']);
					$link_text = "<a href=\"".$link."\">".$lang->dash_lower_issue." #".$activity['issid']."</a>";
					$type = $lang->dash_lower_issue;
					$activity['style'] = "act_newbug";
				}
				$lang->dash_act_has = '';
				$lang->user_activity = $lang->dash_act_new." ".$type." &raquo; ".$link_text; // StUpId BuG wuz ere y2k+9... ¬_¬
				$active_go = '';
				break;
			case 4: // Resolved bug - w00t!
				// We're not doing features, silly...
				$activity['style'] = "act_resolved";
				$link = get_issue_url($activity['issid']);
				$link_text = "<a href=\"".$link."\">".$lang->dash_lower_issue." #".intval($activity['issid'])."</a>";
				$lang->user_activity = $lang->dash_act_resolve." ".$link_text;
				if($mybb->input['req'] == "ajax")
				{
					$url2 = "javascript:;";
				}
				else
				{
					$url2 = "";
				}
				$active_go = "<a href=\"".$url2."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=edithistory&amp;activity={$activity['actid']}&amp;issue={$activity['issid']}' });\">";
				$active_go .= "<img src=\"../images/tracker/bullet_go.png\" alt=\"{$lang->iss_in_show_changes}\" title=\"{$lang->iss_in_show_changes}\" style=\"vertical-align:middle;\" /></a>";
		}

		// Can we approve / unapprove the activity? Never delete(!)
		if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
		{
			$link = "index.php?action=unapprove&amp;actid={$activity['actid']}";
			$extra = " name=\"jscript\"";
			// Determine if we can use AJAX call for this
			if($mybb->input['req'] == "ajax")
			{	
				$link = "javascript:;";
			}
			else
			{
				$link = $link;
			}
			if($activity['visible'])
			{
				$activity['mod_action'] = "<div class=\"float_right\"><a href=\"".$link."\"".$extra." onclick=\"jQuery('#loading').show(); jQuery('#body').load('index.php?req=ajax&amp;action=unapprove&amp;actid={$activity['actid']}', '', function(){ jQuery('#loading').hide(); });\"><img src=\"../images/tracker/rem_delete.png\" alt=\"{$lang->iss_in_invis}\" title=\"{$lang->iss_in_invis}\" /></a></div>";
			}
			else
			{
				$activity['mod_action'] = "<div class=\"float_right\"><a href=\"".$link."\"".$extra." onclick=\"jQuery('#loading').show(); jQuery('#body').load('index.php?req=ajax&amp;action=unapprove&amp;actid={$activity['actid']}', '', function(){ jQuery('#loading').hide(); });\"><img src=\"../images/tracker/rem_tick.png\" alt=\"{$lang->iss_in_vis}\" title=\"{$lang->iss_in_vis}\" /></a></div>";
			}
		}
		eval("\$latest_activity .= \"".$templates->get("mytracker_index_latest_activity")."\";");
	}

	if($actual_count == 0)
	{
		// If there's no activities to show, show no activities
		$latest_activity = "<tr><td class=\"trow1\" colspan=\"2\">{$lang->dash_no_activity}</td></tr>";
	}
}
else
{
	$latest_activity = "<tr><td class=\"trow1\" colspan=\"2\">{$lang->dash_no_activity}</td></tr>";
}

// Figure out the statistics while we're here in 'proper' English(!)
if($project_count != 1)
{
	$lang->plural_1 = $lang->dash_are;
	$lang->plural_2 = $lang->dash_plural;
}
else
{
	$lang->plural_1 = $lang->dash_is;
	$lang->plural_2 = '';
}
if($total_count_issues != 1)
{
	$lang->plural_3 = $lang->dash_plural;
}
else
{
	$lang->plural_3 = '';
}

$lang->dash_stats_issues = $lang->sprintf($lang->dash_stats_issues, $lang->plural_1, my_number_format($project_count), $lang->plural_2, my_number_format($total_count_issues), $lang->plural_3); 

if($mybb->settings['ideasys'])
{
	// Add to the stats
	if($total_count_features != 1)
	{
		$lang->plural_4 = $lang->dash_plural;
	}
	else
	{
		$lang->plural_4 = '';
	}
	$lang->dash_stats_feats = $lang->sprintf($lang->dash_stats_feats, my_number_format($total_count_features), $lang->plural_4);
	$lang->dash_stats_issues .= " ".$lang->dash_stats_feats;
}
else
{
	$lang->dash_stats_issues .= "."; // End the sentence, Jeeves...
}

// WIP - #45
$query = $db->query("
	SELECT 
	(SELECT SUM(replies) FROM ".TABLE_PREFIX."tracker_issues ".$where_2.") AS issue_replies,
	(SELECT SUM(replies) FROM ".TABLE_PREFIX."tracker_features ".$where_2.") AS feature_replies
");
$all_comments = $db->fetch_array($query);
$comment_count = $all_comments['issue_replies'] + $all_comments['feature_replies'];

$lang->dash_stats_users = $lang->sprintf($lang->dash_stats_users, my_number_format($comment_count), $lang->dash_plural);

$menu = output_nav_tabs($sub_tabs, 'dashboard', true);
if($mybb->input['req'] == "ajax")
{
	// Just load content
	eval("\$tracker_index = \"".$templates->get("mytracker_index_content")."\";");
}
else
{
	// Load content and index!
	eval("\$content = \"".$templates->get("mytracker_index_content")."\";");
	eval("\$tracker_index = \"".$templates->get("mytracker_index")."\";");
}
output_page($tracker_index);
?>