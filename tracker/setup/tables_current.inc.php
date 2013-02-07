<?php
/**
 * eGroupWare - Setup
 * http://www.egroupware.org
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package tracker
 * @subpackage setup
 * @version $Id: tables_current.inc.php 26782 2009-04-10 09:16:43Z ralfbecker $
 */

$phpgw_baseline = array(
	'egw_tracker' => array(
		'fd' => array(
			'tr_id' => array('type' => 'auto','nullable' => False),
			'tr_summary' => array('type' => 'varchar','precision' => '80','nullable' => False),
			'tr_tracker' => array('type' => 'int','precision' => '4','nullable' => False),
			'cat_id' => array('type' => 'int','precision' => '4'),
			'tr_version' => array('type' => 'int','precision' => '4'),
			'tr_status' => array('type' => 'int','precision' => '4','default' => '-100'),
			'tr_description' => array('type' => 'text'),
			'tr_private' => array('type' => 'int','precision' => '2','default' => '0'),
			'tr_budget' => array('type' => 'decimal','precision' => '20','scale' => '2'),
			'tr_completion' => array('type' => 'int','precision' => '2','default' => '0'),
			'tr_creator' => array('type' => 'int','precision' => '4','nullable' => False),
			'tr_created' => array('type' => 'int','precision' => '8','nullable' => False),
			'tr_modifier' => array('type' => 'int','precision' => '4'),
			'tr_modified' => array('type' => 'int','precision' => '8'),
			'tr_closed' => array('type' => 'int','precision' => '8'),
			'tr_priority' => array('type' => 'int','precision' => '2','default' => '5'),
			'tr_resolution' => array('type' => 'char','precision' => '1','default' => 'n'),
			'tr_cc' => array('type' => 'text'),
			'tr_group' => array('type' => 'int','precision' => '11'),
			'tr_edit_mode' => array('type' => 'varchar','precision' => '5','default' => 'ascii'),
			'tr_seen' => array('type' => 'text')
		),
		'pk' => array('tr_id'),
		'fk' => array(),
		'ix' => array('tr_summary','tr_tracker','tr_version','tr_status','tr_group',array('cat_id','tr_status')),
		'uc' => array()
	),
	'egw_tracker_replies' => array(
		'fd' => array(
			'reply_id' => array('type' => 'auto','nullable' => False),
			'tr_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'reply_creator' => array('type' => 'int','precision' => '4','nullable' => False),
			'reply_created' => array('type' => 'int','precision' => '8','nullable' => False),
			'reply_message' => array('type' => 'text')
		),
		'pk' => array('reply_id'),
		'fk' => array(),
		'ix' => array(array('tr_id','reply_created')),
		'uc' => array()
	),
	'egw_tracker_votes' => array(
		'fd' => array(
			'tr_id' => array('type' => 'int','precision' => '4'),
			'vote_uid' => array('type' => 'int','precision' => '4'),
			'vote_ip' => array('type' => 'varchar','precision' => '128'),
			'vote_time' => array('type' => 'int','precision' => '8','nullable' => False)
		),
		'pk' => array('tr_id','vote_uid','vote_ip'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_tracker_bounties' => array(
		'fd' => array(
			'bounty_id' => array('type' => 'auto','nullable' => False),
			'tr_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'bounty_creator' => array('type' => 'int','precision' => '4','nullable' => False),
			'bounty_created' => array('type' => 'int','precision' => '8','nullable' => False),
			'bounty_amount' => array('type' => 'decimal','precision' => '20','scale' => '2','nullable' => False),
			'bounty_name' => array('type' => 'varchar','precision' => '64'),
			'bounty_email' => array('type' => 'varchar','precision' => '128'),
			'bounty_confirmer' => array('type' => 'int','precision' => '4'),
			'bounty_confirmed' => array('type' => 'int','precision' => '8'),
			'bounty_payedto' => array('type' => 'varchar','precision' => '128'),
			'bounty_payed' => array('type' => 'int','precision' => '8')
		),
		'pk' => array('bounty_id'),
		'fk' => array(),
		'ix' => array('tr_id'),
		'uc' => array()
	),
	'egw_tracker_assignee' => array(
		'fd' => array(
			'tr_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'tr_assigned' => array('type' => 'int','precision' => '4','nullable' => False)
		),
		'pk' => array('tr_id','tr_assigned'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_tracker_escalations' => array(
		'fd' => array(
			'esc_id' => array('type' => 'auto','nullable' => False),
			'tr_tracker' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'cat_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'tr_version' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'tr_status' => array('type' => 'varchar','precision' => '255','nullable' => False,'default' => '0'),
			'tr_priority' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'esc_title' => array('type' => 'varchar','precision' => '128','nullable' => False),
			'esc_time' => array('type' => 'int','precision' => '4','nullable' => False),
			'esc_type' => array('type' => 'int','precision' => '1','nullable' => False,'default' => '0'),
			'esc_tr_assigned' => array('type' => 'varchar','precision' => '255'),
			'esc_add_assigned' => array('type' => 'bool'),
			'esc_tr_tracker' => array('type' => 'int','precision' => '4'),
			'esc_cat_id' => array('type' => 'int','precision' => '4'),
			'esc_tr_version' => array('type' => 'int','precision' => '4'),
			'esc_tr_status' => array('type' => 'int','precision' => '4'),
			'esc_tr_priority' => array('type' => 'int','precision' => '4'),
			'esc_reply_message' => array('type' => 'text')
		),
		'pk' => array('esc_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array(array('tr_tracker','cat_id','tr_version','tr_status','tr_priority','esc_time','esc_type'))
	),
	'egw_tracker_escalated' => array(
		'fd' => array(
			'tr_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'esc_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'esc_created' => array('type' => 'timestamp','nullable' => False,'default' => 'current_timestamp')
		),
		'pk' => array('tr_id','esc_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_tracker_extra' => array(
		'fd' => array(
			'tr_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'tr_extra_name' => array('type' => 'varchar','precision' => '64','nullable' => False),
			'tr_extra_value' => array('type' => 'text')
		),
		'pk' => array('tr_id','tr_extra_name'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	)
);
