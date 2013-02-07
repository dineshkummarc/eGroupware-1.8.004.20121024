<?php
/**************************************************************************\
* eGroupWare Wiki - SiteMgr module                                         *
* http://www.egroupware.org                                                *
* -------------------------------------------------                        *
* Copyright (c) 2004-6 by RalfBecker@outdoor-training.de                   *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id: class.module_wiki.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */

/**
 * extends the wiki user-interface for sitemgr
 *
 * As php cant do multiple inheritance, we have to use two classes
 */
class sitemgr_wiki extends wiki_ui
{
	var $wikipage_param = 'wikipage';	// name of the get-param used to transport the wiki-page-names
	var $wikilang_param = 'wikilang';	// name of the get-param used to transport the wiki-lang
	var $search_tag = '--s-e-a-r-c-h--';	// used to mark queries
	var $arguments;

	function sitemgr_wiki($arguments)
	{
		$this->arguments = $arguments;

		// calling the constructor of the extended class
		$this->uiwiki();
	}

	/**
	 * reimplemented that the we stay in SiteMgr and dont loose the actual SiteMgr page
	 */
	function viewURL($page, $lang='', $version='', $full = '')
	{
		$url = $_SERVER['PHP_SELF'].'?';
		foreach($_GET as $name => $val)
		{
			if (!in_array($name,array('search',$this->wikipage_param,$this->wikilang_param)))
			{
				$url .= $name.'='.urlencode($val).'&';
			}
		}
		foreach(array('lang','version','full') as $name)
		{
			if ($$name || is_array($page) && $page[$name])
			{
				if (!$$name && !($$name = $page[$name])) continue;

				if ($name != 'lang')
				{
					$url .= $name.'='.urlencode($$name).'&';
				}
				elseif($lang != $GLOBALS['egw_info']['user']['prefereces']['common']['lang'])
				{
					$url .= $this->wikilang_param.'='.urlencode($$name).'&';
				}
			}
		}
		$url .= $this->wikipage_param.'='.urlencode(is_array($page) ? $page['name'] : $page);
		
		// the page-parameter has to be the last one, as the old wiki code only calls it once with empty page and appends the pages later
		return $url;
	}
	
	/**
	 * reimplemented to disallow editing
	 */
	function editURL($page, $lang='',$version = '')
	{
		return False;
	}
	
	/**
	 * Show the page-header for the sitemgr module
	 *
	 * @param object/boolean $page sowikipage object or false
	 * @param string $title title of the search
	 */
	function header($page=false,$title='')
	{
		$html = '<table class="wiki-title"><tr>';
		if ($page && ($this->arguments['title'] == 2 || $this->arguments['title'] == 1 && $page->name == $this->arguments['startpage']))
		{
			if (isset($page->title)) $title = html::htmlspecialchars($page->title);
		}
		if ($this->arguments['search'])
		{
			$search = '<form class="wiki-search" action="'.htmlspecialchars($this->viewURL($this->search_tag)).'" method="POST">'.
				'<input name="search" value="'.html::htmlspecialchars($_REQUEST['search']).'" />&nbsp;'.
				'<input type="submit" value="'.html::htmlspecialchars(lang('Search')).'" /></form>'."\n";
		}
		if ($title && $search || $search && $this->arguments['title'] == 1)
		{
			return '<table class="wiki-title" cellpadding="0" cellspacing="0"><tr><td><div class="wiki-title">'.$title.
				'</div></td><td align="right" class="wiki-search">'.$search."</td></tr></table>\n";
		}
		elseif ($title)
		{
			return '<div class="wiki-title">'.$title."</div>\n";
		}
		else
		{
			return '<div class="wiki-search">'.$search."</div>\n";
		}
		return '';
	}
	
	/**
	 * Show the page-footer for the sitemgr module
	 *
	 * @param object/boolean $page sowikipage object or false
	 */
	function footer($page=false)
	{
		return '';
	}

	/**
	 * view a manual page
	 */
	function view()
	{
		$wikipage = isset($_GET[$this->wikipage_param]) ? stripslashes(urldecode($_GET['wikipage'])) :
			(empty($this->arguments['startpage']) ? $this->config['wikihome'] : $this->arguments['startpage']);

		if ($wikipage == $this->search_tag) return $this->search(true);

		$parts = explode(':',$wikipage);
		if (count($parts) > 1)
		{
			$lang = array_pop($parts);
			if (strlen($lang) == 2 || strlen($lang) == 5 && $lang[2] == '-')
			{
				$wikipage = implode(':',$parts);
			}
			else
			{
				$lang = $_GET[$this->wikilang_param];
			}
		}
		$page =& $this->page($wikipage,$lang);
		if ($page->read() === False) $page = false;
		
		$html = $this->header($page);
		
		if (!$page) $html .= '<p><b>'.lang("Page '%1' not found !!!",'<i>'.$wikipage.($lang ? ':'.$lang : '').'</i>')."</b></p>\n";

		$html .= '<div class="wiki-content">' . $this->get($page) . "</div>\n";
		$html .= $this->footer($page);

		return $html;
	}
}

/**
 * extends SiteMgr's Module class and instanciates the sitemgr_wiki class to display pages
 */
class module_wiki extends Module
{
	function module_wiki()
	{
		$GLOBALS['egw']->translation->add_app('wiki');

		$this->arguments = array(
			'startpage' => array(
				'type' => 'textfield',
				'label' => lang('Wiki startpage')
			),
			'title' => array(
				'type' => 'select',
				'label' => lang('Show the title of the wiki page'),
				'options' => array(
					0 => lang('never'),
					1 => lang('only on the first page'),
					2 => lang('on all pages'),
				)
			),	
			'search' => array(
				'type' => 'checkbox',
				'label' => lang('Show a search'),
			),
		);
		$this->properties = array();
		$this->title = lang('Wiki');
		$this->description = lang('Use this module for displaying wiki-pages');
	}

	function get_content(&$arguments,$properties)
	{
		if (!@$GLOBALS['egw_info']['user']['apps']['wiki'])
		{
			return lang('You have no rights to view wiki content !!!');
		}
		$wiki = new sitemgr_wiki($arguments);

		return $wiki->view();
	}
}
