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

	/* $Id: class.Category_SO.inc.php 15048 2004-05-02 09:26:18Z ralfbecker $ */

	class Category_SO
	{
		var $id = 0;
		var $name;
		var $description;
		var $sort_order;
		var $parent = 0;
		var $depth;
		var $root;
		var $state = 0;
		var $index_page_id = 0;

		function Category_SO()
		{
		}
	}
?>
