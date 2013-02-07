<?php
	/***************************************************************************\
	* eGroupWare - SambaAdmin                                                   *
	* http://www.egroupware.org                                                 *
	* http://www.linux-at-work.de                                               *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/
	/* $Id: class.uiuserdata.inc.php 19994 2005-11-25 12:36:45Z lkneschke $ */

	class uiuserdata
	{

		var $public_functions = array
		(
			'editUserData'	=> True,
			'saveUserData'	=> True
		);

		function uiuserdata()
		{
			$this->t		=& CreateObject('phpgwapi.Template',$GLOBALS['egw']->common->get_tpl_dir('sambaadmin'));
			$this->bosambaadmin	=& CreateObject('sambaadmin.bosambaadmin');
			
			$this->rowColor[0] = $GLOBLAS['phpgw_info']["theme"]["bg01"];
			$this->rowColor[1] = $GLOBLAS['phpgw_info']["theme"]["bg02"];
											 
		}
	
		function display_app_header()
		{
			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
		}

		function editUserData($_useCache='0')
		{
			$accountID = $_GET['account_id'];			

			$this->display_app_header();

			$this->translate();

			$this->t->set_file(array("editUserData" => "edituserdata.tpl"));
			$this->t->set_block('editUserData','form','form');
			$this->t->set_block('editUserData','link_row','link_row');
			$this->t->set_var("th_bg",$GLOBALS['egw_info']["theme"]["th_bg"]);
			$this->t->set_var("tr_color1",$GLOBALS['egw_info']["theme"]["row_on"]);
			$this->t->set_var("tr_color2",$GLOBALS['egw_info']["theme"]["row_off"]);
			
			$this->t->set_var("lang_button",lang("save"));
			$this->t->set_var("lang_ready",lang("Done"));
			$this->t->set_var("link_back",$GLOBALS['egw']->link('/admin/accounts.php'));
			
			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uiuserdata.saveUserData',
				'account_id'	=> $accountID
			);
			$this->t->set_var("form_action", $GLOBALS['egw']->link('/index.php',$linkData));
			
			// only when we show a existing user
			if($userData = $this->bosambaadmin->getUserData($accountID, $_useCache))
			{
				$charset = $GLOBALS['egw']->translation->charset();
				$this->t->set_var('displayname',htmlspecialchars($userData["displayname"],ENT_QUOTES,$charset));
				$this->t->set_var('sambahomepath',htmlspecialchars($userData["sambahomepath"],ENT_QUOTES,$charset));
				$this->t->set_var('sambahomedrive',htmlspecialchars($userData['sambahomedrive'],ENT_QUOTES,$charset));
				$this->t->set_var('sambalogonscript',htmlspecialchars($userData['sambalogonscript'],ENT_QUOTES,$charset));
				$this->t->set_var('sambaprofilepath',htmlspecialchars($userData['sambaprofilepath'],ENT_QUOTES,$charset));
				
				$this->t->set_var("uid",rawurlencode($_accountData["dn"]));
			}
			else
			{
			}
		
			// create the menu on the left, if needed		
			$menuClass =& CreateObject('admin.uimenuclass');
			$this->t->set_var('rows',$menuClass->createHTMLCode('edit_user'));

			$this->t->pfp("out","form");

		}
		
		function saveUserData()
		{
			$formData = array
			(
				'displayname'		=> get_var('displayname',array('POST')),
				'sambahomepath'		=> get_var('sambahomepath',array('POST')),
				'sambahomedrive'	=> get_var('sambahomedrive',array('POST')),
				'sambalogonscript'	=> get_var('sambalogonscript',array('POST')),
				'sambaprofilepath'	=> get_var('sambaprofilepath',array('POST'))
			);

			$this->bosambaadmin->saveUserData(get_var('account_id',array('GET')), $formData);

			// read data fresh from ldap storage
			$this->editUserData();
		}
		
		function translate()
		{
			$this->t->set_var('lang_displayname',lang('displayname'));
			$this->t->set_var('lang_homepath',lang('homepath'));
			$this->t->set_var('lang_homedrive',lang('homedrive'));
			$this->t->set_var('lang_logonscript',lang('logonscript'));
			$this->t->set_var('lang_profilepath',lang('profilepath'));
			$this->t->set_var('lang_samba_config',lang('samba config'));
		}
	}
?>
