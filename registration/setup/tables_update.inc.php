<?php
/**
 * EGroupware Registration
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package registration
 * @link http://www.egroupware.org
 * @version $Id: tables_update.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

function registration_upgrade0_8_1()
{
	global $setup_info, $phpgw_setup;

	$phpgw_setup->oProc->CreateTable('phpgw_reg_fields', array(
		'fd' => array(
			'field_name' => array('type' => 'varchar', 'precision' => 255,'nullable' => False),
			'field_text' => array('type' => 'text','nullable' => False),
			'field_type' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
			'field_values' => array('type' => 'text','nullable' => True),
			'field_required' => array('type' => 'char', 'precision' => 1,'nullable' => True),
			'field_order' => array('type' => 'int', 'precision' => 4,'nullable' => True)
		),
		'pk' => array(),
		'ix' => array(),
		'fk' => array(),
		'uc' => array()
	));

	return $setup_info['registration']['currentver'] = '0.8.2';
}


function registration_upgrade0_8_2()
{
	$setup_info['registration']['currentver'] = '1.0.1';
	return $setup_info['registration']['currentver'];
}


function registration_upgrade1_0_0()
{
	return $setup_info['registration']['currentver'] = '1.0.1';
}


function registration_upgrade1_0_1()
{
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_reg_fields','egw_reg_fields');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_reg_accounts','egw_reg_accounts');

	return $GLOBALS['setup_info']['registration']['currentver'] = '1.2';
}


function registration_upgrade1_2()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_reg_accounts','reg_status',array(
		'type' => 'varchar',
		'precision' => '1',
		'nullable' => False,
		'default' => 'x'
	));

	return $GLOBALS['setup_info']['registration']['currentver'] = '1.3.001';
}


function registration_upgrade1_3_001()
{
	return $GLOBALS['setup_info']['registration']['currentver'] = '1.4';
}


function registration_upgrade1_4()
{
	return $GLOBALS['setup_info']['registration']['currentver'] = '1.8';
}
