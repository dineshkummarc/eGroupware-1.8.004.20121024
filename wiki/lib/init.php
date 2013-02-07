<?php
	/**************************************************************************\
	* eGroupWare Wiki - general initialization of the old tavi code            *
	* http://www.egroupware.org                                                *
	* -------------------------------------------------                        *
	* Originaly from tavi, modified by RalfBecker@outdoor-training.de for eGW  *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	// $Id: init.php 27222 2009-06-08 16:21:14Z ralfbecker $

	require('lib/defaults.php');

	/**
	 * Check if we allow anon access and with which creditials
	 * 
	 * @param array &$anon_account anon account_info with keys 'login', 'passwd' and optional 'passwd_type'
	 * @return boolean true if we allow anon access, false otherwise
	 */
	function wiki_check_anon_access(&$anon_account)
	{
		$c =& CreateObject('phpgwapi.config','wiki');
		$c->read_repository();
		$config =& $c->config_data;
		unset($c);

		if ($config['allow_anonymous'] && $config['anonymous_username'])
		{
			$anon_account = array(
				'login'  => $config['anonymous_username'],
				'passwd' => $config['anonymous_password'],
				'passwd_type' => 'text',
			);
			return true;
		}
		return false;
	}
		
	// uncomment the next line if wiki should use a eGW domain different from the first one defined in your header.inc.php
	// and of cause change the name accordingly ;-)
	// $GLOBALS['egw_info']['user']['domain'] = $GLOBALS['egw_info']['server']['default_domain'] = 'developers';

	$GLOBALS['egw_info']['flags'] = array(
		'disable_Template_class' => True,
		'noheader'  => True,
		'currentapp' => 'wiki',
		'autocreate_session_callback' => 'wiki_check_anon_access',
	);
	include('../header.inc.php');

	$c =& CreateObject('phpgwapi.config','wiki');
	$c->read_repository();
	if (!isset($c->config_data['ExpireLen']))
	{
		$c->config_data['ExpireLen'] = $ExpireLen;
		$c->save_repository();
	}
	$config = $c->config_data;
	unset($c);

	// anonymous sessions have no navbar !!!
	$GLOBALS['egw_info']['flags']['nonavbar'] = $config['allow_anonymous'] &&
		$config['anonymous_username'] == $GLOBALS['egw_info']['user']['account_lid'];

	$HomePage = (isset($config[wikihome])?$config[wikihome]:'eGroupWare');
	$InterWikiPrefix = (isset($config[InterWikiPrefix])?$config[InterWikiPrefix]:'EGroupWare');
	$EnableFreeLinks = (isset($config[Enable_Free_Links])?$config[Enable_Free_Links]:1);
	$EnableWikiLinks = (isset($config[Enable_Wiki_Links])?$config[Enable_Wiki_Links]:1);
	$EditWithPreview = (isset($config[Edit_With_Preview])?$config[Edit_With_Preview]:1);
	$ExpireLen = $config['ExpireLen'];

	$UserName = $GLOBALS['egw_info']['user']['account_lid'];
	if (!($_GET['action'] == 'save' && !$_POST['Preview']) && $_GET['action'] != 'admin' && !($_GET['action'] == 'prefs' && $_POST['Save']) && $_GET['action'] != 'xml')
	{
		$GLOBALS['egw']->common->egw_header();
	}

	define('TemplateDir', 'template');

	$Charset = $GLOBALS['egw']->translation->charset();
	if (strtolower($Charset) == 'iso-8859-1')	// allow all iso-8859-1 extra-chars
	{
		$UpperPtn = "[A-Z\xc0-\xde]";
		$LowerPtn = "[a-z\xdf-\xff]";
		$AlphaPtn = "[A-Za-z\xc0-\xff]";
		$LinkPtn = $UpperPtn . $AlphaPtn . '*' . $LowerPtn . '+' .
			$UpperPtn . $AlphaPtn . '*(\\/' . $UpperPtn . $AlphaPtn . '*)?';
	}

	$WikiLogo = $GLOBALS['egw_info']['server']['webserver_url'] . '/phpgwapi/templates/default/images/logo.png';
	// use eGW's temp dir
	$TempDir = $GLOBALS['egw_info']['server']['temp_dir'];

	require('lib/url.php');
	require('lib/messages.php');

	$pagestore =& CreateObject('wiki.wiki_so');

	$FlgChr = chr(255);                     // Flag character for parse engine.

	$Entity = array();                      // Global parser entity list.

	// Strip slashes from incoming variables.

	if(get_magic_quotes_gpc())
	{
		$document = stripslashes($document);
		$categories = stripslashes($categories);
		$comment = stripslashes($comment);
		if (is_array($page))
		{
			$page['name'] = stripslashes($page['name']);
		}
		else
		{
			$page = stripslashes($page);
		}
	}

	// Read user preferences from cookie.

	$prefstr = isset($_COOKIE[$CookieName])
	? $_COOKIE[$CookieName] : '';

	if(!empty($prefstr))
	{
		if(preg_match('/'."rows=([[:digit:]]+)".'/', $prefstr, $result))
		{ $EditRows = $result[1]; }
		if(preg_match('/'."cols=([[:digit:]]+)".'/', $prefstr, $result))
		{ $EditCols = $result[1]; }
		if(preg_match('/'."user=([^&]*)".'/', $prefstr, $result))
		{ $UserName = urldecode($result[1]); }
		if(preg_match('/'."days=([[:digit:]]+)".'/', $prefstr, $result))
		{ $DayLimit = $result[1]; }
		if(preg_match('/'."auth=([[:digit:]]+)".'/', $prefstr, $result))
		{ $AuthorDiff = $result[1]; }
		if(preg_match('/'."min=([[:digit:]]+)".'/', $prefstr, $result))
		{ $MinEntries = $result[1]; }
		if(preg_match('/'."hist=([[:digit:]]+)".'/', $prefstr, $result))
		{ $HistMax = $result[1]; }
		if(preg_match('/'."tzoff=([[:digit:]]+)".'/', $prefstr, $result))
		{ $TimeZoneOff = $result[1]; }
	}
