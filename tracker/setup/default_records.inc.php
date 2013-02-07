<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @subpackage setup
 * @copyright (c) 2006 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: default_records.inc.php 23963 2007-05-28 07:11:28Z ralfbecker $ 
 */

// create some example trackers and global versions and cats
foreach(array(
	'Feature Requests' => 'tracker',
	'Bugs' => 'tracker',
	'Patches' => 'tracker',
	'Stable Release' => 'version',
	'Development Version' => 'version',
	'Tracker' => 'cat',
	'API' => 'cat',
) as $name => $type)
{
	$GLOBALS['egw_setup']->db->insert($GLOBALS['egw_setup']->cats_table,array(
		'cat_owner'  => -1,
		'cat_access' => 'public',
		'cat_appname'=> 'tracker',
		'cat_name'   => $name,
		'cat_description' => ucfirst($type).' added by setup.',
		'cat_data'   => serialize(array('type' => $type)),
		'last_mod'   => time(),
	),false,__LINE__,__FILE__);
	$cat_id = $GLOBALS['egw_setup']->db->get_last_insert_id($GLOBALS['egw_setup']->cats_table,'cat_id');
	$GLOBALS['egw_setup']->db->update($GLOBALS['egw_setup']->cats_table,array(
		'cat_main' => $cat_id,
	),array(
		'cat_id' => $cat_id,
	),__LINE__,__FILE__);
	
	if ($name == 'Patches') $patches = $cat_id;
}
// create a tracker specific cat
$GLOBALS['egw_setup']->db->insert($GLOBALS['egw_setup']->cats_table,array(
	'cat_main'   => $patches,
	'cat_parent' => $patches,
	'cat_level'  => 1,
	'cat_owner'  => -1,
	'cat_access' => 'public',
	'cat_appname'=> 'tracker',
	'cat_name'   => 'Translations',
	'cat_description' => 'Added by setup.',
	'cat_data'   => 'cat',
	'last_mod'   => time(),
),false,__LINE__,__FILE__);

// create Admin group and make them tracker-admins
$admingroup = $GLOBALS['egw_setup']->add_account('Admins','Admin','Group',False,False);
$GLOBALS['egw_setup']->add_acl('tracker','run',$admingroup);
// create Default group and make them tracker-technicians
$defaultgroup = $GLOBALS['egw_setup']->add_account('Default','Default','Group',False,False);
$GLOBALS['egw_setup']->add_acl('tracker','run',$defaultgroup);

include(EGW_INCLUDE_ROOT.'/tracker/inc/class.botracker.inc.php');	// for the contstants
// save a default configuration
foreach(array(
	'overdue_days'       => 14,
	'pending_close_days' => 0,
	'allow_voting'       => 1,
	'allow_assign_groups'=> 1,
	'field_acl' => serialize(array(
		'tr_summary'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_tracker'     => TRACKER_ITEM_NEW|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'cat_id'         => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_version'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_status'      => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_description' => TRACKER_ITEM_NEW,
		'tr_assigned'    => TRACKER_ITEM_CREATOR|TRACKER_ADMIN,
		'tr_private'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_budget'      => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_resolution'  => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_completion'  => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'tr_priority'    => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		// pseudo fields used in edit
		'link_to'        => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'canned_response' => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
		'reply_message'  => TRACKER_USER,
		'add'            => TRACKER_USER,
		'vote'           => TRACKER_EVERYBODY,	// TRACKER_USER for NO anon user
	)),
	'admins' => serialize(array(
		0 => array($admingroup),		// Admin-group tracker-admins for all trackers
	)),
	'technicians' => serialize(array(
		0 => array($defaultgroup),		// Default group tracker-technicians for all trackers
	)),
) as $name => $value)
{
	$GLOBALS['egw_setup']->db->insert($GLOBALS['egw_setup']->config_table,array(
		'config_value' => $value,
		'config_app'   => 'tracker',
	),array(
		'config_name'  => $name,
	),__LINE__,__FILE__);
}
	

