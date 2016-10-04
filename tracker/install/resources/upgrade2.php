<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: $
+--------------------------------------------------------------------------
*/
/**
 * Upgrade Script: 1.0.0
 */

$upgrade_detail = array(
	"requires_deactivated_plugins" => 0
);

$version_detail = array(
	"version" => "1.0.0",
	"version_code" => "1000",
	"last_check" => time(),
	"latest_version" => "1.0.1",
	"latest_version_code" => "1001"
);

function upgrade2_dbchanges()
{
	global $db, $output;

	$output->print_header("Upgrading MyTracker");
	$contents .= "<p>Making necessary database modifications...";

	// Update the version cache
	$version_detail = array(
		"version" => "1.0.1",
		"version_code" => "1001",
		"last_check" => TIME_NOW,
		"latest_version" => "1.0.1",
		"latest_version_code" => "1001"
	);
	$cache_contents = serialize($version_detail);
	$replace_array = array(
		"title" => "trackerversion",
		"cache" => $cache_contents
	);
	$db->replace_query("datacache", $replace_array, "title", false);

	$contents .= " done!</p>";
	$contents .= $lang->upgrade_template_resync;

	$output->print_contents("$contents<p>Please click next to continue with the upgrade process.</p>");
	$output->print_footer("2_dbchanges2");
}

function upgrade2_dbchanges2()
{
	global $db, $mybb, $output;

	$output->print_header("Resyncing Stylesheets");
	$contents .= "<p>Attempting to sync the forum's stylesheets with the cache...";

	require_once MYBB_ROOT."admin/inc/functions_themes.php";
	$query = $db->simple_select("themestylesheets", "*");
	while($stylesheet = $db->fetch_array($query))
	{
		$themes[] = $stylesheet['tid'];
		cache_stylesheet($stylesheet['tid'], $stylesheet['cachefile'], $stylesheet['stylesheet']);
		resync_stylesheet($stylesheet);
	}
	foreach($themes as $up_theme)
	{
		update_theme_stylesheet_list($up_theme);
	}

	$contents .= " done!</p>";
	$contents .= $lang->upgrade_template_resync_complete;

	$output->print_contents("$contents<p>Please click next to continue with the upgrade process.</p>");
	$output->print_footer("2_done");
}
?>