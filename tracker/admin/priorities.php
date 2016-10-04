<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: priorities.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/priorities.php');
$templatelist = "mytrackercp_mainmenu, mytrackercp_priorities_categorylist, mytrackercp_priorities_prioritylist, mytrackercp_priorities, mytrackercp_priorities_edit, mytrackercp_priorities_delete, mytrackercp_priorities_new";

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
	$pri_active = "active";
	$pr_raquo = "&raquo;";
}
elseif($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	$newpri_active = "active";
	$np_raquo = "&raquo; ";
}
$extra_options = array(
	"\t\t<li class=\"{$newpri_active}\"><div class=\"margin_left\"><a href=\"./priorities.php?action=new\">{$np_raquo}{$lang->new_priority}</a></div></li>",
);
$priority_options = implode("\n", $extra_options);
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
			$db->update_query("tracker_priorities", $update_array, "priorid ='".intval($key)."'");
		}
		$messages = "<div id=\"flash_message\" class=\"success\">{$lang->updated_orders}</div>";
	}
	$mybb->input['action'] = ''; // Reset, because we're doing inline message
}

if($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	add_breadcrumb($lang->new_priority);

	// Are we trying to save the new category?
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();

		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->pri_no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->pri_short_name;
		}

		if(count($errors) > 0)
		{
			// Show inline error
			$messages = inline_error($errors);
			
			// Show posted values on page
			$value_1 = $mybb->input['name'];
			$value_2 = $mybb->input['displayorder'];
			$value_3 = $mybb->input['style'];
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
				"priorityname" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['displayorder']),
				"forgroups" => '',
				"priorstyle" => $db->escape_string($mybb->input['style'])
			);

			if(!$insert_array['disporder'])
			{
				// If there isn't a display order, let's whack it at the back
				$query = $db->simple_select("tracker_priorities", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_priorities)");
				$last_disporder = $db->fetch_field($query, "disporder");
				$insert_array['disporder'] = ++$last_disporder;
			}

			if($mybb->input['group'])
			{
				// We've selected groups, so let's figure out which ones we've added
				$insert_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
			}
			
			$db->insert_query("tracker_priorities", $insert_array);
			redirect("priorities.php", $lang->created_priority);
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
	
	eval("\$trackercp_priorities_new = \"".$templates->get("mytrackercp_priorities_new")."\";");
	output_page($trackercp_priorities_new);
}

if($mybb->input['action'] == "delete" || $mybb->input['action'] == "do_delete")
{
	$mybb->input['priority'] = intval($mybb->input['priority']);
	$query = $db->simple_select("tracker_priorities", "*", "priorid = '".$mybb->input['priority']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_pri_found);
	}
	else
	{
		// Priority exists, get the info
		// Trail
		add_breadcrumb($lang->priorities, "priorities.php");
		add_breadcrumb($lang->del_pri_crumb);
		
		$priority = $db->fetch_array($query);
		$priority['name'] = htmlspecialchars_uni($priority['priorityname']);
		
		if($mybb->request_method == "post")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			// You can't delete priority #1, 2, 3, 4 or 5 - None
			$check_array = array(1, 2, 3, 4, 5);
			if(in_array($priority['priorid'], $check_array))
			{
				error($lang->no_delete_num1);
			}
			
			// Update the issues first
			$db->update_query("tracker_issues", array("priority" => 1), "priority = '".$priority['priorid']."'");
			// Delete
			$db->delete_query("tracker_priorities", "priorid = '".$priority['priorid']."'");
			redirect("priorities.php", $lang->pri_deleted);
		}
		
		// Output normal page if we're not deleting - we haven't confirmed yet		
		eval("\$trackercp_priorities_delete = \"".$templates->get("mytrackercp_priorities_delete")."\";");
		output_page($trackercp_priorities_delete);
	}
}

if($mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit")
{
	$mybb->input['priority'] = intval($mybb->input['priority']);
	$query = $db->simple_select("tracker_priorities", "*", "priorid = '".$mybb->input['priority']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_pri_found); // Oh dear, where's the RSPCA?... :/
	}
	else
	{
		$priority = $db->fetch_array($query);
		$priority['name'] = htmlspecialchars_uni($priority['priorityname']);

		// Default Form values
		$value_1 = $priority['priorityname'];
		$value_2 = $priority['disporder'];
		$value_3 = $priority['priorstyle'];
		
		if($mybb->request_method == "post")
		{
			$errors = array();
			
			$mybb->input['name'] = trim($mybb->input['name']);
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->pri_edit_no_name;
			}
			if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
			{
				$errors[] = $lang->pri_short_name;
			}
			
			if(count($errors) > 0)
			{
				$messages = inline_error($errors);
				$value_1 = $mybb->input['name'];
				$value_2 = $mybb->input['displayorder'];
				$value_3 = $mybb->input['style'];
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
					"priorityname" => $db->escape_string($mybb->input['name']),
					"disporder" => intval($mybb->input['displayorder']),
					"priorstyle" => $db->escape_string($mybb->input['style']),
					"forgroups" => ''
				);

				if(!$update_array['disporder'])
				{
					// If there isn't a display order, let's whack it at the back
					$query = $db->simple_select("tracker_priorities", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_priorities)");
					$last_disporder = $db->fetch_field($query, "disporder");
					$update_array['disporder'] = ++$last_disporder;
				}
	
				if($mybb->input['group'])
				{
					// We've selected groups, so let's figure out which ones we've added
					$update_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
				}

				$db->update_query("tracker_priorities", $update_array, "priorid = '".$priority['priorid']."'");
				redirect("priorities.php", $lang->priority_saved);
			}
		}

		add_breadcrumb($lang->priorities, "priorities.php");
		add_breadcrumb($lang->edit_pri);

		// List of groups, why did I decide to do it this way... ^_^;;;
		$selected_groups = explode(",", $priority['forgroups']);
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
		
		eval("\$trackercp_priorities_edit = \"".$templates->get("mytrackercp_priorities_edit")."\";");
		output_page($trackercp_priorities_edit);
	}
}


if(!$mybb->input['action'])
{
	add_breadcrumb($lang->pri_crumb);

	// Loop through the category list
	$query = $db->simple_select("tracker_priorities", "*", "", array("order_by" => "disporder", "order_dir" => "ASC"));
	while($priority = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$priority['name'] = htmlspecialchars_uni($priority['priorityname']);
		// Check if this priority can't be deleted
		$check_array = array(1,2,3,4,5);
		if(!in_array($priority['priorid'], $check_array))
		{
			$delete_link = " &middot; <a href=\"priorities.php?action=delete&amp;priority={$priority['priorid']}\">{$lang->delete}</a>";
		}
		else
		{
			$delete_link = '';
		}

		eval("\$priority_list .= \"".$templates->get("mytrackercp_priorities_prioritylist")."\";");
	}
	
	eval("\$trackercp_priorities = \"".$templates->get("mytrackercp_priorities")."\";");
	output_page($trackercp_priorities);
}
?>