<?php
/* $Id: url.php 30173 2010-05-15 07:54:24Z ralfbecker $ */

// Under EGroupware these URL's are NOT configurable, you can set the webserver_url in setup
$ScriptBase = $GLOBALS['egw']->link('/wiki/index.php');
$ScriptBase .= strpos($ScriptBase,'?') !== false ? '&' : '?';

$AdminScript = $ScriptBase . 'action=admin';

//if(!isset($ViewBase))
	{ $ViewBase    = $GLOBALS['egw']->link('/index.php',array('menuaction'=>'wiki.wiki_ui.view')). '&page='; }
//if(!isset($EditBase))
	{ $EditBase    = $GLOBALS['egw']->link('/index.php',array('menuaction'=>'wiki.wiki_ui.edit')).'&page='; }
//if(!isset($HistoryBase))
	{ $HistoryBase = $ScriptBase . 'action=history&page='; }
//if(!isset($FindScript))
	{ $FindScript  = $GLOBALS['egw']->link('/index.php',array('menuaction'=>'wiki.wiki_ui.search')); }
//if(!isset($FindBase))
	{ $FindBase    = $FindScript . '&search='; }
//if(!isset($SaveBase))
	{ $SaveBase    = $ScriptBase . 'action=save&page='; }
//if(!isset($DiffScript))
	{ $DiffScript  = $ScriptBase . 'action=diff'; }
//if(!isset($PrefsScript))
	{ $PrefsScript = $ScriptBase . 'action=prefs'; }
//if(!isset($StyleScript))
	{ $StyleScript = $ScriptBase . 'action=style'; }

if(!function_exists('viewURL'))
{
	function viewURL($page, $version = '', $full = '')
	{
		global $ViewBase;

		if (is_array($page))
		{
			$lang = @$page['lang'] && $page['lang'] != $GLOBALS['egw_info']['user']['preferences']['common']['lang'] ? '&lang='.$page['lang'] : '';
			$page = $page['name'];
		}
		elseif (is_object($page))
		{
			$lang = @$page->lang && $page->lang != $GLOBALS['egw_info']['user']['preferences']['common']['lang'] ? '&lang='.$page->lang : '';
			$page = $page->name;
		}
		return $ViewBase . urlencode($page) . @$lang .
				($version == '' ? '' : "&version=$version") .
				($full == '' ? '' : '&full=1');
	}
}

if(!function_exists('editURL'))
{
	function editURL($page, $version = '')
	{
		global $EditBase;

		if (is_array($page))
		{
			$lang = @$page['lang'] && $page['lang'] != $GLOBALS['egw_info']['user']['preferences']['common']['lang'] ? '&lang='.$page['lang'] : '';
			$page = $page['name'];
		}
		return $EditBase . urlencode($page) . @$lang .
				($version == '' ? '' : "&version=$version");
	}
}

if(!function_exists('historyURL'))
{
	function historyURL($page, $full = '',$lang='')
	{
		global $HistoryBase;

		if ($lang || (is_array($page) && isset($page['lang'])))
		{
			$lang = '&lang=' . ($lang ? $lang : $page['lang']);
		}
		return $HistoryBase . urlencode(is_array($page) ? $page['name'] : $page) . $lang .
				($full == '' ? '' : '&full=1');
	}
}

if(!function_exists('findURL'))
{
	function findURL($page,$lang='')
	{
		global $FindBase;

		if ($lang || (is_array($page) && isset($page['lang'])))
		{
			$lang = '&lang=' . ($lang ? $lang : $page['lang']);
		}
		return $FindBase . urlencode(is_array($page) ? $page['name'] : $page) . $lang;
	}
}

if(!function_exists('saveURL'))
{
	function saveURL($page)
	{
		global $SaveBase;

		return $SaveBase . urlencode($page);
	}
}

?>
