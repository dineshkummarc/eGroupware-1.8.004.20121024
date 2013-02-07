<?php
/**
 * sitemgr - return the current API version or an arbitrary string
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker(at)outdoor-training.de> updated to new vfs
 * @package sitemgr
 * @subpackage modules
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_version.inc.php 26743 2009-04-03 14:44:52Z ralfbecker $
 */

class module_version extends Module
{
	function __construct()
	{
		$this->arguments = array(
			'version' => array(
				'type' => 'textfield',
				'params' => array('size' => 50),
				'label' => lang('String to return (default API version)')
			)
		);
		$this->title = lang('Version');
		$this->description = lang('Module returns API version or an arbitrary string.');
	}

	function get_content(&$arguments,$properties)
	{
		$version = (string)$arguments['version'] !== '' ? $arguments['version'] : $GLOBALS['egw_info']['apps']['phpgwapi']['version'];
		if ($GLOBALS['sitemgr_info']['mode'] != 'Edit')
		{
			ob_end_clean();		// for mos templates, stop the output buffering
			echo (string)$arguments['version'] !== '' ? $arguments['version'] : $GLOBALS['egw_info']['apps']['phpgwapi']['version'];
			$GLOBALS['egw']->common->egw_exit();
		}
		return lang('String to return (default API version)').': '.$version;
	}
}
