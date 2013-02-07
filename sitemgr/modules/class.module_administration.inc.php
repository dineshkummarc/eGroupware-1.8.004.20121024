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

	/* $Id: class.module_administration.inc.php 13729 2004-02-10 14:56:34Z ralfbecker $ */

class module_administration extends Module
{
	function module_administration()
	{
		$this->arguments = array();
		$this->properties = array();
		$this->title = lang('Administration');
		$this->description = lang('This module is a selectbox to change the mode (production, draft or edit) plus a link back to SiteMgr and to log out. It is meant for registered users only');
	}

	function get_content(&$arguments,$properties)
	{
		$content = '<form name="modeselect" method="post">' . "\n".
			'<select onChange="location.href=this.value" name="mode">'."\n";
		foreach(array(
			'Production' => lang('Production mode'),
			'Draft'      => lang('Draft mode'),
			'Edit'       => lang('Edit mode')) as $mode => $label)
		{
			$selected = ($GLOBALS['sitemgr_info']['mode'] == $mode) ? ' selected="selected"' : '';
			$content .=	'<option value="' .$this->link(array(),array('mode'=>$mode)) .'"' . $selected  . '>' . $label . "</option>\n";
		}
		$content .= "</select>\n</form>\n" .
			'<p>&nbsp;&nbsp;<strong><big>&middot;</big></strong><a href="' . phpgw_link('/sitemgr/') .
			'">' . lang('Content Manager') . "</a><br />\n".
			'&nbsp;&nbsp;<strong><big>&middot;</big></strong><a href="' . phpgw_link('/logout.php') .
			'">' . lang('Logout') . "</a></p>\n";
		return $content;
	}

}
