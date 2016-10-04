<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: connect.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
// This file is loaded through jQuery. It checks ("on-the-fly") to see if there's a new
// version of MyTracker, and to shamelessly plug Xekko news.

define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/connect.php');
$templatelist = "";

chdir(dirname(dirname(dirname(__FILE__))));
require_once "./inc/init.php";
require_once "./inc/functions_tracker.php";

$user_version = $cache->read('trackerversion');
// Is there a new version?
$vcheck = fetch_remote_file("http://resources.xekko.co.uk/newversion.php");
if($vcheck && $vcheck != $user_version['latest_version_code'] && $user_version['version_code'] == $user_version['latest_version_code'])
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
	}
}

// Quick check to Xekko to see if there is any new news
// Note: Cheating to make sure that if there is a new version, it will show automatically in the Control Panel
$news = fetch_remote_file("http://resources.xekko.co.uk/latestnews.php?action=getlatest");
if($news)
{
	$cached_news = $cache->read("trackernews");
	if($cached_news != $news)
	{
		$cache->update("trackernews", array("content" => $db->escape_string($news)));
	}
	print($news); // We echo the news, because it updates the front page
}
?>