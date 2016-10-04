<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: index.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/index.php');
$templatelist = "mytrackercp_mainmenu, mytrackercp_index";

chdir(dirname(dirname(dirname(__FILE__))));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

add_breadcrumb($mybb->settings['trackername'], "../");
add_breadcrumb($lang->trackercp);

// Verify that we can access the CP
if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
{
	$ismod = true;
}
else
{
	error_no_permission();
}

$user_version = $cache->read('trackerversion'); // Read the cache for the user's version

// Check for a new version if we haven't checked for two weeks
// Please note: this is done "on-the-fly" automatically when a user visits the Control Panel
// (if javascript is on). The following is a fall-back in case something goes wrong
$threshold = TIME_NOW - 1209600; // Two weeks!
if($user_version['last_check'] < $threshold || $mybb->input['action'] == "checkversion")
{
	require_once MYBB_ROOT."inc/class_xml.php";
	$contents = fetch_remote_file("http://resources.xekko.co.uk/versioncheck.php");
	if($contents)
	{
		$parser = new XMLParser($contents);
		$check = $parser->get_tree();

		$latest_code = $check['version']['version_code']['value'];
		$latest_version = $check['version']['latest_version']['value'];
		if($latest_code > $user_version['version_code'])
		{
			$version_warning = 1;
			$updated_cache['version'] = $user_version['version'];
			$updated_cache['version_code'] = $user_version['version_code'];
			$updated_cache['last_check'] = TIME_NOW;
			$updated_cache['latest_version'] = $latest_version;
			$updated_cache['latest_version_code'] = $latest_code;
			
			$cache->update("trackerversion", $updated_cache);
		}

		$user_version = $cache->read('trackerversion'); // Re-read the cache for the latest info
	}
}

if($version_warning == 1 || $user_version['version_code'] != $user_version['latest_version_code'])
{
	// The version is out of date - show update link
	$lang->outdated_version = $lang->sprintf($lang->outdated_version, $user_version['latest_version'], $user_version['latest_version_code']);
	$version_text = "<div id=\"flash_message\" class=\"error\">{$lang->outdated_version}<br /><br /><p>{$lang->update_link}</p></div>";
}
else
{
	// Version is up to date, show nice green message
	if($mybb->input['action'] == "checkversion")
	{
		$lang->updatecheck = $lang->checked_for_updates."<br />";
		$lang->uptodate_version = $lang->uptodate_version_checked;
	}
	$version_text = "<div id=\"flash_message\" class=\"success\">{$lang->updatecheck}{$lang->uptodate_version}</div>";
}

$news = $cache->read("trackernews");
$xekko_news = $news['content'];

// Get the 5 stats in one massov query eyyy! And then array them... then number format them...
$query = $db->query("
	SELECT
	(SELECT COUNT(issid) FROM ".TABLE_PREFIX."tracker_issues) AS issues,
	(SELECT COUNT(isspid) FROM ".TABLE_PREFIX."tracker_issuesposts) AS issue_comments,
	(SELECT COUNT(featid) FROM ".TABLE_PREFIX."tracker_features) AS ideas,
	(SELECT COUNT(featpid) FROM ".TABLE_PREFIX."tracker_featuresposts) AS idea_comments
");
$stats = $db->fetch_array($query);
$number_issues = my_number_format($stats['issues']);
$number_ideas = my_number_format($stats['ideas']);
// Figure out the comments > (issue_comments - issues) + (idea_comments - ideas) (because the first posts aren't comments!)
$comment_math = ($stats['issue_comments'] - $stats['issues']) + ($stats['idea_comments'] - $stats['ideas']);
$number_comments = my_number_format($comment_math);

// Let's figure out how many are new today
$threshold = TIME_NOW - 86400;
$query = $db->query("
	SELECT
	(SELECT COUNT(i.issid) FROM ".TABLE_PREFIX."tracker_issues i WHERE dateline > '$threshold') AS issues,
	(SELECT COUNT(ip.isspid) FROM ".TABLE_PREFIX."tracker_issuesposts ip WHERE dateline > '$threshold') AS issue_comments,
	(SELECT COUNT(f.featid) FROM ".TABLE_PREFIX."tracker_features f WHERE dateline > '$threshold') AS ideas,
	(SELECT COUNT(fp.featpid) FROM ".TABLE_PREFIX."tracker_featuresposts fp WHERE dateline > '$threshold') AS idea_comments
");
$today_stats = $db->fetch_array($query);
// Today's Issues
$lang->issues_new_today = $lang->sprintf($lang->issues_new_today, my_number_format($today_stats['issues']));
// Today's Ideas
$lang->ideas_new_today = $lang->sprintf($lang->ideas_new_today, my_number_format($today_stats['ideas']));
// Today's Comments
$new_comment_math = ($today_stats['issue_comments'] - $today_stats['issues']) + ($today_stats['idea_comments'] - $today_stats['ideas']);
$lang->comments_new_today = $lang->sprintf($lang->comments_new_today, my_number_format($new_comment_math));

// The Main Menu (the left menu from the ACP)
$home_active = "active"; // We're on the index page!
$h_raquo = "&raquo ";
eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");

eval("\$trackercp_index = \"".$templates->get("mytrackercp_index")."\";");
output_page($trackercp_index);
?>