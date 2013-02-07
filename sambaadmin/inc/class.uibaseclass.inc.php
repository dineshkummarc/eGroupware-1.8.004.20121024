<?php
	/***************************************************************************\
	* phpGroupWare - FeLaMiMail                                                 *
	* http://www.linux-at-work.de                                               *
	* http://www.phpgw.de                                                       *
	* http://www.phpgroupware.org                                               *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/
	/* $Id: class.uibaseclass.inc.php 20093 2005-12-02 16:35:48Z lkneschke $ */

	class uibaseclass
	{
		var $public_functions = array(
			'accounts_popup'	=>	True,
			'create_html'		=>	True
		);
		
		function accounts_popup($_appName)
		{
			$GLOBALS['egw']->accounts->accounts_popup($_appName);
		}
		
		function create_html()
		{
			if(!isset($GLOBALS['egw_info']['server']['deny_user_grants_access']) || !$GLOBALS['egw_info']['server']['deny_user_grants_access'])
			{
				$accounts = $GLOBALS['egw']->acl->get_ids_for_location('run',1,'calendar');
				$users = Array();
#				$this->build_part_list($users,$accounts,$event['owner']);

				$str = '';
				@asort($users);
				@reset($users);

				switch($GLOBALS['egw_info']['user']['preferences']['common']['account_selection'])
				{
					case 'popup':
						while (is_array($event['participants']) && list($id) = each($event['participants']))
						{
							if($id != intval($event['owner']))
							{
								$str .= '<option value="' . $id.$event['participants'][$id] . '"'.($event['participants'][$id] ? ' selected' : '').'>('.$GLOBALS['egw']->accounts->get_type($id)
										.') ' . $GLOBALS['egw']->common->grab_owner_name($id) . '</option>' . "\n"; 
							}
						}
						$var[] = array
						(
							'field'	=> '<input type="button" value="' . lang('Participants') . '" onClick="accounts_popup();">' . "\n"
									. '<input type="hidden" name="accountid" value="' . $accountid . '">',
							'data'	=> "\n".'   <select name="participants[]" multiple size="7">' . "\n" . $str . '</select>'
						);
						break;
					default:
						foreach($users as $id => $user_array)
						{
							if($id != intval($event['owner']))
							{
								$str .= '    <option value="' . $id.$event['participants'][$id] . '"'.($event['participants'][$id] ? ' selected' : '').'>('.$user_array['type'].') '.$user_array['name'].'</option>'."\n";
							}
						}
						$var[] = array
						(
							'field'	=> lang('Participants'),
							'data'	=> "\n".'   <select name="participants[]" multiple size="7">'."\n".$str.'   </select>'
						);
						break;
				}
			}
			
		}

		function create_table($_start, $_total, $_defaultSort, $_defaultOrder, $_header, $_rows, $_menuaction, $_name, $_tablePrefix)
		{
			$t 		=& CreateObject('phpgwapi.Template',EGW_APP_TPL);
			$nextmatchs	=& CreateObject('phpgwapi.nextmatchs');
			
			$rowCSS = array
			(
				'row_on','row_off'
			);
			
			$t->set_file(array("body" => 'nextmatchtable.tpl'));
			$t->set_block('body','main');
			
			$url = $GLOBALS['egw']->link('/index.php','menuaction='.$_menuaction);

			$order	= get_var('order',array('POST','GET')) ? get_var('order',array('POST','GET')) : $_defaultOrder;
			$sort	= get_var('sort',array('POST','GET')) ? get_var('sort',array('POST','GET')) : $_defaultSort;
			$start	= get_var('start',array('POST','GET')) ? get_var('start',array('POST','GET')) : $_start;
			
			#print "o: $order st: $start so: $sort<br>";
			
			$t->set_var('left_next_matchs', $nextmatchs->left($url,$start,$_total,$_menuaction));
			$t->set_var('name', lang('%1 - %2 of %3',$start+1,$start+count($_rows),$_total).'&nbsp;'.$_name);
			$t->set_var('right_next_matchs', $nextmatchs->right($url,$start,$_total,$_menuaction));
			$t->set_var('table_prefix', $_tablePrefix);
																		
			// create the header
			if(is_array($_header))
			{
				// hack to reset start to 0, when switching sorting
				$GLOBALS['start'] = 0;
				foreach($_header as $key => $value)
				{
					if(!empty($value))
						$string = $nextmatchs->show_sort_order($sort,$value,$order,$url,$key);
					else
						$string = $key;
						
					$header .= '<td class="th" align="center">'.$string.'</td>';
				}
				$t->set_var('header','<tr>'.$header.'</tr>');
				unset($GLOBALS['start']);
			}

			// create the rows
			if(is_array($_rows))
			{
				$i=0;
				foreach($_rows as $key => $value)
				{
					$rowData .= "<tr>\n";
					foreach($value as $cellData)
					{
						$rowData .= '<td class="'.$rowCSS[$i%2].'" align="center">'.$cellData.'</td>';
					}
					$rowData .= "</tr>\n";
					$i++;
				}
				$t->set_var('rows',$rowData);
			}
			
			return $t->fp("out","main");
		}
	}
?>