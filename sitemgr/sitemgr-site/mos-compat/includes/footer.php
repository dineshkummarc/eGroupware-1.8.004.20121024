<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - SiteMgr support for Mambo Open Source templates     *
	* http://www.egroupware.org                                                *
	* Written and (c) by RalfBecker@outdoor-training.de                        *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: footer.php 25894 2008-08-13 13:54:19Z ralfbecker $ */

	// This file echos the contentarea 'footer'
	global $objui;
	echo $objui->t->process_blocks('footer');

	// this is necessary to get wz_tooltips
	echo $GLOBALS['egw_info']['flags']['need_footer'];
