<?php
	/**************************************************************************\
	* phpGroupWare - Registration                                              *
	* http://www.phpgroupware.org                                              *
	* This application written by Joseph Engo <jengo@phpgroupware.org>         *
	* --------------------------------------------                             *
	* Funding for this program was provided by http://www.checkwithmom.com     *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: index.php 27884 2009-09-21 08:34:45Z ralfbecker $ */

	/**
	 * Check if we allow anon access and with which creditials
	 *
	 * @param array &$anon_account anon account_info with keys 'login', 'passwd' and optional 'passwd_type'
	 * @return boolean true if we allow anon access, false otherwise
	 */
	function registration_check_anon_access(&$anon_account)
	{
		$c =& CreateObject('phpgwapi.config','registration');
		$c->read_repository();
		$config =& $c->config_data;
		unset($c);

		if ($config['enable_registration'] == "True" && $config['anonymous_user'])
		{
			$anon_account = array(
				'login'  => $config['anonymous_user'],
				'passwd' => $config['anonymous_pass'],
				'passwd_type' => 'text',
			);
			return true;
		}
		return false;
	 }

	 // if activation id is given header to activate method
	 if(isset($_GET['aid']) && preg_match('/^[0-9a-f]{32}$/',$_GET['aid']))
	 {
		header('Location:index.php?menuaction=registration.uireg.step4_activate_account&reg_id='.$_GET['aid']);
		exit;
	 }
	 if(isset($_GET['pwid']) && preg_match('/^[0-9a-f]{32}$/',$_GET['pwid']))
	 {
		header('Location:index.php?menuaction=registration.uireg.lostpw_step3_validate_reg_id&reg_id='.$_GET['pwid']);
		exit;
	 }

	$GLOBALS['egw_info']['flags'] = array(
		'noheader'  => True,
		'nonavbar' => True,
		'currentapp' => 'registration',
		'autocreate_session_callback' => 'registration_check_anon_access',
	);
	include('../header.inc.php');

	// config needs to be global in registration app
	$c =& CreateObject('phpgwapi.config','registration');
	$c->read_repository();
	$config = $c->config_data;

	$app = 'registration';
	if ($_GET['menuaction'])
	{
		list($a,$class,$method) = explode('.',$_GET['menuaction']);
		if ($a && $class && $method)
		{
			$obj =& CreateObject($app. '.'. $class);
			if (is_array($obj->public_functions) && $obj->public_functions[$method])
			{
				$obj->$method();
				exit();
			}
		}
	}
	$_obj =& CreateObject('registration.uireg');
	$_obj->step1_ChooseLangAndLoginName();
