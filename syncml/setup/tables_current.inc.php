<?php
/**
 * eGroupWare - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage setup
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (c) 2007-9 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: tables_current.inc.php 27446 2009-07-15 19:33:36Z ralfbecker $
 */

$phpgw_baseline = array(
	'egw_contentmap' => array(
		'fd' => array(
			'map_id' => array('type' => 'varchar','precision' => '128','nullable' => False),
			'map_guid' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'map_locuid' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'map_timestamp' => array('type' => 'timestamp','nullable' => False),
			'map_expired' => array('type' => 'bool','nullable' => False)
		),
		'pk' => array('map_id','map_guid','map_locuid'),
		'fk' => array(),
		'ix' => array('map_expired',array('map_id','map_locuid')),
		'uc' => array()
	),
	'egw_syncmldevinfo' => array(
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
	),
	'egw_syncmlsummary' => array(
		'fd' => array(
			'dev_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
			'sync_path' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'sync_serverts' => array('type' => 'varchar','precision' => '20','nullable' => False),
			'sync_clientts' => array('type' => 'varchar','precision' => '20','nullable' => False)
		),
		'pk' => array(array('dev_id','sync_path')),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_syncmldeviceowner' => array(
		'fd' => array(
			'owner_locname' => array('type' => 'varchar','precision' => '200','nullable' => False),
			'owner_devid' => array('type' => 'int','precision' => '4','nullable' => False),
			'owner_deviceid' => array('type' => 'varchar','precision' => '100','nullable' => False)
		),
		'pk' => array(),
		'fk' => array(),
		'ix' => array('owner_deviceid'),
		'uc' => array(array('owner_locname','owner_devid','owner_deviceid'))
	)
);
