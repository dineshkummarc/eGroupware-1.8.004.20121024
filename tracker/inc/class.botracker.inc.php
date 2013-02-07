<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-8 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.botracker.inc.php 25462 2008-05-18 06:58:20Z ralfbecker $
 */

/**
 * Intermediate class to facilitate the update.
 *
 * It will be called only once and can be removed after 1.6 is released.
 */
class botracker extends tracker_bo
{
	function run_update()
	{
		// register all hooks
		ExecMethod('phpgwapi.hooks.register_hooks','tracker');

		if ($this->pending_close_days > 0)
		{
			self::set_async_job(false);	// switching it off, to remove the old botracker method
			self::set_async_job(true);
		}
	}

	function search_link($location)
	{
		$this->run_update();

		return tracker_hooks::search_link($location);
	}

	function close_pending()
	{
		$this->run_update();

		return parent::close_pending();
	}
}