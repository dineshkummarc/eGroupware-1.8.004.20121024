<?php
	/**
	 * eGroupWare - Notifications
	 *
	 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	 * @package notifications
	 * @link http://www.egroupware.org
	 * @author Cornelius Weiss <nelius@cwtech.de>
	 * @version $Id: hook_preferences.inc.php 24919 2008-01-30 18:58:00Z jaytraxx $
	 */
	
	$file = Array(	'Preferences' => $GLOBALS['egw']->link('/index.php',array(
		'menuaction'	=> 'preferences.uisettings.index',
		'appname'		=> $appname,
		)));
	display_section($appname,$file);
?>
