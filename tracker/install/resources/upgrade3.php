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
 * Upgrade Script: 1.0.1
 */

$upgrade_detail = array(
	"requires_deactivated_plugins" => 0
);

function upgrade3_dbchanges()
{
	global $db, $output;

	$output->print_header("Upgrading MyTracker");
	$contents .= "<p>Making necessary database modifications...";

	// Update the version cache
	$version_detail = array(
		"version" => "1.0.2",
		"version_code" => "1002",
		"last_check" => TIME_NOW,
		"latest_version" => "1.0.2",
		"latest_version_code" => "1002"
	);
	$cache_contents = serialize($version_detail);
	$replace_array = array(
		"title" => "trackerversion",
		"cache" => $cache_contents
	);
	$db->replace_query("datacache", $replace_array, "title", false);

	// Replace aspects in the mytracker_project_content template with language variables
	$query = $db->simple_select("templates", "*", "title = 'mytracker_project_content'");
	while($pct = $db->fetch_array($query))
	{
		$pct['template'] = str_replace("<strong>Name</strong>", "<strong>{\$lang->pro_name}", $pct['template']);
		$pct['template'] = str_replace("<strong>Description</strong>", "<strong>{\$lang->pro_description}", $pct['template']);
		$pct['template'] = str_replace("<strong>Started</strong>", "<strong>{\$lang->pro_started}", $pct['template']);
		$pct['template'] = str_replace("<strong>Stage</strong>", "<strong>{\$lang->pro_stage}", $pct['template']);
		$pct['template'] = str_replace("<strong>Issues</strong>", "<strong>{\$lang->issues}", $pct['template']);

		$db->update_query("templates", array("template" => $db->escape_string($pct['template'])), "tid = '".$pct['tid']."'");
	}

	$contents .= " done!</p>";

	$output->print_contents("$contents<p>Please click next to continue with the upgrade process.</p>");
	$output->print_footer("3_done");
}
?>