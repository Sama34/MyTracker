<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: issues.php 11 2009-10-05 14:48:17Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/issues.php');
$templatelist = "mytracker_issue_timeline, mytracker_issue_comments, mytracker_issue_button_edit, mytracker_issue_button_visibleoff, mytracker_issue_newcomment, mytracker_issue_content, mytracker_issue, mytracker_project_nocontent, mytracker_issue_all";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

// If the issue is all, then we're viewing all issues
if($mybb->input['issue'] == "all")
{
	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($lang->iss_all_title);
	
	$lang->load("forumdisplay"); // For sorting options

	// Build query and loop de loop
	if($mybb->settings['dotfolders'] != 0)
	{
		$query = $db->simple_select("tracker_issuesposts", "issid, isspid, dateline", "uid = '".$mybb->user['uid']."'");
		while($postissue = $db->fetch_array($query))
		{
			$issuecache[$postissue['issid']]['posted'] = $postissue['isspid'];
		}
	}

	$query = $db->query("
		SELECT i.issid, r.dateline AS readthread
		FROM ".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_issuesread r ON (i.issid=r.issid AND r.uid='{$mybb->user['uid']}')
		WHERE r.uid = '".$mybb->user['uid']."'
		ORDER BY issid"
	);
	while($readissue = $db->fetch_array($query))
	{
		$issuecache[$readissue['issid']]['readthread'] = $readissue['readthread'];
	}
	
	// Build drop down box for Priorities
	$priorities = get_tracker_priorities(0, true);
	
	// Go through the sorting options
	// Dates
	if($mybb->input['datecut'])
	{
		$datecut = intval($mybb->input['datecut']);
	}
	else
	{
		// Has a user manually set a date cut?
		if($mybb->user['daysprune'])
		{
			$datecut = $mybb->user['daysprune'];
		}
	}
	
	$datecutsel[$datecut] = "selected=\"selected\"";
	if($datecut > 0 && $datecut != 9999)
	{
		$checkdate = TIME_NOW - ($datecut * 86400);
		$datecutsql = "AND i.lastpost >= '$checkdate'";
		$datecutsql2 = "AND lastpost >= '$checkdate'";
	}
	else
	{
		$datecutsql = '';
	}

	// Priorities
	if($mybb->input['priority'] && $mybb->input['priority'] != "all")
	{
		$priority = intval($mybb->input['priority']);
		$prioritysql = "AND i.priority = '{$priority}'";
		$prioritysql2 = "AND priority = '{$priority}'";
	}
	else
	{
		$prioritysql = '';
	}
	
	// Sort order
	$mybb->input['order'] = htmlspecialchars($mybb->input['order']);
	switch(my_strtolower($mybb->input['order']))
	{
		case "asc":
			$sortordernow = "asc";
			$ordersel['asc'] = "selected=\"selected\"";
			$oppsort = $lang->desc;
			$oppsortnext = "desc";
			break;
		default:
			$sortordernow = "desc";
			$ordersel['desc'] = "selected=\"selected\"";
			$oppsort = $lang->asc;
			$oppsortnext = "asc";
			break;
	}
	
	// Sort by
	$sortby = htmlspecialchars($mybb->input['sortby']);
	switch($mybb->input['sortby'])
	{
		case "subject":
			$sortfield = "subject";
			break;
		case "views":
			$sortfield = "views";
			break;
		case "starter":
			$sortfield = "username";
			break;
		case "started":
			$sortfield = "dateline";
			break;
		default:
			$sortby = "lastpost";
			$sortfield = "lastpost";
			$mybb->input['sortby'] = "lastpost";
			break;
	}
	
	$sortsel[$mybb->input['sortby']] = "selected=\"selected\"";

	// Moderators == Big Brother
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		$where = "WHERE visible >= '0'"; // Cheating cheetah!... =P
		$moderator = true;
	}
	else
	{
		$where = "WHERE visible = '1'";
		$moderator = false;
	}
	
	// Pages of results
	$query = $db->query("SELECT COUNT(issid) AS issues FROM ".TABLE_PREFIX."tracker_issues {$where} {$datecutsql2} {$prioritysql2}");
	$total_issues = $db->fetch_field($query, "issues");

	$page = intval($mybb->input['page']);
	$perpage = 15;
	$pages = $total_issues / $perpage;
	$pages = ceil($pages);
	
	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	$query = $db->query("
		SELECT i.*, c.catname AS category, s.statusname AS status, p.name AS project, pr.priorityname AS priority
		FROM ".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_projects p ON (i.projid=p.proid)
		LEFT JOIN ".TABLE_PREFIX."tracker_priorities pr ON (i.priority=pr.priorid)
		LEFT JOIN ".TABLE_PREFIX."tracker_categories c ON (i.category=c.catid)
		LEFT JOIN ".TABLE_PREFIX."tracker_status s ON (i.status=s.statid)
		{$where} {$datecutsql} {$prioritysql}
		ORDER BY i.{$sortfield} {$sortordernow}
		LIMIT ".$start.", ".$perpage."
	");
	
	// Build the page URL
	if($mybb->input['sortby'] || $mybb->input['order'] || $mybb->input['datecut'] || $mybb->input['priority'])
	{
		$page_url = ISSUE_LIST_URL;
		if($mybb->settings['seourls'] == "yes" || ($mybb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1))
		{
			$q = "?";
			$and = '';
		}
		else
		{
			$q = '';
			$and = "&";
		}

		if($sortby != "lastpost")
		{
			$page_url .= "{$q}{$and}sortby={$sortby}";
			$q = '';
			$and = "&";
		}
		
		if($sortordernow != "desc")
		{
			$page_url .= "{$q}{$and}order={$sortordernow}";
			$q = '';
			$and = "&";
		}
		
		if($datecut > 0 && $datecut != 9999)
		{
			$page_url .= "{$q}{$and}datecut={$datecut}";
		}
	}
	else
	{
		$page_url = ISSUE_LIST_URL;
	}
	
	$multipage = my_multipage($total_issues, $perpage, $page, $page_url);
	while($issue = $db->fetch_array($query))
	{
		++$count;
		$bgcolor = alt_trow();

		// Folder icons
		$folder = '';
		$folder_label = '';
		$issue['postthread'] = $issuecache[$issue['issid']]['posted'];
		$issue['readthread'] = $issuecache[$issue['issid']]['readthread'];
		if($issue['postthread'])
		{
			$folder = "dot_";
			$folder_label .= $lang->icon_dot;
		}
		if($mybb->settings['threadreadcut'] > 0)
		{
			$cutoff = TIME_NOW-$mybb->settings['threadreadcut']*60*60*24;
		}
			// Tough tomatoes if they haven't read it by now!
			/*if($issue['lastpost'] > $cutoff)
			{
				$issue['readthread'] = $mybb->user['lastvisit'];
			}*/
		// If it's new, then show new
		// Attempt at a new "read" thingy
		if($mybb->user['uid'])
		{
			if(!$issue['readthread'] || ($issue['readthread'] && $issue['readthread'] < $issue['lastpost']))
			{
				$folder .= "new";
				$folder_label .= $lang->icon_new;
				$new_class = " class=\"subject_new\"";
			}
			else
			{
				$new_class = '';
			}
		}
		// If it's closed, show locked symbol
		if($issue['closed'])
		{
			$folder .= "lock";
			$folder_label .= $lang->icon_lock;
		}		
		$folder .= "folder";
		
		// Clean variables
		$issue['subject'] = htmlspecialchars_uni($issue['subject']);
		$issue['project_url'] = get_project_link($issue['projid']);
		$issue['project'] = "<a href=\"".$issue['project_url']."\">".htmlspecialchars_uni($issue['project'])."</a>";
		$issue['url'] = get_issue_url($issue['issid']);
		$issue['author'] = "<a href=\"".get_user_url($issue['uid'])."\">".htmlspecialchars_uni($issue['username'])."</a>";
		$issue['category'] = htmlspecialchars_uni($issue['category']);
		$issue['priority'] = htmlspecialchars_uni($issue['priority']);
		$issue['views'] = my_number_format($issue['views']);
		$issue['replies'] = my_number_format($issue['replies']);
		$issue['status'] = htmlspecialchars_uni($issue['status']);
		$issue['lastpost_time'] = my_date($mybb->settings['dateformat'], $issue['lastpost']).", ".my_date($mybb->settings['timeformat'], $issue['lastpost']);
		$issue['lastpost_author'] = "<a href=\"".get_user_url($issue['lastposteruid'])."\">".htmlspecialchars_uni($issue['lastposter'])."</a>";

		eval("\$issues .= \"".$templates->get("mytracker_issue_all_row")."\";");
	}

	if(!$count)
	{
		$search_option = $lang->misc_clear_search = $lang->sprintf($lang->misc_clear_search, ISSUE_ALL_URL);
		eval("\$issues = \"".$templates->get("mytracker_project_nocontent")."\";");
	}

	// I want to break frrreeee![/Freddie Mercury]
	// (We're cleaning up the title so it doesn't go all wierd when we use the Issue templates)
	$project['name'] = $mybb->settings['trackername'];
	$lang->dash_lower_issue = $lang->iss_all_title;

	$menu = output_nav_tabs($sub_tabs, 'issues', true);
	eval("\$content = \"".$templates->get("mytracker_issue_all")."\";");
	eval("\$issue_index = \"".$templates->get("mytracker_issue")."\";"); // Same template as the index!
	
	// Output the page
	output_page($issue_index);
	exit;
}

// Say hello to my little fwend...
$mybb->input['issue'] = intval($mybb->input['issue']);

$query = $db->query("
	SELECT i.*, c.catname AS categoryname, p.priorityname, p.priorstyle AS style, s.statusname
	FROM ".TABLE_PREFIX."tracker_issues i
	LEFT JOIN ".TABLE_PREFIX."tracker_categories c ON (i.category=c.catid)
	LEFT JOIN ".TABLE_PREFIX."tracker_priorities p ON (i.priority=p.priorid)
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
}

// Get the Project info for this Issue
$query = $db->simple_select("tracker_projects", "proid, name, active", "proid = '".$issue['projid']."'", array("limit" => 1));
$project = $db->fetch_array($query);

// Moderator or original author?
if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1)
{
	$moderator = true;
}
elseif($mybb->user['uid'] == $issue['uid'])
{
	if($project['active'] == 0)
	{
		// If the project is hidden, then the guy isn't a mod!
		$moderator = false;
	}
	else
	{
		$moderator = true;
	}
}
else
{
	$moderator = false;
}

// If the project is hidden, hide to non mods
if($project['active'] == 0 && $moderator == false)
{
	error($lang->iss_no_issue);
}

//---------------------------------------------------
// Mod Options =D
//---------------------------------------------------
// Approving/Unapproving Activities
if($mybb->input['action'] == "unapprove" || $mybb->input['action'] == "approve" && $mybb->input['actid'])
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
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'");
			}
			else
			{
				$update = array(
					"visible" => 1
				);
				$db->update_query("tracker_activity", $update, "actid = '".$actid."'");
			}
			if($mybb->input['req'] != "ajax")
			{
				redirect("".get_issue_url($issue['issid'])."", $lang->iss_act_redirect);
			}
		}
	}
}
// Approving/Unapproving this Issue
if($mybb->input['visibility'])
{
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
	{
		// Requesting to go invisible, and issue is currently visible
		if($mybb->input['visibility'] == "off")
		{
			$update = array(
				"visible" => 0
			);
			$db->update_query("tracker_issues", $update, "issid = '".$issue['issid']."'");
			$issue['visible'] = 0; // To stop 'double-loading' effect
		}
		// ...else requesting to go visible and issue is currently invisible
		if($mybb->input['visibility'] == "on")
		{
			$update = array(
				"visible" => 1
			);
			$db->update_query("tracker_issues", $update, "issid = '".$issue['issid']."'");
			$issue['visible'] = 1;
		}
		if($mybb->input['req'] != "ajax")
		{
			redirect("".get_issue_url($issue['issid'])."", $lang->iss_redirect);
		}		
	}
}

// Is this issue visible?
if($issue['visible'] == 0)
{
	if($moderator == true)
	{
		$issue['visiblestyle'] = " class=\"trow_shaded\" style=\"border:1px solid #CCCCCC;\"";
	}
	else
	{
		error($lang->iss_no_issue); // If it's invisible, and not able to moderate this issue, then block the user
	}
}

//---------------------------------------------------
// Posting 'Comments'
//---------------------------------------------------
if($mybb->input['action'] == "new_comment" && $mybb->request_method == "post")
{
	if($issue['allowcomments'] != 1 || !$mybb->user['uid'])
	{
		error($lang->iss_notcommallowed);
	}
	else
	{
		$query = $db->simple_select("tracker_issuesposts", "uid", "issid = '".$issue['issid']."'", array("limit" => 1, "order_by" => 'dateline', "order_dir" => 'DESC'));
		$last_uid = $db->fetch_field($query, "uid");
		if($last_uid && $last_uid == $mybb->user['uid'] && $moderator == false)
		{
			error($lang->iss_lastcomment);
		}

		$mybb->input['comment'] = trim($mybb->input['comment']);
		if(my_strlen($mybb->input['comment']) == 0)
		{
			error($lang->iss_no_cont_comment);
		}
		elseif(my_strlen($mybb->input['comment']) < 5)
		{
			error($lang->iss_not_long_comment);
		}

		$comment = array(
			"issid" => $issue['issid'],
			"projid" => $issue['projid'],
			"uid" => $mybb->user['uid'],
			"username" => $db->escape_string($mybb->user['username']),
			"dateline" => TIME_NOW,
			"message" => $db->escape_string($mybb->input['comment']),
			"visible" => 1
		);
		$db->insert_query("tracker_issuesposts", $comment);
		
		// Update the bug - but only if it's visible!
		if($issue['visible'])
		{
			$update_array = array(
				"replies" => $issue['replies']+1,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid']
			);

			// Update
			$db->update_query("tracker_issues", $update_array, "issid = '".$issue['issid']."'");
			$issue['replies'] = $update_array['replies'];
		}
		// We're creating a comment, so add it to the activity
		// Shorten the message for the activity
		if(my_strlen($mybb->input['comment']) > 50)
		{
			$comment['message'] = my_substr($comment['message'], 0, 50)."...";
		}
		$update_array = array(
			"action" => 1,
			"issid" => $comment['issid'],
			"feature" => 0,
			"content" => $db->escape_string($comment['message']),
			"uid" => $mybb->user['uid'],
			"username" => $db->escape_string($mybb->user['username']),
			"dateline" => TIME_NOW,
			"visible" => 1
		);

		if($issue['visible'] == 0)
		{
			// If the issue is invisible, then so are the updates!
			$update_array['visible'] = 0;
		}

		$db->insert_query("tracker_activity", $update_array);

		$mybb->settings['redirects'] = 0; // This (temporarily) removes the "friendly" redirection - "quick" posting
		redirect("".get_issue_url($issue['issid'])."", $lang->iss_added_comment);
	}
}

// Deleting a comment?
if($mybb->input['action'] == "delete_comment")
{
	if($moderator == true)
	{
		if($mybb->input['ydelete'])
		{
			// Delete Comment
			$db->delete_query("tracker_issuesposts", "isspid = '".intval($mybb->input['pid'])."' AND issid = '".$issue['issid']."'");
			$update_array = array();
			$update_array['replies'] = $issue['replies']-1;

			// Update
			$db->update_query("tracker_issues", $update_array, "issid = '".$issue['issid']."'");
			$issue['replies'] = $update_array['replies'];
			redirect("".get_issue_url($issue['issid'])."", $lang->iss_comment_deleted);
		}
		elseif($mybb->input['ndelete'])
		{
			// Do no nothin'!
			// This probably should never appear with javascript, but there's some cunning idiots out there...
			redirect("".get_issue_url($issue['issid'])."", $lang->iss_no_action);
		}
	}
	else
	{
		error_no_permission();
	}
}

// Updating a comment
if($mybb->input['action'] == "edit_comment")
{
	$isspid = intval($mybb->input['pid']);
	$issid = intval($mybb->input['issue']);

	// Check for a guest, or not enough info
	if(!$mybb->user['uid'] || !$isspid || !$issid)
	{
		error_no_permission();
	}

	// Get the comment
	$query = $db->simple_select("tracker_issuesposts", "*", "isspid = '".$isspid."' AND issid = '".$issid."'", array("limit" => 1));	
	if(!$db->num_rows($query))
	{
		error($lang->iss_no_issue);
	}
	else
	{
		$comment = $db->fetch_array($query);
	}

	// If they aren't a moderator, then are they the maker?
	if($moderator == false && $comment['uid'] != $mybb->user['uid'])
	{
		error_no_permission();
	}

	$update_array = array(
		"message" => $db->escape_string($mybb->input['comment']),
		"edituid" => $mybb->user['uid'],
		"edituser" => $mybb->user['username'],
		"edittime" => TIME_NOW
	);
	$db->update_query("tracker_issuesposts", $update_array, "isspid = '".$isspid."' AND issid = '".$issid."'");

	if($mybb->input['all'])
	{
		// Came from "all comments", so send them back there
		redirect("".get_comments_url($issid)."#comm".$isspid."", $lang->ed_updated);
	}
	else
	{
		// else back to the field with you...
		redirect("".get_issue_url($issid)."", $lang->ed_updated);
	}
}

// Update when we've read this issue (if a member, and if it's new!)
if($mybb->user['uid'])
{
	$query = $db->simple_select("tracker_issuesread", "dateline", "uid = '".$mybb->user['uid']."' AND issid = '".$issue['issid']."'", array("limit" => 1));
	$issue_read = $db->fetch_field($query, "dateline");
	if(!$db->num_rows($query))
	{
		// No row in database, insert into table
		$insert_array = array(
			'issid' => $issue['issid'],
			'uid' => $mybb->user['uid'],
			'dateline' => TIME_NOW
		);
		$db->insert_query("tracker_issuesread", $insert_array);
	}
	else
	{
		if($issue_read <= $issue['lastpost'])
		{
			// This issue has a new post since the user has last read it - update the time
			$update_array = array(
				'dateline' => TIME_NOW
			);
			$db->update_query("tracker_issuesread", $update_array, "uid = '".$mybb->user['uid']."' AND issid = '".$issue['issid']."'");
		}
	}
}

// Update the views
$db->update_query("tracker_issues", array("views" => $issue['views']+1), "issid = '".$issue['issid']."'", true);
++$issue['views'];

// Add the project to the new issue link
if($sub_tabs['newbug'])
{
	$sub_tabs['newbug']['link'] = $sub_tabs['newbug']['link']."&amp;project=".$project['proid']."";
}

add_breadcrumb($mybb->settings['trackername'], "./");
add_breadcrumb($project['name'], get_project_link($project['proid']));
add_breadcrumb("#".$issue['issid']." &raquo; ".$issue['subject']);

// We should be sorted for the majority of the stuff, let's clean up some bad-ass muvva variables...
// Make some of the stuff friendly...
$issue['subject'] = htmlspecialchars_uni($issue['subject']);
$issue['categoryname'] = htmlspecialchars_uni($issue['categoryname']);
$issue['priorityname'] = htmlspecialchars_uni($issue['priorityname']);
if(!$issue['priorityname'])
{
	$issue['priorityname'] = $lang->iss_none;
}
$issue['statusname'] = htmlspecialchars_uni($issue['statusname']);
if(!$issue['statusname'])
{
	$issue['statusname'] = $lang->iss_none;
}
$issue['assignedto'] = "<a href=\"".get_user_url($issue['assignee'])."\">".htmlspecialchars_uni($issue['assignname'])."</a>";
if(!$issue['assignee'])
{
	$issue['assignedto'] = $lang->iss_noone;
}
else
{
	$issue['assignedto'] = "<a href=\"".get_user_url($issue['assignee'])."\">".htmlspecialchars_uni($issue['assignname'])."</a>";
}
if(!$issue['complete'])
{
	$issue['complete'] = '0';
}
$issue['comments'] = my_number_format($issue['replies']);
$issue['reported'] = my_date($mybb->settings['dateformat'], $issue['dateline']).", ".my_date($mybb->settings['timeformat'], $issue['dateline']);
$issue['reportedby'] = "<a href=\"".get_user_url($issue['uid'])."\">".htmlspecialchars_uni($issue['username'])."</a>";

if($issue['replies'] == 1)
{
	$lang->iss_p_comments = $lang->iss_p_comment;
}

// Sort out the timeline
// Do a quick check if we can see invisible activity/comments
if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
{
	$where = '';
}
else
{
	$where = " AND visible = '1'";
}
// We don't include comments made by users, just updates
$timeline_link = get_timeline_url($issue['issid']);
$query = $db->simple_select("tracker_activity", "*", "issid = '".$issue['issid']."' AND action != '1' AND feature = '0'".$where."", array("order_by" => 'dateline', "order_dir" => 'ASC'));
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
			$bgcolor = alt_trow();
		}
		$user_link = get_user_url($activity['uid']);
		$activity['user'] = "<a href=\"".$user_link."\">".htmlspecialchars_uni($activity['username'])."</a>";
		$activity['time'] = my_date($mybb->settings['dateformat'], $activity['dateline']).", ".my_date($mybb->settings['timeformat'], $activity['dateline']);

		// Show moderation options?
		if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
		{
			if($mybb->input['req'] == "ajax")
			{
				$link = "javascript:;"; // We've already used the javascript, so use it again
			}
			else
			{
				$link = "issues.php?issue=".$issue['issid']."&amp;action=unapprove&amp;actid=".$activity['actid'].""; // Otherwise, show normal and let jscript take care of it
			}
			if($activity['visible'])
			{
				$activity['mod_action'] = "<div class=\"float_right\"><a href=\"".$link."\" name=\"jscript\" onclick=\"jQuery('#loading').show(); jQuery('#body').load('issues.php?issue=".$issue['issid']."&amp;req=ajax&amp;action=unapprove&amp;actid={$activity['actid']}', '', function(){ jQuery('#loading').hide(); });\"><img src=\"../images/tracker/rem_delete.png\" alt=\"{$lang->iss_in_invis}\" title=\"{$lang->iss_in_invis}\" /></a></div>";
			}
			else
			{
				$activity['mod_action'] = "<div class=\"float_right\"><a href=\"".$link."\" name=\"jscript\" onclick=\"jQuery('#loading').show(); jQuery('#body').load('issues.php?issue=".$issue['issid']."&amp;req=ajax&amp;action=unapprove&amp;actid={$activity['actid']}', '', function(){ jQuery('#loading').hide(); });\"><img src=\"../images/tracker/rem_tick.png\" alt=\"{$lang->iss_in_vis}\" title=\"{$lang->iss_in_vis}\" /></a></div>";
			}
		}
	
		switch($activity['action'])
		{
			case 2:
				$lang->issue_activity = $lang->iss_act_update;
				$activity['class'] = "act_update";
				if($mybb->input['req'] == "ajax")
				{
					$url = "javascript:;";
				}
				else
				{
					$url = get_timeline_url($issue['issid'])."#act{$activity['actid']}";
				}
				$active_go = "<a href=\"".$url."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=edithistory&amp;activity={$activity['actid']}&amp;issue={$issue['issid']}' });\">";
				$active_go .= "<img src=\"../images/tracker/bullet_go.png\" alt=\"{$lang->iss_in_show_changes}\" title=\"{$lang->iss_in_show_changes}\" style=\"vertical-align:middle;\" /></a>";
				break;
			case 3:
				$lang->issue_activity = $lang->iss_act_new;
				$activity['class'] = "act_newbug";
				$active_go = "";
				break;
			case 4:
				$lang->issue_activity = $lang->iss_act_resolved;
				$activity['class'] = "act_resolved";
				if($mybb->input['req'] == "ajax")
				{
					$url = "javascript:;";
				}
				else
				{
					$url = get_timeline_url($issue['issid'])."#act{$activity['actid']}";
				}
				$active_go = "<a href=\"".$url."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=edithistory&amp;activity={$activity['actid']}&amp;issue={$issue['issid']}' });\">";
				$active_go .= "<img src=\"../images/tracker/bullet_go.png\" alt=\"{$lang->iss_in_show_changes}\" title=\"{$lang->iss_in_show_changes}\" style=\"vertical-align:middle;\" /></a>";
				break;
		}
		eval("\$issue_timeline .= \"".$templates->get("mytracker_issue_timeline")."\";");
	}
}
else
{
	$no_content = $lang->iss_act_none;
	eval("\$issue_timeline = \"".$templates->get("mytracker_issue_timeline_nocontent")."\";");
}

// We'll setup the handler to get the firstpost for the Issue, as we always show that...
require_once MYBB_ROOT."inc/class_parser.php"; $parser = new postParser;
$options = array(
		'allow_html' => 'no',
		'filter_badwords' => 'yes',
		'allow_mycode' => 'yes',
		'allow_smilies' => 'yes',
		'allow_imgcode' => '1',
		'nl2br' => 'yes',
		'me_username' => 'yes'
);

// Firstpost / Description
$query = $db->simple_select("tracker_issuesposts", "*", "isspid = '".$issue['firstpost']."'", array("limit" => 1));
$firstpost = $db->fetch_array($query);

// Parse firstpost with ze uber-parser
$issue['message'] = $parser->parse_message($firstpost['message'], $options);

// We don't want imgcode in the comments
$options['allow_imgcode'] = 0;

// Comments
// We only show the last 5 comments for niceness, not including the firstpost!
$query = $db->query("
	SELECT p.*, u.avatar, u.avatartype
	FROM ".TABLE_PREFIX."tracker_issuesposts p
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
	WHERE issid = '".$issue['issid']."' AND isspid NOT IN (".$issue['firstpost'].")
	".$where."
	ORDER BY dateline DESC
	LIMIT 0, 4"
);
if($db->num_rows($query))
{
	while($comment = $db->fetch_array($query))
	{
		if(!$comment['visible'])
		{
			$comment_class = " class=\"commentmod trow_shaded\""; // Give 'em the pink!
		}
		else
		{
			$comment_class = " style=\"padding:5px;\""; // Just give them a padded cell...
		}
		$comment['authorlink'] = get_user_url($comment['uid']);
		$comment['author'] = htmlspecialchars_uni($comment['username']);
		$comment['posted'] = my_date($mybb->settings['dateformat'], $comment['dateline']).", ".my_date($mybb->settings['timeformat'], $comment['dateline']);
		$comment['comment'] = $parser->parse_message($comment['message'], $options);
		// Get a user avatar, show in nice bubble
		if($comment['avatar']) // Show user avatar
		{
			$user_avatar = htmlspecialchars_uni($comment['avatar']);
			if($comment['avatartype'] == "upload")
			{
				$user_avatar = str_replace("./", "", $user_avatar);
				$avatar = "<img src=\"{$mybb->settings['bburl']}/".$user_avatar."\" alt=\"\" width=\"40\" height=\"40\" style=\"padding-top: 7px;\" />";
			}
			else
			{
				$avatar = "<img src=\"".$user_avatar."\" alt=\"\" width=\"40\" height=\"40\" style=\"padding-top: 7px;\" />";
			}
		}
		else // Show default avatar
		{
			$avatar = "<img src=\"{$mybb->settings['bburl']}/images/default_avatar.gif\" alt=\"\" width=\"40\" height=\"40\" style=\"padding: 7px;\" />";
		}

		// Mod Actions
		if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1)
		{
			$mod_actions = " | <a href=\"comments.php?issue=".$comment['issid']."&amp;action=edit&amp;pid=".$comment['isspid']."#comm".$comment['isspid']."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=editcomment&amp;issue={$issue['issid']}&amp;pid={$comment['isspid']}' });\">{$lang->iss_edit_comment}</a> &middot; <a href=\"issues.php?issue={$issue['issid']}&amp;action=delete_comment&amp;pid={$comment['isspid']}&amp;ydelete=1\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=deletecomment&amp;issue={$issue['issid']}&amp;pid={$comment['isspid']}' });\">$lang->iss_delete_comment</a> ";
		}
		elseif($mybb->user['uid'] == $comment['uid'])
		{
			$mod_actions = " | <a href=\"comments.php?issue=".$comment['issid']."&amp;action=edit&amp;pid=".$comment['isspid']."#comm".$comment['isspid']."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=editcomment&amp;issue={$issue['issid']}&amp;pid={$comment['isspid']}' });\">{$lang->iss_edit_comment}</a>";
		}
		else
		{
			$mod_actions = '';
		}
		
		if($comment['edittime'])
		{
			$editor = "<a href=\"".get_user_url($comment['edituid'])."\">".htmlspecialchars_uni($comment['edituser'])."</a>";
			$lang->iss_edit_details = $lang->sprintf($lang->iss_edit_details, $editor, my_date($mybb->settings['dateformat'], $comment['edittime']), my_date($mybb->settings['timeformat'], $comment['edittime']));
			$edit_details = "<br /><br /><em>{$lang->iss_edit_details}</em>";
		}
		else
		{
			$edit_details = '';
		}

		eval("\$issue_comments .= \"".$templates->get("mytracker_issue_comments")."\";");
	}
}
else
{
	$issue_comments = "<br /><br />".$lang->iss_comments_none;
}

// Do we have enough comments to choose from?
if($issue['replies'] > 5)
{
	$more_comments = " &middot; <a href=\"".get_comments_url($issue['issid'])."\">{$lang->iss_viewall}</a>";
}

// Can this user leave a comment?
if($issue['allowcomments'] == 1 && $mybb->user['uid'])
{
	if($mybb->input['req'] == "ajax")
	{
		$link = "javascript:;";
	}
	else
	{
		$link = get_comments_url($issue['issid']);
	}
	$leave_comments = " &middot; <a href=\"".$link."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=addcomment&amp;issue={$issue['issid']}' });\">{$lang->iss_addcomm}</a>";
}

// Show the options for members only
if($mybb->user['uid'])
{
	$issue_buttons = '<div class="float_right">';
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'] || $mybb->user['uid'] == $issue['uid'])
	{
		// Edit button
		eval("\$issue_buttons .= \"".$templates->get("mytracker_issue_button_edit")."\";");
		// Delete button
		eval("\$issue_buttons .= \"".$templates->get("mytracker_issue_button_delete")."\";");
		// Visibility button (only for mods/devs)
		if($issue['visible'] == 1 && ($mybb->usergroup['canmodtrack'] || $mybb->user['developer']))
		{
			if($mybb->input['req'] == "ajax")
			{
				$visbutton_link = "javascript:;"; // If we've used ajax, then turn the link jscript
			}
			else
			{
				$visbutton_link = "issues.php?issue={$issue['issid']}&amp;visibility=off"; // Otherwise let jscript do the work
			}
			eval("\$issue_buttons .= \"".$templates->get("mytracker_issue_button_visibleoff")."\";");
		}
		elseif($issue['visible'] == 0 && ($mybb->usergroup['canmodtrack'] || $mybb->user['developer']))
		{
			if($mybb->input['req'] == "ajax")
			{
				$visbutton_link = "javascript:;";
			}
			else
			{
				$visbutton_link = "issues.php?issue={$issue['issid']}&amp;visibility=on";
			}
			eval("\$issue_buttons .= \"".$templates->get("mytracker_issue_button_visibleon")."\";");
		}
	}
	$issue_buttons .= "</div>";
}

// Are we having comments?
if($issue['allowcomments'] == 1)
{
	eval("\$new_comment = \"".$templates->get("mytracker_issue_newcomment")."\";");
}

// Has this been edited?
if($firstpost['edituid'])
{
	$user_link = get_user_url($firstpost['edituid']);
	$edit_time = relative_time($firstpost['edittime']);
	$lang->fea_editedby = $lang->sprintf($lang->fea_editedby, $user_link, $firstpost['edituser'], $edit_time);
	$edited_by = "<br /><span style=\"padding-left:15px;\" class=\"smalltext\">".$lang->fea_editedby."</span>";
}

// Babas?
$sub_tabs['project'] = array(
	'title' => "Project: ".$project['name']."",
	'link' => get_project_link($project['proid'])
);
$sub_tabs['issue'] = array(
	'title' => "".$lang->dash_lower_issue." #".$issue['issid']."",
	'link' => get_issue_url($issue['issid']),
	'description' => $lang->iss_tab_info = $lang->sprintf($lang->iss_tab_info, $issue['issid'], $issue['subject'])
);

// Output content
$menu = output_nav_tabs($sub_tabs, 'issue', true);
if($mybb->input['req'] == "ajax")
{
	eval("\$issue_index = \"".$templates->get("mytracker_issue_content")."\";"); // We're just wanting the content to change(!)
}
else
{
	eval("\$content = \"".$templates->get("mytracker_issue_content")."\";");
	eval("\$issue_index = \"".$templates->get("mytracker_issue")."\";");
}

// Output the page
output_page($issue_index);
?>