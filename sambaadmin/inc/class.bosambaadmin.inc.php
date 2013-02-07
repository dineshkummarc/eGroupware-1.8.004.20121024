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
	/* $Id: class.bosambaadmin.inc.php 19994 2005-11-25 12:36:45Z lkneschke $ */

	class bosambaadmin
	{
		var $sessionData;
		var $LDAPData;

		function bosambaadmin()
		{
			#

			$this->sosambaadmin =& CreateObject('sambaadmin.sosambaadmin');
			
			$this->restoreSessionData();

		}

		function checkLDAPSetup()
		{
			return $this->sosambaadmin->checkLDAPSetup();
		}
		
		function changePassword($_accountID, $_newPassword)
		{
			if (!$GLOBALS['egw_info']['server']['ldap_host']) return false;

			return $this->sosambaadmin->changePassword($_accountID, $_newPassword);
		}
		
		function deleteWorkstation($_workstations)
		{
			return $this->sosambaadmin->deleteWorkstation($_workstations);
		}
		
		function expirePassword($_accountID)
		{
			return $this->sosambaadmin->expirePassword($_accountID);
		}
		
		function getUserData($_accountID, $_usecache)
		{
			if ($_usecache)
			{
				$userData = $this->userSessionData[$_accountID];
			}
			else
			{
				$userData = $this->sosambaadmin->getUserData($_accountID);
				$this->userSessionData[$_accountID] = $userData;
				$this->saveSessionData();
			}
			return $userData;
		}

		function getWorkstationData($_uidnumber)
		{
			return $this->sosambaadmin->getWorkstationData($_uidnumber);
		}
		
		function getWorkstationList($_start, $_sort, $_order, $_searchString)
		{
			return $this->sosambaadmin->getWorkstationList($_start, $_sort, $_order, $_searchString);
		}

		function restoreSessionData()
		{
			$this->sessionData = $GLOBALS['egw']->session->appsession('session_data');
			$this->userSessionData = $GLOBALS['egw']->session->appsession('user_session_data');
		}
		
		function saveSessionData()
		{
			$GLOBALS['egw']->session->appsession('session_data','',$this->sessionData);
			$GLOBALS['egw']->session->appsession('user_session_data','',$this->userSessionData);
		}
		
		function saveUserData($_accountID, $_formData)
		{
			return $this->sosambaadmin->saveUserData($_accountID, $_formData);
		}

		function updateAccount()
		{
			if (!$GLOBALS['egw_info']['server']['ldap_host']) return false;

			if($accountID = (int)$GLOBALS['hook_values']['account_id'])
			{
				$config =& CreateObject('phpgwapi.config','sambaadmin');
				$config->read_repository();
				$config = $config->config_data;

				$oldAccountData = $this->getUserData($accountID,false);

				// account_status
				$accountData = array();
				if($GLOBALS['hook_values']['new_passwd'])
				{
					$accountData['password']	= $GLOBALS['hook_values']['new_passwd'];
				}
				if(!$oldAccountData['sambahomedrive'] && $config['samba_homedrive'])
					$accountData['sambahomedrive']		= $config['samba_homedrive'];
				if(!$oldAccountData['sambahomepath'] && $config['samba_homepath'])
					$accountData['sambahomepath']		= $config['samba_homepath'].$GLOBALS['hook_values']['account_lid'];
				if(!$oldAccountData['sambalogonscript'] && $config['samba_logonscript'])
					$accountData['sambalogonscript']	= $config['samba_logonscript'];
				if(!$oldAccountData['sambaprofilepath'] && $config['samba_profilepath'])
					$accountData['sambaprofilepath']	= $config['samba_profilepath'].$GLOBALS['hook_values']['account_lid'];
				$accountData['status']				= ($GLOBALS['hook_values']['account_status'] == 'A' ? 'activated' : 'deactivated');

				return $this->sosambaadmin->saveUserData($accountID, $accountData);
			}

			// something went wrong
			return false;
		}

		function updateGroup()
		{
			if (!$GLOBALS['egw_info']['server']['ldap_host']) return false;

			if($accountID = (int)$GLOBALS['hook_values']['account_id'])
			{
				return $this->sosambaadmin->updateGroup($accountID);
			}
			return false;
		}
				
		function updateWorkstation($_newData)
		{
			if(!$this->verifyData($_newData))
				return false;
				
			return $this->sosambaadmin->updateWorkstation($_newData);
		}
		
		function verifyData($_newData)
		{
			return true;
		}
	}
?>
