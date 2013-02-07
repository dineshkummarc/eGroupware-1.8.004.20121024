<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_datasource.inc.php 39305 2012-05-22 17:54:14Z ralfbecker $
 */

include_once(EGW_INCLUDE_ROOT.'/projectmanager/inc/class.datasource.inc.php');

/**
 * DataSource for Tracker
 *
 * The Tracker datasource set's only real start- and endtimes and the assigned user as resources.
 */
class tracker_datasource extends datasource
{
	/**
	 * Constructor
	 */
	function datasource_tracker()
	{
		$this->datasource('tracker');

		$this->valid = PM_COMPLETION|PM_READ_START|PM_READ_END|PM_PLANNED_BUDGET|PM_RESOURCES;
	}

	/**
	 * get an entry from the underlaying app (if not given) and convert it into a datasource array
	 *
	 * @param mixed $data_id id as used in the link-class for that app, or complete entry as array
	 * @return array/boolean array with the data supported by that source or false on error (eg. not found, not availible)
	 */
	function get($data_id)
	{
		// we use $GLOBALS['boinfolog'] as an already running instance might be availible there
		if (!is_object($GLOBALS['botracker']))
		{
			include_once(EGW_INCLUDE_ROOT.'/tracker/inc/class.botracker.inc.php');
			$GLOBALS['botracker'] = new botracker();
		}
		if (!is_array($data_id))
		{
			$data =& $GLOBALS['botracker']->read((int) $data_id);

			if (!is_array($data)) return false;
		}
		else
		{
			$data =& $data_id;
		}
		return array(
			'pe_title'        => $GLOBALS['botracker']->link_title($data),
			'pe_completion'   => $data['tr_completion'],
			'pe_real_start'   => $data['tr_created'],
			'pe_real_end'     => $data['tr_closed'],
			'pe_resources'    => $data['tr_assigned'] ? (array)$data['tr_assigned'] : null,
			'pe_details'      => $data['tr_description'] ? nl2br($data['tr_description']) : '',
			'pe_planned_budget'   => $data['tr_budget'],
		);
	}
}
