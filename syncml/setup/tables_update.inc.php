<?php
/**
 * eGroupWare - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage setup
 * @version $Id: tables_update.inc.php 33066 2010-11-21 15:17:44Z jlehrke $
 */

function syncml_upgrade0_9_0()
{
	// We drop and create the table new, as the info's in it are queried
	// automatic from the device if not present in the table.
	// This way we dont have to fight with DB's who can create an
	// autoincrement-index for an existing table.
	$GLOBALS['egw_setup']->oProc->DropTable('egw_syncmldevinfo');

	$GLOBALS['egw_setup']->oProc->CreateTable('egw_syncmldevinfo',array(
		'fd' => array(
			'dev_dtdversion' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'dev_numberofchanges' => array('type' => 'bool','nullable' => False),
			'dev_largeobjs' => array('type' => 'bool','nullable' => False),
			'dev_swversion' => array('type' => 'varchar','precision' => '100'),
			'dev_oem' => array('type' => 'varchar','precision' => '100'),
			'dev_model' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_manufacturer' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_devicetype' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_datastore' => array('type' => 'text','nullable' => False),
			'dev_id' => array('type' => 'auto','nullable' => False),
			'dev_fwversion' => array('type' => 'varchar','precision' => '100'),
			'dev_hwversion' => array('type' => 'varchar','precision' => '100'),
			'dev_utc' => array('type' => 'bool','nullable' => False)
		),
		'pk' => array('dev_id'),
		'fk' => array(),
		'ix' => array('dev_id'),
		'uc' => array('dev_id',array('dev_model','dev_manufacturer','dev_swversion'))
	));

	return $GLOBALS['setup_info']['syncml']['currentver'] = '0.9.4';
}


function syncml_upgrade0_9_4()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('egw_syncmldeviceowner',array(
		'fd' => array(
			'owner_locname' => array('type' => 'varchar','precision' => '200','nullable' => False),
			'owner_devid' => array('type' => 'int','precision' => '4','nullable' => False),
			'owner_deviceid' => array('type' => 'varchar','precision' => '100','nullable' => False)
		),
		'pk' => array('owner_devid'),
		'fk' => array(),
		'ix' => array('owner_locname','owner_deviceid'),
		'uc' => array(array('owner_locname','owner_devid','owner_deviceid'))
	));
	return $GLOBALS['setup_info']['syncml']['currentver'] = '0.9.6';
}


function syncml_upgrade0_9_6()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_syncmldeviceowner',array(
		'fd' => array(
			'owner_locname' => array('type' => 'varchar','precision' => '200','nullable' => False),
			'owner_devid' => array('type' => 'int','precision' => '4','nullable' => False),
			'owner_deviceid' => array('type' => 'varchar','precision' => '100','nullable' => False)
		),
		'pk' => array(),
		'fk' => array(),
		'ix' => array('owner_deviceid'),
		'uc' => array(array('owner_locname','owner_devid','owner_deviceid'))
	));

	return $GLOBALS['setup_info']['syncml']['currentver'] = '0.9.007';
}


function syncml_upgrade0_9_007()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_syncmldevinfo',array(
		'fd' => array(
			'dev_dtdversion' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'dev_numberofchanges' => array('type' => 'bool','nullable' => False),
			'dev_largeobjs' => array('type' => 'bool','nullable' => False),
			'dev_swversion' => array('type' => 'varchar','precision' => '100'),
			'dev_oem' => array('type' => 'varchar','precision' => '100'),
			'dev_model' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_manufacturer' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_devicetype' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'dev_datastore' => array('type' => 'text','nullable' => False),
			'dev_id' => array('type' => 'auto','nullable' => False),
			'dev_fwversion' => array('type' => 'varchar','precision' => '100'),
			'dev_hwversion' => array('type' => 'varchar','precision' => '100'),
			'dev_utc' => array('type' => 'bool','nullable' => False)
		),
		'pk' => array('dev_id'),
		'fk' => array(),
		'ix' => array(array('dev_model','dev_manufacturer')),
		'uc' => array()
	));

	return $GLOBALS['setup_info']['syncml']['currentver'] = '0.9.008';
}


function syncml_upgrade0_9_008()
{
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.4';
}


function syncml_upgrade1_4()
{
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.6';
}

/**
 * Remove install_id from egw_contentmap.map_guid
 */
function syncml_upgrade1_6()
{
	foreach($GLOBALS['egw_setup']->db->select('egw_contentmap','map_id,map_guid,map_locuid',false,__LINE__,__FILE__,false,'','syncml') as $row)
	{
		$guid_parts = explode('-',$row['map_guid']);
		if (count($guid_parts) > 2)
		{
			array_pop($guid_parts);	// remove last part (install_id)

			$GLOBALS['egw_setup']->db->update('egw_contentmap',array(
				'map_guid' => implode('-',$guid_parts),
			),$row,__LINE__,__FILE__,'syncml');
		}
	}
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.6.001';
}

/**
 * Delete deviceInfo table
 */
function syncml_upgrade1_6_001()
{
	$GLOBALS['egw_setup']->db->delete('egw_syncmldevinfo', array(0 => '1'), __LINE__, __FILE__, 'syncml');
	$GLOBALS['egw_setup']->db->delete('egw_syncmldeviceowner', array(0 => '1'), __LINE__, __FILE__, 'syncml');
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.6.500jl';
}

function syncml_upgrade1_6_001jl()
{
	$GLOBALS['egw_setup']->db->delete('egw_syncmldevinfo', array(0 => '1'), __LINE__, __FILE__, 'syncml');
	$GLOBALS['egw_setup']->db->delete('egw_syncmldeviceowner', array(0 => '1'), __LINE__, __FILE__, 'syncml');
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.6.500jl';
}

function syncml_upgrade1_6_500jl()
{
	return $GLOBALS['setup_info']['syncml']['currentver'] = '1.8';
}
