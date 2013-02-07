<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-9 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_hooks.inc.php 28165 2009-10-19 18:27:44Z ralfbecker $
 */

/**
 * diverse tracker hooks, all static
 */
class tracker_hooks
{
	/**
	 * Hook called by link-class to include tracker in the appregistry of the linkage
	 *
	 * @param array/string $location location and other parameters (not used)
	 * @return array with method-names
	 */
	static function search_link($location)
	{
		return array(
			'query' => 'tracker.tracker_bo.link_query',
			'title' => 'tracker.tracker_bo.link_title',
			'titles' => 'tracker.tracker_bo.link_titles',
			'view'  => array(
				'menuaction' => 'tracker.tracker_ui.edit',
			),
			'view_id' => 'tr_id',
			'view_popup'  => '700x500',
			'add' => array(
				'menuaction' => 'tracker.tracker_ui.edit',
			),
			'add_app'    => 'link_app',
			'add_id'     => 'link_id',
			'add_popup'  => '700x480',
		);
	}

	/**
	 * hooks to build trackers's sidebox-menu plus the admin and preferences sections
	 *
	 * @param string/array $args hook args
	 */
	static function all_hooks($args)
	{
		$appname = 'tracker';
		$location = is_array($args) ? $args['location'] : $args;
		//echo "<p>tr_admin_prefs_sidebox_hooks::all_hooks(".print_r($args,True).") appname='$appname', location='$location'</p>\n";

		if ($location == 'sidebox_menu')
		{
			$file = array(
			);
			display_sidebox($appname,$GLOBALS['egw_info']['apps'][$appname]['title'].' '.lang('Menu'),$file);
		}

		if ($GLOBALS['egw_info']['user']['apps']['preferences'] && $location != 'admin')
		{
			$file = array(
				'Preferences'     => egw::link('/index.php','menuaction=preferences.uisettings.index&appname='.$appname),
			);
			if ($location == 'preferences')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Preferences'),$file);
			}
		}

		if ($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'preferences')
		{
			$file = Array(
				'Site configuration' => egw::link('/index.php','menuaction=tracker.tracker_admin.admin'),
				'Define escalations' => egw::link('/index.php','menuaction=tracker.tracker_admin.escalations'),
				'Custom fields' => egw::link('/index.php','menuaction=admin.customfields.edit&appname='.$appname),
			);
			if ($location == 'admin')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Admin'),$file);
			}
		}
	}

	/**
	 * populates $GLOBALS['settings'] for the preferences
	 */
	static function settings()
	{
		return array(
			'notify_creator' => array(
				'type'   => 'check',
				'label'  => 'Receive notifications about created tracker-items',
				'name'   => 'notify_creator',
				'help'   => 'Should the Tracker send you notification mails, if tracker items you created get updated?',
				'xmlrpc' => True,
				'admin'  => False,
				'default'=> true,
			),
			'notify_assigned' => array(
				'type'   => 'check',
				'label'  => 'Receive notifications about assigned tracker-items',
				'name'   => 'notify_assigned',
				'help'   => 'Should the Tracker send you notification mails, if tracker items assigned to you get updated?',
				'xmlrpc' => True,
				'admin'  => False,
				'default'=> true,
			),
			'notify_own_modification' => array(
				'type'   => 'check',
				'label'  => 'Recieve notifications about own changes in tracker-items',
				'name'   => 'notify_own_modification',
				'help'   => 'Show the Tracker send you notification mails, in tracker items that you updates?',
				'xmlrpc' => True,
				'admin'  => False,
				'default'=> false,
			),
			'show_actions' => array(
				'type'   => 'check',
				'label'  => 'Show actions in tracker listing',
				'name'   => 'show_actions',
				'help'   => 'Should the actions column in the tracker list-view be shown?',
				'xmlrpc' => True,
				'admin'  => False,
				'forced' => true,
			),
			'allow_defaultproject' => array(
				'type'   => 'check',
				'label'  => 'Allow default projects for tracker',
				'name'   => 'allow_defaultproject',
				'help'   => 'Allow the predefinition of projects that will be assigned to new tracker-items.',
				'xmlrpc' => True,
				'admin'  => False,
				'forced' => true,
			),
			'show_sum_timesheet' => array(
				'type'   => 'check',
				'label'  => 'Show the acumulated times of timesheet entries',
				'name'   => 'show_sum_timesheet',
				'help'   => 'Show a new column that calculated the acumulated times of timesheet entries.',
				'xmlrpc' => True,
				'admin'  => False,
				'forced' => true,
			),
		);
	}
}
