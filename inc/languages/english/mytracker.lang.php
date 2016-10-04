<?php
/*
+--------------------------------------------------------------------------
|   MyTracker
|   =============================================
|   by Tom Moore (www.xekko.co.uk)
|   (c) 2009 Mooseypx Design / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mytracker.lang.php 8 2009-08-17 09:59:23Z Tomm $
|
|	Language file for MyTracker
|	You might notice that some of these are used in more than one area.
|	Apparently recycling is a big thing these days...
+--------------------------------------------------------------------------
*/
//---------------------------------------------------
// Tabbeeh!
//---------------------------------------------------
$l['dashboard'] = "Dashboard";
$l['dashboard_info'] = "Various statistics, latest bugs, ideas and a general overview of our projects";
$l['projectlist_info'] = "projects are listed below...";
$l['issues'] = "Issues";
$l['features'] = "Features";
$l['feature'] = "Feature";
$l['ideas'] = "Ideas";
$l['issuelist_info'] = "Current issues from all Projects...";
$l['newbug_info'] = "Create a new Issue &raquo; Give us as much information as possible!";
$l['ideas_info'] = "Suggestions, ideas and improvements for our projects...";
$l['controlpanel'] = "Control Panel";
$l['cp_info'] = "Manage MyTracker's settings, options and buttons...";
$l['comments'] = "Comments";
$l['idea_sys_off'] = "The Administrator has disabled new feature suggestions";
$l['idea_sys_off_project'] = "The Administrator has disabled new feature suggestions for this Project.";
//---------------------------------------------------
// Relative Time
//---------------------------------------------------
$l['relative_plural'] = "s";
$l['relative_ago'] = "ago";
$l['relative_to_go'] = "to go";
$l['moments'] = "moments ago";
//---------------------------------------------------
// Menu Items
//---------------------------------------------------
$l['tracker_projects'] = "Projects";
$l['tracker_issue_tracking'] = "Bug Tracking";
$l['tracker_issue_list'] = "Issues List";
$l['tracker_unsquished'] = "Unsquished Bugs";
$l['tracker_all_bugs'] = "View Bug History";
$l['tracker_new_report'] = "New Issue";
$l['tracker_ideas'] = "New Idea";
$l['tracker_ideas_list'] = "Ideas List";
$l['tracker_new_idea'] = "New Suggestion";
//---------------------------------------------------
// Dashboard
//---------------------------------------------------
$l['dash_active_projects'] = "Active Projects";
$l['dash_projects'] = "Projects";
$l['dash_features'] = "Features";
$l['dash_lower_feature'] = "Feature";
$l['dash_issues'] = "Issues";
$l['dash_lower_issue'] = "Issue";
$l['dash_edit_issue'] = "Editing Issue";
$l['dash_edit_feature'] = "Editing Feature";
$l['dash_complete'] = "Issues Squished";
$l['dash_stats'] = "Statistics";
$l['dash_activity'] = "Latest Activity";
$l['dash_no_activity'] = "<em>There has been no activity lately...</em>";
$l['dash_are'] = "are";
$l['dash_is'] = "is";
$l['dash_plural'] = "s";
$l['dash_stats_issues'] = "There {1} <strong>{2}</strong> project{3} with <strong>{4}</strong> issue{5}";
$l['dash_stats_feats'] = "and <strong>{1}</strong> feature suggestion{2}.";
$l['dash_stats_users'] = "Our members have posted a total of <strong>{1}</strong> comment{2} in our tracker.";
$l['dash_whos_online'] = "Who's Online";
$l['dash_act_has'] = "has";
$l['dash_act_comment'] = "made a comment about";
$l['dash_act_comment_ns'] = "has written a comment.";
$l['dash_act_update'] = "updated";
$l['dash_act_new'] = "made a new";
$l['dash_act_resolve'] = "resolved";
$l['dash_act_woo'] = "... Wooo!";
$l['dash_act_redirect'] = "The Activity has been updated.<br />You will be taken back to the Dashboard.";
//---------------------------------------------------
// Projects
//---------------------------------------------------
$l['pro_noproject'] = "The specified project does not exist.";
$l['pro_description'] = "Project #{1} &raquo; {2}";
$l['pro_info'] = "Project Information";
$l['pro_versions'] = "Versions";
$l['pro_versions_info'] = "Versions of the {1} project...";
$l['pro_issues_info'] = "Issues for the {1} project...";
$l['pro_features_info'] = "Feature Suggestions for the {1} project...";
$l['pro_issue_name'] = "Issue";
$l['pro_views'] = "Views";
$l['pro_replies'] = "Replies";
$l['pro_lastpost'] = "Last Post";
$l['pro_stats'] = "Stats";
$l['pro_version_name'] = "Version";
$l['pro_stage'] = "Stage";
$l['pro_first_unread'] = "Go to first unread post";
$l['pro_issue_lastpost'] = "Last Post";

// 1.0.2 additions
$l['pro_name'] = "Name";
$l['pro_description'] = "Description";
$l['pro_started'] = "Started";

//---------------------------------------------------
// Issues, issues, issues...
//---------------------------------------------------
$l['iss_tab_info'] = "Issue #{1} &raquo; {2}";
$l['iss_tab_editinfo'] = "Editing Issue #{1} &raquo; {2}";
$l['iss_no_issue'] = "The specified issue does not exist.";
$l['iss_info'] = "Information";
$l['iss_none'] = "None";
$l['iss_noone'] = "<em>Not Assigned</em>";
$l['iss_reported'] = "Reported by";
$l['iss_subject'] = "Subject";
$l['iss_project'] = "Project";
$l['iss_message'] = "Message";
$l['iss_category'] = "Category";
$l['iss_priority'] = "Priority";
$l['iss_priorities'] = "Priorities";
$l['iss_status'] = "Status";
$l['iss_assign'] = "Assigned To";
$l['iss_complete'] = "% Complete";
$l['iss_versions'] = "Version";
$l['iss_comments'] = "Latest Comments";
$l['iss_p_comments'] = "Comments";
$l['iss_p_comment'] = "Comment";
$l['iss_comments_none'] = "<em>No Comments have been left for this Report.</em>";
$l['iss_problem'] = "The Problem";
$l['iss_timeline'] = "Timeline";
$l['iss_redirect'] = "The Issue has been updated.<br />You will be taken back to the Issue.";
$l['iss_newcomment'] = "Make a Comment";
$l['iss_viewall'] = "View All Comments";
$l['iss_addcomm'] = "Add a Comment";
$l['iss_edit_comment'] = "Edit";
$l['iss_notcommallowed'] = "Posting comments isn't allowed in this Issue.";
$l['iss_lastcomment'] = "Please edit the last comment you made, rather than create a new one.";
$l['iss_no_cont_comment'] = "It seems you never actually posted a comment! Please go back and enter a comment.";
$l['iss_not_long_comment'] = "The comment you entered wasn't long enough. Please go back and enter a longer comment.";
$l['iss_added_comment'] = "Your comment was added!<br />Please wait while we take you back to the Issue.";
$l['iss_delete_comment'] = "Delete";
$l['iss_comment_deleted'] = "Comment Deleted!<br />You will be taken back to the Issue.";
$l['iss_no_comment'] = "The comment you wanted to delete does not exist!";
$l['iss_edit_details'] = "Edited by {1} | {2}, {3}";
$l['iss_no_action'] = "No action has been taken.<br />You will be taken back to the Issue...";
$l['iss_no_permission'] = "This Feature/Issue either doesn't exist, or you don't have permission to view it.";
$l['iss_comments_for'] = "Comments for";
$l['iss_timeline_for'] = "Timeline for";
$l['iss_pleaselogin'] = "You must be logged in to be able to do this.";
$l['confirm_delete_issue'] = "Are you sure you want to delete this Issue? This will remove all comments, activity and tracking details.<br /><br /><strong>This can't be undone!</strong>";
$l['deleted_issue'] = "The Issue has been deleted.<br />Please wait while we transfer you back to the Project.";
$l['iss_delete_issue'] = "Delete this Issue";
$l['iss_edit_issue'] = "Edit this Issue";
$l['iss_issue_invis'] = "Unapprove this Issue";
$l['iss_issue_vis'] = "Approve this Issue";
$l['iss_show_changes'] = " (Show changes in Activity Timeline?)";
$l['iss_in_show_changes'] = "See changes made in this Update";
// Activity stuff
$l['iss_act_new'] = "reported this issue.";
$l['iss_act_update'] = "made an update.";
$l['iss_act_resolved'] = "resolved the problem!";
$l['iss_act_none'] = "<em>No Activity</em>";
$l['iss_act_redirect'] = "The Activity has been updated.<br />You will be taken back to the Issue.";
$l['iss_act_time_redirect'] = "The Activity has been updated.<br />You will be taken back to the Issue's Timeline.";
$l['iss_none'] = "None";
$l['iss_act_details'] = "View Details";
$l['iss_comment_info'] = "This page displays all the member's comments for this Issue. You can also view the [<a href='{1}'>Timeline</a>] of activity to track the changes made to it, too.";
$l['iss_in_invis'] = "Unapprove this Update";
$l['iss_in_vis'] = "Approve this Update";
// All Issue stuff
$l['iss_all_title'] = "All Issues";
//---------------------------------------------------
// Editing stuff
//---------------------------------------------------
$l['ed_not_long'] = "You didn't enter a long enough message.<br />Please go back, and be sure to give as much information as you can about your problem!";
$l['ed_not_long_subject'] = "You didn't enter a long enough subject.<br />Please go back, and be sure to give a longer subject title for your problem!";
$l['ed_process_error'] = "We couldn't seem to update the information in the database.<br />Please go back and try again. If the problems persist, contact an Administrator.";
$l['ed_complete'] = "Issue updated!<br />Please wait while we take you back to the Issue.";
$l['ed_no_hist'] = "The history for this activity couldn't be found!";
$l['ed_his_author'] = "{1} updated this issue: {2}";
$l['ed_his_updated'] = "Updated";
$l['ed_no_change'] = "No changes have been made.<br />Please wait while we take you back to the Issue.";
$l['ed_edit_comment'] = "Edit your comment in the box below";
$l['ed_update_comment'] = "Update Comment";
$l['ed_edit_a_comment'] = "Edit a Comment";
$l['ed_updated'] = "The comment has been updated!<br />Please wait while we take you back...";
$l['ed_add_dev'] = " &raquo; <a href=\"admin/projects.php?action=managedevs\">Add a Developer</a>";
$l['ed_no_devs'] = "<em>None</em>";
$l['fea_tab_editinfo'] = "Editing Feature #{1} &raquo; {2}";
//---------------------------------------------------
// New stuff
//---------------------------------------------------
$l['new_report'] = "Report a new Issue";
$l['new_desc_info'] = "Enter a description of the problem below...";
$l['new_post_issue'] = "Post Issue";
$l['new_already_posted'] = "You've already posted this report! It is {1}";
$l['new_already_posted_feature'] = "You've already posted this idea! It is {1}";
$l['new_quick_posted'] = "You've already posted a report in the last few minutes. Please wait a bit longer before posting another report.";
$l['new_no_subject'] = "You didn't provide a subject for your report.";
$l['new_short_subject'] = "The subject you gave to your report wasn't long enough! Please provide an accurate subject.";
$l['new_no_message'] = "You didn't enter a message. Please give us as much information as you can.";
$l['new_short_message'] = "Please give us a bit more information in your message...";
$l['new_no_project'] = "Couldn't locate a Project to assign the Issue to. Please select a valid Project from the available list!";
$l['new_issue_redir'] = "Thanks for reporting this Issue!<br />You will now be taken to your report.";
$l['new_bread'] = "Posting a new Issue";
$l['new_act_info'] = "{1} has reported a new bug. Booooo!";
$l['new_idea_info'] = "{1} has made a suggestion!";
$l['new_idea_redir'] = "Thanks for your idea!<br />You will now be taken to your suggestion.";
//---------------------------------------------------
// Features Stuff
//---------------------------------------------------
$l['fea_report'] = "Make a new Feature Suggestion";
$l['fea_no_feature'] = "The specified feature does not exist.";
$l['fea_reported'] = "Suggested by";
$l['fea_idea'] = "The Idea";
$l['fea_new_desc'] = "Enter a description of your idea!";
$l['fea_post'] = "Post Suggestion!";
$l['fea_comments_none'] = "<em>No Comments have been left for this Feature Suggestion</em>";
$l['fea_tab_info'] = "Feature #{1} &raquo; {2}";
$l['fea_comment_info'] = "This page displays all the member's comments for this feature. Remember to vote whether you like this suggestion or not!";
$l['fea_popular_info'] = "{1}% of people that have voted like this idea. {2} people have voted - {3} said that they liked it, and {4} said that they didn't like it.";
$l['fea_vote_for'] = "You said that you <strong>liked</strong> this idea! You can choose the option below to change your vote though...";
$l['fea_vote_against'] = "You said that you <strong>didn't like</strong> this idea! You can choose the option below to change your vote though...";
$l['fea_vote_none'] = "You haven't voted whether you like this suggestion or not. You can choose an option below to have your say!";
$l['fea_remove'] = "Remove Vote";
$l['fea_dont_like'] = "I don't like this idea!";
$l['fea_do_like'] = "I like this idea!";
$l['fea_voted_for'] = "You've voted for the idea!<br />Please wait while we take you back the Feature.";
$l['fea_voted_against'] = "You've voted against the idea!<br />Please wait while we take you back to the Feature.";
$l['fea_voted_removed'] = "Your vote for the idea has been removed.<br />Please wait while we take you back to the Feature.";
$l['fea_redirect'] = "The Feature has been updated.<br />You will be taken back to the Feature.";
$l['fea_editedby'] = "&raquo; Last updated by <a href=\"{1}\">{2}</a> | {3}";
$l['fea_delete_fea'] = "Delete this Idea";
$l['fea_edit_fea'] = "Edit this Idea";
$l['fea_invis'] = "Unapprove this Idea";
$l['fea_vis'] = "Approve this Idea";
//---------------------------------------------------
// Misc. stuff
//---------------------------------------------------
$l['misc_options'] = "Sort Options";
$l['misc_clear_search'] = " (<a href=\"{1}\">Clear Search</a>)";
$l['misc_all_priors'] = "All Priorities";
$l['misc_error'] = "Error";
$l['misc_editing'] = "Editing Issue";
$l['misc_visible'] = "Approve this Update";
$l['misc_invisible'] = "Unapprove this Update";
$l['save_button'] = "Save Changes";
$l['cancel_button'] = "Cancel";
$l['important'] = "Important!";
$l['important_1'] = "Before submitting this report, please be sure to provide as much information as you possibly can! Remember to include the version you're using and the steps to reproduce the issue you're experiencing.";
$l['important_fea'] = "Before submitting these changes, remember to include as much information about your idea as possible! Some people might not know what you're on about, so make sure it's easy to understand...";
$l['delete_issue'] = "Delete Issue";
$l['misc_description'] = "Description";
$l['misc_suggested'] = "Suggested by <a href=\"{1}\">{2}</a> &raquo; {3}";
$l['delete_feature'] = "Delete Feature";
$l['confirm_delete_feature'] = "Are you sure you want to delete this Feature? This will remove all comments, activity and tracking details.<br /><br /><strong>This can't be undone!</strong>";
$l['deleted_feature'] = "The Feature has been deleted.<br />Please wait while we transfer you back to the Project.";
$l['misc_show_update'] = "Show Update";
//---------------------------------------------------
// Who's Online in ze Tracker?
//---------------------------------------------------
$l['viewing'] = "Viewing <a href=\"{1}\">{2}</a>";
$l['viewing_project'] = "Viewing Project <a href=\"{1}\">{2}</a>";
$l['viewing_project2'] = "Viewing Project";
$l['viewing_issue'] = "Viewing Issue <a href=\"{1}\">{2}</a>";
$l['viewing_issue2'] = "Viewing Issue";
$l['viewing_feature'] = "Viewing Idea <a href=\"{1}\">{2}</a>";
$l['viewing_feature2'] = "Viewing Idea";
$l['new_issue'] = "Reporting a new Issue";
$l['new_idea'] = "Suggesting a new Idea";
?>