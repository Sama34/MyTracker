<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: stages.php 10 2009-08-24 08:16:07Z Tomm $
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
	$stag_active = "active";
	$sg_raquo = "&raquo;";
}
elseif($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	$newstg_active = "active";
	$ng_raquo = "&raquo; ";
}
$extra_options = array(
	"\t\t<li class=\"{$newstg_active}\"><div class=\"margin_left\"><a href=\"./stages.php?action=new\">{$ng_raquo}{$lang->new_stage}</a></div></li>",
);
$stage_options = implode("\n", $extra_options);
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
			$db->update_query("tracker_stages", $update_array, "stageid ='".intval($key)."'");
		}
		$messages = "<div id=\"flash_message\" class=\"success\">{$lang->updated_orders}</div>";
	}
	$mybb->input['action'] = ''; // Reset, because we're doing inline message
}

if($mybb->input['action'] == "new" || $mybb->input['action'] == "do_new")
{
	add_breadcrumb($lang->new_stage);
	
	// Are we trying to save the new category?
	if($mybb->request_method == "post")
	{
		verify_post_check($mybb->input['my_post_key']);
		$errors = array();
		
		// Check the length of the name
		$mybb->input['name'] = trim($mybb->input['name']);
		if(!$mybb->input['name'])
		{
			$errors[] = $lang->stg_no_name;
		}
		if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
		{
			$errors[] = $lang->stg_short_name;
		}
		
		if(count($errors) > 0)
		{
			// Show inline error
			$messages = inline_error($errors);
			
			// Show posted values on page
			$value_1 = $mybb->input['name'];
			$value_2 = $mybb->input['displayorder'];
		}
		else
		{
			// Everything is fine, let's create the category
			$insert_array = array(
				"stagename" => $db->escape_string($mybb->input['name']),
				"disporder" => intval($mybb->input['displayorder']),
			);

			if(!$insert_array['disporder'])
			{
				// If there isn't a display order, let's whack it at the back
				$query = $db->simple_select("tracker_stages", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_stages)");
				$last_disporder = $db->fetch_field($query, "disporder");
				$insert_array['disporder'] = ++$last_disporder;
			}
			
			$db->insert_query("tracker_stages", $insert_array);
			redirect("stages.php", $lang->created_stage);
		}
	}
	
	eval("\$trackercp_stages_new = \"".$templates->get("mytrackercp_stages_new")."\";");
	output_page($trackercp_stages_new);
}

if($mybb->input['action'] == "delete" || $mybb->input['action'] == "do_delete")
{
	$mybb->input['stage'] = intval($mybb->input['stage']);
	$query = $db->simple_select("tracker_stages", "*", "stageid = '".$mybb->input['stage']."'");
	if(!$db->num_rows($query))
	{
		// No such status
		error($lang->no_stage_found);
	}
	else
	{
		// Stage exists, get the info
		// Trail
		add_breadcrumb($lang->stages, "stages.php");
		add_breadcrumb($lang->del_stg_crumb);
		
		$stage = $db->fetch_array($query);
		$stage['name'] = htmlspecialchars_uni($stage['stagename']);
		
		if($mybb->request_method == "post")
		{
			verify_post_check($mybb->input['my_post_key']);
			
			// You can't delete stage #1 - None
			if($stage['stageid'] == 1)
			{
				error($lang->no_delete_stage_num1);
			}
			
			// Update the Projects first
			$db->update_query("tracker_projects", array("stage" => 1), "stage = '".$stage['stageid']."'");
			// Delete
			$db->delete_query("tracker_stages", "stageid = '".$stage['stageid']."'");
			redirect("stages.php", $lang->stg_deleted);
		}
		
		// Output normal page if we're not deleting - we haven't confirmed yet		
		eval("\$trackercp_stages_delete = \"".$templates->get("mytrackercp_stages_delete")."\";");
		output_page($trackercp_stages_delete);
	}
}

if($mybb->input['action'] == "edit" || $mybb->input['action'] == "do_edit")
{
	$mybb->input['stage'] = intval($mybb->input['stage']);
	$query = $db->simple_select("tracker_stages", "*", "stageid = '".$mybb->input['stage']."'");
	if(!$db->num_rows($query))
	{
		// No such category
		error($lang->no_stage_found); // Oh dear, where's the RSPCA?... :/
	}
	else
	{
		$stage = $db->fetch_array($query);
		$stage['name'] = htmlspecialchars_uni($stage['stagename']);

		// Default Form values
		$value_1 = $stage['stagename'];
		$value_2 = $stage['disporder'];
		
		if($mybb->request_method == "post")
		{
			$errors = array();
			
			$mybb->input['name'] = trim($mybb->input['name']);
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->stg_edit_no_name;
			}
			if($mybb->input['name'] && my_strlen($mybb->input['name']) < 3)
			{
				$errors[] = $lang->stg_short_name;
			}
			
			if(count($errors) > 0)
			{
				$messages = inline_error($errors);
				$value_1 = $mybb->input['name'];
				$value_2 = $mybb->input['displayorder'];
			}
			else
			{
				$update_array = array(
					"stagename" => $db->escape_string($mybb->input['name']),
					"disporder" => intval($mybb->input['displayorder'])
				);

				if(!$update_array['disporder'])
				{
					// If there isn't a display order, let's whack it at the back
					$query = $db->simple_select("tracker_stages", "disporder", "disporder = (SELECT MAX(disporder) FROM ".TABLE_PREFIX."tracker_stages)");
					$last_disporder = $db->fetch_field($query, "disporder");
					$update_array['disporder'] = ++$last_disporder;
				}

				$db->update_query("tracker_stages", $update_array, "stageid = '".$stage['stageid']."'");
				redirect("stages.php", $lang->stage_saved);
			}
		}

		add_breadcrumb($lang->stages, "stages.php");
		add_breadcrumb($lang->editing_stage);
		
		eval("\$trackercp_stages_edit = \"".$templates->get("mytrackercp_stages_edit")."\";");
		output_page($trackercp_stages_edit);
	}
}


if(!$mybb->input['action'])
{
	// Breadcrumb thingy
	add_breadcrumb($lang->sta_crumb);

	// Loop through the category list
	$query = $db->simple_select("tracker_stages", "*", "", array("order_by" => "disporder", "order_dir" => "ASC"));
	while($stage = $db->fetch_array($query))
	{
		$bgcolor = alt_trow();
		$stage['name'] = htmlspecialchars_uni($stage['stagename']);
		if($stage['stageid'] == 1)
		{
			$delete_link = '';
		}
		else
		{
			$delete_link = " &middot; <a href=\"stages.php?action=delete&amp;stage={$stage['stageid']}\">{$lang->delete}</a>";
		}
		eval("\$stage_list .= \"".$templates->get("mytrackercp_stages_stagelist")."\";");
	}
	
	eval("\$trackercp_stages = \"".$templates->get("mytrackercp_stages")."\";");
	output_page($trackercp_stages);
}
?>