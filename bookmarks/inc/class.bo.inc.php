<?php
	/**************************************************************************\
	* eGroupWare - Bookmarks                                                   *
	* http://www.egroupware.org                                                *
	* Based on Bookmarker Copyright (C) 1998  Padraic Renaghan                 *
	*                     http://www.renaghan.com/bookmarker                   *
	* Ported to phpgroupware by Joseph Engo                                    *
	* Ported to three-layered design by Michael Totschnig                      *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.bo.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */

	class bo
	{
		var $so;
		var $grants;
		var $url_format_check;
		var $validate;
		var $categories;
		//following two are used by the export function
		var $type;
		var $expanded;
		var $error_msg;
		var $msg;

		function bo()
		{
			$this->so =& CreateObject('bookmarks.so');
			$this->grants      = $GLOBALS['egw']->acl->get_grants('bookmarks');
			$this->categories = $GLOBALS['egw']->categories;
			$this->config = config::read('bookmarks');
			$this->url_format_check = True;
			$this->validate =& CreateObject('phpgwapi.validator');

			$this->translation = $GLOBALS['egw']->translation;
			$this->charset = $this->translation->charset();
		}

		function grab_form_values($returnto,$returnto2,$bookmark)
		{
			$location_info = array(
				'returnto'  => $returnto,
				'returnto2' => $returnto2,
				'bookmark'  => array(
				'url'       => $bookmark['url'],
				'name'      => $bookmark['name'],
				'desc'      => $bookmark['desc'],
				'keywords'  => $bookmark['keywords'],
				'category'  => $bookmark['category'],
				'rating'    => $bookmark['rating'],
				'access' => $bookmark['access']
				)
			);
			$this->save_session_data($location_info);
		}

		function date_information(&$tpl, $raw_string)
		{
			$ts = explode(',',$raw_string);

			$tpl->set_var('added_value',$GLOBALS['egw']->common->show_date($ts[0]));
			$tpl->set_var('visited_value',($ts[1]?$GLOBALS['egw']->common->show_date($ts[1]):lang('Never')));
			$tpl->set_var('updated_value',($ts[2]?$GLOBALS['egw']->common->show_date($ts[2]):lang('Never')));
		}

		function _list($cat_id,$start=False,$where_clause=False,$subcatsalso=True)
		{
			$cat_list = $cat_id ?
				($subcatsalso ? $this->getcatnested($cat_id,True,True) : array($cat_id)):
				False;
			return $this->so->_list($cat_list,$this->get_user_grant_list(),$start,$where_clause);
		}

		function read($id,$do_htmlspecialchars=True)
		{
			$bookmark = $this->so->read($id,$do_htmlspecialchars);
			foreach(array(EGW_ACL_READ,EGW_ACL_EDIT,EGW_ACL_DELETE) as $required)
			{
				$bookmark[$required] = $this->check_perms2($bookmark['owner'],$bookmark['access'],$required);
			}
			return $bookmark;
		}

		function get_user_grant_list()
		{
			if (is_array($this->grants))
			{
				reset($this->grants);
				while (list($user) = each($this->grants))
				{
					$public_user_list[] = $user;
				}
				return $public_user_list;
			}
			else
			{
				return False;
			}
		}

		function check_perms2($owner,$access,$required)
		{
			return ($owner == $GLOBALS['egw_info']['user']['account_id']) ||
				($access == 'public' && ($this->grants[$owner] & $required));
		}

		function check_perms($id, $required)
		{
			if (!($bookmark = $this->so->read($id)))
			{
				return False;
			}
			else
			{
				return $this->check_perms2($bookmark['owner'],$bookmark['access'],$required);
			}
		}

		function categories_list($selected_id,$multiple=False)
		{
			$option_list = $this->getcatnested(0);
			$s = '';
			foreach($option_list as $option)
			{
				$s .= '<option value="' . $option['value'] . '"' .
				(($option['value']==$selected_id) ? ' selected' : '') .
				'>' . $option['display'] . '</option>' . "\n";
			}
			return '<select name="bookmark[category]' .
				($multiple ? '[]" multiple="multiple" ' : '" ') .
				'size="5">' . $s . '</select>';
		}

		function getcatnested($cat_id,$idsonly=False,$parentalso=False)
		{
			$retval = $parentalso ? array($cat_id) : array();
			$root_list = $this->categories->return_array($cat_id ? 'all' : 'mains',0,False,'','cat_name','',True,$cat_id,-1,$idsonly ? 'id' : '');

			if (is_array($root_list))
			{
				foreach($root_list as $cat)
				{
					$padding = str_pad('',12*$cat['level'],'&nbsp;');
					$retval[] = $idsonly ? $cat['id'] : array('value'=>$cat['id'], 'display'=>$padding.$cat['name']);
					$sublist = $this->getcatnested($cat['id'],$idsonly);
					if (is_array($sublist) && count($sublist)>0)
					{
						$retval = array_merge($retval,$sublist);
					}
				}
			}
			return $retval;
		}

		function add($values)
		{
			if ($this->validate($values))
			{
				if ($this->so->exists($values['url']))
				{
					$this->error_msg .= sprintf('<br>URL <B>%s</B> already exists!', $values['url']);
					return False;
				}
				$bm_id = $this->so->add($values);
				if ($bm_id)
				{
					$this->msg .= lang('Bookmark created successfully.');
					return $bm_id;
				}
			}
			else
			{
				return false;
			}
		}

		function update($id, $values)
		{
			#echo "bo::update<pre>".htmlspecialchars(print_r($values,True))."</pre>\n";
			if ($this->validate($values) && $this->check_perms($id,EGW_ACL_EDIT))
			{
				if ($this->so->update($id,$values))
				{
					$this->msg .= lang('Bookmark changed sucessfully');
					return True;
				}
			}
			else
			{
				return false;
			}
		}

		function updatetimestamp($id,$timestamp)
		{
			$this->so->updatetimestamp($id,$timestamp);
		}

		function delete($id)
		{
			if ($this->check_perms($id,EGW_ACL_DELETE))
			{
				if ($this->so->delete($id))
				{
					$this->msg .= lang('bookmark deleted successfully.');
					return True;
				}
			}
			else
			{
				return false;
			}
		}

		function validate($values)
		{
			$result = True;
			if (! $values['name'])
			{
				$this->error_msg .= '<br>' . lang('Name is required');
				$result = False;
			}

			if (! $values['category'])
			{
				$this->error_msg .= '<br>' . lang('You must select a category');
				$result = False;
			}

			if (! $values['url'] || $values['url'] == 'http://')
			{
				$this->error_msg .= '<br>' . lang('URL is required.');
				$result = False;
			}
			// does the admin want us to check URL format
			elseif ($this->url_format_check)
			{
				if (! $this->validate->is_url($values['url']))
				{
					$this->error_msg = '<br>URL invalid. Format must be <strong>http://</strong> or
						<strong>ftp://</strong> followed by a valid hostname and
						URL!<br><small>' .  $this->validate->ERROR . '</small>';
					$result = False;
				}
			}
			return $result;
		}

		function save_session_data($data)
		{
			$GLOBALS['egw']->session->appsession('session_data','bookmarks',$data);
		}

		function read_session_data()
		{
			return $GLOBALS['egw']->session->appsession('session_data','bookmarks');
		}

		function get_category($catname,$parent)
		{
			$this->_debug('<br>Testing for category: ' . $catname . ' with parent: \'' . $parent . '\'');

			$catid = $this->categories->exists($parent?'subs':'mains',$catname,0,$parent);
			if ($catid)
			{
				$this->_debug(' - ' . $catname . ' already exists - id: ' . $catid);
			}
			else
			{
				$catid = $this->categories->add(array(
					'name'   => $catname,
					'descr'  => '',
					'parent' => $parent,
					'access' => '',
					'data'   => ''
				));
				$this->_debug(' - ' . $Catname . ' does not exist - new id: ' . $catid);
			}
			return $catid;
		}

		function import($bkfile,$parent)
		{
			$this->_debug('<p><b>DEBUG OUTPUT:</b>');
			$this->_debug('<br>file_name: ' . $bkfile['name']);
			$this->_debug('<br>file_size: ' . $bkfile['size']);
			$this->_debug('<br>file_type: ' . $bkfile['type'] . '<p><b>URLs:</b>');
			$this->_debug('<table border="1" width="100%">');
			$this->_debug('<tr><td>cat id</td> <td>sub id</td> <td>name</td> <td>url</td> <td>add date</td> <td>change date</td> <td>vist date</td></tr>');

			if (!$bkfile['name'] || $bkfile['name'] == 'none' || @$bkfile['error'])
			{
				$this->error_msg .= '<br>'.lang('Netscape bookmark filename is required!');
			}
			elseif (!$parent)
			{
				$this->error_msg .= '<br>'.lang('You need to select a category!');
			}
			else
			{
				$fd = @fopen($bkfile['tmp_name'],'r');
				if ($fd)
				{
					$default_rating = 0;
					$inserts = 0;
					$folderstack = array($parent);

					$utf8flag = False;

					//In the bookmark import file, description for site is allowd to be empty
					//The description of folders in the bookmark will be skiped.
					$have_desc = True;
					$dir_desc = False;

					while ($line = @fgets($fd, 2048))
					{
						$from_charset = False;
						if (preg_match('/<META HTTP-EQUIV="Content-Type" CONTENT="text\\/html; charset=([^"]*)/',$line,$matches))
						{
							 $from_charset = $matches[1];
						}
						// URLs are recognized by A HREF tags in the NS file.
						elseif (preg_match('/<A HREF="([^"]*)[^>]*>(.*)<\\/A>/i', $line, $match))
						{
							if(!$have_desc)
							{
								unset($values['desc']);
								if ($this->add($values))
								{
									$inserts++;
								}
							}
							$have_desc = False;
							$dir_desc = False;

							$url_parts = @parse_url($match[1]);
							if
							(
								$url_parts[scheme] == 'http' || $url_parts[scheme] == 'https' ||
								$url_parts[scheme] == 'ftp' || $url_parts[scheme] == 'news'
							)
							{
								$values['category'] = end($folderstack);
								$values['url']      = $match[1];

								$values['name']     = str_replace(array('&amp;','&lt;','&gt;','&quote;','&#039;'),
									array('&','<','>','"',"'"),$this->translation->convert($match[2],$from_charset));
								$values['rating']   = $default_rating;

								eregi('ADD_DATE="([^"]*)"',$line,$add_info);
								eregi('LAST_VISIT="([^"]*)"',$line,$vist_info);
								eregi('LAST_MODIFIED="([^"]*)"',$line,$change_info);

								$values['timestamps'] = sprintf('%s,%s,%s',$add_info[1],$vist_info[1],$change_info[1]);

								unset($keywords);
								eregi('SHORTCUTURL="([^"]*)"',$line,$keywords);
								$values['keywords']=$keywords[1];

								$this->_debug(sprintf("<tr><td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> <td>%s</td> </tr>",$cid,$scid,$match[2],$match[1],$add_info[1],$change_info[1],$vist_info[1]));
							}
						}
						// folders start with the folder name inside an <H3> tag,
						// and end with the close </DL> tag.
						// we use a stack to keep track of where we are in the
						// folder hierarchy.
						elseif (preg_match('/<H3[^>]*>(.*)<\\/H3>/i', $line, $match))
						{
							$folder_name = $this->translation->convert($match[1],$from_charset);
							$current_cat_id = $this->get_category($folder_name,end($folderstack));
							$dir_desc = True;
							array_push($folderstack,$current_cat_id);
						}
						// description start with tag <DD> and the description for folder
						// will be skiped
						elseif (preg_match('/<DD>(.*)/i',$line,$desc))
						{
							if($dir_desc)
							{
								continue;
							}
							else
							{
								$values['desc']     = str_replace(array('&amp;','&lt;','&gt;','&quot;','&#039;'),array('&','<','>','"',"'"),$this->translation->convert($desc[1],$from_charset));
								if ($this->add($values))
								{
									$inserts++;
								}
								$have_desc = True;
							}
						}
						elseif (preg_match('/<\\/DL>/i', $line))
						{
							array_pop($folderstack);
						}
					}

					if(!$have_desc)
					{
						unset($values['desc']);
						if ($this->add($values))
						{
							$inserts++;
						}
					}

					@fclose($fd);
					$this->_debug('</table>');
					$this->msg = '<br>'.lang("%1 bookmarks imported from %2 successfully.", $inserts, $bkfile['name']);
				}
				else
				{
					$this->error_msg .= '<br>'.lang('Unable to open temp file %1 for import.',$bkfile['name']);
				}
			}
		}

		function export($catlist,$type,$expanded=array())
		{
			$this->type = $type;
			$this->expanded = $expanded;

			$t =& CreateObject('phpgwapi.Template',EGW_INCLUDE_ROOT . '/bookmarks/templates/export');
			$t->set_file('export','export_' . $this->type . '.tpl');
			$t->set_block('export','catlist','categs');
			if (is_array($catlist))
			{
				foreach  ($catlist as $catid)
				{
					$t->set_var('categ',$this->gencat($catid));
					$t->fp('categs','catlist',True);
				}
			}
			return $t->fp('out','export');
		}

		function gencat($catid)
		{
			$t = new Template(EGW_INCLUDE_ROOT . '/bookmarks/templates/export');
			$t->set_file('categ','export_' . $this->type . '_catlist.tpl');
			$t->set_block('categ','subcatlist','subcats');
			$t->set_block('categ','urllist','urls');
			$subcats =  $this->categories->return_array('subs',0,False,'','cat_name','',True,$catid);

			if ($subcats)
			{
				foreach($subcats as $subcat)
				{
					$t->set_var('subcat',$this->gencat($subcat['id']));
					$t->fp('subcats','subcatlist',True);
				}
			}

			$t->set_var(array(
				'catname' => $this->translation->convert($GLOBALS['egw']->strip_html($this->categories->id2name($catid)),$this->charset,'utf-8'),
				'catid' => $catid,
				'folded' => (in_array($catid,$this->expanded) ? 'no' : 'yes')
			));

			$bm_list = $this->_list($catid,False,False,False);

			while(list($bm_id,$bookmark) = @each($bm_list))
			{
				$t->set_var(array(
					'url' => $bookmark['url'],
					'name' => $this->translation->convert($bookmark['name'],$this->charset,'utf-8'),
					'desc' => $this->translation->convert($bookmark['desc'],$this->charset,'utf-8')
				));
				$t->fp('urls','urllist',True);
			}
			return $t->fp('out','categ');
		}

		function _debug($s)
		{
			//echo $s;
		}
	}
?>
