<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Block_SO.inc.php 18769 2005-07-10 15:30:36Z ralfbecker $ */

	class Block_SO
	{
		var $id;
		var $cat_id;
		var $page_id;
		var $area;
		var $module_id;
		var $module_name;
		var $arguments;
		var $sort_order;
		var $title;
		var $view;
		var $state;
		var $version;
		
		function Block_SO($args=array())
		{
			if (is_array($args))
			{
				foreach($args as $name => $value)
				{
					$this->$name = $value;
				}
			}
		}

		function set_version($version)
		{
			$this->arguments = $version['arguments'];
			$this->state = $version['state'];
			$this->version = $version['id'];
		}
	}
?>
