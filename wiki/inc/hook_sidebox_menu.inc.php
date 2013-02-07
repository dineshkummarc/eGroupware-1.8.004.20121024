<?php
/**
 * Wiki - easing migration to new hooks
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: hook_sidebox_menu.inc.php 28160 2009-10-19 17:04:11Z ralfbecker $
 */

ExecMethod('phpgwapi.hooks.register_all_hooks');
wiki_hooks::sidebox_menu(array('location' => 'preferences'));
