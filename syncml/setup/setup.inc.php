<?php
/**
 * eGroupWare - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage setup
 * @version $Id: setup.inc.php 33066 2010-11-21 15:17:44Z jlehrke $
 */

/* Basic information about this app */
$setup_info['syncml']['name']      = 'syncml';
$setup_info['syncml']['title']     = 'SyncML';
$setup_info['syncml']['version']   = '1.8';
$setup_info['syncml']['enable']    = 2;
$setup_info['syncml']['app_order'] = 99;

$setup_info['syncml']['author'] = array(
		'name' => 'Lars Kneschke, Horde Project/Jörg Lehrke',
		'url'  => 'http://k.noc.de'
	);
$setup_info['syncml']['note']   = 'SyncML interface for eGroupWare with extensions from Joerg Lehrke';
$setup_info['syncml']['license']  = 'GPL';
$setup_info['syncml']['description'] =
	'This module allows you to syncronize your SyncML enabled device.';

$setup_info['syncml']['maintainer'] = array(
		'name' => 'eGroupware coreteam/Jörg Lehrke',
		'email' => 'egroupware-developers@list.sf.net'
	);


/* The tables this app creates */
$setup_info['syncml']['tables'][]  = 'egw_contentmap';
$setup_info['syncml']['tables'][]  = 'egw_syncmldevinfo';
$setup_info['syncml']['tables'][]  = 'egw_syncmlsummary';
$setup_info['syncml']['tables'][]  = 'egw_syncmldeviceowner';

/* The hooks this app includes, needed for hooks registration */
$setup_info['syncml']['hooks']['preferences'] = 'syncml_hooks::preferences';
$setup_info['syncml']['hooks']['settings'] = 'syncml_hooks::settings';
$setup_info['syncml']['hooks']['deleteaccount'] = 'syncml.devices.deleteAccount';



/* Dependencies for this app to work */
$setup_info['syncml']['depends'][] = array(
	 'appname'  => 'phpgwapi',
	 'versions' => Array('1.7','1.8','1.9')
);
// installation checks for SyncML
$setup_info['syncml']['check_install'] = array(
	'' => array(
		'func' => 'pear_check',
		'from' => 'SyncML',
	),
	'Log' => array(
		'func' => 'pear_check',
		'from' => 'SyncML',
	),
);
