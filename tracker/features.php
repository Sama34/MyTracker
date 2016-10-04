<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: features.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/features.php');
$templatelist = "mytracker_issue_timeline, mytracker_issue_comments, mytracker_issue_button_edit, mytracker_issue_button_visibleoff, mytracker_feature_comments, mytracker_feature_voting, mytracker_feature_content, mytracker_feature, mytracker_feature_button_edit, mytracker_feature_button_delete, mytracker_feature_button_visibleoff";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

// Say hello to my little fwend...
$mybb->input['feature'] = intval($mybb->input['feature']);

$query = $db->simple_select("tracker_features", "*", "featid = '".$mybb->input['feature']."'", array("LIMIT" => 1));

if(!$mybb->input['feature'] || !$db->num_rows($query))
{
	error($lang->fea_no_feature);
}
else
{
	$feature = $db->fetch_array($query);
}

// Moderator or original author?
if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
{
	$moderator = true;
	$where = '';
}
else
{
	$moderator = false;
	$where = " AND visible = '1'";
}

// Update when we've read this issue (if a member, and if it's new!)
if($mybb->user['uid'])
{
	$query = $db->simple_select("tracker_featuresread", "dateline", "uid = '".$mybb->user['uid']."' AND featid = '".$feature['featid']."'", array("limit" => 1));
	$feature_read = $db->fetch_field($query, "dateline");
	if(!$db->num_rows($query))
	{
		// No row in database, insert into table
		$insert_array = array(
			'featid' => $feature['featid'],
			'uid' => $mybb->user['uid'],
			'dateline' => TIME_NOW
		);
		$db->insert_query("tracker_featuresread", $insert_array);
	}
	else
	{
		if($feature_read <= $feature['lastpost'])
		{
			// This issue has a new post since the user has last read it - update the time
			$update_array = array(
				'dateline' => TIME_NOW
			);
			$db->update_query("tracker_featuresread", $update_array, "uid = '".$mybb->user['uid']."' AND featid = '".$feature['featid']."'");
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
			$db->update_query("tracker_features", $update, "featid = '".$feature['featid']."'");
			$feature['visible'] = 0; // To stop 'double-loading' effect
		}
		// ...else requesting to go visible and issue is currently invisible
		if($mybb->input['visibility'] == "on")
		{
			$update = array(
				"visible" => 1
			);
			$db->update_query("tracker_features", $update, "featid = '".$feature['featid']."'");
			$feature['visible'] = 1;
		}
		if($mybb->input['req'] != "ajax")
		{
			redirect("".get_feature_url($feature['featid'])."", $lang->fea_redirect);
		}
	}
}

// Is this issue visible?
if($feature['visible'] == 0)
{
	if($moderator == true)
	{
		$feature['visiblestyle'] = " class=\"trow_shaded\" style=\"border:1px solid #CCCCCC;\"";
	}
	else
	{
		error($lang->fea_no_feature); // If it's invisible, and not able to moderate this issue, then block the user
	}
}

// Update the views
$db->update_query("tracker_features", array("views" => $feature['views']+1), "featid = '".$feature['featid']."'", true);
++$feature['views'];

// Get the Project info for this Feature
$query = $db->simple_select("tracker_projects", "proid, name, active", "proid = '".$feature['projid']."'", array("limit" => 1));
$project = $db->fetch_array($query);

if($project['active'] == 0 && $moderator == false)
{
	error($lang->fea_no_feature);
}

// Add the project to the new issue link
if($sub_tabs['newbug'])
{
	$sub_tabs['newbug']['link'] = $sub_tabs['newbug']['link']."&amp;project=".$project['proid']."";
}

//---------------------------------------------------
// Posting 'Comments'
//---------------------------------------------------
if($mybb->input['action'] == "new_comment" && $mybb->request_method == "post")
{
	if($feature['allowcomments'] != 1 || !$mybb->user['uid'])
	{
		error($lang->iss_notcommallowed);
	}
	else
	{
		$query = $db->simple_select("tracker_featuresposts", "uid", "featid = '".$feature['featid']."'", array("limit" => 1, "order_by" => 'dateline', "order_dir" => 'DESC'));
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
			"featid" => $feature['featid'],
			"projid" => $feature['projid'],
			"uid" => $mybb->user['uid'],
			"username" => $db->escape_string($mybb->user['username']),
			"dateline" => TIME_NOW,
			"message" => $db->escape_string($mybb->input['comment']),
			"visible" => 1
		);
		$db->insert_query("tracker_featuresposts", $comment);
		
		// Update the bug - but only if it's visible!
		if($feature['visible'])
		{
			$update_array = array(
				"replies" => $feature['replies']+1,
				"lastpost" => TIME_NOW,
				"lastposter" => $db->escape_string($mybb->user['username']),
				"lastposteruid" => $mybb->user['uid']
			);

			// Update
			$db->update_query("tracker_features", $update_array, "featid = '".$feature['featid']."'");
			$feature['replies'] = $update_array['replies'];
		}
		// We're creating a comment, so add it to the activity
		// Shorten the message for the activity
		if(my_strlen($mybb->input['comment']) > 50)
		{
			$comment['message'] = my_substr($comment['message'], 0, 50)."...";
		}
		$update_array = array(
			"action" => 1,
			"issid" => $comment['featid'],
			"feature" => 1,
			"content" => $db->escape_string($comment['message']),
			"uid" => $mybb->user['uid'],
			"username" => $db->escape_string($mybb->user['username']),
			"dateline" => TIME_NOW,
			"visible" => 1
		);
		$db->insert_query("tracker_activity", $update_array);

		$mybb->settings['redirects'] = 0; // This (temporarily) removes the "friendly" redirection - "quick" posting
		redirect("".get_feature_url($feature['featid'])."", $lang->iss_added_comment);
	}
}

// Deleting a Comment
if($mybb->input['action'] == "delete_comment")
{
	if($moderator == true)
	{
		if($mybb->input['ydelete'])
		{
			// Delete Comment
			$db->delete_query("tracker_featuresposts", "featpid = '".intval($mybb->input['pid'])."' AND featid = '".$feature['featid']."'");
			$update_array = array();
			$update_array['replies'] = $feature['replies']-1;
			
			// Delete any activity for this comment
			$query = $db->simple_select("tracker_activity", "issid", "action = '1' AND feature = '1' AND issid = '".$feature['featid']."'");
			if($db->num_rows($query))
			{
				$db->delete_query("tracker_activity", "action = '1' AND feature = '1' AND issid = '".$feature['featid']."'");
			}

			// Update
			$db->update_query("tracker_features", $update_array, "featid = '".$feature['featid']."'");
			$feature['replies'] = $update_array['replies'];
			$mybb->settings['redirects'] = 0; // Quick post!
			redirect("".get_feature_url($feature['featid'])."", $lang->iss_comment_deleted);
		}
		elseif($mybb->input['ndelete'])
		{
			// Do no nothin'!
			// This probably should never appear with javascript, but there's some cunning idiots out there...
			redirect("".get_feature_url($feature['featid'])."", $lang->iss_no_action);
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
	$fpid = intval($mybb->input['pid']);
	$fid = intval($mybb->input['feature']);

	// Check for a guest, or not enough info
	if(!$mybb->user['uid'] || !$fpid || !$fid)
	{
		error_no_permission();
	}

	// Get the comment
	$query = $db->simple_select("tracker_featuresposts", "*", "featpid = '".$fpid."' AND featid = '".$fid."'", array("limit" => 1));	
	if(!$db->num_rows($query))
	{
		error($lang->fea_no_feature);
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
	$db->update_query("tracker_featuresposts", $update_array, "featpid = '".$fpid."' AND featid = '".$fid."'");

	if($mybb->input['all'])
	{
		// Came from "all comments", so send them back there
		redirect("".get_features_comments_url($fid)."#comm".$fpid."", $lang->ed_updated);
	}
	else
	{
		// else back to the field with you...
		redirect("".get_feature_url($fid)."", $lang->ed_updated);
	}
}


// Voting for Idea Suggestions
// Only for members, non banned groups and most certainly not for the original poster(!)
if($mybb->user['uid'] != 0 && $mybb->usergroup['isbannedgroup'] != 1 && $mybb->user['uid'] != $feature['uid'])
{
	if($mybb->input['action'] == "voteagainst")
	{
		$query = $db->simple_select("tracker_featuresvotes", "fvid", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'");
		$vote_array = array(
			"featid" => $feature['featid'],
			"uid" => $mybb->user['uid'],
			"for" => '0',
			"against" => '1'
		);
		if(!$db->num_rows($query))
		{
			$db->insert_query("tracker_featuresvotes", $vote_array);
			$db->update_query("tracker_features", array("votesagainst" => $feature['votesagainst']+1), "featid = '".$feature['featid']."'", true);
		}
		elseif($db->num_rows($query))
		{
			$fvid = $db->fetch_field($query, "fvid");
			$db->update_query("tracker_featuresvotes", $vote_array, "fvid = '".$fvid."'");
			$db->update_query("tracker_features", array("votesfor" => $feature['votesfor']-1, "votesagainst" => $feature['votesagainst']+1), "featid = '".$feature['featid']."'", true);
		}
		redirect("".get_feature_url($feature['featid'])."", $lang->fea_voted_against);
	}
	elseif($mybb->input['action'] == "votefor")
	{
		$query = $db->simple_select("tracker_featuresvotes", "fvid", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'");
		$vote_array = array(
			"featid" => $feature['featid'],
			"uid" => $mybb->user['uid'],
			"for" => '1',
			"against" => '0'
		);
		if(!$db->num_rows($query))
		{
			$db->insert_query("tracker_featuresvotes", $vote_array);
			$db->update_query("tracker_features", array("votesfor" => $feature['votesfor']+1), "featid = '".$feature['featid']."'", true);
		}
		elseif($db->num_rows($query))
		{
			$fvid = $db->fetch_field($query, "fvid");
			$db->update_query("tracker_featuresvotes", $vote_array, "fvid = '".$fvid."'");
			$db->update_query("tracker_features", array("votesfor" => $feature['votesfor']+1, "votesagainst" => $feature['votesagainst']-1), "featid = '".$feature['featid']."'", true);
		}
		redirect("".get_feature_url($feature['featid'])."", $lang->fea_voted_for);
	}
	elseif($mybb->input['action'] == "removevote")
	{
		$query = $db->simple_select("tracker_featuresvotes", "*", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'");
		if($db->num_rows($query))
		{
			$voting = $db->fetch_array($query);
			if($voting['for'])
			{
				$db->update_query("tracker_features", array("votesfor" => $feature['votesfor']-1), "featid = '".$feature['featid']."'", true);
			}
			elseif($voting['against'])
			{
				$db->update_query("tracker_features", array("votesagainst" => $feature['votesagainst']-1), "featid = '".$feature['featid']."'", true);
			}
			$db->delete_query("tracker_featuresvotes", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'");
			$mybb->settings['redirects'] = 0;
			redirect("".get_feature_url($feature['featid'])."");
		}
	}
}

// Show the options for members only
if($mybb->user['uid'])
{
	$feature_buttons = '<div class="float_right">';
	if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'] || $mybb->user['uid'] == $issue['uid'])
	{
		// Edit button
		eval("\$feature_buttons .= \"".$templates->get("mytracker_feature_button_edit")."\";");
		// Delete button
		eval("\$feature_buttons .= \"".$templates->get("mytracker_feature_button_delete")."\";");
		// Visibility button (only for mods/devs)
		if($feature['visible'] == 1 && ($mybb->usergroup['canmodtrack'] || $mybb->user['developer']))
		{
			if($mybb->input['req'] == "ajax")
			{
				$visbutton_link = "javascript:;"; // If we've used ajax, then turn the link jscript
			}
			else
			{
				$visbutton_link = "features.php?feature={$feature['featid']}&amp;visibility=off"; // Otherwise let jscript do the work
			}
			eval("\$feature_buttons .= \"".$templates->get("mytracker_feature_button_visibleoff")."\";");
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
			eval("\$feature_buttons .= \"".$templates->get("mytracker_feature_button_visibleon")."\";");
		}
	}
	$feature_buttons .= "</div>";
}

// Sort out some nasty pasties
$feature['reported'] = my_date($mybb->settings['dateformat'], $feature['dateline']).", ".my_date($mybb->settings['timeformat'], $feature['dateline']);
$feature['reportedby'] = "<a href=\"".get_user_url($feature['uid'])."\">".htmlspecialchars_uni($feature['username'])."</a>";
$feature['subject'] = htmlspecialchars_uni($feature['subject']);
$feature['comments'] = my_number_format($feature['replies']);

add_breadcrumb($mybb->settings['trackername'], "./");
add_breadcrumb($project['name'], get_project_link($project['proid'], 'features'));
add_breadcrumb("#".$feature['featid']." &raquo; ".$feature['subject']);

// Parse the messages/comments
require_once MYBB_ROOT."inc/class_parser.php"; $parser = new postParser;
$options = array(
	'allow_html' => 'no', 
	'filter_badwords' => 'yes', 
	'allow_mycode' => 'yes', 
	'allow_smilies' => 'yes', 
	'nl2br' => 'yes', 
	'me_username' => 'yes'
);

// Firstpost / Description
$query = $db->simple_select("tracker_featuresposts", "*", "featpid = '".$feature['firstpost']."'", array("limit" => 1));
$firstpost = $db->fetch_array($query);

// Parse firstpost with ze uber-parser
$feature['message'] = $parser->parse_message($firstpost['message'], $options);

// Grab the comments
$query = $db->query("
	SELECT p.*, u.avatar, u.avatartype
	FROM ".TABLE_PREFIX."tracker_featuresposts p
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
	WHERE featid = '".$feature['featid']."'
	AND featpid NOT IN (".$feature['firstpost'].")
	AND message != ''
	".$where."
	ORDER BY dateline DESC
	LIMIT 0, 4"
);
if(!$db->num_rows($query))
{
	$feature_comments = "<br /><br />".$lang->fea_comments_none;
}
else
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
		
		if($moderator == true || $comment['uid'] == $mybb->user['uid'])
		{
			$mod_actions = " | <a href=\"comments.php?feature=".$comment['featid']."&amp;action=edit&amp;pid=".$comment['featpid']."#comm".$comment['featpid']."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=editcomment&amp;feature={$feature['featid']}&amp;pid={$comment['featpid']}' });\">{$lang->iss_edit_comment}</a> &middot; <a href=\"features.php?feature={$feature['featid']}&amp;action=delete_comment&amp;pid={$comment['featpid']}&amp;ydelete=1\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=deletecomment&amp;feature={$feature['featid']}&amp;pid={$comment['featpid']}' });\">$lang->iss_delete_comment</a>";
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

		eval("\$feature_comments .= \"".$templates->get("mytracker_feature_comments")."\";");
	}
}

// Do we have enough comments to choose from?
if($feature['replies'] > 5)
{
	$more_comments = " &middot; <a href=\"".get_features_comments_url($feature['featid'])."\">{$lang->iss_viewall}</a>";
}

// Can this user leave a comment?
if($feature['allowcomments'] == 1 && $mybb->user['uid'])
{
	if($mybb->input['req'] == "ajax")
	{
		$link = "javascript:;";
	}
	else
	{
		$link = get_features_comments_url($feature['featid']);
	}
	$leave_comments = " &middot; <a href=\"".$link."\" name=\"jscript\" onclick=\"jQuery.facebox({ ajax: 'tracker_misc.php?action=addcomment&amp;feature={$feature['featid']}' });\">{$lang->iss_addcomm}</a>";
}

// Vote on features and display the info
// Votes For / Against the idea
if($feature['votesfor'] == 0)
{
	$feature['calcvotesfor'] = 1;
}
else
{
	$feature['calcvotesfor'] = intval($feature['votesfor']);
}
if($feature['votesagainst'] == 0)
{
	$feature['calcvotesagainst'] = 1;
}
else
{
	$feature['calcvotesagainst'] = intval($feature['votesagainst']);
}
// Totals (so we don't divide by 0 and because there's always 1 that likes it
$feature['calcvotestotal'] = $feature['votesagainst'] + $feature['votesfor'];
if($feature['calcvotestotal'] == 0)
{
	$feature['calcvotestotal'] = 1;
}

$math = round(($feature['calcvotesfor'] / $feature['calcvotestotal']) * 100);
$feature['percent'] = $math;
if($math >= "100")
{
	$math = "99"; // Fit nicely into the percent box
}
$feature['percentbar'] = $math;
$lang->fea_popular_info = $lang->sprintf($lang->fea_popular_info, $feature['percent'], $feature['calcvotestotal'], $feature['votesfor'], $feature['votesagainst']);

// We only need to retrieve this user's voting on the feature
$query = $db->simple_select("tracker_featuresvotes", "*", "featid = '".$feature['featid']."' AND uid = '".$mybb->user['uid']."'", array("LIMIT" => '1'));
$vote = $db->fetch_array($query);

// Create options
if($mybb->user['uid'] != $feature['uid'])
{
	$option_for = "<tr><td></td><td colspan=\"2\"><img src=\"../images/tracker/for.png\" alt=\"\" /> <a href=\"features.php?feature=".$feature['featid']."&amp;action=votefor\">{$lang->fea_do_like}</a></td></tr>";
	$option_against = "<tr><td></td><td colspan=\"2\"><img src=\"../images/tracker/against.png\" alt=\"\" /> <a href=\"features.php?feature=".$feature['featid']."&amp;action=voteagainst\">{$lang->fea_dont_like}</a></td></tr>";
	$option_both = $option_for."\n".$option_against;
	
	if($db->num_rows($query))
	{
		if($vote['for'] == 1)
		{
			// User voted that they liked this idea
			$lang->fea_vote = $lang->fea_vote_for;
			$option_for = ''; // Unset the "for" vote option
		}
		elseif($vote['against'] == 1)
		{
			$lang->fea_vote = $lang->fea_vote_against;
			$option_against = '';
		}
		$option_both = ''; // As a result, we don't want both options appearing
		$option_remove = "<tr><td colspan=\"3\"><a href=\"features.php?feature=".$feature['featid']."&amp;action=removevote\">{$lang->fea_remove}</a></td></tr>";
	}
	else
	{
		$option_for = '';
		$option_against = '';
		$lang->fea_vote = $lang->fea_vote_none;
	}
}
eval("\$feature_voting = \"".$templates->get("mytracker_feature_voting")."\";");

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
	'link' => get_project_link($project['proid'], 'features')
);
$sub_tabs['feature'] = array(
	'title' => "".$lang->dash_lower_feature." #".$feature['featid']."",
	'link' => get_feature_url($feature['featid']),
	'description' => $lang->fea_tab_info = $lang->sprintf($lang->fea_tab_info, $feature['featid'], $feature['subject'])
);

$menu = output_nav_tabs($sub_tabs, 'feature', true);
if($mybb->input['req'] == "ajax")
{
	eval("\$feature_index = \"".$templates->get("mytracker_feature_content")."\";"); // We're just wanting the content to change(!)
}
else
{
	eval("\$content = \"".$templates->get("mytracker_feature_content")."\";");
	eval("\$feature_index = \"".$templates->get("mytracker_feature")."\";");
}

// Output the page
output_page($feature_index);
?>