<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: statuses.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/categories.php');
$templatelist = "mytrackercp_mainmenu";

chdir(dirname(dirname(dirname(__FILE__))));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

add_breadcrumb($mybb->settings['trackername'], "../");
add_breadcrumb($lang->trackercp, "./");

// Verify that we can access the CP
if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
{
	$ismod = true;
}
else
{
	error_no_permission(); // Global; if not a mod, they can't do anything past here
}

// Main Menu
// We have an extra option for this one, too
if(!$mybb->input['action'] || $mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit" || $mybb->input['action'] == "save_orders")
{
	$sta_active = "active";
	$st_raquo = "&raquo;";
}
elseif($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	$newsta_active = "active";
	$ns_raquo = "&raquo; ";
}
$extra_options = array(
	"\t\t<li class=\"{$newsta_active}\"><div class=\"margin_left\"><a href=\"./statuses.php?action=new\">{$ns_raquo}{$lang->new_status}</a></div></li>",
);
$status_options = implode("\n", $extra_options);
eval("\$main_menu = \"".$templates->get("mytrackercp_mainmenu")."\";");

// Saving the display orders
if($mybb->input['action'] == "save_orders")
{
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		
		foreach($mybb->input['disporder'] as $key => $value)
		{
			$update_array = array(
				"disporder" => intval($value)
			);
			$db->update_query("tracker_status", $update_array, "statid ='".intval($key)."'");
		}
		$messages = "<div id=\"flash_message\" class=\"success\">{$lang->updated_orders}</div>";
	}
	$mybb->input['action'] = ''; // Reset, because we're doing inline message
}

if($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	add_breadcrumb($lang->new_status);
	
	// Are we trying to save the new category?
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();
		
		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->sta_no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->sta_short_name;
		}
		
		if(count($errors) > 0)
		{
			// Show inline error
			$messages = inline_error($errors);
			
			// Show posted values on page
			$value_1 = $mybb->input['name'];
			$value_2 = $mybb->input['displayorder'];
			if($mybb->input['group'])
			{
				foreach($mybb->input['group'] as $group)
				{
					$mybb->input[$group]['checked'] = "checked=\"checked\"";
				}
			}
		}
		else
		{
			// Everything is fine, let's create the category
			$insert_array = array(
				"statusname" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['displayorder']),
				"forgroups" => ''
			);

			if(!$insert_array['disporder'])
			{
				// If there isn't a display order, let's whack it at the back
				$query = $db->simple_select("tracker_status", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_status)");
				$last_disporder = $db->fetch_field($query, "disporder");
				$insert_array['disporder'] = ++$last_disporder;
			}

			if($mybb->input['group'])
			{
				// We've selected groups, so let's figure out which ones we've added
				$insert_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
			}
			
			$db->insert_query("tracker_status", $insert_array);
			redirect("statuses.php", $lang->created_status);
		}
	}

	// Generate the group list to choose from
	// Guests can't post in the tracker, so eliminate them
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array("order_by" => "gid", "order_dir" => "ASC"));
	while($group = $db->fetch_array($query))
	{
		$group['title'] = htmlspecialchars_uni($group['title']);
		$group_list .= "<label for=\"group[".$group['gid']."]\"><input type=\"checkbox\" name=\"group[".$group['gid']."]\"".$mybb->input[$group['gid']]['checked']." value=\"".$group['gid']."\" id=\"group[".$group['gid']."]\" /> ".$group['title']."</label><br />\n";
	}
	
	eval("\$trackercp_statuses_new = \"".$templates->get("mytrackercp_statuses_new")."\";");
	output_page($trackercp_statuses_new);
}

if($mybb->input['action'] == "delete" || $mybb->input['action'] == "do_delete")
{
	$mybb->input['status'] = intval($mybb->input['status']);
	$query = $db->simple_select("tracker_status", "*", "statid = '".$mybb->input['status']."'");
	if(!$db->num_rows($query))
	{
		// No such status
		error($lang->no_status_found);
	}
	else
	{
		// Status exists, get the info
		// Trail
		add_breadcrumb($lang->statuses, "statuses.php");
		add_breadcrumb($lang->del_sta_crumb);
		
		$status = $db->fetch_array($query);
		$status['name'] = htmlspecialchars_uni($status['statusname']);
		
		if($mybb->request_method == "post")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			// You can't delete category #1 - None
			if($status['statid'] == 1)
			{
				error($lang->no_delete_stat_num1);
			}
			
			// Update the issues first
			$db->update_query("tracker_issues", array("status" => 1), "status = '".$status['statid']."'");
			// Delete
			$db->delete_query("tracker_status", "statid = '".$status['statid']."'");
			redirect("statuses.php", $lang->sta_deleted);
		}
		
		// Output normal page if we're not deleting - we haven't confirmed yet		
		eval("\$trackercp_statuses_delete = \"".$templates->get("mytrackercp_statuses_delete")."\";");
		output_page($trackercp_statuses_delete);
	}
}

if($mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit")
{
	$mybb->input['status'] = intval($mybb->input['status']);
	$query = $db->simple_select("tracker_status", "*", "statid = '".$mybb->input['status']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_status_found); // Oh dear, where's the RSPCA?... :/
	}
	else
	{
		$status = $db->fetch_array($query);
		$status['name'] = htmlspecialchars_uni($status['statusname']);

		// Default Form values
		$value_1 = $status['statusname'];
		$value_2 = $status['disporder'];
		
		if($mybb->request_method == "post")
		{
			$errors = array();
			
			$mybb->input['name'] = trim($mybb->input['name']);
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->sta_edit_no_name;
			}
			if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
			{
				$errors[] = $lang->sta_short_name;
			}
			
			if(count($errors) > 0)
			{
				$messages = inline_error($errors);
				$value_1 = $mybb->input['name'];
				$value_2 = $mybb->input['displayorder'];
				if($mybb->input['group'])
			{
				foreach($mybb->input['group'] as $group)
				{
					$mybb->input[$group]['checked'] = "checked=\"checked\"";
				}
			}
			}
			else
			{
				$update_array = array(
					"statusname" => $db->escape_string($mybb->input['name']),
					"disporder" => intval($mybb->input['displayorder']),
					"forgroups" => ''
				);

				if(!$update_array['disporder'])
				{
					// If there isn't a display order, let's whack it at the back
					$query = $db->simple_select("tracker_status", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_status)");
					$last_disporder = $db->fetch_field($query, "disporder");
					$update_array['disporder'] = ++$last_disporder;
				}
	
				if($mybb->input['group'])
				{
					// We've selected groups, so let's figure out which ones we've added
					$update_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
				}

				$db->update_query("tracker_status", $update_array, "statid = '".$status['statid']."'");
				redirect("statuses.php", $lang->status_saved);
			}
		}

		add_breadcrumb($lang->statuses, "statuses.php");
		add_breadcrumb($lang->editing_status);

		// List of groups, why did I decide to do it this way... ^_^;;;
		$selected_groups = explode(",", $status['forgroups']);
		foreach($selected_groups as $group)
		{
			$mybb->input[$group]['checked'] = " checked=\"checked\"";
		}

		// Provide checkboxes
		$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array("order_by" => "gid", "order_dir" => "ASC"));
		while($group = $db->fetch_array($query))
		{
			$group['title'] = htmlspecialchars_uni($group['title']);
			$group_list .= "<label for=\"group[".$group['gid']."]\"><input type=\"checkbox\" name=\"group[".$group['gid']."]\"".$mybb->input[$group['gid']]['checked']." value=\"".$group['gid']."\" id=\"group[".$group['gid']."]\" /> ".$group['title']."</label><br />\n";
		}
		
		eval("\$trackercp_statuses_edit = \"".$templates->get("mytrackercp_statuses_edit")."\";");
		output_page($trackercp_statuses_edit);
	}
}


if(!$mybb->input['action'])
{
	// Editing Categories thing
	add_breadcrumb($lang->sta_crumb);

	// Loop through the category list
	$query = $db->simple_select("tracker_status", "*", "", array("order_by" => "disporder", "order_dir" => "ASC"));
	while($status = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$status['name'] = htmlspecialchars_uni($status['statusname']);
		if($status['statid'] == 1)
		{
			$delete_link = '';
		}
		else
		{
			$delete_link = " &middot; <a href=\"statuses.php?action=delete&amp;status={$status['statid']}\">{$lang->delete}</a>";
		}
		eval("\$status_list .= \"".$templates->get("mytrackercp_statuses_statuslist")."\";");
	}
	
	eval("\$trackercp_statuses = \"".$templates->get("mytrackercp_statuses")."\";");
	output_page($trackercp_statuses);
}
?>