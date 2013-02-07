<?php
/**
 * eGroupWare - Notifications
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package notifications
 * @link http://www.egroupware.org
 * @author Cornelius Weiss <nelius@cwtech.de>
 * @version $Id: tables_current.inc.php 22498 2006-09-25 10:20:46Z nelius_weiss $
 */

	$phpgw_baseline = array(
		'egw_notificationpopup' => array(
			'fd' => array(
				'account_id' => array('type' => 'int','precision' => '20','nullable' => False),
				'session_id' => array('type' => 'varchar','precision' => '255','nullable' => False),
				'message' => array('type' => 'longtext')
			),
			'pk' => array(),
			'fk' => array(),
			'ix' => array('account_id','session_id'),
			'uc' => array()
		)
	);
