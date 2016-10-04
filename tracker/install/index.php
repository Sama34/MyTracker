<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: index.php 15 2009-10-05 15:54:49Z Tomm $
|	> Installer originally written by MyBB Group © 2009 (mybboard.net)
+--------------------------------------------------------------------------
*/
@error_reporting(E_ALL & ~E_NOTICE);

@set_time_limit(0);

define('MYBB_ROOT', dirname(dirname(dirname(__FILE__)))."/");
define("INSTALL_ROOT", dirname(__FILE__)."/");
define("TIME_NOW", time());
define("IN_MYBB", 1);
define("IN_INSTALL", 1);

@include_once MYBB_ROOT.'inc/db_base.php'; // MyBB >= 1.8.4

require_once MYBB_ROOT.'inc/class_core.php';
$mybb = new MyBB;

require_once MYBB_ROOT.'inc/class_error.php';
$error_handler = new errorHandler();

// Include the files necessary for installation
require_once MYBB_ROOT.'inc/class_timers.php';
require_once MYBB_ROOT.'inc/functions.php';

// Include the installation resources
require_once INSTALL_ROOT.'resources/output.php';
$output = new installerOutput;

// Perform a check if MyBB is already installed or not
$installed = false;
if(file_exists(MYBB_ROOT."/inc/config.php"))
{
	require MYBB_ROOT."/inc/config.php";
	if(is_array($config))
	{
		$mybb_installed = true;
		$mybb->config = &$config;
	}	
}
if(file_exists("lock"))
{
	$tracker_installed = true;
}

require_once MYBB_ROOT.'inc/class_xml.php';
require_once MYBB_ROOT.'inc/functions_user.php';
require_once MYBB_ROOT.'inc/class_language.php';
$lang = new MyLanguage();
$lang->set_path(INSTALL_ROOT.'resources');
$lang->load('install');

// Considering I've only used PgSQL for 10 minutes, and had a
// 32 minute crash course in SQLite to fix a bug in MyBB, just
// for the moment, mySQL only please.
$dboptions = array();
if(function_exists('mysqli_connect'))
{
	$dboptions['mysqli'] = array(
		'class' => 'DB_MySQLi',
		'title' => 'MySQL Improved',
		'short_title' => 'MySQLi',
		'structure_file' => 'mysql_db_tables.php',
		'population_file' => 'mysql_db_inserts.php'
	);
}

if(function_exists('mysql_connect'))
{
	$dboptions['mysql'] = array(
		'class' => 'DB_MySQL',
		'title' => 'MySQL',
		'short_title' => 'MySQL',
		'structure_file' => 'mysql_db_tables.php',
		'population_file' => 'mysql_db_inserts.php'
	);
}

// This version of MyTracker?
$tracker_version = array(
	"version" => "1.0.1",
	"version_code" => "1001",
	"last_check" => TIME_NOW,
	"latest_version" => "1.0.1",
	"latest_version_code" => "1001"
);

if(file_exists("lock"))
{
	$output->print_error($lang->locked);
}
elseif($mybb_installed != true && !$mybb->input['action'])
{
	$output->print_error($lang->no_mybb);
}
elseif($tracker_installed == true && !$mybb->input['action'])
{
	$output->print_header($lang->already_installed, "errormsg", 0);
	echo $lang->sprintf($lang->mytracker_already_installed, $tracker_version['version']);
	$output->print_footer();
}
else
{
	$output->steps = array(
		'intro' => $lang->welcome,
		'license' => $lang->license_agreement,
		'requirements_check' => $lang->req_check,
		'create_tables' => $lang->table_creation,
		'populate_tables' => $lang->data_insertion,
		'templates' => $lang->theme_install,
		'final' => $lang->finish_setup,
	);

	// If we're uninstalling, attempt to remove the install file
	if($mybb->input['action'] == "uninstall")
	{
		$file_open = fopen("resources/installed", "w");
		if($file_open)
		{
			fclose($file_open);
		}
		@unlink("resources/installed");
		$mybb->input['action'] = 'do_uninstall';
	}

	if(!isset($mybb->input['action']))
	{
		$mybb->input['action'] = 'intro';
	}

	if(file_exists("resources/installed"))
	{
		// We're already installed, skip straight to the options
		install_check();
		if($mybb->input['action'] == "install_redir")
		{
			header("Location: ./upgrade.php");
			exit;
		}
	}

	switch($mybb->input['action'])
	{
		case 'do_uninstall':
			uninstall();
			break;
		case 'license':
			license_agreement();
			break;
		case 'requirements_check':
			requirements_check();
			break;
		case 'create_tables':
			create_tables();
			break;
		case 'populate_tables':
			populate_tables();
			break;
		case 'templates':
			insert_templates();
			break;
		case 'final':
			install_done();
			break;
		default:
			intro();
			break;
	}
}

function intro()
{
	global $output, $mybb, $lang, $tracker_version;
	
	$output->print_header($lang->welcome, 'welcome');
	if(strpos(strtolower($_SERVER['PHP_SELF']), "upload/") !== false)
	{
		echo $lang->sprintf($lang->mybb_incorrect_folder);
	}
	echo $lang->sprintf($lang->welcome_step, $tracker_version['version']);
	$output->print_footer('license');
}

function install_check()
{
	global $output, $mybb, $lang, $tracker_version;

	$output->print_header($lang->already_installed, 'welcome');
	echo $lang->sprintf($lang->mytracker_installed, $tracker_version['version']);
	$output->print_footer('uninstall');
}

function clear_overflow($fp, &$contents) 
{
	global $mybb;
	
	if(function_exists('gzopen')) 
	{
		gzwrite($fp, $contents);
	} 
	else 
	{
		fwrite($fp, $contents);
	}
		
	$contents = '';	
}

function uninstall()
{
	global $config, $db, $mybb, $output, $lang;

	// Attempt to connect to the db
    include_once MYBB_ROOT.'inc/db_base.php'; // MyBB >= 1.8.4
	require_once MYBB_ROOT."inc/db_{$config['database']['type']}.php";
	switch($config['database']['type'])
	{
		case "mysqli":
			$db = new DB_MySQLi;
			break;
		default:
			$db = new DB_MySQL;
	}
 	$db->error_reporting = 0;

	$connect_array = array(
		"hostname" => $config['database']['hostname'],
		"username" => $config['database']['username'],
		"password" => $config['database']['password'],
		"database" => $config['database']['database'],
		"table_prefix" => $config['database']['table_prefix']
	);

	$connection = $db->connect($connect_array);
	if(!$connection)
	{
		$errors[] = $lang->sprintf($lang->db_step_error_noconnect, $connect_array['hostname']);
	}
	// double check if the DB exists for MySQL
	elseif(method_exists($db, 'select_db') && !$db->select_db($connect_array['database']))
	{
		$errors[] = $lang->sprintf($lang->db_step_error_nodbname, $connect_array['database']);
	}

	if(is_array($errors))
	{
		print_r($errors);
	}
	
	$output->print_header($lang->uninstalled, 'welcome');
	echo $lang->mytracker_uninstalled;
	$db->set_table_prefix($config['database']['table_prefix']);

	$tables = array(
		"tracker_activity",
		"tracker_categories",
		"tracker_features",
		"tracker_featuresposts",
		"tracker_featuresread",
		"tracker_featuresvotes",
		"tracker_issues",
		"tracker_issuesposts",
		"tracker_issuesread",
		"tracker_priorities",
		"tracker_projects",
		"tracker_projectsread",
		"tracker_stages",
		"tracker_status"
	);

	// See if any of the tables exist
	$table_count = 0;
	foreach($tables as $table)
	{
		if($db->table_exists($table))
		{
			++$table_count;
		}
	}

	// If tables exist, then we haven't uninstalled
	if($table_count > 0)
	{
		// Let's do some stuff here that uninstalls the tracker, backing it up in the process
		$file = 'backups/tracker_'.substr(md5(TIME_NOW), 0, 10).random_str(54);			

		// First, attempt to backup the database tables for MyTracker
		// Taken from the database backup task from MyBB.
		if(function_exists('gzopen'))
		{
			$fp = gzopen($file.'.sql.gz', 'w9');
			$filename = $file.'.sql.gz';
		}
		else
		{
			$fp = fopen($file.'.sql', 'w');
			$filename = $file.'.sql';
		}
		$time = date('dS F Y \a\t H:i', TIME_NOW);
		$header = "-- MyTracker Database Backup\n-- Generated: {$time}\n-- -------------------------------------\n\n";
		$contents = $header;
		
		foreach($tables as $table)
		{
			$field_list = array();
			$fields_array = $db->show_fields_from($table);
			foreach($fields_array as $field)
			{
				$field_list[] = $field['Field'];
			}
	
			$fields = implode(",", $field_list);
		
			$structure = $db->show_create_table($table).";\n";
			$contents .= $structure;
			clear_overflow($fp, $contents);
			
			$query = $db->simple_select($table);
			while($row = $db->fetch_array($query))
			{
				$insert = "INSERT INTO {$table} ($fields) VALUES (";
				$comma = '';
				foreach($field_list as $field)
				{
					if(!isset($row[$field]) || trim($row[$field]) == "")
					{
						$insert .= $comma."''";
					}
					else
					{
						$insert .= $comma."'".$db->escape_string($row[$field])."'";
					}
					$comma = ',';
				}
				$insert .= ");\n";
				$contents .= $insert;
				clear_overflow($fp, $contents);
			}
		}
		
		if(function_exists('gzopen'))
		{
			gzwrite($fp, $contents);
			gzclose($fp);
		}
		else
		{
			fwrite($fp, $contents);
			fclose($fp);
		}
		
		// Figure out if we've made the backup or not
		if($filename)
		{
			$filesize = filesize($filename);
			if($filesize > '1000')
			{
				// Over 1kb, means it was probably saved
				echo $lang->data_backed_up;
			}
			else
			{
				// Less, probably a failed backup
				echo $lang->data_backed_up;
			}
		}
		else
		{
			// Defintely not saved if there wasn't a file created!
			echo $lang->data_backed_up;
		}

		// We'll fight them on the beaches... (or just start the uninstall)...
		// Remove the tables if they exist
		foreach($tables as $table)
		{
			if($db->table_exists($table))
			{
				$db->write_query("DROP TABLE ".$db->table_prefix."{$table}");
			}
		}

		// Remove the extras things we added to the database. Do write_query to hide the errors in case the user has already deleted these
		// Remove the cache table
		$db->write_query("DELETE FROM ".$db->table_prefix."datacache WHERE title = 'trackerversion'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."datacache WHERE title = 'trackernews'", 1);

		// Remove the settings/setting group
		$db->write_query("DELETE FROM ".$db->table_prefix."settinggroups WHERE name = 'tracker'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."settings WHERE name = 'trackername'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."settings WHERE name = 'trackerseo'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."settings WHERE name = 'ideasys'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."settings WHERE name = 'trackdir'", 1);

		// Remove the templates we've added
		$db->write_query("DELETE FROM ".$db->table_prefix."templategroups WHERE prefix LIKE 'mytracker%'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."templates WHERE title LIKE 'mytracker%'", 1);

		// Remove the stylesheets we've added
		$db->write_query("DELETE FROM ".$db->table_prefix."themestylesheets WHERE name = 'mytracker.css'", 1);
		$db->write_query("DELETE FROM ".$db->table_prefix."themestylesheets WHERE name = 'facebox.css'", 1);

		// Remove the templates/usergroup/users table alterations
		$db->write_query("ALTER TABLE ".$db->table_prefix."templates DROP myversion", 1);
		$db->write_query("ALTER TABLE ".$db->table_prefix."usergroups DROP canmodtrack", 1);
		$db->write_query("ALTER TABLE ".$db->table_prefix."users DROP developer", 1);

		if(!$db->table_exists("tracker_activity") || !$db->table_exists("tracker_status"))
		{
			// The first and last table don't exists, so we've successfully removed them

			// First, resync the stylesheets!
			require_once MYBB_ROOT.$config['admin_dir']."/inc/functions_themes.php";
			$query = $db->simple_select("themestylesheets", "*");
			while($stylesheet = $db->fetch_array($query))
			{
				$themes[] = $stylesheet['tid'];
				cache_stylesheet($stylesheet['tid'], $stylesheet['cachefile'], $stylesheet['stylesheet']);
				resync_stylesheet($stylesheet);
			}
			foreach($themes as $theme)
			{
				update_theme_stylesheet_list($theme);
			}

			// Finally, output the content
			echo $lang->uninstall_success;
			$output->print_footer('license');
		}
		else
		{
			// We failed the uninstall!
			echo $lang->uninstall_fail;
		}
	}
	else
	{
		// We've already uninstalled the tracker, skip to install
		echo $lang->tracker_not_here;
		$output->print_footer('intro');
	}
}

function license_agreement()
{
	global $output, $lang;
	
	$output->print_header($lang->license_agreement, 'license');

	$license = <<<EOF
<pre>+--------------------------------------------------------------------------
|   =============================================
|   MyTracker v1.0.0
|   by Tomm (www.xekko.co.uk)
|   2009  Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > Xekko Licence
+---------------------------------------------------------------------------

MyTracker is the property of Tom Moore ("Xekko"). It is protected under the UK Copyright, Designs and Patents Act and its international equivilant.
Only a document signed by all directors of Xekko may alter this licence. MyTracker has no warranty or guarantee of any kind.

By downloading this Software, you agree NOT to:

 - Sell this product to another person
 - Rename and/or redistribute this Software (unless permission has been given) for any purpose
 - Create derivitive works of this Software, and distribute any of the modified version (unless permission has been given)

You MAY:

 - Modify the source code and templates on websites MyTracker is installed on (for your own use)
 - Create 'language packs' for the Software, and redistribute them

Under no circumstances will Xekko, or any of its staff, be liable for any damage, loss of data, service or loss of profit.

Any questions should be directed to tom@xekko.co.uk
</pre>
EOF;

	echo $lang->sprintf($lang->license_step, $license);
	$output->print_footer('requirements_check');
}

function requirements_check()
{
	global $output, $mybb, $dboptions, $lang;

	$mybb->input['action'] = "requirements_check";
	$output->print_header($lang->req_check, 'requirements');
	echo $lang->req_step_top;
	$errors = array();
	$showerror = 0;
	
	if(!file_exists(MYBB_ROOT."/inc/config.php"))
	{
		if(!@rename(MYBB_ROOT."/inc/config.default.php", MYBB_ROOT."/inc/config.php"))
		{
			if(!$configwritable)
			{
				$errors[] = $lang->sprintf($lang->req_step_error_box, $lang->req_step_error_configdefaultfile);
				$configstatus = $lang->sprintf($lang->req_step_span_fail, $lang->not_writable);
				$showerror = 1;
			}
		}
	}

	// Check PHP Version
	$phpversion = @phpversion();
	if($phpversion < '4.1.0')
	{
		$errors[] = $lang->sprintf($lang->req_step_error_box, $lang->sprintf($lang->req_step_error_phpversion, $phpversion));
		$phpversion = $lang->sprintf($lang->req_step_span_fail, $phpversion);
		$showerror = 1;
	}
	else
	{
		$phpversion = $lang->sprintf($lang->req_step_span_pass, $phpversion);
	}
	
	if(function_exists('mb_detect_encoding'))
	{
		$mboptions[] = $lang->multi_byte;
	}
	
	if(function_exists('iconv'))
	{
		$mboptions[] = 'iconv';
	}
	
	// Check Kitten Saver ability
	// Basically checking that the MyTracker functions are installed
	if(file_exists(MYBB_ROOT."/inc/functions_tracker.php"))
	{
		$kittenstatus = $lang->sprintf($lang->req_step_span_pass, $lang->installed);
	}
	else
	{
		$kittenstatus = $lang->sprintf($lang->req_step_span_fail, $lang->not_installed);
		$errors[] = $lang->sprintf($lang->req_step_error_box, $lang->req_step_error_mykitten);
		$showerror = 1;
	}

	// Check database engines
	if(count($dboptions) < 1)
	{
		$errors[] = $lang->sprintf($lang->req_step_error_box, $lang->req_step_error_dboptions);
		$dbsupportlist = $lang->sprintf($lang->req_step_span_fail, $lang->none);
		$showerror = 1;
	}
	else
	{
		foreach($dboptions as $dboption)
		{
			$dbsupportlist[] = $dboption['title'];
		}
		$dbsupportlist = implode(', ', $dbsupportlist);
	}

	// Output requirements page
	echo $lang->sprintf($lang->req_step_reqtable, $phpversion, $dbsupportlist, $kittenstatus);

	if($showerror == 1)
	{
		$error_list = error_list($errors);
		echo $lang->sprintf($lang->req_step_error_tablelist, $error_list);
		echo "\n			<input type=\"hidden\" name=\"action\" value=\"{$mybb->input['action']}\" />";
		echo "\n				<div id=\"next_button\"><input type=\"submit\" class=\"submit_button\" value=\"{$lang->recheck} &raquo;\" /></div><br style=\"clear: both;\" />\n";
		$output->print_footer();
	}
	else
	{
		echo $lang->req_step_reqcomplete;
		$output->print_footer('create_tables');
	}
}

function create_tables()
{
	global $config, $output, $dbinfo, $errors, $mybb, $dboptions, $lang;
	
	// require MYBB_ROOT."/inc/config.php";
	
	if(!file_exists(MYBB_ROOT."inc/db_{$config['database']['type']}.php"))
	{
		$errors[] = $lang->db_step_error_invalidengine;
	}

	// Attempt to connect to the db
	require_once MYBB_ROOT."inc/db_{$config['database']['type']}.php";
	switch($config['database']['type'])
	{
		case "mysqli":
			$db = new DB_MySQLi;
			break;
		default:
			$db = new DB_MySQL;
	}
 	$db->error_reporting = 0;

	$connect_array = array(
		"hostname" => $config['database']['hostname'],
		"username" => $config['database']['username'],
		"password" => $config['database']['password'],
		"database" => $config['database']['database'],
		"table_prefix" => $config['database']['table_prefix']
	);

	$connection = $db->connect($connect_array);
	if(!$connection)
	{
		$errors[] = $lang->sprintf($lang->db_step_error_noconnect, $connect_array['hostname']);
	}
	// double check if the DB exists for MySQL
	elseif(method_exists($db, 'select_db') && !$db->select_db($connect_array['database']))
	{
		$errors[] = $lang->sprintf($lang->db_step_error_nodbname, $connect_array['database']);
	}

	if(is_array($errors))
	{
		print_r($errors);
	}
	
	// Decide if we can use a database encoding or not
	if($db->fetch_db_charsets() != false)
	{
		$db_encoding = "\$config['database']['encoding'] = '{$config['encoding']}';";
	}
	else
	{
		$db_encoding = "// \$config['database']['encoding'] = '{$config['encoding']}';";
	}

	// Error reporting back on
 	$db->error_reporting = 1;

	$output->print_header($lang->table_creation, 'createtables');
	echo $lang->sprintf($lang->tablecreate_step_connected, $db->short_title, $db->get_version());
	
	$structure_file = 'mysql_db_tables.php'; // Sticking with this for the moment.

	require_once INSTALL_ROOT."resources/{$structure_file}";
	foreach($tables as $val)
	{
		$val = preg_replace('#mybb_(\S+?)([\s\.,\(]|$)#', $connect_array['table_prefix'].'\\1\\2', $val);
		$val = preg_replace('#;$#', $db->build_create_table_collation().";", $val);
		preg_match('#CREATE TABLE (\S+)(\s?|\(?)\(#i', $val, $match);
		if($match[1])
		{
			$db->drop_table($match[1], false, false);
			echo $lang->sprintf($lang->tablecreate_step_created, $match[1]);
		}
		$db->query($val);
		if($match[1])
		{
			echo $lang->done . "<br />\n";
		}
	}
	echo $lang->tablecreate_step_done;
	$output->print_footer('populate_tables');
}

function populate_tables()
{
	global $db, $config, $cache, $output, $lang;

	$db = db_connection($config);

	$output->print_header($lang->table_population, 'tablepopulate');
	echo $lang->sprintf($lang->populate_step_insert);

	if($dboptions[$db->type]['population_file'])
	{
		$population_file = $dboptions[$db->type]['population_file'];
	}
	else
	{
		$population_file = 'mysql_db_inserts.php';
	}

	require_once INSTALL_ROOT."resources/{$population_file}";
	foreach($inserts as $val)
	{
		$val = preg_replace('#mybb_(\S+?)([\s\.,]|$)#', $config['database']['table_prefix'].'\\1\\2', $val);
		$db->query($val);
	}

	$db->set_table_prefix = $config['database']['table_prefix']; // If all else fails...
	// Insert the settings group
	// Query the database to get the top gid/display order
	$query = $db->simple_select("settinggroups", "gid", "gid = (SELECT MAX(gid) FROM ".$config['database']['table_prefix']."settinggroups)");
	$latest_info['gid'] = $db->fetch_field($query, "gid");
	$query = $db->simple_select("settinggroups", "MAX(disporder) as disporder");
	$latest_info['disporder'] = $db->fetch_field($query, "disporder");

	++$latest_info['gid'];
	++$latest_info['disporder'];

	$insert_array = array(
		"gid" => $latest_info['gid'],
		"name" => "tracker",
		"title" => "MyTracker Settings",
		"description" => "This section controls the settings for MyTracker.",
		"disporder" => $latest_info['displayorder'],
		"isdefault" => 1
	);
	$db->insert_query("settinggroups", $insert_array);
	
	$settings_1 = array(
		"name" => "trackername",
		"title" => "Tracker Name",
		"description" => "The name of your tracking system.",
		"optionscode" => "text",
		"value" => "Bug Tracker",
		"disporder" => '1',
		"gid" => $insert_array['gid'],
		"isdefault" => '1'
	);
	$settings_2 = array(
		"name" => "trackerseo",
		"title" => "Tracker Friendly URLs",
		"description" => "Use friendly URLs with your tracker?",
		"optionscode" => "select\nyes=Enabled\nno=Disabled",
		"value" => "yes",
		"disporder" => '2',
		"gid" => $insert_array['gid'],
		"isdefault" => '1'
	);
	$settings_3 = array(
		"name" => "ideasys",
		"title" => "Ideas System",
		"description" => "Globally turn on (or off) the Ideas/Features System.",
		"optionscode" => "yesno",
		"value" => "1",
		"disporder" => '3',
		"gid" => $insert_array['gid'],
		"isdefault" => '1'
	);
	
	// Current path to the folder
	$current_dir = basename(dirname(dirname(__FILE__)));
	$settings_4 = array(
		"name" => "trackdir",
		"title" => "Tracker Directory",
		"description" => "Path to the MyTracker folder. Exclude the trailing slash.",
		"optionscode" => "text",
		"value" => $db->escape_string($current_dir),
		"disporder" => '4',
		"gid" => $insert_array['gid'],
		"isdefault" => '1'
	);
	$db->insert_query("settings", $settings_1);
	$db->insert_query("settings", $settings_2);
	$db->insert_query("settings", $settings_3);
	$db->insert_query("settings", $settings_4);

	// Rebuild the settings so the above takes effect
	rebuild_settings();

	// Update the usergroup cache
	$query = $db->simple_select("usergroups");
	while($g = $db->fetch_array($query))
	{
		$gs[$g['gid']] = $g;
	}
	$contents = $db->escape_string(serialize($gs));
	$replace_array = array(
		"title" => "usergroups",
		"cache" => $contents
	);
	$db->replace_query("datacache", $replace_array, "title", false);

	echo $lang->populate_step_inserted;
	$output->print_footer('templates');
}

function insert_templates()
{
	global $config, $output, $cache, $db, $lang, $mybb;

	$db = db_connection($config);

	require_once MYBB_ROOT.'inc/class_datacache.php';
	$cache = new datacache;

	$output->print_header($lang->theme_installation, 'theme');

	echo $lang->theme_step_importing;

	$contents = @file_get_contents(INSTALL_ROOT.'resources/mytracker_templates.xml');
	if(file_exists(MYBB_ROOT.$config['admin_dir']."/inc/functions_themes.php"))
	{ 
		require_once MYBB_ROOT.$config['admin_dir']."/inc/functions_themes.php";
	}
	elseif(file_exists(MYBB_ROOT."admin/inc/functions_themes.php"))
	{
		require_once MYBB_ROOT."admin/inc/functions_themes.php";
	}
	else
	{
		$output->print_error("Please make sure your MyBB admin directory is uploaded correctly, or if you've changed the name, configured in ./inc/config.php.");
	}
	
	// We need to alter some templates to make sure things are compatible.
	// Codebuttons template
	$query = $db->query("SELECT template, tid FROM ".$config['database']['table_prefix']."templates WHERE title = 'codebuttons'");
	while($template = $db->fetch_array($query))
	{
		$new_template = str_replace("\"jscripts/editor.js", "\"{\$mybb->settings['bburl']}/jscripts/editor.js", $template['template']);
		$db->update_query("templates", array("template" => $db->escape_string($new_template)), "tid = '".$template['tid']."'");
	}

	// First install the templates
	my_import_theme_xml($contents, array("templateset" => -2, "version_compat" => 1, "no_stylesheets" => 1));

	// Then, for the Master Theme, install the stylesheets
	my_import_theme_xml($contents, array("no_templates" => 1, "version_compat" => 1, "tid" => '4')); // Just installing the stylesheets

	echo $lang->theme_step_imported;
	$output->print_footer('final');
}

function install_done()
{
	global $config, $cache, $output, $db, $mybb, $errors, $cache, $lang, $tracker_version;

	$output->print_header($lang->finish_setup, 'finish');
	
	$db = db_connection($config);

	// We might need to update the tracker cache here
	$contents = serialize($tracker_version);
	$replace_array = array(
		"title" => "trackerversion",
		"cache" => $contents
	);
	$db->replace_query("datacache", $replace_array, "title", false);

	echo $lang->done_step_success;

	// Write the 'lock' file
	$written = 0;
	if(is_writable('./'))
	{
		$lock = @fopen('./lock', 'w');
		$written = @fwrite($lock, '1');
		@fclose($lock);
		if($written)
		{
			$lang->done_step_locked = $lang->sprintf($lang->done_step_locked, $config['admin_dir']);
			echo $lang->done_step_locked;
		}
	}
	// Write the 'installed' file - so we know it's installed easily
	$ins_written = 0;
	if(is_writable('./resources/'))
	{
		$installed = @fopen('./resources/installed', 'w');
		$ins_written = @fwrite($installed, '1');
		@fclose($installed);
	}
	if(!$written || !$ins_written)
	{
		echo $lang->done_step_dirdelete;
	}
	echo $lang->done_subscribe_mailing;
	$output->print_footer('');
}

function db_connection($config)
{
	require_once MYBB_ROOT."inc/db_{$config['database']['type']}.php";
	switch($config['database']['type'])
	{
		case "mysqli":
			$db = new DB_MySQLi;
			break;
		default:
			$db = new DB_MySQL;
	}
	
	// Connect to Database
	define('TABLE_PREFIX', $config['database']['table_prefix']);

	$db->connect($config['database']);
	$db->set_table_prefix(TABLE_PREFIX);
	$db->type = $config['database']['type'];
	
	return $db;
}

function error_list($array)
{
	$string = "<ul>\n";
	foreach($array as $error)
	{
		$string .= "<li>{$error}</li>\n";
	}
	$string .= "</ul>\n";
	return $string;
}

function my_import_theme_xml($xml, $options=array())
{
	global $mybb, $db;
	
	require_once MYBB_ROOT."inc/class_xml.php";

	$parser = new XMLParser($xml);
	$tree = $parser->get_tree();

	if(!is_array($tree) || !is_array($tree['theme']))
	{
		return -1;
	}
	
	$theme = $tree['theme'];
	
	// Do we have MyBB 1.2 template's we're importing?
	$css_120 = "";
	
	if(is_array($theme['cssbits']))
	{
		$cssbits = kill_tags($theme['cssbits']);
		
		foreach($cssbits as $name => $values)
		{
			$css_120 .= "{$name} {\n";
			foreach($values as $property => $value)
			{
				if(is_array($value))
				{
					$property = str_replace('_', ':', $property);
					
					$css_120 .= "}\n{$name} {$property} {\n";
					foreach($value as $property2 => $value2)
					{
						$css_120 .= "\t{$property2}: {$value2}\n";
					}
				}
				else
				{
					$css_120 .= "\t{$property}: {$value}\n";
				}
			}
			$css_120 .= "}\n";
		}
	}
	
	if(is_array($theme['themebits']))
	{
		$themebits = kill_tags($theme['themebits']);
		
		$theme['properties']['tag'] = 'properties';
		
		foreach($themebits as $name => $value)
		{
			if($name == "extracss")
			{
				$css_120 .= $value;
				continue;
			}
			
			$theme['properties'][$name] = $value;
		}
	}
	
	if($css_120)
	{
		$css_120 = upgrade_css_120_to_140($css_120);
		$theme['stylesheets']['tag'] = 'stylesheets';
		$theme['stylesheets']['stylesheet'][0]['tag'] = 'stylesheet';
		$theme['stylesheets']['stylesheet'][0]['attributes'] = array('name' => 'global.css', 'version' => $mybb->version_code);
		$theme['stylesheets']['stylesheet'][0]['value'] = $css_120;
		
		unset($theme['cssbits']);
		unset($theme['themebits']);
	}
	
	if(is_array($theme['properties']))
	{
		foreach($theme['properties'] as $property => $value)
		{
			if($property == "tag" || $property == "value")
			{
				continue;
			}
			
			$properties[$property] = $value['value'];
		}
	}
	
	if(empty($mybb->input['name']))
	{
		$name = $theme['attributes']['name'];
	}
	else
	{
		$name = $mybb->input['name'];
	}
	$version = $theme['attributes']['version'];

	$query = $db->simple_select("themes", "tid", "name='".$db->escape_string($name)."'", array("limit" => 1));
	$existingtheme = $db->fetch_array($query);
	if($options['force_name_check'] && $existingtheme['tid'])
	{
		return -3;
	}
	else if($existingtheme['tid'])
	{
		$options['tid'] = $existingtheme['tid'];
	}

	if($mybb->version_code != $version && $options['version_compat'] != 1)
	{
		return -2;
	}
	
	// Do we have any templates to insert?
	if(!empty($theme['templates']['template']) && !$options['no_templates'])
	{		
		if($options['templateset'])
		{ 
			$sid = $options['templateset'];
		} 
		else 
		{ 
			$sid = $db->insert_query("templatesets", array('title' => $db->escape_string($name)." Templates"));
		}
		
		$templates = $theme['templates']['template'];
		if(is_array($templates))
		{
			// Theme only has one custom template
			if(array_key_exists("attributes", $templates))
			{
				$templates = array($templates);
			}
		}
	
		foreach($templates as $template)
		{
			// PostgreSQL causes apache to stop sending content sometimes and 
			// causes the page to stop loading during many queries all at one time
			if($db->engine == "pgsql")
			{
				echo " ";
				flush();
			}
			
			$new_template = array(
				"title" => $db->escape_string($template['attributes']['name']),
				"template" => $db->escape_string($template['value']),
				"sid" => $db->escape_string($sid),
				"version" => $db->escape_string($template['attributes']['version']),
				"myversion" => $db->escape_string($template['attributes']['myversion']),
				"dateline" => TIME_NOW
			);
			$db->insert_query("templates", $new_template);
		}
		
		$properties['templateset'] = $sid;
	}

	// Not overriding an existing theme
	if(!$options['tid'])
	{
		// Insert the theme
		$theme_id = build_new_theme($name, $properties, $options['parent']);
	}
	// Overriding an existing - delete refs.
	else
	{
		// $db->update_query("themes", array("properties" => $db->escape_string(serialize($properties))), "tid='{$options['tid']}'");
		$theme_id = $options['tid'];
	}

	// If we have any stylesheets, process them
	if(!empty($theme['stylesheets']['stylesheet']) && !$options['no_stylesheets'])
	{
		// Are we dealing with a single stylesheet?
		if(isset($theme['stylesheets']['stylesheet']['tag']))
		{
			// Trick the system into thinking we have a good array =P
			$theme['stylesheets']['stylesheet'] = array($theme['stylesheets']['stylesheet']);
		}
		
		foreach($theme['stylesheets']['stylesheet'] as $stylesheet)
		{
			if(!$stylesheet['attributes']['lastmodified'])
			{
				$stylesheet['attributes']['lastmodified'] = TIME_NOW;
			}
			
			$new_stylesheet = array(
				"name" => $db->escape_string($stylesheet['attributes']['name']),
				"tid" => $theme_id,
				"attachedto" => $db->escape_string($stylesheet['attributes']['attachedto']),
				"stylesheet" => $db->escape_string($stylesheet['value']),
				"lastmodified" => intval($stylesheet['attributes']['lastmodified']),
				"cachefile" => $db->escape_string($stylesheet['attributes']['name'])
			);
			$sid = $db->insert_query("themestylesheets", $new_stylesheet);
			$css_url = "css.php?stylesheet={$sid}";
			$cached = cache_stylesheet($theme_id, $stylesheet['attributes']['name'], $stylesheet['value']);
			if($cached)
			{
				$css_url = $cached;
			}
			
			$attachedto = $stylesheet['attributes']['attachedto'];
			if(!$attachedto)
			{
				$attachedto = "global";
			}
			
			// private.php?compose,folders|usercp.php,global|global
			$attachedto = explode("|", $attachedto);
			foreach($attachedto as $attached_file)
			{
				$attached_actions = explode(",", $attached_file);
				$attached_file = array_shift($attached_actions);
				if(count($attached_actions) == 0)
				{
					$attached_actions = array("global");
				}
				
				foreach($attached_actions as $action)
				{
					$theme_stylesheets[$attached_file][$action][] = $css_url;
				}
			}
		}
		// Now we have our list of built stylesheets, save them
		$updated_theme = array(
			"stylesheets" => $db->escape_string(serialize($theme_stylesheets))
		);
		$db->update_query("themes", $updated_theme, "tid='{$theme_id}'");
	}
	
	update_theme_stylesheet_list($theme_id);

	// And done?
	return $theme_id;
}
?>