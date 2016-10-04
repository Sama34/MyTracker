<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: comments.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("NO_ONLINE", 1);
define('THIS_SCRIPT', 'tracker/comments.php');
$templatelist = "mytracker_issue_newcomment, mytracker_feature_comments, mytracker_comments_content, mytracker_comments, mytracker_feature_newcomment, mytracker_issue_comments";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

if(!$mybb->user['uid'] || $mybb->usergroup['isbannedgroup'])
{
	error_no_permission();
}

if($mybb->input['issue'])
{
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
	if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $issue['uid'])
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
	
	// Breadcrumbs
	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($issue['project'], get_project_link($issue['projid']));
	add_breadcrumb($lang->dash_lower_issue." #".$issue['issid'], get_issue_url($issue['issid']));
	add_breadcrumb($lang->iss_p_comments);
	
	// Do we show the new comment box?
	if($mybb->user['uid'] && $issue['allowcomments'] == 1)
	{
		eval("\$new_comment = \"".$templates->get("mytracker_issue_newcomment")."\";");
	}
	
	// Show all comments (bar the first - it's the issue, remember!)
	require_once MYBB_ROOT."inc/class_parser.php"; $parser = new postParser;
	$options = array(
			'allow_html' => 'no', 
			'filter_badwords' => 'yes', 
			'allow_mycode' => 'yes', 
			'allow_smilies' => 'yes', 
			'nl2br' => 'yes', 
			'me_username' => 'yes'
	);
	$query = $db->query("
		SELECT p.*, u.avatar, u.avatartype
		FROM ".TABLE_PREFIX."tracker_issuesposts p
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		WHERE issid = '".$issue['issid']."' AND isspid NOT IN (".$issue['firstpost'].")
		".$where."
		ORDER BY dateline DESC"
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
				$mod_actions = " | <a href=\"comments.php?issue=".$comment['issid']."&amp;action=edit&amp;all=true&amp;pid=".$comment['isspid']."#comm".$comment['isspid']."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=editcomment&amp;issue={$issue['issid']}&amp;pid={$comment['isspid']}&amp;all=true' });\">{$lang->iss_edit_comment}</a> &middot; <a href=\"issues.php?issue={$issue['issid']}&amp;action=delete_comment&amp;pid={$comment['isspid']}&amp;ydelete=1\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=deletecomment&amp;issue={$issue['issid']}&amp;pid={$comment['isspid']}' });\">$lang->iss_delete_comment</a> ";
			}
			else
			{
				$mod_actions = "&nbsp;";
			}
			
			// Has this been edited?
			if($comment['edittime'])
			{
				$editor = "<a href=\"".get_user_url($comment['edituid'])."\">".htmlspecialchars_uni($comment['edituser'])."</a>";
				$lang->iss_edit_details = $lang->sprintf($lang->iss_edit_details, $editor, my_date($mybb->settings['dateformat'], $comment['edittime']), my_date($mybb->settings['timeformat'], $comment['edittime']));
				$edit_details = "<br /><br /><em>{$lang->iss_edit_details}</em>";
			}
			
			if($mybb->input['action'] == "edit" && $mybb->input['pid'] == $comment['isspid'])
			{
				eval("\$issue_comments .= \"".$templates->get("mytracker_issue_commentinedit")."\";");
			}
			else
			{
				eval("\$issue_comments .= \"".$templates->get("mytracker_issue_comments")."\";");
			}
		}
	}
	else
	{
		$issue_comments = $lang->iss_comments_none;
	}

	$timeline_link = get_timeline_url($issue['issid']);
	$comment_link = get_comments_url($issue['issid']);

	$lang->iss_comment_info = $lang->sprintf($lang->iss_comment_info, $timeline_link);

	// Extra tabs
	$sub_tabs['issue'] = array(
		'title' => "".$lang->dash_lower_issue." #".$issue['issid']."",
		'link' => get_issue_url($issue['issid']),
	);
	$sub_tabs['comments'] = array(
		'title' => $lang->iss_p_comments,
		'link' => $comment_link,
		'description' => "".$lang->iss_comments_for." ".$lang->dash_lower_issue." #".$issue['issid'].""
	);
}
elseif($mybb->input['feature'])
{
	// Yes, there's issue things in here. But it saves space in the database and stuff
	// and helps prevent global warming.
	
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
		error($lang->iss_no_issue);
	}
	else
	{
		$feature = $db->fetch_array($query);
	}
	
	// Check moderator
	if($mybb->usergroup['canmodtrack'] == 1 || $mybb->user['developer'] == 1 || $mybb->user['uid'] == $feature['uid'])
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
		error($lang->fea_no_feature);
	}

	// Breadcrumbs
	add_breadcrumb($mybb->settings['trackername'], "./");
	add_breadcrumb($feature['project'], get_project_link($feature['projid'], 'features'));
	add_breadcrumb($lang->dash_lower_feature." #".$feature['featid'], get_feature_url($feature['featid']));
	add_breadcrumb($lang->iss_p_comments);

	// Do we show the new comment box?
	if($mybb->user['uid'] && $feature['allowcomments'] == 1)
	{
		$query = $db->simple_select("tracker_featuresposts", "uid", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'", array("order_by" => "dateline", "order_dir" => "DESC", "limit" => 1));
		$poster_uid = $db->fetch_field($query, "uid");
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'] || $mybb->user['uid'] != $poster_uid)
		{
			eval("\$new_comment = \"".$templates->get("mytracker_feature_newcomment")."\";");
		}
	}

	// Show all comments (bar the first - it's the issue, remember!)
	require_once MYBB_ROOT."inc/class_parser.php"; $parser = new postParser;
	$options = array(
		'allow_html' => 'no', 
		'filter_badwords' => 'yes', 
		'allow_mycode' => 'yes', 
		'allow_smilies' => 'yes', 
		'nl2br' => 'yes', 
		'me_username' => 'yes'
	);
	$query = $db->query("
		SELECT p.*, u.avatar
		FROM ".TABLE_PREFIX."tracker_featuresposts p
		LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
		WHERE featid = '".$feature['featid']."' AND featpid NOT IN (".$feature['firstpost'].")
		".$where."
		ORDER BY dateline DESC"
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
			
			// Do some conversions - this will probably change in 1.2
			
			// Get a user avatar, show in nice bubble
			if($comment['avatar']) // Show user avatar
			{
				$user_avatar = htmlspecialchars_uni($comment['avatar']);
				$avatar = "<img src=\"".$user_avatar."\" alt=\"\" width=\"40\" height=\"40\" style=\"padding-top: 7px;\" />";
			}
			else // Show default avatar
			{
				$avatar = "<img src=\"{$mybb->settings['bburl']}/images/default_avatar.gif\" alt=\"\" width=\"40\" height=\"40\" style=\"padding: 7px;\" />";
			}
	
			// Mod Actions
			if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
			{
				$mod_actions = " | <a href=\"comments.php?feature=".$comment['featid']."&amp;action=edit&amp;pid=".$comment['featpid']."#comm".$comment['featpid']."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=editcomment&amp;feature={$feature['featid']}&amp;pid={$comment['featpid']}&amp;all=1' });\">{$lang->iss_edit_comment}</a> &middot; <a href=\"features.php?feature={$feature['featid']}&amp;action=delete_comment&amp;pid={$comment['featpid']}&amp;ydelete=1\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=deletecomment&amp;feature={$feature['featid']}&amp;pid={$comment['featpid']}' });\">$lang->iss_delete_comment</a>";
			}
			else
			{
				$mod_actions = "&nbsp;";
			}
			
			// Has this been edited?
			if($comment['edittime'])
			{
				$editor = "<a href=\"".get_user_url($comment['edituid'])."\">".htmlspecialchars_uni($comment['edituser'])."</a>";
				$lang->iss_edit_details = $lang->sprintf($lang->iss_edit_details, $editor, my_date($mybb->settings['dateformat'], $comment['edittime']), my_date($mybb->settings['timeformat'], $comment['edittime']));
				$edit_details = "<br /><br /><em>{$lang->iss_edit_details}</em>";
			}
			
			if($mybb->input['action'] == "edit" && $mybb->input['pid'] == $comment['featpid'])
			{
				eval("\$issue_comments .= \"".$templates->get("mytracker_feature_commentinedit")."\";");
			}
			else
			{
				eval("\$issue_comments .= \"".$templates->get("mytracker_feature_comments")."\";");
			}
			
		}
	}
	else
	{
		$issue_comments = $lang->iss_comments_none;
	}
	
	$comment_link = get_features_comments_url($feature['featid']);
	$lang->iss_comment_info = $lang->fea_comment_info;

	// Extra tabs
	$sub_tabs['issue'] = array(
		'title' => "".$lang->dash_lower_feature." #".$feature['featid']."",
		'link' => get_feature_url($feature['featid']),
	);
	$sub_tabs['comments'] = array(
		'title' => $lang->iss_p_comments,
		'link' => $comment_link,
		'description' => "".$lang->iss_comments_for." ".$lang->dash_lower_feature." #".$feature['featid'].""
	);
}

// Output content
$menu = output_nav_tabs($sub_tabs, 'comments', true);
eval("\$content = \"".$templates->get("mytracker_comments_content")."\";");
eval("\$comments_index = \"".$templates->get("mytracker_comments")."\";");

// Output the page
output_page($comments_index);
?>