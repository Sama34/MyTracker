<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: install.lang.php 12 2009-10-05 15:34:01Z Tomm $
|	> Installer originally written by MyBB Group  2009 (mybboard.net)
+--------------------------------------------------------------------------
*/
$l['none'] = 'None';
$l['not_installed'] = 'Not Installed';
$l['installed'] = 'Installed';
$l['not_writable'] = 'Not Writable';
$l['writable'] = 'Writable';
$l['done'] = 'done';
$l['next'] = 'Next';
$l['error'] = 'Error';
$l['multi_byte'] = 'Multi-Byte';
$l['recheck'] = 'Recheck';

$l['title'] = "MyTracker Installation Wizard";
$l['welcome'] = 'Welcome';
$l['license_agreement'] = 'License Agreement';
$l['req_check'] = 'Requirements Check';
$l['table_creation'] = 'Table Creation';
$l['data_insertion'] = 'Data Insertion';
$l['theme_install'] = 'Template Installation';
$l['tracker_config'] = 'MyTracker Configuration';
$l['finish_setup'] = 'Finish Setup';

$l['table_population'] = 'Table Population';
$l['theme_installation'] = 'Theme Insertion';

$l['already_installed'] = "MyTracker is already installed";

$l['mytracker_installed'] = '<p>Welcome to the installation wizard for MyTracker {1}. MyTracker has found it\'s already installed on your forum, and you need to choose an option to continue.</p>
<p>Please note that if this wizard fails, you may need to remove the software manually. Remember to <strong>disable the plugin in your Admin Control Panel first</strong> before continuing.</p>

<div class="border_wrapper upgrade_note" style="padding: 4px;">
	<h3>Upgrading?</h3>
	<p>You should check the <a href="http://xekko.co.uk/mytracker/">MyTracker Homepage</a> for information on how to upgrade your tracker. By upgrading your version of MyTracker, you keep all of your current Projects, Issues, Features and Comments.</p>
	<div class="next_button"><a href="./upgrade.php"><input class="submit_button" value="Upgrade MyTracker &raquo;" /></a></div>
</div>

<p>This option will <span style="color: red;">delete any existing tracker you may have set up</span> and install a fresh version of MyTracker. It will try and automatically create a backup of MyTracker data for you (located in the \'backups\' folder), but really, you should backup your entire forum.</p>
<p>Please note that this uninstaller will attempt to resync your forum stylesheets. This should help optimise your forum, but is still in experimental stages. Please remember to backup your entire forum and database before making any changes.</p>
<p>Click "Next" to completely remove your copy of MyTracker, and install a new one.</p>';

$l['mybb_incorrect_folder'] = "<div class=\"border_wrapper upgrade_note\" style=\"padding: 4px;\">
	<h3>MyBB has detected that it is running from the \"Upload\" directory.</h3>
	<p>While there is nothing wrong with this, it is recommended that your upload the contents of the \"Upload\" directory and not the directory itself.<br /><br />For more information see our <a href=\"http://wiki.mybboard.net/index.php/Help:Upload_Directory\" target=\"_blank\">wiki page</a>.</p>
</div>";

$l['welcome_step'] = '<p>Welcome to the installation wizard for MyTracker {1}. This wizard will install and configure a copy of MyTracker on your server.</p>
<p><strong>Please note that this is not an "official" MyBB Plugin or Software. You won\'t receive any support for MyTracker at mybboard.net.</strong></p>
<p>Now that you\'ve uploaded the MyTracker files, the database and settings need to be created and imported. Below is an outline of what is going to be completed during installation.</p>
<ul>
	<li>Requirements checked</li>
	<li>Creation of database tables</li>
	<li>Default data inserted</li>
	<li>Default templates imported</li>
	<li>MyTracker settings configured</li>
</ul>
<p>After each step has successfully been completed, click Next to move on to the next step.</p>
<p>Click "Next" to view the MyTracker license agreement.</p>';

$l['license_step'] = '<div class="license_agreement">
{1}
</div>
<p><strong>By clicking Next, you agree to the terms stated in the License Agreement above.</strong></p>';


$l['req_step_top'] = '<p>Before you can install MyTracker, we must check that you meet the minimum requirements for installation. Considering you\'re already running MyBB, you should also be able to run MyTracker, too. But, just in case, we need to do a few checks...</p>';
$l['req_step_reqtable'] = '<div class="border_wrapper">
			<div class="title">Requirements Check</div>
		<table class="general" cellspacing="0">
		<thead>
			<tr>
				<th colspan="2" class="first last">Requirements</th>
			</tr>
		</thead>
		<tbody>
		<tr class="first">
			<td class="first">PHP Version:</td>
			<td class="last alt_col">{1}</td>
		</tr>
		<tr class="alt_row">
			<td class="first">Supported DB Extensions:</td>
			<td class="last alt_col">{2}</td>
		</tr>
		<tr class="alt_row">
			<td class="first">MyKitten Saver:</td>
			<td class="last alt_col">{3}</td>
		</tr>
		</tbody>
		</table>
		</div>';
$l['req_step_reqcomplete'] = '<p><strong>Congratulations, you meet the requirements to run MyTracker. Issue Tracking is just a few more steps away!</strong></p>
<p>Click Next to continue with the installation process.</p>';

$l['req_step_span_fail'] = '<span class="fail"><strong>{1}</strong></span>';
$l['req_step_span_pass'] = '<span class="pass">{1}</span>';

$l['req_step_error_box'] = '<p><strong>{1}</strong></p>';
$l['req_step_error_phpversion'] = 'MyTracker Requires PHP 4.1.0 or later to run. You currently have {1} installed.';
$l['req_step_error_dboptions'] = 'MyTracker requires one or more suitable database extensions to be installed. Your server reported that none were available.';
$l['req_step_error_mykitten'] = 'MyTracker requires all of its files uploaded to the correct areas. Please double check that you\'ve uploaded all the files that came with the MyTracker download.';
$l['req_step_error_tablelist'] = '<div class="error">
<h3>Error</h3>
<p>The MyTracker installation can\'t continue because you did not meet the MyTracker requirements. Please correct the errors below and try again:</p>
{1}
</div>';


$l['db_step_config_db'] = '<p>It is now time to configure the database that MyBB will use as well as your database authentication details. If you do not have this information, it can usually be obtained from your webhost.</p>';
$l['db_step_config_table'] = '<div class="border_wrapper">
<div class="title">Database Configuration</div>
<table class="general" cellspacing="0">
<tr>
	<th colspan="2" class="first last">Database Settings</th>
</tr>
<tr class="first">
	<td class="first"><label for="dbengine">Database Engine:</label></td>
	<td class="last alt_col"><select name="dbengine" id="dbengine" onchange="updateDBSettings();">{1}</select></td>
</tr>
{2}
</table>
</div>
<p>Once you\'ve checked these details are correct, click next to continue.</p>';

$l['database_settings'] = "Database Settings";
$l['database_path'] = "Database Path:";
$l['database_host'] = "Database Server Hostname:";
$l['database_user'] = "Database Username:";
$l['database_pass'] = "Database Password:";
$l['database_name'] = "Database Name:";
$l['table_settings'] = "Table Settings";
$l['table_prefix'] = "Table Prefix:";
$l['table_encoding'] = "Table Encoding:";

$l['db_step_error_config'] = '<div class="error">
<h3>Error</h3>
<p>There seems to be one or more errors with the database configuration information that you supplied:</p>
{1}
<p>Once the above are corrected, continue with the installation.</p>
</div>';
$l['db_step_error_invalidengine'] = 'You have selected an invalid database engine. Please make your selection from the list below.';
$l['db_step_error_noconnect'] = 'Could not connect to the database server at \'{1}\' with the supplied username and password. Are you sure the hostname and user details are correct?';
$l['db_step_error_nodbname'] = 'Could not select the database \'{1}\'. Are you sure it exists and the specified username and password have access to it?';
$l['db_step_error_missingencoding'] = 'You have not selected an encoding yet. Please make sure you selected an encoding before continuing. (Select \'UTF-8 Unicode\' if you are not sure)';
$l['db_step_error_sqlite_invalid_dbname'] = 'You may not use relative URLs for SQLite databases. Please use a file system path (ex: /home/user/database.db) for your SQLite database.';

$l['tablecreate_step_connected'] = '<p>Connection to the database server and table you specified was successful.</p>
<p>Database Engine: {1} {2}</p>
<p>The MyTracker database tables will now be created.</p>';
$l['tablecreate_step_created'] = 'Creating table {1}...';
$l['tablecreate_step_done'] = '<p>All tables have been created, click Next to populate them.</p>';

$l['populate_step_insert'] = '<p>Now that the basic tables have been created, it\'s time to insert the default data.</p>';
$l['populate_step_inserted'] = '<p>The default data has successfully been inserted into the database. Click Next to insert the default MyTracker templates and stylesheets.</p>';


$l['theme_step_importing'] = '<p>Loading and importing template file...</p>';
$l['theme_step_imported'] = '<p>The default template sets have been successfully inserted. Click Next to finish the MyTracker installation.</p>';

$l['done_step_success'] = '<p class="success">MyTracker has been successfully installed!</p>
<p>Thanks for installing MyTracker!</p>';
$l['done_step_locked'] = '<p>Your installer has been locked. To unlock the installer please delete the \'lock\' file in this directory.</p><p>All you need to do now is activate the plugin in your <a href="../../{1}/index.php">Admin Control Panel</a>!</p>';
$l['done_step_dirdelete'] = '<p><strong><span style="colour:red">Please remove this install directory to protect your forum!</span></strong></p>';
$l['done_subscribe_mailing'] = '<div class="error"><p><strong>Important Notes</strong></p><p>There\'s just a few important things you need to remember before you start:</p>
<ul>
	<li><p><strong>MyTracker doesn\'t alter your Forum</strong>. It just adds to it. This means that if you want a nice link for your members to access the tracker, you\'ll need to modify the templates yourself.</p></li>
	<li><p>MyTracker is <strong>not an official plugin for MyBB</strong>. It was made by <a href="http://xekko.co.uk/">Xekko</a>. You might not receive help from MyBBoard.net.</p></li>
	<li><p><strong>Need help?</strong> Visit <a href="http://xekko.co.uk/mytracker/help.html">MyTracker Help</a> or <a href="http://resources.xekko.co.uk/">Xekko Resources</a>.
</ul>
<p>We hope you enjoy MyTracker!</p>';

/* UPGRADE LANGUAGE VARIABLES */
$l['upgrade'] = "Upgrade Process";
$l['upgrade_welcome'] = "<p>Welcome to the upgrade wizard for MyTracker {1}.</p><p>This upgrade wizard is currently being tested. Although we've tried it as best we could, there may still be errors or bugs in it.</p><p><strong>We strongly recommend that you also obtain a complete backup of your database and files before attempting to upgrade</strong> so if something goes wrong you can easily revert back to the previous version.  Also, ensure that your backups are complete before proceeding.</p><p>Make sure you only click Next ONCE on each step of the upgrade process. Pages may take a while to load depending on the size of your forum.</p><p>Once you are ready, please select your old version below and click Next to continue.</p>";
$l['upgrade_templates_reverted'] = 'New Templates';
$l['upgrade_templates_reverted_success'] = "<p>All of the templates have successfully been reverted to the new ones contained in this release. Please press next to continue with the upgrade process.</p>";
$l['upgrade_settings_sync'] = 'Settings Synchronisation';
$l['upgrade_settings_sync_success'] = "<p>The board settings have been synchronised with the latest in MyBB.</p><p>{1} new settings inserted along with {2} new setting groups.</p><p>To finalise the upgrade, please click next below to continue.</p>";
$l['upgrade_datacache_building'] = 'Data Cache Building';
$l['upgrade_building_datacache'] = '<p>Building cache\'s...';
$l['upgrade_continue'] = 'Please press next to continue';
$l['upgrade_locked'] = "<p>Your installer has been locked. To unlock the installer please delete the 'lock' file in this directory.</p><p>You may now proceed to your upgraded copy of <a href=\"../index.php\">MyTracker</a>.</p>";
$l['upgrade_removedir'] = 'Please remove this directory before exploring your upgraded MyBB.';
$l['upgrade_congrats'] = "<p>Congratulations, your copy of MyTracker has successfully been updated to {1}.</p>{2}<p><strong>What's Next?</strong></p><ul><li>Please check the upgrade information to see what's changed with this upgrade.</li><li>Ensure that your board and tracker are still fully functional.</li></ul>";
$l['upgrade_template_reversion'] = "Template Reversion Warning";
$l['upgrade_template_resync'] = "<p>All necessary database modifications have successfully been made to upgrade your board.</p><p>This upgrade will attempt to resync your theme stylesheets. Please remember to backup your forum and database before clicking next.</p>";
$l['upgrade_template_resync_complete'] = "<p>All templates sync'd successfully.</p>";
$l['upgrade_template_reversion_success'] = "<p>All necessary database modifications have successfully been made to upgrade your board.</p><p>This upgrade requires all templates to be reverted to the new ones contained in the package so please back up any custom templates you have made before clicking next.</p>";

/* Error messages */
$l['locked'] = 'The installer is currently locked, please remove the file named \'lock\' from the tracker install directory to continue.';
$l['no_mybb'] = "MyBB does not seem to be installed. Please install MyBB before continuing.";

/* Uninstall */
$l['uninstalled'] = "Uninstall MyTracker";
$l['mytracker_uninstalled'] = "<p>MyTracker will now try and uninstall the version you have on your forum. Before it does, it will try and create a backup of the data it already has. You'll find this data backup in the 'backups' folder in your MyTracker directory.</p>";
$l['data_backed_up'] = "<div style=\"padding-left:15px;\"><img src=\"images/tracker/rem_tick.gif\" alt=\"\" /> <strong>Data backed up successfully!</strong></div>";
$l['data_not_backed_up'] = "<div style=\"padding-left:15px;\"><img src=\"images/tracker/cross.gif\" alt=\"\" /> <strong>Data hasn't been backed up!</strong></div>";
$l['uninstall_success'] = "<div style=\"padding-left:15px;\"><img src=\"images/tracker/rem_tick.gif\" alt=\"\" /> <strong>MyTracker uninstalled successfully!</strong></div>
<p>MyTracker has been uninstalled. Press the 'Next' button to continue and reinstall MyTracker, or close (or navigate away from) this window. If you aren't reinstalling, then please remove this ./install/ directory to prevent malicious use.</p>";
$l['uninstall_fail'] = "<div style=\"padding-left:15px;\"><img src=\"images/tracker/cross.gif\" alt=\"\" /> <strong>MyTracker uninstall has failed!</strong></div>
<p>For some strange reason, MyTracker couldn't uninstall itself. If the tables exist in the database, then remove them manually or ask for help at the <a href=\"http://resources.xekko.co.uk\">Xekko Support Forums</a></p>";
$l['tracker_not_here'] = "<div style=\"padding-left:15px;\"><img src=\"images/tracker/cross.gif\" alt=\"\" /> <strong>Could not find MyTracker!</strong></div>
<p>It seems MyTracker doesn't exist in your database, so there's nothing to uninstall! If you run into any problems, visit the <a href=\"http://resources.xekko.co.uk\">Xekko Support Forums</a>. Click the 'Next' button to continue to install MyTracker. If you're not planning on installing it, then please remove this ./install/ directory to prevent misuse.</p>";
?>