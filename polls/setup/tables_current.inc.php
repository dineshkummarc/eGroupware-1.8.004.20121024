<?php
/**
 * eGroupWare - Setup
 * http://www.egroupware.org 
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @subpackage setup
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: tables_current.inc.php 23899 2007-05-20 15:01:21Z ralfbecker $ 
 */

$phpgw_baseline = array(
	'egw_polls' => array(
		'fd' => array(
			'poll_id' => array('type' => 'auto','nullable' => False),
			'poll_title' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'poll_timestamp' => array('type' => 'int','precision' => '8','nullable' => False),
			'poll_visible' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'poll_votable' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
		),
		'pk' => array('poll_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_polls_answers' => array(
		'fd' => array(
			'answer_id' => array('type' => 'auto','nullable' => False),
			'poll_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'answer_text' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'answer_votes' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
		),
		'pk' => array('answer_id'),
		'fk' => array(),
		'ix' => array('poll_id'),
		'uc' => array()
	),
	'egw_polls_votes' => array(
		'fd' => array(
			'poll_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'answer_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'vote_uid' => array('type' => 'int','precision' => '4','nullable' => False),
			'vote_ip' => array('type' => 'varchar','precision' => '128'),
			'vote_timestamp' => array('type' => 'int','precision' => '8'),
		),
		'pk' => array('poll_id','answer_id','vote_uid','vote_ip'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	)
);
