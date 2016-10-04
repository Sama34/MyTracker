<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: categories.php 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define("IN_TRACKER_CP", 1);
define('THIS_SCRIPT', 'tracker/admin/categories.php');
$templatelist = "mytrackercp_mainmenu, mytrackercp_categories_categorylist, mytrackercp_categories, mytrackercp_categories_edit, mytrackercp_categories_delete, mytrackercp_categories_new";

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
	$cat_active = "active";
	$ca_raquo = "&raquo;";
}
elseif($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	$newcat_active = "active";
	$ne_raquo = "&raquo; ";
}
$extra_options = array(
	"\t\t<li class=\"{$newcat_active}\"><div class=\"margin_left\"><a href=\"./categories.php?action=new\">{$ne_raquo}{$lang->new_category}</a></div></li>",
);
$category_options = implode("\n", $extra_options);
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
			$db->update_query("tracker_categories", $update_array, "catid ='".intval($key)."'");
		}
		$messages = "<div id=\"flash_message\" class=\"success\">{$lang->updated_orders}</div>";
	}
	$mybb->input['action'] = ''; // Reset, because we're doing inline message
}

if($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	add_breadcrumb($lang->new_category);
	
	// Are we trying to save the new category?
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();
		
		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->cat_no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->cat_short_name;
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
				"catname" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['displayorder']),
				"forgroups" => ''
			);

			if(!$insert_array['disporder'])
			{
				// If there isn't a display order, let's whack it at the back
				$query = $db->simple_select("tracker_categories", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_categories)");
				$last_disporder = $db->fetch_field($query, "disporder");
				$insert_array['disporder'] = ++$last_disporder;
			}

			if($mybb->input['group'])
			{
				// We've selected groups, so let's figure out which ones we've added
				$insert_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
			}
			
			$db->insert_query("tracker_categories", $insert_array);
			redirect("categories.php", $lang->created_category);
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
	
	eval("\$trackercp_categories_new = \"".$templates->get("mytrackercp_categories_new")."\";");
	output_page($trackercp_categories_new);
}

if($mybb->input['action'] == "delete" || $mybb->input['action'] == "do_delete")
{
	$mybb->input['category'] = intval($mybb->input['category']);
	$query = $db->simple_select("tracker_categories", "*", "catid = '".$mybb->input['category']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_cat_found); // Oh dear, where's the RSPCA?... :/
	}
	else
	{
		// Category exists, get the info
		// Trail
		add_breadcrumb($lang->categories, "categories.php");
		add_breadcrumb($lang->del_cat_crumb);
		
		$category = $db->fetch_array($query);
		$category['name'] = htmlspecialchars_uni($category['catname']);
		
		if($mybb->request_method == "post")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			// You can't delete category #1 - None
			if($category['catid'] == 1)
			{
				error($lang->no_delete_num1);
			}
			
			// Update the issues first
			$db->update_query("tracker_issues", array("category" => 1), "category = '".$category['catid']."'");
			// Delete
			$db->delete_query("tracker_categories", "catid = '".$category['catid']."'");
			redirect("categories.php", $lang->cat_deleted);
		}
		
		// Output normal page if we're not deleting - we haven't confirmed yet		
		eval("\$trackercp_categories_delete = \"".$templates->get("mytrackercp_categories_delete")."\";");
		output_page($trackercp_categories_delete);
	}
}

if($mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit")
{
	$mybb->input['category'] = intval($mybb->input['category']);
	$query = $db->simple_select("tracker_categories", "*", "catid = '".$mybb->input['category']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_cat_found); // Oh dear, where's the RSPCA?... :/
	}
	else
	{
		$category = $db->fetch_array($query);
		$category['name'] = htmlspecialchars_uni($category['catname']);

		// Default Form values
		$value_1 = $category['catname'];
		$value_2 = $category['disporder'];
		
		if($mybb->request_method == "post")
		{
			$errors = array();
			
			$mybb->input['name'] = trim($mybb->input['name']);
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->cat_edit_no_name;
			}
			if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
			{
				$errors[] = $lang->cat_short_name;
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
					"catname" => $db->escape_string($mybb->input['name']),
					"disporder" => intval($mybb->input['displayorder']),
					"forgroups" => ''
				);

				if(!$update_array['disporder'])
				{
					// If there isn't a display order, let's whack it at the back
					$query = $db->simple_select("tracker_categories", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_categories)");
					$last_disporder = $db->fetch_field($query, "disporder");
					$update_array['disporder'] = ++$last_disporder;
				}
	
				if($mybb->input['group'])
				{
					// We've selected groups, so let's figure out which ones we've added
					$update_array['forgroups'] = $db->escape_string(implode(",", $mybb->input['group']));
				}

				$db->update_query("tracker_categories", $update_array, "catid = '".$category['catid']."'");
				redirect("categories.php", $lang->category_saved);
			}
		}

		add_breadcrumb($lang->categories, "categories.php");
		add_breadcrumb($lang->editing_cat);

		// List of groups, why did I decide to do it this way... ^_^;;;
		$selected_groups = explode(",", $category['forgroups']);
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
		
		eval("\$trackercp_categories_edit = \"".$templates->get("mytrackercp_categories_edit")."\";");
		output_page($trackercp_categories_edit);
	}
}


if(!$mybb->input['action'])
{
	// Editing Categories thing
	add_breadcrumb($lang->cat_crumb);

	// Loop through the category list
	$query = $db->simple_select("tracker_categories", "*", "", array("order_by" => "disporder", "order_dir" => "ASC"));
	while($category = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$category['name'] = htmlspecialchars_uni($category['catname']);
		eval("\$category_list .= \"".$templates->get("mytrackercp_categories_categorylist")."\";");
	}
	
	eval("\$trackercp_categories = \"".$templates->get("mytrackercp_categories")."\";");
	output_page($trackercp_categories);
}

































?>