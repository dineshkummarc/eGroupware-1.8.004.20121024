<?php
/**
 * EGroupware - Bookmarks
 *
 * Based on Bookmarker Copyright (C) 1998  Padraic Renaghan
 *                     http://www.renaghan.com/bookmarker
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package admin
 * @subpackage setup
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

/* Basic information about this app */
$setup_info['bookmarks']['name']      = 'bookmarks';
$setup_info['bookmarks']['title']     = 'Bookmarks';
$setup_info['bookmarks']['version']   = '1.8';
$setup_info['bookmarks']['app_order'] = '12';
$setup_info['bookmarks']['enable']    = 1;

$setup_info['bookmarks']['author'] = 'Joseph Engo';
$setup_info['bookmarks']['license']  = 'GPL';
$setup_info['bookmarks']['description'] =
	'Manage your bookmarks with eGW.  Has Netscape plugin.';
$setup_info['bookmarks']['maintainer'] = array(
	'name' => 'eGroupWare Developers',
	'email' => 'egroupware-developers@lists.sourceforge.net'
);

/* The tables this app creates */
$setup_info['bookmarks']['tables'][] = 'egw_bookmarks';

/* The hooks this app includes, needed for hooks registration */
$setup_info['bookmarks']['hooks'][] = 'admin';
$setup_info['bookmarks']['hooks'][] = 'preferences';
$setup_info['bookmarks']['hooks'][] = 'sidebox_menu';

/* Dependencies for this app to work */
$setup_info['bookmarks']['depends'][] = array(
	'appname'  => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
