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

	/* $Id: class.ui.inc.php 31888 2010-09-05 10:29:25Z ralfbecker $ */

	class ui
	{
		/**
		 * @var Template3
		 */
		var $t;

		function ui()
		{
			$themesel = $GLOBALS['sitemgr_info']['themesel'];
			if ($themesel[0] == '/')
			{
				$templateroot = $GLOBALS['egw_info']['server']['files_dir'] . $themesel;
			}
			else
			{
				$templateroot = $GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates' . SEP . $themesel;
			}
			$this->t = new Template3($templateroot);
		}

		function displayPageByName($page_name)
		{
			global $objbo;
			global $page;
			$objbo->loadPage($GLOBALS['Common_BO']->pages->so->PageToID($page_name));
			$this->generatePage();
		}

		function displayPage($page_id)
		{
			global $objbo;
			$objbo->loadPage($page_id);
			$this->generatePage();
		}

		function displayIndex()
		{
			global $objbo;
			$objbo->loadIndex();
			$this->generatePage();
		}

		function displayTOC($categoryid=false)
		{
			global $objbo;
			$objbo->loadTOC($categoryid);
			$this->generatePage();
		}

		function displaySearch($search_result,$lang,$mode,$options)
		{
			global $objbo;
			$objbo->loadSearchResult($search_result,$lang,$mode,$options);
			$this->generatePage();
		}

		function generatePage()
		{
			// add a content-type header to overwrite an existing default charset in apache (AddDefaultCharset directiv)
			header('Content-type: text/html; charset='.$GLOBALS['egw']->translation->charset());

			echo $this->t->parse();
		}
	}
