<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - SiteMgr support for Mambo Open Source templates     *
	* http://www.egroupware.org                                                *
	* Written and (c)) by RalfBecker@outdoor-training.de                       *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: metadata.php 22611 2006-10-11 15:40:01Z nelius_weiss $ */

	// This file echos the sites metadata
	global $objui;
	$objui->t->loadfile(realpath(dirname(__FILE__).'/../metadata.tpl'));
	echo $objui->t->parse();
