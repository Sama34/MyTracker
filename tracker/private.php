<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: private.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
//---------------------------------------------------
// This is because MyBB's PM "dismiss notice" will stupidly default to /tracker/private.php
//---------------------------------------------------
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
chdir(dirname(dirname(__FILE__)));
require_once "./global.php";

// Verify incoming POST request
verify_post_check($mybb->input['my_post_key']);

if($mybb->input['action'] == "dismiss_notice")
{
	if($mybb->user['pmnotice'] != 2)
	{
		exit;
	}

	$updated_user = array(
		"pmnotice" => 1
	);
	$db->update_query("users", $updated_user, "uid='{$mybb->user['uid']}'");

	if($mybb->input['ajax'])
	{
		echo 1;
		exit;
	}
}
?>