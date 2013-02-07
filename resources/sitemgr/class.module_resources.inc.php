<?php
	/**
	 * eGroupWare - resources
	 * http://www.egroupware.org
	 *
	 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	 * @package resources
	 * @author Cornelius Weiss <egw@von-und-zu-weiss.de>
	 * @author Lukas Weiss <wnz_gh05t@users.sourceforge.net>
	 * @version $Id: class.module_resources.inc.php 38844 2012-04-06 12:50:01Z ralfbecker $
	 */

	require_once (EGW_INCLUDE_ROOT.'/etemplate/inc/class.sitemgr_module.inc.php');

	class module_resources extends sitemgr_module
	{
		function module_resources()
		{
			$this->arguments = array();
			$this->properties = array();
			$this->title = lang('Resources');
			$this->description = lang('This module displays the resources app');
			$this->etemplate_method = 'resources.resources_ui.index';
		}
	}