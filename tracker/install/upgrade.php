<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: upgrade.php 4 2009-08-03 15:41:36Z Tomm $
+--------------------------------------------------------------------------
*/
error_reporting(E_ALL & ~E_NOTICE);

define('MYBB_ROOT', dirname(dirname(dirname(__FILE__)))."/");
define("INSTALL_ROOT", dirname(__FILE__)."/");
define("TIME_NOW", time());
define("IN_MYBB", 1);
define("IN_UPGRADE", 1);

require_once MYBB_ROOT."inc/class_core.php";
$mybb = new MyBB;

require_once MYBB_ROOT."inc/config.php";

$orig_config = $config;

if(!is_array($config['database']))
{
	$config['database'] = array(
		"type" => $config['dbtype'],
		"database" => $config['database'],
		"table_prefix" => $config['table_prefix'],
		"hostname" => $config['hostname'],
		"username" => $config['username'],
		"password" => $config['password'],
		"encoding" => $config['db_encoding'],
	);
}
$mybb->config = &$config;

// Include the files necessary for installation
require_once MYBB_ROOT."inc/class_timers.php";
require_once MYBB_ROOT."inc/functions.php";
require_once MYBB_ROOT."inc/class_xml.php";
require_once MYBB_ROOT.'inc/class_language.php';

$lang = new MyLanguage();
$lang->set_path(INSTALL_ROOT.'resources');
$lang->load('install');

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

// Load Settings
if(file_exists(MYBB_ROOT."inc/settings.php"))
{
	require_once MYBB_ROOT."inc/settings.php";
}
$mybb->settings = &$settings;
$mybb->parse_cookies();

// Require cache and session
require_once MYBB_ROOT."inc/class_datacache.php";
$cache = new datacache;
$mybb->cache = &$cache;

require_once MYBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();
$mybb->session = &$session;

// Include the installation resources
require_once INSTALL_ROOT."resources/output.php";
$output = new installerOutput;
$output->script = "upgrade.php";
$output->title = "MyTracker Upgrade Wizard";

// What's the current version of MyTracker?
$version = $cache->read("trackerversion");

if(file_exists("lock"))
{
	$output->print_error($lang->locked);
}
else
{
	// Check if the user is logged in, and able to do this upgrade
	if($mybb->input['action'] == "logout" && $mybb->user['uid'])
	{	
		// Check session ID if we have one
		if($mybb->input['logoutkey'] != $mybb->user['logoutkey'])
		{
			$output->print_error("Your user ID could not be verified to log you out.  This may have been because a malicious Javascript was attempting to log you out automatically.  If you intended to log out, please click the Log Out button at the top menu.");
		}
	
		my_unsetcookie("mybbuser");
		my_unsetcookie("sid");
		if($mybb->user['uid'])
		{
			$time = TIME_NOW;
			$lastvisit = array(
				"lastactive" => $time-900,
				"lastvisit" => $time,
			);
			$db->update_query("users", $lastvisit, "uid='".$mybb->user['uid']."'");
			$db->delete_query("sessions", "sid='".$session->sid."'");
		}
		header("Location: upgrade.php");
	}
	else if($mybb->input['action'] == "do_login" && $mybb->request_method == "post")
	{	
		require_once MYBB_ROOT."inc/functions_user.php";
	
		if(!username_exists($mybb->input['username']))
		{
			$output->print_error("The username you have entered appears to be invalid.");
		}
		$query = $db->simple_select("users", "uid,username,password,salt,loginkey", "username='".$db->escape_string($mybb->input['username'])."'", array('limit' => 1));
		$user = $db->fetch_array($query);
		if(!$user['uid'])
		{
			$output->print_error("The username you have entered appears to be invalid.");
		}
		else
		{
			$user = validate_password_from_uid($user['uid'], $mybb->input['password'], $user);
			if(!$user['uid'])
			{
				$output->print_error("The password you entered is incorrect. If you have forgotten your password, click <a href=\"../member.php?action=lostpw\">here</a>. Otherwise, go back and try again.");
			}
		}
		
		$db->delete_query("sessions", "ip='".$db->escape_string($session->ipaddress)."' AND sid != '".$session->sid."'");
		
		$newsession = array(
			"uid" => $user['uid']
		);
		
		$db->update_query("sessions", $newsession, "sid='".$session->sid."'");
	
		// Temporarily set the cookie remember option for the login cookies
		$mybb->user['remember'] = $user['remember'];
	
		my_setcookie("mybbuser", $user['uid']."_".$user['loginkey'], null, true);
		my_setcookie("sid", $session->sid, -1, true);
	
		header("Location: ./upgrade.php");
	}

	$output->steps = array($lang->upgrade);

	if($mybb->user['uid'] == 0)
	{
		$output->print_header("Please Login", "errormsg", 0, 1);
		
		$output->print_contents('<p>Please enter your username and password to begin the upgrade process. You must be a valid forum administrator to perform the upgrade.</p>
<form action="upgrade.php" method="post">
	<div class="border_wrapper">
		<table class="general" cellspacing="0">
		<thead>
			<tr>
				<th colspan="2" class="first last">Login</th>
			</tr>
		</thead>
		<tbody>
			<tr class="first">
				<td class="first">Username:</td>
				<td class="last alt_col"><input type="text" class="textbox" name="username" size="25" maxlength="'.$mybb->settings['maxnamelength'].'" style="width: 200px;" /></td>
			</tr>
			<tr class="alt_row last">
				<td class="first">Password:<br /><small>Please note that passwords are case sensitive.</small></td>
				<td class="last alt_col"><input type="password" class="textbox" name="password" size="25" style="width: 200px;" /></td>
			</tr>
		</tbody>
		</table>
	</div>
	<div id="next_button">
		<input type="submit" class="submit_button" name="submit" value="Login" />
		<input type="hidden" name="action" value="do_login" />
	</div>
</form>');
		$output->print_footer("");
		
		exit;
	}
	else if($mybb->usergroup['cancp'] != 1 && $mybb->usergroup['cancp'] != 'yes')
	{
		$output->print_error("You do not have permissions to run this process. You need administrator permissions to be able to run the upgrade procedure.<br /><br />If you need to logout, please click <a href=\"upgrade.php?action=logout&amp;logoutkey={$mybb->user['logoutkey']}\">here</a>. From there you will be able to log in again under your administrator account.");
	}

	if(!$mybb->input['action'] || $mybb->input['action'] == "intro")
	{
		$output->print_header();
		
		// Hijacking MyBB's upgrade_data table
		if($db->table_exists("upgrade_data"))
		{
			$db->drop_table("upgrade_data");
		}
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."upgrade_data (
			title varchar(30) NOT NULL,
			contents text NOT NULL,
			UNIQUE (title)
		);");
		
		$dh = opendir(INSTALL_ROOT."resources");
		while(($file = readdir($dh)) !== false)
		{
			if(preg_match("#upgrade([0-9]+).php$#i", $file, $match))
			{
				$upgradescripts[$match[1]] = $file;
				$key_order[] = $match[1];
			}
		}
		closedir($dh);
		natsort($key_order);
		$key_order = array_reverse($key_order);

		foreach($key_order as $k => $key)
		{
			$file = $upgradescripts[$key];
			$upgradescript = file_get_contents(INSTALL_ROOT."resources/$file");
			preg_match("#Upgrade Script:(.*)#i", $upgradescript, $verinfo);
			preg_match("#upgrade([0-9]+).php$#i", $file, $keynum);
			if(trim($verinfo[1]))
			{
				if($k == 0)
				{
					$vers .= "<option value=\"$keynum[1]\" selected=\"selected\">$verinfo[1]</option>\n";
				}
				else
				{
					$vers .= "<option value=\"$keynum[1]\">$verinfo[1]</option>\n";
				}
			}
		}
		unset($upgradescripts);
		unset($upgradescript);
		
		$output->print_contents($lang->sprintf($lang->upgrade_welcome, $version['version'])."<p><select name=\"from\">$vers</select>");
		$output->print_footer("doupgrade");
	}
	elseif($mybb->input['action'] == "doupgrade")
	{
		require_once INSTALL_ROOT."resources/upgrade".intval($mybb->input['from']).".php";
		if(!$upgrade_detail)
		{
			$output->print_header();
			$lang->plugin_warning = "<input type=\"hidden\" name=\"from\" value=\"".intval($mybb->input['from'])."\" />\n<input type=\"hidden\" name=\"donewarning\" value=\"true\" />\n<div class=\"error\"><strong><span style=\"color: red\">Warning:</span></strong> <p>There seems to be some problem with the upgrade process. Please make sure that the upgrade files are in place.</p></div> <br />";
			$output->print_contents($lang->sprintf($lang->plugin_warning, $version['version']));
			$output->print_footer("doupgrade");
		}
		else
		{
			add_upgrade_store("startscript", $mybb->input['from']);
			$runfunction = next_function($mybb->input['from']);
		}
	}
	$currentscript = get_upgrade_store("currentscript");
	$system_upgrade_detail = get_upgrade_store("upgradedetail");
	if($mybb->input['action'] == "templates")
	{
		$runfunction = "upgradethemes";
	}
	elseif($mybb->input['action'] == "rebuildsettings")
	{
		$runfunction = "buildsettings";
	}
	elseif($mybb->input['action'] == "buildcaches")
	{
		$runfunction = "buildcaches";
	}
	elseif($mybb->input['action'] == "finished")
	{
		$runfunction = "upgradedone";
	}
	else // Busy running modules, come back later
	{
		$bits = explode("_", $mybb->input['action'], 2);
		if($bits[1]) // We're still running a module
		{
			$from = $bits[0];
			$runfunction = next_function($bits[0], $bits[1]);

		}
	}
	// Fetch current script we're in
	
	if(function_exists($runfunction))

	{
		$runfunction();
	}
}

function whatsnext()
{
	global $output, $db, $system_upgrade_detail, $lang;

	if($system_upgrade_detail['revert_all_templates'] > 0)
	{
		$output->print_header($lang->upgrade_template_reversion);
		$output->print_contents($lang->upgrade_template_reversion_success);
		$output->print_footer("templates");
	}
	else
	{
		upgradethemes();
	}
}

function next_function($from, $func="dbchanges")
{
	global $oldvers, $system_upgrade_detail, $currentscript;

	load_module("upgrade".$from.".php");
	if(function_exists("upgrade".$from."_".$func))
	{
		$function = "upgrade".$from."_".$func;
	}
	else
	{
		$from = $from+1;
		if(file_exists(INSTALL_ROOT."resources/upgrade".$from.".php"))
		{
			$function = next_function($from);
		}
	}

	if(!$function)
	{
		$function = "whatsnext";
	}
	return $function;
}

function load_module($module)
{
	global $system_upgrade_detail, $currentscript, $upgrade_detail;
	
	require_once INSTALL_ROOT."resources/".$module;
	if($currentscript != $module)
	{
		foreach($upgrade_detail as $key => $val)
		{
			if(!$system_upgrade_detail[$key] || $val > $system_upgrade_detail[$key])
			{
				$system_upgrade_detail[$key] = $val;
			}
		}
		add_upgrade_store("upgradedetail", $system_upgrade_detail);
		add_upgrade_store("currentscript", $module);
	}
}

function get_upgrade_store($title)
{
	global $db;
	
	$query = $db->simple_select("upgrade_data", "*", "title='".$db->escape_string($title)."'");
	$data = $db->fetch_array($query);
	return unserialize($data['contents']);
}

function add_upgrade_store($title, $contents)
{
	global $db;
	
	$replace_array = array(
		"title" => $db->escape_string($title),
		"contents" => $db->escape_string(serialize($contents))
	);		
	$db->replace_query("upgrade_data", $replace_array, "title");
}

function upgradethemes()
{
	global $output, $db, $system_upgrade_detail, $lang, $mybb;
	
	$output->print_header($lang->upgrade_templates_reverted);

	$sid = -2;

	// We're removing all MyTracker templates and replacing them with new ones
	if($system_upgrade_detail['revert_all_templates'] == 1)
	{
		$db->write_query("DELETE FROM ".TABLE_PREFIX."templates WHERE title LIKE 'mytracker%'", 1);
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

		// Install the templates
		my_import_theme_xml($contents, array("templateset" => -2, "version_compat" => 1, "no_stylesheets" => 1));
	}
	elseif($system_upgrade_detail['template_version'])
	{
		// We're just updating the ones specified in the upgrade version
		$contents = @file_get_contents(INSTALL_ROOT.'resources/mytracker_templates.xml');
		$parser = new XMLParser($contents);
		$tree = $parser->get_tree();
	
		$theme = $tree['theme'];
	
		if(is_array($theme['templates']))
		{
			$templates = $theme['templates']['template'];
			foreach($templates as $template)
			{
				$templatename = $db->escape_string($template['attributes']['name']);
				$templateversion = intval($template['attributes']['version']);
				$templatevalue = $db->escape_string($template['value']);
				$time = TIME_NOW;
				$query = $db->simple_select("templates", "tid", "sid='-2' AND title='".$db->escape_string($templatename)."' AND myversion = '".$system_upgrade_detail['template_version']."'");
				$oldtemp = $db->fetch_array($query);
				if($oldtemp['tid'])
				{
					$update_array = array(
						'template' => $templatevalue,
						'version' => $templateversion,
						'myversion' => $system_upgrade_detail['template_version'],
						'dateline' => $time
					);
					$db->update_query("templates", $update_array, "title='".$db->escape_string($templatename)."' AND sid='-2' AND myversion = '".$system_upgrade_detail['template_version']."'");
				}
				else
				{
					$insert_array = array(
						'title' => $templatename,
						'template' => $templatevalue,
						'sid' => $sid,
						'version' => $templateversion,
						'myversion' => $system_upgrade_detail['template_version'],
						'dateline' => $time
					);			
					
					$db->insert_query("templates", $insert_array);
					++$newcount;
				}
			}
		}
	}

	$output->print_contents($lang->upgrade_templates_reverted_success);
	$output->print_footer("finished");
}

function upgradedone()
{
	global $db, $cache, $output, $mybb, $lang, $config;

	$output->print_header("Upgrade Complete");
	if(is_writable("./"))
	{
		$lock = @fopen("./lock", "w");
		$written = @fwrite($lock, "1");
		@fclose($lock);
		if($written)
		{
			$lock_note = $lang->sprintf($lang->upgrade_locked, $config['admin_dir']);
		}
	}
	if(is_writable("./resources/"))
	{
		$ins = @fopen("./resources/installed", "w");
		$ins_written = @fwrite($ins, "1");
		@fclose($ins);
	}
	if(!$written)
	{
		$lock_note = "<p><b><span style=\"color: red;\">".$lang->upgrade_removedir."</span></b></p>";
	}
	
	// Rebuild inc/settings.php at the end of the upgrade
	if(function_exists('rebuild_settings'))
	{
		rebuild_settings();
	}
	else
	{
		$options = array(
			"order_by" => "title",
			"order_dir" => "ASC"
		);
		
		$query = $db->simple_select("settings", "value, name", "", $options);
		while($setting = $db->fetch_array($query))
		{
			$setting['value'] = str_replace("\"", "\\\"", $setting['value']);
			$settings[$setting['name']] = $setting['value'];
		}
	}
	
	$version = $cache->read("trackerversion");
	
	$output->print_contents($lang->sprintf($lang->upgrade_congrats, $version['version'], $lock_note));
	$output->print_footer();
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