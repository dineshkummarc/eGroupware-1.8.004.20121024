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

	/* $Id: class.browser_transform.inc.php 13729 2004-02-10 14:56:34Z ralfbecker $ */

class browser_transform
{
	function browser_transform($prevlink,$nextlink)
	{
		$this->prevlink = $prevlink;
		$this->nextlink = $nextlink;
	}

	function apply_transform($title,$content)
	{
		$result = '<form method="post">';
		$result .= $content;
		$result .= '<div align="center">';
		$result .= $this->prevlink;
		$result .= $this->nextlink;
		$result .= '</form></div>';
		return $result;
	}
}
