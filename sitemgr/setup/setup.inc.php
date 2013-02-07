<?php
/**
 * eGroupWare - SiteMgr Web content Management
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sitemgr
 * @subpackage setup
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

$setup_info['sitemgr']['name']      = 'sitemgr';
$setup_info['sitemgr']['title']     = 'SiteMgr Web Content Management';
$setup_info['sitemgr']['version']   = '1.8';
$setup_info['sitemgr']['app_order'] = 14;
$setup_info['sitemgr']['tables']    = array('egw_sitemgr_pages','egw_sitemgr_pages_lang','egw_sitemgr_categories_state','egw_sitemgr_categories_lang','egw_sitemgr_modules','egw_sitemgr_blocks','egw_sitemgr_blocks_lang','egw_sitemgr_content','egw_sitemgr_content_lang','egw_sitemgr_active_modules','egw_sitemgr_properties','egw_sitemgr_sites','egw_sitemgr_notifications','egw_sitemgr_notify_messages');
$setup_info['sitemgr']['enable']    = 1;
$setup_info['sitemgr']['author'] = 'Michael Totschnig and others';
$setup_info['sitemgr']['maintainer'] = array(
	'name'  => 'Ralf Becker',
	'email' => 'ralfbecker@outdoor-training.de'
);
$setup_info['sitemgr']['license']  = 'GPL';
$setup_info['sitemgr']['description'] = nl2br(
'This program will generate a dynamic web site with discrete sections that various eGroupWare users may edit, if the administrator gives them permission to do so.  In effect, the generated website can have sections which independent departments are in charge of maintaining.  The site administrator can choose a theme and create headers, footers, and sidebars to enforce a sitewide look and feel.  Site sections can be viewable public (viewable by anonymous users) or private (viewable by specified users and groups only).

<b>Former Contributors and Maintainers</b>
Michael Totschnig (totschnig.michael -AT- uqam.ca)
wrote multilingual facets of sitemgr, and conceived the modularized architecture

Team 10 in the UC Irvine Systems Design Course, ICS 125, chose this as their project.  Seek3r served as the project\'s "customer" and the team wrote extensive requirements and design documents followed by the actual coding of the project.  The course is ten weeks long, but coding doesn\'t start until part-way through week 6, so version 1.0 of sitemgr was programmed in an intensive 3 weeks.

<u>Credits</u>
ICS 125 Team 10:

Tina Alinaghian (tina -AT- checkyour6.net)
Austin Lee (anhjah -AT- hotmail.com)
Siu Leung (rurouni_master -AT- hotmail.com)
Fang Ming Lo (flo -AT- uci.edu)
Patrick Walsh (mr_e -AT- phpgroupware.org)

Professor:
Hadar Ziv (profziv -AT- aol.com)

TA:
Arijit Ghosh (arijitg -AT- uci.edu)');
$setup_info['sitemgr']['note'] = '';

/* The hooks this app includes, needed for hooks registration */
$setup_info['sitemgr']['hooks'][] = 'preferences';
$setup_info['sitemgr']['hooks'][] = 'about';
$setup_info['sitemgr']['hooks'][] = 'admin';
$setup_info['sitemgr']['hooks'][] = 'sidebox_menu';
$setup_info['sitemgr']['hooks'][] = 'settings';

/* Dependencies for this app to work */
$setup_info['sitemgr']['depends'][] = array(
	'appname'  => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
$setup_info['sitemgr']['depends'][] = array(
	'appname'  => 'etemplate',
	'versions' => Array('1.7','1.8','1.9')
);

/**
 * Sitemgr link aka Website registration
 */
$setup_info['sitemgr-link']['name']      = 'sitemgr-link';
$setup_info['sitemgr-link']['title']     = 'Website';
$setup_info['sitemgr-link']['version']   = '1.8';
$setup_info['sitemgr-link']['app_order'] = 9;
$setup_info['sitemgr-link']['tables']    = array();
$setup_info['sitemgr-link']['enable']    = 1;
$setup_info['sitemgr-link']['index']     = '/sitemgr/sitemgr-link.php';
$setup_info['sitemgr-link']['icon']      = 'sitemgr-link';
$setup_info['sitemgr-link']['icon_app']  = 'sitemgr';
$setup_info['sitemgr-link']['author'] = $setup_info['sitemgr']['author'];
$setup_info['sitemgr-link']['maintainer'] = $setup_info['sitemgr']['maintainer'];
$setup_info['sitemgr-link']['license']  = $setup_info['sitemgr']['license'];

/* Dependencies for this app to work */
$setup_info['sitemgr-link']['depends'][] = array(
	'appname'  => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
$setup_info['sitemgr-link']['depends'][] = array(
	'appname' => 'sitemgr',
	'versions' => array('1.7','1.8','1.9')
);
