<?php
/*
+--------------------------------------------------------------------------
|   MyTracker
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   (c) 2009 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mytrackercp.lang.php 3 2009-08-03 15:11:27Z Tomm $
|
|	Language file for MyTracker CP
+--------------------------------------------------------------------------
*/
//---------------------------------------------------
// 'Admin' Language Variables
//---------------------------------------------------
$l['trackercp'] = "MyTracker Control Panel";
$l['trackerversion'] = "MyTracker Version";
$l['issues_new_today'] = "{1} New Today";
$l['ideas_new_today'] = "{1} New Today";
$l['comments_new_today'] = "{1} New Today";

// Outdated version
$l['outdated_version'] = "Your version of MyTracker is out of date! The latest version available is {1} ({2}).";
$l['update_link'] = "Visit the <a href=\"http://xekko.co.uk/mytracker/\">MyTracker Homepage</a> to find out how to upgrade.";
// Up to date version
$l['uptodate_version'] = "Your version of MyTracker is up to date!";
$l['checked_for_updates'] = "Checking for updates from the server...";
$l['uptodate_version_checked'] = "Your version of MyTracker is still up to date!";

// Latest News
$l['no_xekko_news'] = "There's no news from Xekko at the moment.";
$l['no_xekko_server'] = "Couldn't connect to the Server. Please visit the <a href=\"http://xekko.co.uk/\">Xekko Homepage</a> if it's available.";

// Main Menu
$l['main_menu'] = "Main Menu";
$l['cp_home'] = "CP Home";
$l['menu_projects'] = "Projects";
$l['menu_categories'] = "Categories";
$l['menu_priorities'] = "Priorities";
$l['menu_statuses'] = "Statuses";
$l['menu_stages'] = "Stages";
$l['menu_options'] = "Options";
$l['menu_updates'] = "Check for Updates";
$l['menu_trackhome'] = "MyTracker Homepage";

// Projects Section
$l['projects'] = "Projects";
$l['project'] = "Project";
$l['unknown_project'] = "That specific project does not exist.";
$l['allprojects'] = "All Projects";
$l['options'] = "Options";
$l['order'] = "Order";
$l['save_project'] = "Save Project";
$l['create_project'] = "Create Project";
$l['save_order'] = "Save Project Orders";
$l['reset_order'] = "Reset";
$l['updated_orders'] = "Display orders updated!";
$l['relative_ago'] = "ago";
$l['relative_to_go'] = "to go";
$l['relative_plural'] = "s";
$l['moments'] = "moments ago";
$l['edit'] = "Edit";
$l['delete'] = "Delete";
$l['goto'] = "View Project";
$l['new_project'] = "New Project";
$l['manage_devs'] = "Manage Developers";

// Deleting a Project
$l['delete_project'] = "Delete Project";
$l['confirm_delete_projects'] = "Are you sure you want to delete this project? You will be removing all issues, idea suggestions and comments that have been made.<br /><br /><strong>This cannot be undone!</strong>";
$l['project_deleted'] = "The Project has been deleted.<br />Please wait while we transfer you back to the Control Panel.";

// New/Editing a Project
$l['edit_project'] = "Editing Project: {1}";
$l['active_warning'] = "By setting this to 'No', it will only be visible in the Control Panel.";
$l['stats'] = "Statistics";
$l['person'] = "person";
$l['people'] = "people";
$l['has'] = "has";
$l['have'] = "have";
$l['info'] = "Information";
$l['stats_info_1'] = "This project has {1} issues";
$l['stats_info_2'] = " and {1} feature suggestions";
$l['stats_info_3'] = "{1} {2} {3} left comments.";
$l['stats_info_4'] = "Overall, {1}% of issues have been resolved. The project was added to the Tracker {2}.";
$l['new_info'] = "<strong>Creating a New Project</strong><br/>By creating a new project, you're opening up an easy way to manage your project's problems and future features.<p>If this is a private project, then set 'Project Active' to 'No' and it will not be seen by normal users.</p><p>If you do not want users to add feature suggestions, set 'Allow Features' to 'No'.</p>";
$l['project_name'] = "Project Name";
$l['description'] = "Description";
$l['stage'] = "Stage";
$l['project_active'] = "Project Active?";
$l['allow_features'] = "Allow Features?";
$l['saved_project'] = "The Project has been saved!<br />Please wait while we transfer you back to the Control Panel.";
$l['created_project'] = "Your Project has been created!<br />Please wait while we transfer you back to the Control Panel.";
$l['no_name'] = "You didn't enter a name for your Project. Please enter a name before creating a project.";
$l['short_name'] = "The name you entered isn't long enough for a Project name. Please enter a longer name for your Project.";

// Managing Moderators / Developers
$l['manage_groups'] = "Manage Moderators (Groups)";
$l['group_name'] = "Group Name";
$l['can_mod'] = "Moderate?";
$l['deny'] = "Deny";
$l['allow'] = "Allow";
$l['no_groups'] = "Could not find any group to update.";
$l['no_edit_admin'] = "You can't remove an Administrator's permissions.";
$l['group_updated'] = "Group Permissions updated successfully!";
$l['manage_devs'] = "Manage Developers";
$l['dev_username'] = "Username";
$l['remove_dev'] = "Remove";
$l['no_devs'] = "There are no active Developers.";
$l['add_a_dev'] = "Add a Developer";
$l['add_dev'] = "Add Developer";
$l['added_dev'] = "Developer added!<br />Please wait while we transfer you back to the Control Panel.";
$l['removed_dev'] = "The Developer has been removed.<br />Please wait while we transfer you back to the Control Panel.";
$l['no_find_dev'] = "We couldn't find the Developer. Please go back and try again.";

// Managing Categories
$l['cat_crumb'] = "Managing Categories";
$l['new_category'] = "New Category";
$l['allcategories'] = "All Categories";
$l['category'] = "Category";
$l['categories'] = "Categories";
$l['save_cat_order'] = "Save Category Order";
$l['category_name'] = "Category Name";
$l['cat_displayorder'] = "Display Order";
$l['cat_forgroups'] = "Groups?";
$l['create_category'] = "Create Category";
$l['category_info'] = "Here you can create a new category to which you can assign issues to (so you can easily keep track of where things are going wrong!).<br /><br />Don't forget to select which groups you want to be able to use this new category.";
$l['cat_no_name'] = "You didn't enter a name for your Category. Please enter a name before creating a new category.";
$l['cat_edit_no_name'] = "You didn't enter a name for your Category. Please enter a name before saving the category.";
$l['cat_short_name'] = "Please enter a longer category name.";
$l['created_category'] = "The new Category was successfully created!<br/>Please wait while we transfer you back to the Control Panel.";
$l['no_cat_found'] = "The specified Category couldn't be found in the database.";
$l['confirm_delete_category'] = "Are you sure you want to delete this category? All current issues assigned under this category will now be assigned under 'None'.<br /><br /><strong>This cannot be undone!</strong>";
$l['delete_category'] = "Delete Category";
$l['del_cat_crumb'] = "Delete Category";
$l['cat_deleted'] = "The Category has been deleted.<br />Please wait while we transfer you back to the Control Panel.";
$l['no_delete_num1'] = "You can't delete this default Category.";
$l['edit_category_info'] = "Don't forget to select which groups you want to be able to use this category. If you don't select any groups, then it will not be visible in the Category list.";
$l['save_category'] = "Save Category";
$l['category_saved'] = "The Category has been saved.<br />Please wait while we transfer you back to the Control Panel.";
$l['editing_cat'] = "Editing Category";

// Managing Priorities
$l['new_priority'] = "New Priority";
$l['pri_crumb'] = "Managing Priorities";
$l['allpriorities'] = "All Priorities";
$l['no_pri_found'] = "The specified Priority couldn't be found in the database.";
$l['priorities'] = "Priorities";
$l['priority'] = "Priority";
$l['save_pri_order'] = "Save Priority Order";
$l['new_priority'] = "New Priority";
$l['priority_name'] = "Priority Name";
$l['pri_displayorder'] = "Display Order";
$l['pri_forgroups'] = "Groups?";
$l['priority_style'] = "Style";
$l['create_priority'] = "Create Priority";
$l['priority_info'] = "By creating a new priority, you're creating a new level of importance for your issues! You can also 'style' your priorities using CSS (no HTML). For example, entering this into the 'style' box: <pre>background:#F0A05A; color:#666666;</pre> ...will result in this style:<br /><br /><div style=\"background:#F0A05A; color:#666666;\">An example.</div><br />Always test to ensure you have the right style that you want to use.";
$l['created_priority'] = "The Priority has been created.<br />Please wait while we transfer you back to the Control Panel.";
$l['pri_no_name'] = "You didn't enter a name for your Priority. Please enter a name before creating a new priority.";
$l['pri_edit_no_name'] = "You didn't enter a name for your Priority. Please enter a name before saving the priority.";
$l['pri_short_name'] = "Please enter a longer priority name.";
$l['del_pri_crumb'] = "Delete Priority";
$l['confirm_delete_priority'] = "Are you sure you want to delete this priority? All current issues assigned under this priority will now be assigned under 'None'.<br /><br /><strong>This cannot be undone!</strong>";
$l['delete_priority'] = "Delete Priority";
$l['pri_deleted'] = "The Priority has been deleted.<br />Please wait while we transfer you back to the Control Panel.";
$l['edit_pri'] = "Editing Priority";
$l['save_priority'] = "Save Priority";
$l['edit_priority_info'] = "By creating a new priority, you're creating a new level of importance for your issues! You can also 'style' your priorities using CSS (no HTML). For example, entering this into the 'style' box: <pre>background:#F0A05A; color:#666666;</pre> ...will result in this style:<br /><br /><div style=\"background:#F0A05A; color:#666666;\">An example.</div><br />Always test to ensure you have the right style that you want to use.";
$l['priority_saved'] = "The Category has been saved.<br />Please wait while we transfer you back to the Control Panel.";

// Managing Statuses
$l['new_status'] = "New Status";
$l['sta_crumb'] = "Managing Statuses";
$l['allstatuses'] = "All Statuses";
$l['no_status_found'] = "The specified Status couldn't be found in the database.";
$l['no_delete_stat_num1'] = "You can't delete this Status.";
$l['statuses'] = "Statuses";
$l['status'] = "Status";
$l['save_status_order'] = "Save Status Order";
$l['new_status'] = "New Status";
$l['status_name'] = "Status Name";
$l['sta_displayorder'] = "Display Order";
$l['sta_forgroups'] = "Groups?";
$l['create_status'] = "Create Status";
$l['status_info'] = "Statuses help class what an Issue actually is, or what's happening with it. For example, you can class an Issue as a 'browser quirk' if there a browser is causing a problem with your project.";
$l['edit_status_info'] = "Statuses help class what an Issue actually is, or what's happening with it. For example, you can class an Issue as a 'browser quirk' if there a browser is causing a problem with your project.";
$l['sta_no_name'] = "You didn't enter a name for your Status. Please enter a name before creating a new status.";
$l['sta_edit_no_name'] = "You didn't enter a name for your Status. Please enter a name before saving the status.";
$l['sta_short_name'] = "Please enter a longer status name.";
$l['created_status'] = "The Status has been created.<br />Please wait while we transfer you back to the Control Panel.";
$l['del_sta_crumb'] = "Delete Status";
$l['confirm_delete_status'] = "Are you sure you want to delete this status? All current issues assigned under this status will now be assigned under 'New'.<br /><br /><strong>This cannot be undone!</strong>";
$l['delete_status'] = "Delete Status";
$l['sta_deleted'] = "The Status has been deleted.<br />Please wait while we transfer you back to the Control Panel.";
$l['save_status'] = "Save Status";
$l['editing_status'] = "Editing Status";
$l['status_saved'] = "The Status has been saved.<br />Please wait while we transfer you back to the Control Panel.";

// Managing Stages
$l['new_stages'] = "New Stages";
$l['new_stage'] = "New Stage";
$l['sta_crumb'] = "Managing Stages";
$l['allstages'] = "All Stages";
$l['no_stage_found'] = "The specified Stage couldn't be found in the database.";
$l['no_delete_stage_num1'] = "You can't delete this Stage.";
$l['stages'] = "Stages";
$l['stage'] = "Stage";
$l['save_stage_order'] = "Save Stage Order";
$l['create_stage'] = "Create Stage";
$l['stage_name'] = "Status Name";
$l['stg_displayorder'] = "Display Order";
$l['stg_no_name'] = "You didn't enter a name for your Stage. Please enter a name before creating a new stage.";
$l['stg_edit_no_name'] = "You didn't enter a name for your Stage. Please enter a name before saving the stage.";
$l['stg_short_name'] = "Please enter a longer stage name.";
$l['stage_info'] = "Stages are 'milestones' in your projects. They typically run from planning, alpha, beta etc., but can include things like 'RC' (Release Candidate) and 'Gold'.";
$l['created_stage'] = "The Stage has been created!<br />Please wait while we transfer you back to the Control Panel.";
$l['save_stage'] = "Save Stage";
$l['stage_saved'] = "The Stage has been saved.<br />Please wait while we transfer you back to the Control Panel.";
$l['editing_stage'] = "Editing Stage";
$l['del_stg_crumb'] = "Delete Stage";
$l['confirm_delete_stage'] = "Are you sure you want to delete this stage? All current projects assigned under this stage will now be assigned under 'Planning'.<br /><br /><strong>This cannot be undone!</strong>";
$l['delete_stage'] = "Delete Stage";
$l['stg_deleted'] = "The Stage has been deleted.<br />Please wait while we transfer you back to the Control Panel.";
?>