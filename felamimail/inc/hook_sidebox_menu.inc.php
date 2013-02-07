<?php
/**
 * FelamiMail - easing migration to new hooks
 *
 * @link http://www.egroupware.org
 * @package felamimail
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: hook_sidebox_menu.inc.php 28146 2009-10-18 12:38:50Z ralfbecker $
 */

ExecMethod('phpgwapi.hooks.register_all_hooks');
felamimail_hooks::sidebox_menu(array('location' => 'preferences'));