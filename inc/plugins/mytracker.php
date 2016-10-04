<?php
/*
+--------------------------------------------------------------------------
|   MyTracker
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   (c) 2009 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mytracker.php 12 2009-10-05 15:34:01Z Tomm $
|
|	Plugin file for MyTracker
|	A simple bug / project tracking system for MyBB
+--------------------------------------------------------------------------
*/
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Admin Stuff
$plugins->add_hook("admin_style_templates_set", "mytracker_templates");

// Only add this hook if the plugin is being used
if(IN_TRACKER == 1 || IN_TRACKER_CP == 1)
{
	// Only if we're in the tracker!
	$plugins->add_hook("global_end", "mytracker_global_end");
}

$plugins->add_hook("pre_output_page", "mytracker_scripting");
$plugins->add_hook("misc_start", "mytracker_misc");
$plugins->add_hook("fetch_wol_activity_end", "mytracker_friendly_wol");
$plugins->add_hook("build_friendly_wol_location_end", "mytracker_build_wol");

//---------------------------------------------------
// mytracker Info
//---------------------------------------------------
function mytracker_info()
{
	return array(
		"name"			=> "MyTracker",
		"description"	=> "A Bug / Project Tracker for MyBB",
		"website"		=> "http://xekko.co.uk/mytracker/",
		"author"		=> "Tomm M</a> &raquo; <a href=\"http://xekko.co.uk/\">Xekko",
		"authorsite"	=> "http://community.mybboard.net/user-14621.html",
		"version"		=> "1.1",
		"guid" 			=> "261655b4258f18dcf3e6edc5224deff6",
		"compatibility" => "18*"
	);
}

//---------------------------------------------------
// Global things to do when you're in mytracker
//---------------------------------------------------
// Only accessed when in the tracker directory!
function mytracker_global_end()
{
	global $db, $lang;
	
	$lang->load("mytracker");
	$lang->toplinks_mytracker = $mybb->settings['trackername'];

	if(!$db->table_exists("tracker_activity") || !$db->table_exists("tracker_status"))
	{
		$base_dir = basename(dirname(__FILE__));
		$error = "mytracker doesn't seem to be installed.<br />\n<ul>\n";
		$error .= "\t<li><a href=\"install/\">Install mytracker</a></li>\n";
		$error($error);
	}

	if(IN_TRACKER_CP == 1)
	{
		$lang->load("mytrackercp");
	}
}

//---------------------------------------------------
// Show Tracker templates in template list
//---------------------------------------------------
function mytracker_templates()
{
	global $lang;
	$lang->load("tracker_all");
}

//---------------------------------------------------
// This hook is only used if in the Tracker, and not
// round about the Forum
//---------------------------------------------------
function mytracker_scripting($page)
{
	global $mybb;
	if(IN_TRACKER == 1)
	{
		// Powered by message
		// For legal use, DO NOT remove this line.
		// A little link is nothing to ask for in return for using this software, is it?
		$page = str_replace("<div id=\"debug\">", "<div id=\"debug\">Tracking by Forum Authority", $page);

		// Replace the Private Message text, simply because it will head for the tracker directory
		if($mybb->user['pmnotify'] && $mybb->user['pmnotice'] == 2)
		{
			$page = str_replace(" from <a href=\"user-\">", " from <a href=\"".$mybb->settings['bburl']."/user-\">", $page);
			$page = str_replace(" from <a href=\"member.php?action=profile&amp;uid=", " from <a href=\"".$mybb->settings['bburl']."/member.php?action=profile&amp;uid=", $page);
			$page = str_replace("private.php?action=read&amp;pmid=", "".$mybb->settings['bburl']."/private.php?action=read&amp;pmid=", $page);
		}

		return $page;
	}
}

//---------------------------------------------------
// Viewing all comments for a bug
// Viewing all history for a bug
//---------------------------------------------------
function mytracker_misc()
{
global $db, $lang, $mybb, $stylesheets, $templates, $theme;
	$lang->load("mytracker");
	// Comment history
	if($mybb->input['action'] == "commentspopup")
	{
		$mybb->input['bug'] = intval($mybb->input['bug']);
		$query = $db->simple_select("track_comments", "*", "bugid = '".$mybb->input['bug']."' AND isidea = '0'", array("order_by" => 'dateline', "order_dir" => 'DESC'));		
		while($comment = $db->fetch_array($query))
		{
			$author = build_profile_link($comment['username'], $comment['uid'], '_blank', 'if(window.opener) { window.opener.location = this.href; return false; }');
			$comment_time = my_date($mybb->settings['dateformat'], $comment['dateline']).", ".my_date($mybb->settings['timeformat'], $comment['dateline']);
			eval("\$popup_content .= \"".$templates->get("mytracker_issue_comments_popup_content")."\";");
		}		
		eval("\$headerinclude = \"".$templates->get("headerinclude")."\";"); // Wont' work without this...?...
		eval("\$comments_popup = \"".$templates->get("mytracker_issue_comments_popup")."\";");
		output_page($comments_popup);
	}
	// History... history?
	if($mybb->input['action'] == "historypopup")
	{
		$mybb->input['bug'] = intval($mybb->input['bug']);
		if($mybb->usergroup['canmodtrack'] || $mybb->user['developer'])
		{
			$query = $db->simple_select("track_history", "uid, username, dateline, content, minor", "bugID = '".$mybb->input['bug']."'", array("order_by" => 'dateline', "order_dir" => 'DESC'));
		}
		else
		{
			$query = $db->simple_select("track_history", "uid, username, dateline, content", "bugID = '".$mybb->input['bug']."' AND minor = '0'", array("order_by" => 'dateline', "order_dir" => 'DESC'));
		}
		while($history = $db->fetch_array($query))
		{
			$author = build_profile_link($history['username'], $history['uid'], '_blank', 'if(window.opener) { window.opener.location = this.href; return false; }');
			$history_time = my_date($mybb->settings['dateformat'], $history['dateline']).", ".my_date($mybb->settings['timeformat'], $history['dateline']);
			if($history['minor'])
			{
				$history['change'] = " ".$lang->edit_minor_change;
			}
			eval("\$popup_content .= \"".$templates->get("mytracker_issue_history_popup_content")."\";");
		}
		eval("\$headerinclude = \"".$templates->get("headerinclude")."\";");
		eval("\$history_popup = \"".$templates->get("mytracker_issue_history_popup")."\";");
		output_page($history_popup);
	}
}

function mytracker_friendly_wol(&$user_activity)
{
	global $mybb, $user;

	// Taken from ./inc/functions_online.php
	// First, let's grab the stuff from the database and sowt it aaaaaught...
	$split_loc = explode(".php", $user['location']);
	if(my_strpos($split_loc[0], $mybb->settings['trackdir']."/index"))
	{
		// User is at the tracker index
		$filename = "tracker_index";
		$in_tracker = true;
	}
	elseif($split_loc[0] == $user['location'])
	{
		$filename = '';
	}
	else
	{
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

	// Is there actions and other stuff?
	if($split_loc[1])
	{
		$temp = explode("&amp;", my_substr($split_loc[1], 1));
		foreach($temp as $param)
		{
			$temp2 = explode("=", $param, 2);
			$parameters[$temp2[0]] = $temp2[1];
		}
	}

	switch($filename)
	{
		case "tracker_index":
			$user_activity['activity'] = "tracker_index";
			break;
		case 'projects':
			if(is_numeric($parameters['project']))
			{
				$user_activity['proj_list'][] = $parameters['project'];
			}
			$user_activity['activity'] = "viewing_project";
			$user_activity['project'] = $parameters['project'];
			break;
		case 'issues':
			if(is_numeric($parameters['issue']))
			{
				$user_activity['issue_list'][] = $parameters['issue'];
			}
			$user_activity['activity'] = "viewing_issue";
			$user_activity['issue'] = $parameters['issue'];
			break;
		case 'features':
			if(is_numeric($parameters['feature']))
			{
				$user_activity['feats_list'][] = $parameters['feature'];
			}
			$user_activity['activity'] = "viewing_feature";
			$user_activity['feature'] = $parameters['feature'];
			break;
		case 'new':
			if($parameters['action'] == "newissue")
			{
				$user_activity['activity'] = "new_issue";
			}
			elseif($parameters['action'] == "newidea")
			{
				$user_activity['activity'] = "new_idea";
			}
			break;
	}
}

function mytracker_build_wol(&$plugin_array)
{
	global $cache, $db, $lang, $mybb, $parser, $user;
	
	// Vital checks
	$lang->load("mytracker");	
	if(!function_exists("get_project_link"))
	{
		include(MYBB_ROOT."/inc/functions_tracker.php");
	}

	// Shorten the lists
	$proj_list = $plugin_array['user_activity']['proj_list'];
	$issue_list = $plugin_array['user_activity']['issue_list'];
	$feats_list = $plugin_array['user_activity']['feats_list'];
	$activity = $plugin_array['user_activity'];

	// Any projects?
	$not_in = unviewable_projects("proid");
	if(is_array($proj_list) && count($proj_list) > 0)
	{
		$proj_sql = implode(",", $proj_list);
		$query = $db->simple_select("tracker_projects", "proid,name", "proid IN ($proj_sql) {$not_in}");
		while($project = $db->fetch_array($query))
		{
			$project['name'] = htmlspecialchars_uni($parser->parse_badwords($project['name']));
			$project['url'] = $mybb->settings['trackdir']."/".get_project_link($project['proid']);
			$projects[$project['proid']] = $project;
		}
	}
	
	// What abaut t'issues?
	$not_in_issues = unviewable_issues();
	if(is_array($issue_list) && count($issue_list) > 0)
	{
		$issue_sql = implode(",", $issue_list);
		$query = $db->simple_select("tracker_issues", "issid,subject", "issid IN ($issue_sql) {$not_in_issues}");
		while($issue = $db->fetch_array($query))
		{
			$issue['name'] = htmlspecialchars_uni($parser->parse_badwords($issue['subject']));
			$issue['url'] = $mybb->settings['trackdir']."/".get_issue_url($issue['issid']);
			$issues[$issue['issid']] = $issue;
		}
	}
	
	// Features
	$not_in_feats = unviewable_features();
	if(is_array($feats_list) && count($feats_list) > 0)
	{
		$feature_sql = implode(",", $feats_list);
		$query = $db->simple_select("tracker_features", "featid,subject", "featid IN ($feature_sql) {$not_in_feats}");
		while($feature = $db->fetch_array($query))
		{
			$feature['name'] = htmlspecialchars_uni($parser->parse_badwords($feature['subject']));
			$feature['url'] = $mybb->settings['trackdir']."/".get_feature_url($feature['featid']);
			$features[$feature['featid']] = $feature;
		}
	}

	// We're only picking up a few locations in the Tracker, so for everything else, show 'Viewing Tracker'
	if(strpos($user['location'], "tracker") !== false)
	{
		$plugin_array['location_name'] = $lang->sprintf($lang->viewing, $mybb->settings['trackdir']."/", $mybb->settings['trackername']);
	}

	// If the user's location is below, then it will override and display in the WOL list
	switch($plugin_array['user_activity']['activity'])
	{
		case "tracker_index":
			$plugin_array['location_name'] = $lang->sprintf($lang->viewing, $mybb->settings['trackdir']."/", $mybb->settings['trackername']);
			break;
		case "viewing_project":
			if($not_in == false)
			{
				$plugin_array['location_name'] = $lang->sprintf($lang->viewing_project, $projects[$activity['project']]['url'], $projects[$activity['project']]['name']);
			}
			else
			{
				$plugin_array['location_name'] = $lang->viewing_project2;
			}
			break;
		case "viewing_issue":
			if($not_in_issues == false)
			{
				$plugin_array['location_name'] = $lang->sprintf($lang->viewing_issue, $issues[$activity['issue']]['url'], $issues[$activity['issue']]['name']);
			}
			else
			{
				$plugin_array['location_name'] = $lang->viewing_issue2;
			}
			break;
		case "viewing_feature":
			if($not_in_feats == false)
			{
				$plugin_array['location_name'] = $lang->sprintf($lang->viewing_feature, $features[$activity['feature']]['url'], $features[$activity['feature']]['name']);
			}
			else
			{
				$plugin_array['location_name'] = $lang->viewing_feature2;
			}
			break;
		case "new_issue":
			$plugin_array['location_name'] = $lang->new_issue;
			break;
		case "new_idea":
			$plugin_array['location_name'] = $lang->new_idea;
			break;
	}
}
?>