<?php
/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: projects.php 11 2009-10-05 14:48:17Z Tomm $
+--------------------------------------------------------------------------
*/
define("IN_MYBB", 1);
define("IN_TRACKER", 1);
define('THIS_SCRIPT', 'tracker/projects.php');
$templatelist = "mytracker_project_issues_content, mytracker_project_issues, mytracker_project_content, mytracker_project";

chdir(dirname(dirname(__FILE__)));
require_once "./global.php";
require_once "./inc/functions_tracker.php";

// Figure out which project we're viewing
$projectid = intval($mybb->input['project']);
$query = $db->simple_select("tracker_projects", "*", "proid = '".$projectid."'", array("limit" => 1));
$project = $db->fetch_array($query);

// Project Information
$project['name'] = htmlspecialchars_uni($project['name']);

// Check if we can access this project?
if($mybb->user['developer'] || $mybb->usergroup['canmodtrack'])
{
	$moderator = true;
}
else
{
	$moderator = false;
}

if(!$db->num_rows($query) || $project['active'] == 0 && $moderator == false)
{
	error($lang->pro_noproject);
}

add_breadcrumb($mybb->settings['trackername'], "./");
add_breadcrumb($project['name']);

// More detailed info
$project['starttime'] = relative_time($project['created']);
$project['link'] = get_project_link($project['proid']);
if($sub_tabs['newbug'])
{
	$sub_tabs['newbug']['link'] = $sub_tabs['newbug']['link']."&amp;project=".$project['proid']."";
}
if($sub_tabs['ideas'])
{
	$sub_tabs['ideas']['link'] = $sub_tabs['ideas']['link']."&amp;project=".$project['proid']."";
}

// Get the project stage, issues and features
$query = $db->simple_select("tracker_stages", "stagename", "stageid = '".$project['stage']."'", array("limit" => 1));
$project['project_stage'] = $db->fetch_field($query, "stagename");

$hidden_issues = get_hidden_issues($project['proid']);
$project['issues'] = my_number_format($project['num_issues'] - $hidden_issues);
if($project['issues'] < 0)
{
	$project['issues'] = 0;
}

// Define and check a few things here
if(!$mybb->input['view'])
{
	$mybb->input['view'] = "issues";
}
elseif($mybb->input['view'] == "features" && ($mybb->settings['ideasys'] == 0 || $project['allowfeats'] == 0))
{
	error($lang->idea_sys_off);
}
if($mybb->input['req'] == "ajax")
{
	$js = true;
}

//---------------------------------------------------
// Issues
//---------------------------------------------------
$issues_link = get_project_link($project['proid'], 'issues');
$project_tabs['issues'] = array(
	'title' => $lang->issues,
	'link' => $issues_link,
	'onclick' => "jQuery('#loading').show(); jQuery('#proj_content').load('projects.php?project=".$project['proid']."&amp;req=ajax&amp;action=content&amp;view=issues', '', function(){ jQuery('#loading').hide(); });",
	'description' => $lang->pro_issues_info = $lang->sprintf($lang->pro_issues_info, $project['name'])
);

if(!$project['num_issues'])
{
	eval("\$issues_content = \"".$templates->get("mytracker_project_nocontent")."\";");
}
else
{
	// Folder Icons
	// Has the User posted in this issue? Build a "cache"
	if($mybb->settings['dotfolders'] != 0)
	{
		$query = $db->simple_select("tracker_issuesposts", "issid, isspid, dateline", "uid = '".$mybb->user['uid']."'");
		while($postissue = $db->fetch_array($query))
		{
			$issuecache[$postissue['issid']]['posted'] = $postissue['isspid'];
		}
	}

	// Determine when last read
	$query = $db->query("
		SELECT i.issid, r.dateline AS readthread
		FROM ".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_issuesread r ON (i.issid=r.issid AND r.uid='{$mybb->user['uid']}')
		WHERE r.uid = '".$mybb->user['uid']."'
		ORDER BY issid"
	);
	while($readissue = $db->fetch_array($query))
	{
		$issuecache[$readissue['issid']]['readthread'] = $readissue['readthread'];
	}
	
	// AJAX pagination thingymajiggy
	$page = intval($mybb->input['page']);
	$perpage = 10;
	$pages = $project['num_issues'] / $perpage;
	$pages = ceil($pages);
	
	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}
	
	// Collect and display the Issues with mass-ov JOIN eyy...
	// Mark tests see this as being a small query :o/
	$query = $db->query("
		SELECT i.*, p.priorityname, p.priorstyle, s.statusname
		FROM ".TABLE_PREFIX."tracker_issues i
		LEFT JOIN ".TABLE_PREFIX."tracker_priorities p ON (p.priorid=i.priority)
		LEFT JOIN ".TABLE_PREFIX."tracker_status s ON (s.statid=i.status)
		WHERE i.projid = '".$project['proid']."'
		ORDER BY lastpost DESC
		LIMIT ".$start.", ".$perpage.""
	);

	// WIP - change this to friendy URL if no JS!
	// Strange bug here if friendly URL is used
	$multipage_issues = my_multipage($project['num_issues'], $perpage, $page, "projects.php?project=".$project['proid']."&view=issues&page={page}", "issues", true);
	$issue_count = 0;
	while($issue = $db->fetch_array($query))
	{
		// Check the permissions
		if($moderator == false && $issue['visible'] == 0)
		{
			continue;
		}

		++$issue_count;
		// If it's visible, show the style, otherwise colour it in with trow_shaded
		if($issue['visible'])
		{
			$issue['style'] = "style=\"".$issue['priorstyle']."\"";
		}
		else
		{
			$issue['style'] = "class=\"trow_shaded\"";
		}
		$issue['subject'] = htmlspecialchars_uni($issue['subject']);
		$issue['author'] = "<a href=\"".get_user_url($issue['uid'])."\">".htmlspecialchars_uni($issue['username'])."</a>";
		$issue['link'] = get_issue_url($issue['issid']);

		// Folder Icons
		$folder = '';
		$folder_label = '';
		$issue['postthread'] = $issuecache[$issue['issid']]['posted'];
		$issue['readthread'] = $issuecache[$issue['issid']]['readthread'];

		if($issue['postthread'])
		{
			$folder = "dot_";
			$folder_label .= $lang->icon_dot;
		}
		if($mybb->settings['threadreadcut'] > 0)
		{
			$cutoff = TIME_NOW-$mybb->settings['threadreadcut']*60*60*24;
		}
			// Tough tomatoes if they haven't read it by now!
			/*if($issue['lastpost'] > $cutoff)
			{
				$issue['readthread'] = $mybb->user['lastvisit'];
			}*/
		// If it's new, then show new
		// Attempt at a new "read" thingy
		if($mybb->user['uid'])
		{
			if(!$issue['readthread'] || ($issue['readthread'] && $issue['readthread'] < $issue['lastpost']))
			{
				$folder .= "new";
				$folder_label .= $lang->icon_new;
				$new_class = " class=\"subject_new\"";
				++$loop_count;
			}
			else
			{
				$new_class = '';
			}
		}
		// If it's closed, show locked symbol
		if($issue['closed'])
		{
			$folder .= "lock";
			$folder_label .= $lang->icon_lock;
		}	
	
		$folder .= "folder";
		
		// Lastpost info
		if(!$issue['lastpost'])
		{
			$issue['lastpostinfo'] = "<em>--</em>";
		}
		else
		{
			$issue['lastposttime'] = my_date($mybb->settings['dateformat'], $issue['lastpost'])." ".my_date($mybb->settings['timeformat'], $issue['lastpost']);
			$issue['lastpostauthor'] = "<a href=\"".get_user_url($issue['lastposteruid'])."\">".htmlspecialchars_uni($issue['lastposter'])."</a>";
			$issue['lastpostinfo'] = $issue['lastposttime']."<br /><a href=\"".$issue['link']."?action=lastpost\">".$lang->pro_issue_lastpost."</a>: ".$issue['lastpostauthor'];
		}

		// Get the current status for this project
		$issue['statusname'] = htmlspecialchars_uni($issue['statusname']);
		if($issue['complete'] == "100")
		{
			$current_status = " &raquo; <del>".$issue['statusname']."</del>";
		}
		else
		{
			$current_status = " &raquo; ".$issue['statusname']."";
		}
		
		// Add to content!
		eval("\$issues_content .= \"".$templates->get("mytracker_project_issues_content")."\";");
	}

	// If there isn't an issue_count, there has been no issues (everything is hidden!)
	if($issue_count == 0)
	{
		eval("\$issues_content = \"".$templates->get("mytracker_project_nocontent")."\";");
	}

	// If there isn't a loop_count, then everything in this forum is read, so update it!
	if($mybb->user['uid'] && $loop_count == 0)
	{
		$update_array = array(
			"proid" => $project['proid'],
			"uid" => $mybb->user['uid'],
			"dateline" => TIME_NOW
		);
		$query = $db->simple_select("tracker_projectsread", "uid", "proid = '".$project['proid']."' AND uid = '".$mybb->user['uid']."'");
		if($db->num_rows($query))
		{
			$db->update_query("tracker_projectsread", $update_array, "proid = '".$project['proid']."' AND uid = '".$mybb->user['uid']."'");
		}
		else
		{
			$db->insert_query("tracker_projectsread", $update_array);
		}
	}
}

//---------------------------------------------------
// Features
//---------------------------------------------------
if($mybb->settings['ideasys'] == 1 && $project['allowfeats'] == 1)
{
	$project['features'] = my_number_format($project['num_features']);
	$features_info = "<tr>\n<td class=\"trow2\" valign=\"top\"><strong>{$lang->features}</strong></td>\n";
	$features_info .= "<td class=\"trow2\">{$project['features']}</td>\n</tr>";
	$features_link = get_project_link($project['proid'], 'features');
	$project_tabs['features'] = array(
		'title' => $lang->features,
		'link' => $features_link,
		'onclick' => "jQuery('#loading').show(); jQuery('#proj_content').load('projects.php?project=".$project['proid']."&amp;req=ajax&amp;action=content&amp;view=features', '', function(){ jQuery('#loading').hide(); });",
		'description' => $lang->pro_features_info = $lang->sprintf($lang->pro_features_info, $project['name'])
	);
	
	$page = intval($mybb->input['page']);
	$perpage = 10;
	$pages = $project['num_features'] / $perpage;
	$pages = ceil($pages);
	
	if($page > $pages || $page <= 0)
	{
		$page = 1;
	}

	if($page)
	{
		$start = ($page-1) * $perpage;
	}
	else
	{
		$start = 0;
		$page = 1;
	}

	if(!$project['num_features'])
	{
		eval("\$features_content = \"".$templates->get("mytracker_project_nocontent")."\";");
	}
	else
	{
		// Folder Icons
		// Has the User posted in this issue? Build a "cache"
		if($mybb->settings['dotfolders'] != 0)
		{
			$query = $db->simple_select("tracker_featuresposts", "featid, featpid, dateline", "uid = '".$mybb->user['uid']."'");
			while($postfeature = $db->fetch_array($query))
			{
				$featurecache[$postfeature['featid']]['posted'] = $postfeature['featpid'];
			}
		}
	
		// Determine when last read
		$query = $db->query("
			SELECT f.featid, r.dateline AS readthread
			FROM ".TABLE_PREFIX."tracker_features f
			LEFT JOIN ".TABLE_PREFIX."tracker_featuresread r ON (f.featid=r.featid AND r.uid='{$mybb->user['uid']}')
			WHERE r.uid = '".$mybb->user['uid']."'
			ORDER BY featid"
		);
		while($readfeature = $db->fetch_array($query))
		{
			$featurecache[$readfeature['featid']]['readthread'] = $readfeature['readthread'];
		}
		
		$query = $db->simple_select("tracker_features", "*", "projid = '".$project['proid']."'", array("order_by" => "lastpost", "order_dir" => "DESC", "limit" => $perpage, "limit_start" => $start));
		
		if(!$db->num_rows($query))
		{
			eval("\$features_content = \"".$templates->get("mytracker_project_nocontent")."\";");
		}
		else
		{	
			$multipage_features = my_multipage($project['num_features'], $perpage, $page, "projects.php?project=".$project['proid']."&view=features&page={page}", 'features', true);		
			while($feature = $db->fetch_array($query))
			{
				// Check the permissions
				if($moderator == false && $feature['visible'] == 0)
				{
					continue;
				}

				// If it's visible, show the style, otherwise colour it in with trow_shaded
				if($feature['visible'])
				{
					$feature['style'] = "class=\"".alt_trow()."\"";
				}
				else
				{
					$feature['style'] = "class=\"trow_shaded\"";
				}

				++$features_counting;
				$feature['author'] = "<a href=\"".get_user_url($feature['uid'])."\">".htmlspecialchars_uni($feature['username'])."</a>";
				$feature['link'] = get_feature_url($feature['featid']);

				// Folder Icons
				$folder = '';
				$folder_label = '';
				$feature['postthread'] = $featurecache[$feature['featid']]['posted'];
				$feature['readthread'] = $featurecache[$feature['featid']]['readthread'];
		
				if($feature['postthread'])
				{
					$folder = "dot_";
					$folder_label .= $lang->icon_dot;
				}
				if($mybb->settings['threadreadcut'] > 0)
				{
					$cutoff = TIME_NOW-$mybb->settings['threadreadcut']*60*60*24;
				}
	
				// If it's new, then show new
				// Attempt at a new "read" thingy
				if($mybb->user['uid'])
				{
					if(!$feature['readthread'] || ($feature['readthread'] && $feature['readthread'] < $feature['lastpost']))
					{
						$folder .= "new";
						$folder_label .= $lang->icon_new;
						$new_class = " class=\"subject_new\"";
						++$loop_count;
					}
					else
					{
						$new_class = '';
					}
				}
				// If it's closed, show locked symbol
				if($feature['closed'])
				{
					$folder .= "lock";
					$folder_label .= $lang->icon_lock;
				}	
			
				$folder .= "folder";
			
				if(!$feature['lastpost'])
				{
					$feature['lastpostinfo'] = "<em>--</em>";
				}
				else
				{
					$feature['lastposttime'] = my_date($mybb->settings['dateformat'], $feature['lastpost'])." ".my_date($mybb->settings['timeformat'], $feature['lastpost']);
					$feature['lastpostauthor'] = "<a href=\"".get_user_url($feature['lastposteruid'])."\">".htmlspecialchars_uni($feature['lastposter'])."</a>";
					$feature['lastpostinfo'] = $feature['lastposttime']."<br /><a href=\"".$feature['link']."?action=lastpost\">".$lang->pro_issue_lastpost."</a>: ".$feature['lastpostauthor'];
				}
				eval("\$features_content .= \"".$templates->get("mytracker_project_features_content")."\";");
			}
			// If there isn't an issue_count, there has been no issues (everything is hidden!)
			if($features_counting == 0)
			{
				eval("\$features_content = \"".$templates->get("mytracker_project_nocontent")."\";");
			}
		}
	}
}

//---------------------------------------------------
// Versions
//---------------------------------------------------
// Figure out if we have versions for this project
if($moderator == true)
{
	// Moderators will see all project versions!
	$query = $db->query("
		SELECT p.*, s.stagename
		FROM ".TABLE_PREFIX."tracker_projects p
		LEFT JOIN ".TABLE_PREFIX."tracker_stages s ON (s.stageid=p.stage)
		WHERE p.parent = '".$project['proid']."'
		ORDER BY p.lastpost DESC"
	);
}
else
{
	// Normal uses see just active ones
	$query = $db->query("
		SELECT p.*, s.stagename
		FROM ".TABLE_PREFIX."tracker_projects p
		LEFT JOIN ".TABLE_PREFIX."tracker_stages s ON (s.stageid=p.stage)
		WHERE p.parent = '".$project['proid']."' AND p.active = '1'
		ORDER BY p.lastpost DESC"
	);
}
if($db->num_rows($query))
{
	$hasversions = true;
	$versions_link = get_project_link($project['proid'], "versions");
	$project_tabs['versions'] = array(
		'title' => $lang->pro_versions,
		'link' => $versions_link,
		'onclick' => "jQuery('#loading').show(); jQuery('#proj_content').load('projects.php?project=".$project['proid']."&amp;req=ajax&amp;action=content&amp;view=versions', '', function(){ jQuery('#loading').hide(); });",
		'description' => $lang->pro_versions_info = $lang->sprintf($lang->pro_versions_info, $project['name'])
	);

	if($mybb->input['view'] == "versions") // Only do the loop if we're actually wanting to view this screen
	{
		while($version = $db->fetch_array($query))
		{
			if($version['active'])
			{
				$bgcolor = alt_trow();
			}
			else
			{
				$bgcolor = "trow_shaded";
			}
			$version['link'] = get_project_link($project['proid'], "versions", '', $version['proid']);
			$version['issues'] = my_number_format($version['numissues']);
			
			// Check the lastpost info
			if(!$version['lastpost'])
			{
				$version['lastpostinfo'] = "<em>--</em>";
			}
			else
			{
				$version['posted'] = my_date($mybb->settings['dateformat'], $version['lastpost'])." ".my_date($mybb->settings['timeformat'], $version['lastpost']);
				$version['lastposter'] = build_profile_link($version['lastposter'], $version['lastposteruid']);
				$version['lastissue'] = get_issue_url($version['lastpostissid']);
				$version['lastpostinfo'] = "<a href=\"{$version['lastissue']}\"><strong>".htmlspecialchars_uni($version['lastpostsubject'])."</strong></a><br />{$version['posted']}<br />by {$version['lastposter']}";
			}
	
			eval("\$version_content .= \"".$templates->get("mytracker_project_versions_content")."\";");
		}
	}
}

// Are we doing AJAX'd content?
if($mybb->input['view'] == "versions")
{
	if($mybb->input['req'] == "ajax" && $mybb->settings['use_xmlhttprequest'])
	{
		if($hasversions == true)
		{
			$project_tabs['versions']['link'] = "javascript:;";
		}
		if($mybb->settings['ideasys'] && $project['allowfeats'])
		{
			$project_tabs['features']['link'] = "javascript:;";
		}
		$project_tabs['issues']['link'] = "javascript:;";
		$project_menu = output_nav_tabs($project_tabs, 'versions');
		eval("\$project_index = \"".$templates->get("mytracker_project_versions")."\";");
	}
	else
	{
		$project_menu = output_nav_tabs($project_tabs, 'versions');
		eval("\$project_content = \"".$templates->get("mytracker_project_versions")."\";");
	}
}
elseif($mybb->input['view'] == "issues")
{
	if($mybb->input['req'] == "ajax" && $mybb->settings['use_xmlhttprequest'])
	{
		if($hasversions == true)
		{
			$project_tabs['versions']['link'] = "javascript:;";
		}
		if($mybb->settings['ideasys'] && $project['allowfeats'])
		{
			$project_tabs['features']['link'] = "javascript:;";
		}
		$project_tabs['issues']['link'] = "javascript:;";		
		$project_menu = output_nav_tabs($project_tabs, 'issues');
		eval("\$project_index = \"".$templates->get("mytracker_project_issues")."\";");
	}
	else
	{
		$project_menu = output_nav_tabs($project_tabs, 'issues');
		eval("\$project_content = \"".$templates->get("mytracker_project_issues")."\";");
	}	
}
elseif($mybb->input['view'] == "features")
{
	if($mybb->input['req'] == "ajax" && $mybb->settings['use_xmlhttprequest'])
	{
		if($hasversions == true)
		{
			$project_tabs['versions']['link'] = "javascript:;";
		}
		if($mybb->settings['ideasys'] && $project['allowfeats'])
		{
			$project_tabs['features']['link'] = "javascript:;";
		}
		$project_tabs['issues']['link'] = "javascript:;";
		$project_menu = output_nav_tabs($project_tabs, 'features');
		eval("\$project_index = \"".$templates->get("mytracker_project_features")."\";");
	}
	else
	{
		$project_menu = output_nav_tabs($project_tabs, 'features');
		eval("\$project_content = \"".$templates->get("mytracker_project_features")."\";");
	}
}

// Project Tab
$sub_tabs['project'] = array(
	'title' => $project['name'],
	'link' => "./".$project['link']."",
	'onclick' => "jQuery('#loading').show(); jQuery('#proj_content').load('projects.php?project=".$project['proid']."&amp;req=ajax&amp;view=issues&amp;action=content', '', function(){ jQuery('#loading').hide(); });",
	'description' => $lang->pro_description = $lang->sprintf($lang->pro_description, $project['proid'], $project['name'])
);

// If the ideas system is off, or if the project has them disabled, hide the new idea tab
if($project['allowfeats'] == 0 || $mybb->settings['ideasys'] == 0)
{
	unset($sub_tabs['ideas']);
}
$menu = output_nav_tabs($sub_tabs, 'project', true);
// Are we doing the entire page, or just the project content?
if($mybb->input['action'] != "content")
{
	if($mybb->input['req'] == "ajax" && $mybb->settings['use_xmlhttprequest'])
	{
		eval("\$project_index = \"".$templates->get("mytracker_project_content")."\";");
	}
	else
	{
		eval("\$content = \"".$templates->get("mytracker_project_content")."\";");
		eval("\$project_index = \"".$templates->get("mytracker_project")."\";");
	}
}

output_page($project_index);
?>