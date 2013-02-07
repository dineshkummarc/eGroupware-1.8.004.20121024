<?php
/**
 * EGroupWare - Setup
 * 
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de
 *
 * @link http://www.egroupware.org 
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @subpackage setup
 * @version $Id: tables_update.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

function polls_upgrade0_8_1()
{
	$GLOBALS['phpgw_setup']->oProc->AlterColumn('phpgw_polls_desc','poll_title',array(
		'type' => 'varchar',
		'precision' => '120',
		'nullable' => False
	));
	$GLOBALS['phpgw_setup']->oProc->AlterColumn('phpgw_polls_data','option_text',array(
		'type' => 'varchar',
		'precision' => '100',
		'nullable' => False
	));

	return $GLOBALS['setup_info']['polls']['currentver'] = '0.9.1';
}


function polls_upgrade0_9_1()
{
	return $GLOBALS['setup_info']['polls']['currentver'] = '1.0.0';
}


function polls_upgrade1_0_0()
{
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_polls_desc','egw_polls');
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_polls','poll_timestamp',array(
		'type' => 'int',
		'precision' => '8',
		'nullable' => False
	));
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_polls','poll_visible',array(
		'type' => 'int',
		'precision' => '4',
		'nullable' => False,
		'default' => '0',
	));
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_polls','poll_votable',array(
		'type' => 'int',
		'precision' => '4',
		'nullable' => False,
		'default' => '0',
	));

	// we cant have a vote_id 0 in an auto column, so we have to set a new one!
	$GLOBALS['egw_setup']->db->query('SELECT MAX(vote_id) FROM phpgw_polls_data',__LINE__,__FILE__);
	$id0 = $GLOBALS['egw_setup']->db->next_record() ? 1+$GLOBALS['egw_setup']->db->f(0) : 1;
	$GLOBALS['egw_setup']->db->query("UPDATE phpgw_polls_data SET vote_id=$id0 WHERE vote_id=0",__LINE__,__FILE__);
	
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_polls_data','egw_polls_answers');
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_polls_answers',array(
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
	),array(
		'answer_id' => 'vote_id',
		'answer_text' => 'option_text',
		'answer_votes' => 'option_count',
	));

	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_polls_user','egw_polls_votes');
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_polls_votes',array(
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
	),array(
		'answer_id' => 'vote_id',
		'vote_uid' => 'user_id',
	));
	
	// not need can go into egw_config
	$GLOBALS['egw_setup']->oProc->DropTable('phpgw_polls_settings');

	return $GLOBALS['setup_info']['polls']['currentver'] = '1.4';
}


function polls_upgrade1_4()
{
	return $GLOBALS['setup_info']['polls']['currentver'] = '1.8';
}
