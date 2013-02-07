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

	/* $Id: class.Page_SO.inc.php 13729 2004-02-10 14:56:34Z ralfbecker $ */

	class Page_SO
	{
		var $id;
		var $cat_id;
		var $name;
		var $title;
		var $subtitle;
		var $sort_order;
		var $hidden;
		var $lang;
		var $block;
		var $state;

		function Page_SO()
		{
			$hidden = 0;
		}
	}
?>
