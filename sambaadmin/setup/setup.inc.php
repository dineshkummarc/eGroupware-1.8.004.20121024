<?php
/**
 * EGroupware SambaAdmin
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sambaadmin
 * @link http://www.egroupware.org
 * @author Lars Kneschke
 * @version $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

$setup_info['sambaadmin']['name']	= 'sambaadmin';
$setup_info['sambaadmin']['title']	= 'SambaAdmin';
$setup_info['sambaadmin']['version']	= '1.8';
$setup_info['sambaadmin']['app_order']	= 99;

$setup_info['sambaadmin']['author']	= 'Lars Kneschke';

$setup_info['sambaadmin']['license']	= 'GPL';
$setup_info['sambaadmin']['description']= 'Manage LDAP based Samba servers';

$setup_info['sambaadmin']['maintainer'] = array(
	'name'	=> 'eGroupware coreteam',
	'email'	=> 'egroupware-developers@lists.sf.net',
);

$setup_info['sambaadmin']['tables']    = array();
$setup_info['sambaadmin']['enable']    = 1;
$setup_info['sambaadmin']['index']     = 'sambaadmin.uisambaadmin.listWorkstations';

/* The hooks this app includes, needed for hooks registration */
#$setup_info['sambaadmin']['hooks'][]	= 'preferences';
$setup_info['sambaadmin']['hooks'][]	= 'edit_user';
$setup_info['sambaadmin']['hooks'][]	= 'admin';
$setup_info['sambaadmin']['hooks'][]	= 'changepassword';

$setup_info['sambaadmin']['hooks']['addaccount']	= 'sambaadmin.bosambaadmin.updateAccount';
$setup_info['sambaadmin']['hooks']['editaccount']	= 'sambaadmin.bosambaadmin.updateAccount';
$setup_info['sambaadmin']['hooks']['addgroup']		= 'sambaadmin.bosambaadmin.updateGroup';
$setup_info['sambaadmin']['hooks']['editgroup']		= 'sambaadmin.bosambaadmin.updateGroup';

/* Dependacies for this app to work */
$setup_info['sambaadmin']['depends'][]	= array(
	'appname'  => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
