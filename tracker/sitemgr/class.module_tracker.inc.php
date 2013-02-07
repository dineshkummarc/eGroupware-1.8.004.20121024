<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-8 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_tracker.inc.php 25473 2008-05-19 19:11:56Z ralfbecker $
 */

require_once(EGW_INCLUDE_ROOT.'/etemplate/inc/class.sitemgr_module.inc.php');

/**
 * SiteMgr module for the new tracker
 *
 */
class module_tracker extends sitemgr_module
{
	function module_tracker()
	{
		$this->arguments = array(
			'arg3' => array(		// will be passed as $only_tracker argument to uitracker::index()
				'type' => 'select',
				'label' => lang('Tracker'),
				'options' => array(
					'' => lang('All'),
				)+ExecMethod2('tracker.tracker_bo.get_tracker_labels','tracker')
			),
		);
		$this->title = lang('Tracker');
		$this->description = lang('This module displays information from the Tracker.');

		$this->etemplate_method = 'tracker.tracker_ui.index';
	}
}
