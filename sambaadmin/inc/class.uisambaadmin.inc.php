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
	/* $Id: class.uisambaadmin.inc.php 20093 2005-12-02 16:35:48Z lkneschke $ */

	class uisambaadmin
	{
		#var $grants;
		#var $cat_id;
		#var $start;
		#var $search;
		#var $filter;

		var $public_functions = array
		(
			'checkLDAPSetup'	=> True,
			'listWorkstations'	=> True,
			'deleteWorkstation'	=> True,
			'editWorkstation'	=> True,
			'setSearchFilter'	=> True,
		);

		function uisambaadmin()
		{
			$this->restoreSessionData();
			
			$this->t			=& CreateObject('phpgwapi.Template',EGW_APP_TPL);
			$this->bosambaadmin		=& CreateObject('sambaadmin.bosambaadmin');
			
			$this->rowColor[0] = $GLOBALS['egw_info']["theme"]["row_on"];
			$this->rowColor[1] = $GLOBALS['egw_info']["theme"]["row_off"];

			$this->dataRowColor[0] = $GLOBALS['egw_info']["theme"]["bg01"];
			$this->dataRowColor[1] = $GLOBALS['egw_info']["theme"]["bg02"];
											 
		}
		
		function checkLDAPSetup()
		{
			$this->bosambaadmin->checkLDAPSetup();
		}
		
		function deleteWorkstation()
		{
			if($workstations = get_var('deleteWorkstation','POST'))
			{
				$this->bosambaadmin->deleteWorkstation($workstations);
			}
			$this->listWorkstations();
		}
		
		function displayAppHeader()
		{
			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
		}

		function editWorkstation($_workstationID='')
		{
			$workstationData = array();
			
			if(get_var('workstationID',array('GET','POST')))
			{
				$workstationID = get_var('workstationID',array('GET','POST'));
			}
			else
			{
				$workstationID = $_workstationID;
			}
			
			if(get_var('save','POST'))
			{
				$workstationData['workstationName']	= get_var('workstationname','POST');
				$workstationData['workstationID']	= get_var('workstationID','POST');
				$workstationData['description']		= get_var('description','POST');
				
				if($newUID = $this->bosambaadmin->updateWorkstation($workstationData))
				{
					$workstationID = $newUID;
					$this->listWorkstations();
					return;
				}
			}
			
			$this->displayAppHeader();

			$this->t->set_file(array("body" => 'editworkstation.tpl'));
			$this->t->set_block('body','main');
			
			$this->translate();
			
			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uisambaadmin.editWorkstation'
			);
			$this->t->set_var('form_action',$GLOBALS['egw']->link('/index.php',$linkData));

			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uisambaadmin.listWorkstations'
			);
			$this->t->set_var('back_link',$GLOBALS['egw']->link('/index.php',$linkData));

			if(is_numeric($workstationID) && $workstationID > 0)
			{
				$workstationData = $this->bosambaadmin->getWorkstationData($workstationID);
				$this->t->set_var('workstationid',$workstationData['workstationID']);
			}
			else
			{
				$this->t->set_var('workstationid','new');
			}

			$this->t->set_var('workstationname',$workstationData['workstationName']);
			$this->t->set_var('description',$workstationData['description']);

			print $this->t->fp("out","main");
			#print $this->t->get('out','main');
		}
		
		function listWorkstations()
		{
			$sort	= get_var('sort',array('POST','GET')) ? get_var('sort',array('POST','GET')) : 'ASC';
			$order	= get_var('order',array('POST','GET')) ? get_var('order',array('POST','GET')) : 'workstation_name';
			$start  = get_var('start',array('POST','GET')) ? get_var('start',array('POST','GET')) : 0;
			
			$nextMatch =& CreateObject('sambaadmin.uibaseclass');
			$workstationList = $this->bosambaadmin->getWorkstationList($start, $sort, $order, $this->sessionData['searchString']);
			$this->displayAppHeader();

			$this->t->set_file(array("body" => 'listworkstations.tpl'));
			$this->t->set_block('body','main');
			#$this->t->set_block('body','status_row_tpl');
			#$this->t->set_block('body','header_row');
			
			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uisambaadmin.editWorkstation'
			);
			$this->t->set_var('add_link',$GLOBALS['egw']->link('/index.php',$linkData));

			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uisambaadmin.deleteWorkstation'
			);
			$formAction = $GLOBALS['egw']->link('/index.php',$linkData);

			$linkData = array
			(
				'menuaction'	=> 'sambaadmin.uisambaadmin.setSearchFilter'
			);
			$this->t->set_var('search_form_action',$GLOBALS['egw']->link('/index.php',$linkData));
			
			$this->t->set_var('search_string',$this->sessionData['searchString']);
			
			$tableHeader = array
			(
				lang('workstation name') => 'workstation_name',
				lang('description')	=> 'description',
				lang('delete')		=> ''
				
			);
			
			if(is_array($workstationList['workstations']))
			{
				$wsCount = count($workstationList['workstations']);
				for($i = 0; $i < $wsCount; $i++)
				{
					$linkData = array
					(
						'menuaction'	=> 'sambaadmin.uisambaadmin.editWorkstation',
						'workstationID'	=> $workstationList['workstations'][$i]['uidnumber'][0]
					);
					$editLink = $GLOBALS['egw']->link('/index.php',$linkData);
					
					$rows[] = array
					(
						'workstationname'	=> '<a href="'.$editLink.'">'.$workstationList['workstations'][$i]['uid'][0].'</a>',
						'description'		=> '<a href="'.$editLink.'">'.$workstationList['workstations'][$i]['description'][0].'</a>',
						'select'		=> '<input type="checkbox" name="deleteWorkstation['.$workstationList['workstations'][$i]['uidnumber'][0].']">'
					);
				}
			}
			
			$tablePrefix = "<form method='POST' action='$formAction'>";
			
			$this->t->set_var
			(
				'next_match_table',$nextMatch->create_table
				(
					$start, 
					$workstationList['total'], 
					$sort,
					$order,
					$tableHeader, 
					$rows,
					'sambaadmin.uibaseclass.listWorkstations',
					lang('workstations'),
					$tablePrefix
				)
			);

			$this->translate();

			$this->t->parse("out","main");
			print $this->t->get('out','main');
		}
		
		function restoreSessionData()
		{
			$this->sessionData = $GLOBALS['egw']->session->appsession('session_data');
		}
		
		function saveSessionData()
		{
			$GLOBALS['egw']->session->appsession('session_data','',$this->sessionData);
		}
		
		function setSearchFilter()
		{
			$this->sessionData['searchString'] = $_POST['search_string'];
			
			$this->saveSessionData();
			
			$this->listWorkstations();
		}


		function translate()
		{
			$this->t->set_var('th_bg',$GLOBALS['egw_info']["theme"]["th_bg"]);
			$this->t->set_var('bg_01',$GLOBALS['egw_info']["theme"]["bg01"]);
			$this->t->set_var('bg_02',$GLOBALS['egw_info']["theme"]["bg02"]);

			$this->t->set_var('lang_workstation_list',lang('workstation list'));
			$this->t->set_var('lang_add_workstation',lang('add workstation'));
			$this->t->set_var('lang_workstation_name',lang('workstation name'));
			$this->t->set_var('lang_description',lang('description'));
			$this->t->set_var('lang_select',lang('select'));
			$this->t->set_var('lang_workstation_config',lang('workstation configuration'));
			$this->t->set_var('lang_account_active',lang('workstationaccount active'));
			$this->t->set_var('lang_save',lang('save'));
			$this->t->set_var('lang_back',lang('back'));
			$this->t->set_var('lang_delete',lang('delete'));
			$this->t->set_var('lang_do_you_really_want_to_delete',lang('Do you really want to delete selected workstation accounts?'));
			$this->t->set_var('lang_search',lang('search'));
		}
	}
?>
