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

	/* $Id: class.module_google.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

class module_google extends Module
{
	function module_google()
	{
		$this->arguments = array(
			'sitesearch' => array(
				'type' => 'textfield',
				'params' => array('size' => 40),
				'default' => str_replace(array('localhost','127.0.0.1'),'',$_SERVER['HTTP_HOST']),
				'label' => lang('Domain-name (offical DNS-name eg. www.eGroupWare.org) if you want to give the user a choice between searching this site or the whole web. Leaving it empty allows to search the web only.')
			),
		);
		$this->properties = array();
		$this->title = lang('Google');
		$this->description = lang('Interface to Google website');
	}

	function get_content(&$arguments,$properties)
	{
		$content = '<form action="http://www.google.com/search" name="f" target="_blank">'."\n";
		$content .= '<img src="images/Google_25wht.gif" border="0" align="middle" hspace="0" vspace="0"><br />'."\n";
		$content .= '<input type="hidden" name="hl" value="'.$GLOBALS['sitemgr_info']['userlang'].'">'."\n";
		$content .= '<input type="hidden" name="ie" value="'.$GLOBALS['egw']->translation->charset().'">'."\n";
		$content .= '<input maxLength="256" size="15" name="q" value="">'."\n";
		$content .= '<input type="submit" value="' . lang('Search') . '" name="btnG" title="'.lang('Google Search').'">'."\n";
		if ($arguments['sitesearch'])
		{
			$content .= '<div style="margin-top: 5px;">'."\n";
			$content .= '<input id="this_site" type="radio" checked="1" name="sitesearch" value="'.$arguments['sitesearch'].'" />'."\n";
			$content .= '<label for="this_site">'.lang('this site')."</label>\n";
			$content .= '<input id="the_web" type="radio" name="sitesearch" value="" />'."\n";
			$content .= '<label for="the_web">'.lang('the web')."</label>\n";
			$content .= "</div>\n";
		}
		$content .= "</form>\n";

		return $content;
	}
}
