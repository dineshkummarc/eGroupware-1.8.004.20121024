<?php
/**
 * eGroupWare - Setup
 * http://www.egroupware.org
 * Created by eTemplates DB-Tools written by ralfbecker@outdoor-training.de
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package notifications
 * @subpackage setup
 * @version $Id: tables_update.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

function notifications_upgrade0_5()
{
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_notificationpopup','account_id',array(
		'type' => 'int',
		'precision' => '20',
		'nullable' => False
	));

	return $GLOBALS['setup_info']['notifications']['currentver'] = '0.6';
}


function notifications_upgrade0_6()
{
	return $GLOBALS['setup_info']['notifications']['currentver'] = '1.4';
}


function notifications_upgrade1_4()
{
	return $GLOBALS['setup_info']['notifications']['currentver'] = '1.6';
}


function notifications_upgrade1_6()
{
	return $GLOBALS['setup_info']['notifications']['currentver'] = '1.8';
}
