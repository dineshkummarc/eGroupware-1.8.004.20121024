<?php
	/***************************************************************************\
	* eGroupWare - SambaAdmin                                                   *
	* http://www.egroupware.org                                                 *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; version 2 of the License.                       *
	\***************************************************************************/
	/* $Id: class.sosambaadmin.inc.php 33777 2011-02-14 08:06:50Z ralfbecker $ */

	class sosambaadmin
	{
		function sosambaadmin()
		{
			$config		=& CreateObject('phpgwapi.config','sambaadmin');
			$config->read_repository();

			$this->sid		= $config->config_data['sambasid'];
			$this->computerou	= $config->config_data['samba_computerou'];
			$this->computergroup	= $config->config_data['samba_computergroup'];
			$this->charSet	= $GLOBALS['egw']->translation->charset();

			unset($config);
		}

		function changePassword($_accountID, $_newPassword)
		{
			$smbHash = &CreateObject('phpgwapi.smbhash');
			$ldap = $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(uidnumber=$_accountID)(objectclass=sambasamaccount))";

			$sri = @ldap_search($ldap,$GLOBALS['egw_info']['server']['ldap_context'],$filter);
			if ($sri)
			{
				$allValues 	= ldap_get_entries($ldap, $sri);
				$accountDN 	= $allValues[0]['dn'];

				if($_newPassword)
				{
					$newData['sambaLMPassword'] = $smbHash->lmhash($_newPassword);
					$newData['sambaNTPassword'] = $smbHash->nthash($_newPassword);
					$newData['sambaPwdLastSet'] = $newData['sambaPwdCanChange'] = time();
					$newData['sambaPwdMustChange'] = '2147483647';

					if(@ldap_mod_replace ($ldap, $accountDN, $newData))
					{
						return true;
					}
					// try binded as $accountDN with $_newPassword, in case root DN has no rights to modify anything
					$ldap = $GLOBALS['egw']->common->ldapConnect('',$accountDN,$_newPassword);
					if(@ldap_mod_replace ($ldap, $accountDN, $newData))
					{
						return true;
					}
					#print ldap_error($ldap); exit;
				}
			}
			return false;
		}

		function checkLDAPSetup()
		{
			$sambaGroups = array
			(
				'Domain Admins'	=> array
				(
					'gidNumber'		=> 512,
					'description'		=> 'Netbios Domain Administrators',
					'sambaGroupType'	=> 2
				),
				'Domain Users'	=> array
				(
					'gidNumber'		=> 513,
					'description'		=> 'Netbios Domain Users',
					'sambaGroupType'	=> 2
				),
				'Domain Guests'	=> array
				(
					'gidNumber'		=> 514,
					'description'		=> 'Netbios Domain Guests Users',
					'sambaGroupType'	=> 2
				),
				'Domain Guests'	=> array
				(
					'gidNumber'		=> 514,
					'description'		=> 'Netbios Domain Guests Users',
					'sambaGroupType'	=> 2
				),
				'Administrators'	=> array
				(
					'gidNumber'		=> 544,
					'description'		=> 'Netbios Domain Members can fully administer the computer/sambaDomainName',
					'sambaGroupType'	=> 2
				),
				'Users'	=> array
				(
					'gidNumber'		=> 545,
					'description'		=> 'Netbios Domain Ordinary users',
					'sambaGroupType'	=> 2
				),
				'Guests'	=> array
				(
					'gidNumber'		=> 546,
					'description'		=> 'Netbios Domain Users granted guest access to the computer/sambaDomainName',
					'sambaGroupType'	=> 2
				),
				'Power Users'	=> array
				(
					'gidNumber'		=> 547,
					'description'		=> 'Netbios Domain Members can share directories and printers',
					'sambaGroupType'	=> 2
				),
				'Account Operators'	=> array
				(
					'gidNumber'		=> 548,
					'description'		=> 'Netbios Domain Users to manipulate users accounts',
					'sambaGroupType'	=> 2
				),
				'Server Operators'	=> array
				(
					'gidNumber'		=> 549,
					'description'		=> 'Netbios Domain Server Operators',
					'sambaGroupType'	=> 2
				),
				'Print Operators'	=> array
				(
					'gidNumber'		=> 550,
					'description'		=> 'Netbios Domain Print Operators',
					'sambaGroupType'	=> 2
				),
				'Backup Operators'	=> array
				(
					'gidNumber'		=> 551,
					'description'		=> 'Netbios Domain Members can bypass file security to back up files',
					'sambaGroupType'	=> 2
				),
				'Replicator'	=> array
				(
					'gidNumber'		=> 552,
					'description'		=> 'Netbios Domain Supports file replication in a sambaDomainName',
					'sambaGroupType'	=> 2
				),
				'Domain Computers'	=> array
				(
					'gidNumber'		=> 553,
					'description'		=> 'Netbios Domain Computers accounts',
					'sambaGroupType'	=> 2
				),
			);

			$ldap = $GLOBALS['egw']->common->ldapConnect();

			$dn = $GLOBALS['egw_info']['server']['ldap_group_context'];

			foreach($sambaGroups as $groupName => $groupData)
			{
				$filter = "(&(gidnumber=".$groupData['gidNumber'].")(objectclass=posixgroup))";

				$sri = @ldap_search($ldap,$dn,$filter);

				if(!$sri) return false;

				$allValues = ldap_get_entries($ldap, $sri);
				if($allValues['count'] == 0)
				{
					$newData = array();
					$newData['objectClass'][]	= 'posixGroup';
					$newData['objectClass'][]	= 'sambaGroupMapping';
					$newData['objectClass'][]	= 'phpgwAccount';

					$newData['gidNumber']		= $groupData['gidNumber'];
					$newData['cn']			= $groupName;
					$newData['description']		= $groupData['description'];
					$newData['sambaSID']		= $this->sid.'-'.$groupData['gidNumber'];
					$newData['sambaGroupType']	= $groupData['sambaGroupType'];
					$newData['displayName']		= $groupName;

					$newData['phpgwAccountExpires']	= -1;
					$newData['phpgwAccountType']	= 'g';

					$newDN = "cn=".$groupName.",".$dn;

					if(!@ldap_add($ldap,$newDN,$newData))
					{
						return false;
					}
				}
			}
		}

		function deleteWorkstation($_workstations)
		{
			if(is_array($_workstations))
			{
				$dn	= $this->computerou;
				$ldap	= $GLOBALS['egw']->common->ldapConnect();
				foreach($_workstations as $key => $value)
				{
					$filter = "(&(uidnumber=$key)(objectclass=sambasamaccount))";

					$sri = @ldap_search($ldap,$dn,$filter);
					if($sri)
					{
						$allValues = ldap_get_entries($ldap,$sri);
						$wsDN = $allValues[0]['dn'];

						ldap_delete($ldap, $wsDN);
					}
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		function expirePassword($_accountID)
		{
			$ldap = $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(uidnumber=$_accountID)(objectclass=sambasamaccount))";

			$sri = @ldap_search($ldap,$GLOBALS['phpgw_info']['server']['ldap_context'],$filter);
			if ($sri)
			{
				$allValues      = ldap_get_entries($ldap, $sri);
				$accountDN      = $allValues[0]['dn'];

				$newData['sambaPwdLastSet']     = time();
				$newData['sambaPwdCanChange']   = '1';
				$newData['sambaPwdMustChange']  = '1';

				if(@ldap_mod_replace ($ldap, $accountDN, $newData))
				{
					return true;
				}
				#print ldap_error($ldap);
			}
			return false;
		}

		function findNextUID()
		{
			$nextUID = 0;
			$tmpUID = (int)$GLOBALS['egw']->common->last_id('accounts');
			do
			{
				$ldap = $GLOBALS['egw']->common->ldapConnect();

				$dn = $this->computerou;
				$filter = "(&(uidnumber=$tmpUID)(objectclass=posixaccount))";
				$sri = 	ldap_search($ldap,$dn,$filter);
				{
					$allValues = ldap_get_entries($ldap, $sri);
					if ($allValues['count'] == 0)
					{
						// now search under the accounts dn too, maybe the same dn
						$dn = $GLOBALS['egw_info']['server']['ldap_context'];
						$filter = "(&(uidnumber=$tmpUID)(objectclass=posixaccount))";

						$sri = @ldap_search($ldap,$dn,$filter);
						if($sri)
						{
							$allValues = ldap_get_entries($ldap, $sri);
							if ($allValues['count'] == 0)
							{
								$nextUID = $tmpUID;
							}
						}
					}
				}

				if (!$sri)
				{
					// ldap error
					return false;
				}

				$tmpUID = (int)$GLOBALS['egw']->common->next_id('accounts');
			} while ($nextUID == 0);

			return $nextUID;
		}

		function getUserData($_accountID)
		{
			$dn = $GLOBALS['egw_info']['server']['ldap_context'];
			$ldap = $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(uidnumber=$_accountID)(objectclass=sambaSamAccount))";

			$sri = @ldap_search($ldap,$dn,$filter);
			if ($sri)
			{
				$allValues = ldap_get_entries($ldap, $sri);
				if ($allValues['count'] > 0)
				{
					#print "found something<br>";
					$userData = array();
					$userData["displayname"]	= $GLOBALS['egw']->translation->convert($allValues[0]["displayname"][0],'utf-8');
					$userData["sambahomedrive"]	= $GLOBALS['egw']->translation->convert($allValues[0]["sambahomedrive"][0],'utf-8');
					$userData["sambahomepath"]	= $GLOBALS['egw']->translation->convert($allValues[0]["sambahomepath"][0],'utf-8');
					$userData["sambalogonscript"]	= $GLOBALS['egw']->translation->convert($allValues[0]["sambalogonscript"][0],'utf-8');
					$userData["sambaprofilepath"]	= $GLOBALS['egw']->translation->convert($allValues[0]["sambaprofilepath"][0],'utf-8');
					$userData["uid"]		= $allValues[0]["uid"][0];

					return $userData;
				}
			}

			// if we did not return before, return false
			return false;
		}

		function getWorkstationData($_uidNumber)
		{
			if(empty($this->computerou))
				return false;

			$dn	= $this->computerou;
			$ldap	= $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(uidnumber=$_uidNumber)(objectclass=sambasamaccount))";

			$sri = @ldap_search($ldap,$dn,$filter);
			if($sri)
			{
				$allValues = ldap_get_entries($ldap,$sri);

				$workstationData['workstationName'] 	= $allValues[0]['uid'][0];
				$workstationData['workstationID'] 	= $allValues[0]['uidnumber'][0];
				$workstationData['description'] 	= $allValues[0]['description'][0];

				return $workstationData;
			}

			return false;
		}

		function getWorkstationList($_start, $_sort, $_order, $_searchString)
		{
			if(empty($this->computerou))
				return false;

			$dn	= $this->computerou;
			$ldap	= $GLOBALS['egw']->common->ldapConnect();
			if(!empty($_searchString))
				$filter = "(&(|(uid=*$_searchString*$)(description=*$_searchString*))(objectclass=sambasamaccount))";
			else
				$filter = "(&(uid=*$)(objectclass=sambasamaccount))";

			$sri = @ldap_search($ldap,$dn,$filter);
			if($sri)
			{
				// we can compare the searchresults using a php function
				if(version_compare(phpversion(), '4.2.0','>='))
				{
					switch($_order)
					{
						case'workstation_name':
							$order = 'uid';
							break;
						default:
							$order = $_order;
							break;
					}
					ldap_sort($ldap,$sri,$order);
				}
				$allValues = ldap_get_entries($ldap,$sri);
				unset($allValues['count']);
				if($_sort == 'DESC')
				{
					$allValues = array_reverse($allValues);
				}
				#_debug_array($allValues);

				$wsList['workstations'] = array_slice($allValues,$_start,(int)$GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs']);
				$wsList['total']	= count($allValues);

				return $wsList;
			}

			return false;
		}

		function name2sid($_name)
		{
			$ldap = $GLOBALS['egw']->common->ldapConnect();

			$filter = "(&(cn=$_name)(objectclass=sambasamaccount))";
			$sri = @ldap_search($ldap,$GLOBALS['egw_info']['server']['ldap_context'],$filter);

			if (!$sri) return false;

			$allValues = ldap_get_entries($ldap, $sri);
			if($allValues[0]['sambasid'][0]) return $allValues[0]['sambasid'][0];

			$filter = "(&(cn=$_name)(objectclass=sambagroupmapping))";
			$sri = @ldap_search($ldap,$GLOBALS['egw_info']['server']['ldap_group_context'],$filter);

			if (!$sri) return false;

			$allValues = ldap_get_entries($ldap, $sri);
			if($allValues[0]['sambasid'][0]) return $allValues[0]['sambasid'][0];

			return false;
		}

		function saveUserData($_accountID, $_accountData)
		{
			$ldap = $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(uidnumber=$_accountID)(objectclass=posixaccount))";

			$sri = @ldap_search($ldap,$GLOBALS['egw_info']['server']['ldap_context'],$filter);
			if ($sri)
			{
				$allValues 	= ldap_get_entries($ldap, $sri);
				$accountDN 	= $allValues[0]['dn'];
				$uid	   	= $allValues[0]['uid'][0];
				$uidnumber	= $allValues[0]['uidnumber'][0];
				$cn		= $allValues[0]['cn'][0];
				$homedirectory	= $allValues[0]['homedirectory'][0];
				$objectClass	= $allValues[0]['objectclass'];
				unset($objectClass['count']);

				if(!in_array('sambasamaccount',$objectClass) &&
					 !in_array('sambaSamAccount',$objectClass))
				{
					$objectClass[]	= "sambaSamAccount";
				}
				$objectClass = array_unique($objectClass);
				sort($objectClass,SORT_STRING);
			}
			else
			{
				return false;
			}

			$newData['objectClass']		= $objectClass;

			// set some usefull defaults
			$newData['sambaPwdLastSet']	=
				isset($allValues[0]['sambapwdlastset'][0])?$allValues[0]['sambapwdlastset'][0]:0;
			$newData['sambaLogonTime']	=
				isset($allValues[0]['sambapwdlogontime'][0])?$allValues[0]['sambapwdlogontime'][0]:2147483647;
			$newData['sambaLogoffTime']	=
				isset($allValues[0]['sambapwdlogofftime'][0])?$allValues[0]['sambapwdlogofftime'][0]:2147483647;
			$newData['sambaKickoffTime']	=
				isset($allValues[0]['sambapwdkickofftime'][0])?$allValues[0]['sambapwdkickofftime'][0]:2147483647;
			$newData['sambaPwdCanChange']	=
				isset($allValues[0]['sambapwdcanchange'][0])?$allValues[0]['sambapwdcanchange'][0]:0;
			$newData['sambaPwdMustChange']	=
				isset($allValues[0]['sambapwdmustchange'][0])?$allValues[0]['sambapwdmustchange'][0]:2147483647;
			$newData['sambaSID']	=
				isset($allValues[0]['sambasid'][0])?$allValues[0]['sambasid'][0]:$this->sid.'-'.(2 * $uidnumber + 1000);

			$newData['sambaAcctFlags']	= '[U'.($_accountData['status'] == 'deactivated' ? 'D' : ' ').'         ]';

			$newData['displayname']	= $cn;

			$newData = array_change_key_case($newData);

			#_debug_array($_accountData);
			$formFields = array('sambahomepath','sambahomedrive','sambalogonscript','sambaprofilepath','sambapwdmustchange','sambapwdcanchange');
			foreach($formFields as $fieldName)
			{
				if(isset($_accountData[$fieldName]))
				{
					if(!empty($_accountData[$fieldName]))
					{
						$newData[$fieldName] = $GLOBALS['egw']->translation->convert
						(
							$_accountData[$fieldName],
							$this->charSet,
							'utf-8'
						);
					}
					else
					{
						$newData[$fieldName] = array();
					}
				}
			}

			if(@ldap_mod_replace ($ldap, $accountDN, $newData))
			{
				if(isset($_accountData['password']))
				{
					return $this->changePassword($_accountID,$_accountData['password']);
				}
				return true;
			}

			#print ldap_error($ldap);

			return false;
			// done! :-)
		}

		function updateGroup($_groupID)
		{
			if(!$groupID = abs((int)$_groupID)) return false;

			$ldap = $GLOBALS['egw']->common->ldapConnect();
			$filter = "(&(gidnumber=$groupID)(objectclass=posixgroup))";

			$sri = @ldap_search($ldap,$GLOBALS['egw_info']['server']['ldap_group_context'],$filter);
			if ($sri)
			{
				$allValues 	= ldap_get_entries($ldap, $sri);
				$groupDN 	= $allValues[0]['dn'];
				$cn		= $allValues[0]['cn'][0];
				$objectClass	= $allValues[0]['objectclass'];
				unset($objectClass['count']);

				if(!$allValues[0]['sambasid'][0])
				{
					$objectClass[]	= 'sambaGroupMapping';
					$objectClass = array_unique($objectClass);
					sort($objectClass,SORT_STRING);

					$newData['objectclass']		= $objectClass;
					$newData['sambasid']		= $this->sid.'-'.($groupID*2 + 1001);
					$newData['sambagrouptype']	= 2;
					$newData['displayname']		= $cn;

					if(@ldap_mod_replace ($ldap, $groupDN, $newData))
					{
						return true;
					}
					#print ldap_error($ldap);exit;
				}
			}

			return false;
		}

		function updateWorkstation($_newData)
		{
			// add a new workstation
			if($_newData[workstationID] == 'new')
			{
				if(!$newData['uidNumber'] = $this->findNextUID())
					return false;

				if(!$groupID = $GLOBALS['egw']->accounts->name2id($this->computergroup))
					return false;

				if(!$groupSID = $this->name2sid($this->computergroup))
					return false;

				#$_newData['workstationName'] = trim($_newData['workstationName']);
				#$_newData['description'] = trim($_newData['description']);

				if(empty($_newData['description']))
				{
					$_newData['description']	= lang('workstation account for').' '.$_newData['workstationName'];
				}

				if(substr($_newData['workstationName'],strlen($_newData['workstationName'])-1,1) != '$')
				{
					$_newData['workstationName'] .= "$";
				}

				$newData['objectClass'][0]	= 'top';
				$newData['objectClass'][1]	= 'posixaccount';
				$newData['objectClass'][2]	= 'sambasamaccount';
				$newData['objectClass'][3]	= 'person';
				$newData['uid']			= $GLOBALS['egw']->translation->convert($_newData['workstationName'],'utf-8');
				$newData['description']		= $GLOBALS['egw']->translation->convert($_newData['description'],'utf-8');
				$newData['displayName']		= $GLOBALS['egw']->translation->convert($_newData['workstationName'],'utf-8');
				$newData['cn']			= $GLOBALS['egw']->translation->convert($_newData['workstationName'],'utf-8');
				$newData['sn']			= $newData['cn'];
				$newData['homeDirectory']	= '/dev/null';
				$newData['loginShell']		= '/bin/false';
				#$newData['sambaacctflags']	= '[DW         ]';
				$newData['sambaacctflags']	= '[W          ]';
				$newData['gidNumber']		= $groupID;
				$newData['sambasid']		= $this->sid.'-'.($newData['uidNumber']*2 + 1000);
				$newData['sambaprimarygroupsid']= $groupSID;

				$ldap = $GLOBALS['egw']->common->ldapConnect();
				$dn = "uid=".$_newData['workstationName'].",".$this->computerou;

				if(ldap_add($ldap,$dn,$newData))
				{
					return $newData['uidNumber'];
				}
				else
				{
					return false;
				}
			}
			// update a existing workstation
			elseif(is_numeric($_newData[workstationID]) && $_newData[workstationID] > 0)
			{
				$newData['description']		= $_newData['description'];
				#$newData['sambaacctflags']	= '[DW         ]';
				#$newData['sambaacctflags']	= '[W          ]';

				$dn	= $this->computerou;
				$ldap	= $GLOBALS['egw']->common->ldapConnect();
				$filter = "(&(uidnumber=".$_newData[workstationID].")(objectclass=sambasamaccount))";

				$sri = @ldap_search($ldap,$dn,$filter);
				if($sri)
				{
					$allValues = ldap_get_entries($ldap, $sri);

					$dn = $allValues[0]['dn'];

					ldap_mod_replace ($ldap, $dn, $newData);
					#print "<br><br><br><br><br><br><br><br><br><br>LDAP ERROR:".ldap_error($ldap);
				}
				return $_newData[workstationID];
			}
			// something went wrong
			else
			return false;
		}
	}
?>
