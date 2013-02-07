<?php
/**
 * Wiki - facilitate the update to new autoloading names
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package wiki
 * @copyright (c) 2009 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.bowiki.inc.php 27152 2009-05-29 10:33:47Z ralfbecker $
 */

/**
 * Intermediate class to facilitate the update.
 *
 * It will be called only once and can be removed after 1.8 is released.
 */
class bowiki extends wiki_bo
{
	static function search_link($location)
	{
		include(EGW_INCLUDE_ROOT.'/wiki/setup/setup.inc.php');
		// register all hooks
		ExecMethod2('phpgwapi.hooks.register_hooks','wiki',$setup_info['wiki']['hooks']);

		return parent::search_link($location);
	}
}