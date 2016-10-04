<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: upgrade1.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/
/**
 * Upgrade Script: Beta 1
 */

$upgrade_detail = array(
	"requires_deactivated_plugins" => 0,
	"template_version" => 0,
	"revert_all_templates" => 1
);

$version_detail = array(
	"version" => "1.0.0 Beta",
	"version_code" => "1000",
	"last_check" => time(),
	"latest_version" => "1.0.0 Beta",
	"latest_version_code" => "1000"
);

function upgrade1_dbchanges()
{
	global $db, $output;

	$output->print_header("Database Changes");
	$contents .= "<p>Making necessary database modifications...";

	// Database alterations
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."tracker_issuesposts ADD edituser varchar(80) NOT NULL AFTER edituid", 1);

	$query = $db->simple_select("settings", "gid, disporder", "name='ideasys'");
	$cur_setting = $db->fetch_array($query);
	$current_dir = basename(dirname(dirname(dirname(__FILE__)))); // Back to the tracker's directory
	$next_disporder = $cur_setting['disporder'] + 1;
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name = 'trackdir'");
	$db->write_query("INSERT INTO ".TABLE_PREFIX."settings (name, title, description, optionscode, value, disporder, gid, isdefault) VALUES ('trackdir', 'Tracker Directory', 'Path to the MyTracker folder. Exclude the trailing slash.', 'text', '{$current_dir}', {$next_disporder}, {$cur_setting['gid']}, 1);");

	$contents .= "done</p>";
	
	$contents .= $lang->upgrade_template_reversion_success;

	$output->print_contents("$contents<p>Please click next to continue with the upgrade process.</p>");
	$output->print_footer("1_dbchanges2");
}

function upgrade1_dbchanges2()
{
	global $db, $output, $mybb;

	$output->print_header("Clearing Templates");
	echo "<p>Clearing custom templates and stylesheets...";
	flush();

	// Remove old templates that have been edited!
	$db->delete_query("templates", "title='mytrackercp_index' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_issue_button_delete' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_project_issues_content' AND sid != '-2'");
	$db->delete_query("templates", "title='mytrackercp_categories' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_comments' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_edit' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_feature' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_index' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_issue' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_new' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_newidea' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_project' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_timeline' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_issue_comments' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_feature_content' AND sid != '-2'");
	$db->delete_query("templates", "title='mytracker_issue_content' AND sid != '-2'");

	// Alter any stylesheets on the tracker with these updates
	$query = $db->simple_select("themestylesheets", "sid, stylesheet", "name = 'mytracker.css'");
	while($stylesheet = $db->fetch_array($query))
	{
		$stylesheet['stylesheet'] = str_replace("ul.menu", "ul.tracker_menu", $stylesheet['stylesheet']);
		$db->update_query("themestylesheets", array("stylesheet" => $db->escape_string($stylesheet['stylesheet']), "lastmodified" => TIME_NOW), "sid = '".$stylesheet['sid']."'");
	}

	// Attempt to fix the codebuttons template
	$db->delete_query("templates", "title = 'codebuttons' AND sid != -2");
	$query = $db->simple_select("templates", "tid, template", "title='codebuttons'");
	while($codebuttons = $db->fetch_array($query))
	{
		// Replacing these with the proper replacement - but it could be any one of these!
		$newtemplate = str_replace("//jscripts", "/jscripts", $codebuttons['template']);
		$db->update_query("templates", array("template" => $db->escape_string($newtemplate), "dateline" => TIME_NOW), "tid = '".$codebuttons['tid']."'");
	}
	
	// Update the version cache
	$version_detail = array(
		"version" => "1.0.0 Beta",
		"version_code" => "1000",
		"last_check" => TIME_NOW,
		"latest_version" => "1.0.0 Beta",
		"latest_version_code" => "1000"
	);
	$cache_contents = serialize($version_detail);
	$replace_array = array(
		"title" => "trackerversion",
		"cache" => $cache_contents
	);
	$db->replace_query("datacache", $replace_array, "title", false);

	$contents .= "done.</p>";

	$contents .= "<p>Click next to continue with the upgrade process.</p>";
	$output->print_contents($contents);
	$output->print_footer("1_done");
}
?>