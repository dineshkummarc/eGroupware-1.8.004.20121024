<?php
/**
 * EGroupware - TranslationTools
 *
 * @link http://www.egroupware.org
 * @package calendar
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

/* Basic information about this app */
$setup_info['developer_tools']['name']      = 'developer_tools';
$setup_info['developer_tools']['title']     = 'TranslationTools';
$setup_info['developer_tools']['version']   = '1.8';
$setup_info['developer_tools']['app_order'] = 61;
$setup_info['developer_tools']['enable']    = 1;
$setup_info['developer_tools']['index']     = 'developer_tools.uilangfile.index';

$setup_info['developer_tools']['author'] = 'Miles Lott';
$setup_info['developer_tools']['description'] =
	'The TranslationTools allow to create and extend translations-files for eGroupWare.
	They can search the sources for new / added phrases and show you the ones missing in your language.';
$setup_info['developer_tools']['note'] =
	'Reworked and improved version of the former language-management of Milosch\'s developer_tools.';
$setup_info['developer_tools']['license']  = 'GPL';
$setup_info['developer_tools']['maintainer'] = array(
	'name' => 'Ralf Becker',
	'email' => 'RalfBecker@outdoor-training.de'
);

/* The tables this app creates */
$setup_info['developer_tools']['tables']    = array();

/* The hooks this app includes, needed for hooks registration */
$setup_info['developer_tools']['hooks']     = array();

/* Dependencies for this app to work */
$setup_info['developer_tools']['depends'][] = array(
	'appname' => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
