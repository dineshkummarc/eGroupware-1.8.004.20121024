<?php
/**
 * EGroupware - User manual
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package manual
 * @subpackage setup
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

/* Basic information about this app */
$setup_info['manual']['name']      = 'manual';
$setup_info['manual']['title']     = 'User Manual';
$setup_info['manual']['version']   = '1.8';
$setup_info['manual']['app_order'] = 5;
$setup_info['manual']['enable']    = 4;	// popup

$setup_info['manual']['author']    =
$setup_info['manual']['maintainer'] = 'Ralf Becker';
$setup_info['manual']['maintainer_email'] = 'RalfBecker@outdoor-training.de';
$setup_info['manual']['license']   = 'GPL';
$setup_info['manual']['description'] =
	'The new eGW Online User Manual uses the Wiki app.';

/* The hooks this app includes, needed for hooks registration */
$setup_info['manual']['hooks']['admin'] = 'manual.uimanualadmin.menu';
$setup_info['manual']['hooks']['config'] = 'manual.uimanualadmin.config';
$setup_info['manual']['hooks']['config_validate'] = 'manual.uimanualadmin.config';

/* Dependencies for this app to work */
$setup_info['manual']['depends'][] = array(
	 'appname' => 'phpgwapi',
	 'versions' => Array('1.7','1.8','1.9')
);
$setup_info['manual']['depends'][] = array(
	 'appname' => 'wiki',
	 'versions' => Array('1.7','1.8','1.9')
);
