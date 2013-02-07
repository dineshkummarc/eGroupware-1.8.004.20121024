<?php
/**
 * EGroupware - Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @subpackage setup
 * @copyright (c) 2006-10 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

$setup_info['tracker']['name']      = 'tracker';
$setup_info['tracker']['version']   = '1.8';
$setup_info['tracker']['app_order'] = 5;
$setup_info['tracker']['tables']    = array('egw_tracker','egw_tracker_replies','egw_tracker_votes','egw_tracker_bounties','egw_tracker_assignee','egw_tracker_escalations','egw_tracker_escalated','egw_tracker_extra');
$setup_info['tracker']['enable']    = 1;
$setup_info['tracker']['index']     = 'tracker.tracker_ui.index';

$setup_info['tracker']['author'] =
$setup_info['tracker']['maintainer'] = array(
	'name'  => 'Ralf Becker',
	'email' => 'RalfBecker@outdoor-training.de'
);
$setup_info['tracker']['license']  = 'GPL';
$setup_info['tracker']['description'] =
'Universal tracker (bugs, feature requests, ...) with voting and bounties.';
$setup_info['tracker']['note'] = '';

/* The hooks this app includes, needed for hooks registration */
$setup_info['tracker']['hooks']['preferences'] = 'tracker_hooks::all_hooks';
$setup_info['tracker']['hooks']['settings'] = 'tracker_hooks::settings';
$setup_info['tracker']['hooks']['admin'] = 'tracker_hooks::all_hooks';
$setup_info['tracker']['hooks']['sidebox_menu'] = 'tracker_hooks::all_hooks';
$setup_info['tracker']['hooks']['search_link'] = 'tracker_hooks::search_link';

/* Dependencies for this app to work */
$setup_info['tracker']['depends'][] = array(
	 'appname' => 'phpgwapi',
	 'versions' => Array('1.7','1.8','1.9')
);
$setup_info['tracker']['depends'][] = array(
	 'appname' => 'etemplate',
	 'versions' => Array('1.7','1.8','1.9')
);
$setup_info['tracker']['depends'][] = array(
	 'appname' => 'notifications',
	 'versions' => Array('1.7','1.8','1.9')
);
// installation checks / requirements for tracker
$setup_info['tracker']['check_install'] = array(
	'imap' => array(
		'func' => 'extension_check',
	),
);

