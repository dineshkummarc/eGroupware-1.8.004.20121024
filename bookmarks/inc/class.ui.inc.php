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

	/* $Id: class.ui.inc.php 27471 2009-07-18 11:56:34Z ralfbecker $ */

	require_once(EGW_API_INC. '/class.html.inc.php');

	define('TREE',1);
	define('_LIST',2);
	define('CREATE',3);
	define('SEARCH',4);

	class ui
	{
		var $t;
		var $bo;
		var $img;
		var $expandedcats;
		var $nextmatchs;

		/**
		 * @var html
		 */
		var $html = false;

		var $public_functions = array
		(
			'edit' => True,
			'create' => True,
			'_list' => True,
			'search' => True,
			'tree' => True,
			'view' => True,
			'mail' => True,
			'mass' => True,
			'redirect' => True,
			'export' => True,
			'import' => True
		);

		function ui()
		{
			$this->t = $GLOBALS['egw']->template;
			$this->bo =& CreateObject('bookmarks.bo');
			$this->img = array(
				'collapse' => $GLOBALS['egw']->common->image('bookmarks','tree_collapse'),
				'expand' => $GLOBALS['egw']->common->image('bookmarks','tree_expand'),
				'edit' => $GLOBALS['egw']->common->image('bookmarks','edit'),
				'view' => $GLOBALS['egw']->common->image('bookmarks','document'),
				'mail' => $GLOBALS['egw']->common->image('bookmarks','mail'),
				'delete' => $GLOBALS['egw']->common->image('bookmarks','delete')
			);
			$this->expandedcats = array();
			$this->location_info = $this->bo->read_session_data();
			$this->nextmatchs =& CreateObject('phpgwapi.nextmatchs');
		}

		function init()
		{
			// we maintain two levels of state:
			// returnto the main interface (tree, list, or search)
			// returnto2 temporaray interface (create, edit, view, mail)
			$returnto2 = $this->location_info['returnto2'];
			$returnto = $this->location_info['returnto'];
			if ($returnto2)
			{
				$this->$returnto2();
			}
			elseif ($returnto)
			{
				$this->$returnto();
			}
			elseif ($GLOBALS['egw_info']['user']['preferences']['bookmarks']['defaultview'] == 'tree')
			{
				$this->tree();
			}
			else
			{
				$this->_list();
			}
		}

		function app_header($where=0)
		{
			$tabs[1]['label'] = lang('Tree view');
			$tabs[1]['link']  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.tree');

			$tabs[2]['label'] = lang('List');
			$tabs[2]['link']  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui._list');

			if (! $GLOBALS['egw']->acl->check('anonymous',1,'bookmarks'))
			{
				$tabs[3]['label'] = lang('New');
				$tabs[3]['link']  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.create');
			}

			$tabs[4]['label'] = lang('Search');
			$tabs[4]['link']  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.search');

			$this->t->set_var('app_navbar',$GLOBALS['egw']->common->create_tabs($tabs,$where));
		}

		function app_messages()
		{
			if ($this->bo->error_msg)
			{
				$bk_output_html = lang('Error') . ': ' . $this->bo->error_msg ;
			}
			if ($this->bo->msg)
			{
				$bk_output_html .= $this->bo->msg;
			}

			if ($bk_output_html)
			{
				$this->t->set_var('messages',$bk_output_html);
			}
		}

		function app_template()
		{
			$this->t->set_var(array(
				'lang_url' => lang('URL'),
				'lang_name' => lang('Name'),
				'lang_desc' => lang('Description'),
				'lang_keywords' => lang('Keywords'),
				'lang_access' => lang('Private'),
				'lang_category' => lang('Category'),
				'lang_rating' => lang('Rating'),
				'lang_owner' => lang('Created by'),
				'lang_added' => lang('Date added'),
				'lang_updated' => lang('Date last updated'),
				'lang_visited' => lang('Date last visited'),
				'lang_visits' => lang('Total visits'),
				'cancel_button' => ('<input type="image" name="cancel" title="' . lang('Done') . '" src="'
					. $GLOBALS['egw']->common->image('bookmarks','cancel') . '" border="0">'
				),
				'save_button' => ('<input type="image" name="save" title="' . lang('Save') . '" src="'
					. $GLOBALS['egw']->common->image('bookmarks','save') . '" border="0">'
				),
				'th_bg' => $GLOBALS['egw_info']['theme']['th_bg'],
				'category_image' => ('<input type="image" name="edit_category" title="' . lang('Edit category') . '" src="'
					. $GLOBALS['egw']->common->image('bookmarks','edit') . '" border="0">'
				),
			));
		}

		function create()
		{
			//if we redirect to edit categories, we remember form values and try to come back to create
			if ($_POST['edit_category_x'] || $_POST['edit_category_y'])
			{
				$this->bo->grab_form_values($this->location_info['returnto'],'create',$_POST['bookmark']);
				$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=preferences.uicategories.index&cats_app=bookmarks&cats_level=True&global_cats=True'));
			}
			//save bookmark
			if ($_POST['save_x'] || $_POST['save_y'])
			{
				$bookmark = $_POST['bookmark'];
				$bm_id = $this->bo->add($bookmark);
				if ($bm_id)
				{
					$this->location_info['bm_id'] = $bm_id;
					$this->view();
					return;
				}
			}
			//if we come back from editing categories we restore form values
			elseif ($this->location_info['returnto2'] == 'create')
			{
				$bookmark['name']        = $this->location_info['bookmark']['name'];
				$bookmark['url']         = $this->location_info['bookmark']['url'];
				$bookmark['desc']        = $this->location_info['bookmark']['desc'];
				$bookmark['keywords']    = $this->location_info['bookmark']['keywords'];
				$bookmark['category']    = $this->location_info['bookmark']['category'];
				$bookmark['rating']      = $this->location_info['bookmark']['rating'];
				$bookmark['access']      = $this->location_info['bookmark']['access'];
			}
			//if the user cancelled we go back to the view we came from
			if ($_POST['cancel_x'] || $_POST['cancel_y'])
			{
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}
			//store the view, we came from originally(list,tree,search), and the view we are in
			$this->location_info['bookmark'] = False;
			$this->location_info['returnto2'] = 'create';
			$this->bo->save_session_data($this->location_info);

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header(CREATE);

			$this->t->set_file(array(
				'common_'            => 'common.tpl',
				'form'               => 'form.tpl'
			));
			$this->t->set_block('form','body');
			$this->t->set_block('form','form_info');

			$selected[$bookmark['rating']] = ' selected';
			$this->app_template();

			$this->t->set_var(array(
				'form_info' => '',
				'lang_header' => lang('new bookmark'),
				'input_category' => $this->bo->categories_list($bookmark['category']),
				'input_rating' => ('<select name="bookmark[rating]">'
					. ' <option value="0"' . $selected[0] . '>--</option>'
					. ' <option value="1"' . $selected[1] . '>1 - ' . lang('Lowest') . '</option>'
					. ' <option value="2"' . $selected[2] . '>2</option>'
					. ' <option value="3"' . $selected[3] . '>3</option>'
					. ' <option value="4"' . $selected[4] . '>4</option>'
					. ' <option value="5"' . $selected[5] . '>5</option>'
					. ' <option value="6"' . $selected[6] . '>6</option>'
					. ' <option value="7"' . $selected[7] . '>7</option>'
					. ' <option value="8"' . $selected[8] . '>8</option>'
					. ' <option value="9"' . $selected[9] . '>9</option>'
					. ' <option value="10"' . $selected[10] . '>10 - ' . lang('Highest') . '</option>'
					. '</select>'
				),
				'input_url' => ('<input name="bookmark[url]" size="60" maxlength="255" value="' .
					($bookmark['url']?$bookmark['url']:'http://') . '">'
				),
				'input_name' => ('<input name="bookmark[name]" size="60" maxlength="255" value="' .
					$bookmark['name'] . '">'
				),
				'input_desc' => ('<textarea name="bookmark[desc]" rows="3" cols="60" wrap="virtual">' .
					$bookmark['desc'] . '</textarea>'
				),
				'input_keywords' => ('<input type="text" name="bookmark[keywords]" size="60" maxlength="255" value="' .
					$bookmark['keywords'] . '">'
				),
				'input_access' => ('<input type="checkbox" name="bookmark[access]" value="private"' .
					($bookmark['access'] ?' checked' : '') . '>'
				),
			));
			$this->t->fp('body','form');
			$this->app_messages();
			$this->t->pfp('out','common_');
		}

		function edit()
		{
			if (isset($_GET['bm_id']))
			{
				$bm_id = $_GET['bm_id'];
			}
			elseif (is_array($this->location_info))
			{
				$bm_id = $this->location_info['bm_id'];
			}
			//if the user cancelled we go back to the view we came from
			if ($_POST['cancel_x'] || $_POST['cancel_y'] || !isset($bm_id))
			{
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}
			//delete bookmark and go back to view we came from
			if ($_POST['delete_x'] || $_POST['delete_y'])
			{
				$this->bo->delete($bm_id);
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}
			//if we redirect to edit categories, we remember form values and try to come back to edit
			if ($_POST['edit_category_x'] || $_POST['edit_category_y'])
			{
				$this->bo->grab_form_values($this->location_info['returnto'],'edit',$_POST['bookmark']);
				$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=preferences.uicategories.index&cats_app=bookmarks&cats_level=True&global_cats=True'));
			}
			//save bookmark and go to view interface
			if ($_POST['save_x'] || $_POST['save_y'])
			{
				$bookmark = $_POST['bookmark'];
				if ($this->bo->update($bm_id,$bookmark))
				{
					$this->location_info['bm_id'] = $bm_id;
					$this->view();
					return;
				}
			}
			$bookmark = $this->bo->read($bm_id);

			if (!$bookmark[EGW_ACL_EDIT])
			{
				$this->bo->error_msg = lang('Bookmark not editable');
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}

			//if we come back from editing categories we restore form values
			if ($this->location_info['bookmark'])
			{
				$bookmark['name']     = $location_info['bookmark_name'];
				$bookmark['url']      = $location_info['bookmark_url'];
				$bookmark['desc']     = $location_info['bookmark_desc'];
				$bookmark['keywords'] = $location_info['bookmark_keywords'];
				$bookmark['category'] = $location_info['bookmark_category'];
				$bookmark['rating']   = $location_info['bookmark_rating'];
			}

			//store the view we are in
			$this->location_info['bookmark'] = False;
			$this->location_info['returnto2'] = 'edit';
			$this->location_info['bm_id'] = $bm_id;
			$this->bo->save_session_data($this->location_info);

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header();

			$this->t->set_file(array(
				'common_'            => 'common.tpl',
				'form'               => 'form.tpl'
			));
			$this->t->set_block('form','body');
			$this->t->set_block('form','form_info');

			$this->bo->date_information($this->t,$bookmark['info']);

			$rs[$bookmark['rating']] = ' selected';
			$rating_select = '<select name="bookmark[rating]">'
				. ' <option value="0">--</option>'
				. ' <option value="1"' . $rs[1] . '>1 - ' . lang('Lowest') . '</option>'
				. ' <option value="2"' . $rs[2] . '>2</option>'
				. ' <option value="3"' . $rs[3] . '>3</option>'
				. ' <option value="4"' . $rs[4] . '>4</option>'
				. ' <option value="5"' . $rs[5] . '>5</option>'
				. ' <option value="6"' . $rs[6] . '>6</option>'
				. ' <option value="7"' . $rs[7] . '>7</option>'
				. ' <option value="8"' . $rs[8] . '>8</option>'
				. ' <option value="9"' . $rs[9] . '>9</option>'
				. ' <option value="10"' . $rs[10] . '>10 - ' . lang('Highest') . '</option>'
				. '</select>';

			$account =& CreateObject('phpgwapi.accounts',$bookmark['owner']);
			$ad      = $account->read_repository();

			$this->app_template();
			$this->t->set_var(array(
				'lang_header' => lang('Edit bookmark'),
				'total_visits' => $bookmark['visits'],
				'owner_value' => $GLOBALS['egw']->common->display_fullname($ad['account_lid'],$ad['firstname'],$ad['lastname'])
			));
			$this->t->parse('info','form_info');
			$this->t->set_var(array(
				'form_info' => '',
				'form_action' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.edit&bm_id=' . $bm_id),
				'lang_access' => lang('Private'),
				'input_access' => ('<input type="checkbox" name="bookmark[access]" value="private"' .
					($bookmark['access']=='private'?' checked':'') . '>'
				),
				'input_rating' => $rating_select,
				'input_category' => $this->bo->categories_list($bookmark['category']),
				'input_url' => ('<input name="bookmark[url]" size="60" maxlength="255" value="' .
					$bookmark['url'] . '">'
				),
				'input_name' => ('<input name="bookmark[name]" size="60" maxlength="255" value="' .
					$bookmark['name'] . '">'
				),
				'input_desc' => ('<textarea name="bookmark[desc]" rows="3" cols="60" wrap="virtual">' .
					$bookmark['desc'] . '</textarea>'
				),
				'input_keywords' => ('<input type="text" name="bookmark[keywords]" size="60" maxlength="255" value="' .
					$bookmark['keywords'] . '">'
				),
				'delete_button' => ($this->bo->check_perms($bm_id,EGW_ACL_DELETE,$bookmark['owner']) ?
					('<input type="image" name="delete" title="' . lang('Delete') . '" src="'
						. $GLOBALS['egw']->common->image('bookmarks','delete') . '" border="0">'
					) :
					''
				),
			));

			$this->t->fp('body','form');
			$this->app_messages();
			$this->t->pfp('out','common_');
		}

		function _list()
		{
			if (is_array($this->location_info))
			{
				$start = $this->location_info['start'];
				$bm_cat = $this->location_info['bm_cat'];
			}
			if (isset($_GET['bm_cat']))
			{
				$bm_cat = $_GET['bm_cat'];
			}
			if (isset($_GET['start']))
			{
				$start = $_GET['start'];
			}
			if (isset($_POST['start']))
			{
				$start = $_POST['start'];
			}
			$this->location_info['start'] = $start;
			$this->location_info['bm_cat'] = $bm_cat;
			$this->location_info['returnto'] = '_list';
			unset($this->location_info['returnto2']);
			$this->bo->save_session_data($this->location_info);

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header(_LIST);

			$this->t->set_file(array(
				'common_' => 'common.tpl',
				'listbody'    => 'list.body.tpl'
			));

			$this->t->set_var(array(
				'th_bg' => $GLOBALS['egw_info']['theme']['th_bg'],
				'lang_url' => lang('URL'),
				'lang_name' => lang('Name')
			));

			// We need to send the $start var instead of the page number
			// Use appsession() to remeber the return page,instead of always passing it
			$this->print_list($where_clause,$start,$bm_cat,$bookmark_list);

			$this->t->set_var('BOOKMARK_LIST', $bookmark_list);

			$total_bookmarks = $this->bo->so->total_records;
			if ($total_bookmarks > $GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs'])
			{
				$next = $start + $GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs'];
				$total_matchs = lang('showing %1 - %2 of %3',($start + 1),
					($next <= $total_bookmarks) ? $next : $total_bookmarks,$total_bookmarks);
			}
			else
			{
				$total_matchs = lang('showing %1',$total_bookmarks);
			}
			if ($bm_cat)
			{
				$total_matchs .= ' ' .
					lang('from category %1',$GLOBALS['egw']->strip_html($this->bo->categories->id2name($bm_cat))) .
					' - <a href="' .
					$GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui._list&bm_cat=0&start=0') .
					'">' .
					lang('All bookmarks') .
					'</a>';
			}
			$link_data = array
			(
				'menuaction' => 'bookmarks.ui._list',
				'bm_cat' => $bm_cat
			);

			$this->t->set_var(array(
				'next_matchs_left' =>  $this->nextmatchs->left('/index.php',$start,$total_bookmarks,$link_data),
				'next_matchs_right' => $this->nextmatchs->right('/index.php',$start,$total_bookmarks,$link_data),
				'showing' => $total_matchs
			));

			$this->t->fp('body','listbody');
			$this->app_messages();
			$this->t->pfp('out','common_');
		}

		function search()
		{
			global $y, $x;
			if (is_array($this->location_info))
			{
				$start = $this->location_info['searchstart'];
				$x = $this->location_info['x'];
			}
			if (isset($_POST['x']))
			{
				$x = $_POST['x'];
				$this->location_info['x'] = $x;
			}
			if (isset($_POST['start']))
			{
				$start = $_POST['start'];
				$this->location_info['searchstart'] = $start;
			}
			$this->location_info['returnto'] = 'search';

			$this->bo->save_session_data($this->location_info);

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header(SEARCH);

			$q =& CreateObject('bookmarks.sqlquery');

			$this->t->set_file(array(
				'common_'  => 'common.tpl',
				'searchbody'    => 'search.body.tpl',
				'results' => 'search.results.tpl'
			));

			// the following fields are selectable
			$field = array(
				'bm_name'        => lang('Name'),
				'bm_keywords'    => lang('Keywords'),
				'bm_url'         => lang('URL'),
				'bm_desc'        => lang('Description')
			//		'bm_category'    => 'Category',
			//		'bm_subcategory' => 'Sub Category',
			);

			// PHPLIB's sqlquery class loads this string when
			// no query has been specified.
			$noquery = "1=0";

			# build the where clause based on user entered fields
			if (isset($x))
			{
				#
				# we need to pre-process the input fields so we can
				# handle quotes properly. we can't put an addslashes
				# on the resulting sql because the sql_query object
				# doesn't do the quotes correctly
				foreach ($x as $key => $value)
				{
					if (substr($key,0,4) == 'sel_' && !preg_match('/^bm_(name|keywords|url|desc)$/',$value) ||
						substr($key,0,5) == 'comp_' && !preg_match('/^(like|[<>=]{1,2})$/',$value))
					{
						continue;	// someone trying something nasty
					}
					$y[$key] = addslashes($value);
				}
				if (is_array($y)) $q->query = $q->where("y", 1);
			}

			$this->t->set_var(array(
				'SEARCH_SELECT' => $search_select,
				'FORM_ACTION'   => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.search')
			));

			# build the search form
			$this->t->set_var(QUERY_FORM, $q->form("x", $field, "qry", $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.search')));

			if ($q->query == $noquery)
			{
			}
			else
			{
				$this->print_list($q->query, $start,0,$bookmark_list);

				$total_bookmarks = $this->bo->so->total_records;
				if ($total_bookmarks > $GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs'])
				{
					$next = $start + $GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs'];
					$total_matchs = lang('showing %1 - %2 of %3',($start + 1),
					($next <= $total_bookmarks) ? $next : $total_bookmarks,$total_bookmarks);
				}
				else
				{
					$total_matchs = lang('showing %1',$total_bookmarks);
				}
				$link_data = array
				(
					'menuaction' => 'bookmarks.ui.search'
				);

				$this->t->set_var(array(
					'next_matchs_left' =>  $this->nextmatchs->left('/index.php',$start,$total_bookmarks,$link_data),
					'next_matchs_right' => $this->nextmatchs->right('/index.php',$start,$total_bookmarks,$link_data),
					'showing' => $total_matchs
				));

				$this->t->set_var(array(
					'QUERY_CONDITION' => $GLOBALS['egw']->strip_html($q->query),
					'LANG_QUERY_CONDITION' => lang('Query Condition'),
					'BOOKMARK_LIST'   => $bookmark_list
				));
				$this->t->parse('QUERY_RESULTS', 'results');
			}

			$this->t->fp('body','searchbody');
			$this->app_messages();
			$this->t->pfp('out','common_');
		}

		function print_list_break ($category_id)
		{
			$category = $GLOBALS['egw']->strip_html($this->bo->categories->id2name($category_id));

			$massupdate_shown = $GLOBALS['massupdate_shown'];

			// We only want to display the massupdate section once
			if (! $massupdate_shown)
			{
				$this->t->set_var(array(
					'lang_massupdate' => lang('Mass update:'),
					'massupdate_delete_icon' => sprintf('<input type="image" name="delete" border="0" src="%s">',$this->img['delete']),
					'massupdate_mail_icon' => sprintf('<input type="image" name="mail" border="0" src="%s">',$this->img['mail'])
				));
				$massupdate_shown = True;
			}
			else
			{
				$this->t->set_var(array(
					'lang_massupdate' => '',
					'massupdate_delete_icon' => '',
					'massupdate_mail_icon' =>''
				));
			}

			$this->t->set_var('CATEGORY',$GLOBALS['egw']->strip_html($category));

			$this->t->fp('LIST_HDR','list_header');
			$this->t->fp('LIST_FTR','list_footer');
			$this->t->fp('CONTENT','list_section',TRUE);
			$this->t->set_var('LIST_ITEMS','');
		}

		function print_list($where_clause, $start, $bm_cat, &$content)
		{
			$page_header_shown = $GLOBALS['page_header_shown'];

			$this->t->set_file(array(
				'list' => 'list.tpl'
			));
			$this->t->set_block('list','list_section');
			$this->t->set_block('list','list_header');
			$this->t->set_block('list','list_footer');
			$this->t->set_block('list','list_item');
			$this->t->set_block('list','list_keyw');
			$this->t->set_block('list','page_header');
			$this->t->set_block('list','page_footer');

			$this->t->set_var('list_mass_select_form',$GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.mass'));

			if (! $page_header_shown)
			{
				$this->t->fp('header','page_header');
				$page_header_shown = True;
			}
			else
			{
				$this->t->set_var('header','');
			}

			$bm_list = $this->bo->_list($bm_cat,$start,$where_clause);

			$prev_category_id = -1;
			$rows_printed = 0;

			while (list($bm_id,$bookmark) = @each($bm_list))
			{
				$rows_printed++;

				if ($bookmark['category'] != $prev_category_id)
				{
					if ($rows_printed > 1)
					{
						$this->print_list_break($prev_category_id);
					}
					$prev_category_id       = $bookmark['category'];
				}

				if ($bookmark['keywords'])
				{
					$this->t->set_var(BOOKMARK_KEYW, $bookmark['keywords']);
					$this->t->parse('bookmark_keywords','list_keyw');
				}
				else
				{
					$this->t->set_var('bookmark_keywords','');
				}

				// Check owner
				if ($this->bo->check_perms2($bookmark['owner'],$bookmark['access'],EGW_ACL_EDIT))
				{
					$maintain_url  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.edit&bm_id=' . $bm_id);
					$maintain_link = sprintf(
						'<a href="%s"><img src="%s" align="top" border="0" alt="%s"></a>',
						$maintain_url,
						$this->img['edit'],
						lang('Edit this bookmark')
					);
				}
				else
				{
					$maintain_link = '';
				}

				$view_url      = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.view&bm_id=' . $bm_id);
				$view_link     = sprintf(
					'<a href="%s"><img src="%s" align="top" border="0" alt="%s"></a>',
					$view_url,
					$this->img['view'],
					lang('View this bookmark')
				);

				$mail_link = sprintf(
					'<a href="%s"><img align="top" border="0" src="%s" alt="%s"></a>',
					$GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.mail&bm_id='.$bm_id),
					$this->img['mail'],
					lang('Mail this bookmark')
				);

				$this->t->set_var(array(
					'maintain_link' => $maintain_link,
					'bookmark_url' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.redirect&bm_id='.$bm_id),
					'view_link' => $view_link,
					'mail_link' => $mail_link,
					'checkbox' => '<input type="checkbox" name="item_cb[]" value="' . $bm_id . '">',
					'bookmark_name' => $bookmark['name'],
					'bookmark_desc' => nl2br($bookmark['desc']),
					'bookmark_rating' => sprintf('<img src="%s/bar-%s.jpg">',EGW_IMAGES,$bookmark['rating'])
				));
				$this->t->parse(LIST_ITEMS,'list_item',True);
			}

			if ($rows_printed > 0)
			{
				$this->print_list_break($prev_category_id);
				$content = $this->t->get('CONTENT');
				$this->t->fp('footer','page_footer');
			}
		}

		function tree()
		{
			$this->location_info['returnto'] = 'tree';
			unset($this->location_info['returnto2']);
			$this->bo->save_session_data($this->location_info);

			if ($_COOKIE['menutree'])
			{
				$this->expandedcats = array_keys($_COOKIE['menutree']);
			}
			else
			{
				$this->expandedcats = Array();
			}

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header(TREE);

			$this->t->set_file(array(
				'common_' => 'common.tpl',
			));
			$this->t->set_var(Array(
				'th_bg' => $GLOBALS['egw_info']['theme']['th_bg']
			));

			$categories = (array)$this->bo->categories->return_array( 'all', 0 , false, '', '', '', true );

			//build cat tree
			foreach ( $categories as $key => $cat ) {
				$categories[$key]['tree'] = $cat['id'];
				$parent = $cat['parent'];
				while ( $parent != 0) {
					$categories[$key]['tree'] = $parent. '/'. $categories[$key]['tree'];
					// Select a nonexisting key, in case the referenced cat doesn't exist.
					$parcatkey = count($categories) + 1;
					foreach ( $categories as $ikey => $icat ) {
						if ( $icat['id'] == $parent ) {
							$parcatkey = $ikey;
							break;
						}
					}
					$parent = $categories[$parcatkey]['parent'];
				}
			}

			// buld bm tree
			foreach ( $categories as $cat ) {
				$bookmarks = $this->bo->_list($cat['id'],False,False,False);
				$bm_tree[$cat['tree']] = $cat['name'];

				foreach ( (array)$bookmarks as $id => $bm ) {
					// begin entry
					$bm_tree[$cat['tree']. '/'. $id] = array();
					$entry = &$bm_tree[$cat['tree']. '/'. $id]['label'];

					// edit
					if ($this->bo->check_perms2( $bm['owner'], $bm['access'], EGW_ACL_EDIT) ) {
						$entry .= '<a href ="'.
							$GLOBALS['egw']->link( '/index.php', 'menuaction=bookmarks.ui.edit&bm_id='. $id ). '">'.
							html::image( 'bookmarks', $this->img['edit'], lang( 'Edit this bookmark' ) ).
							'</a>';
					}

					//view
					$entry .= '<a href ="'.
						$GLOBALS['egw']->link( '/index.php', 'menuaction=bookmarks.ui.view&bm_id='. $id ). '">'.
						html::image( 'bookmarks', $this->img['view'], lang( 'View this bookmark' ) ).
						'</a>';

					//redirect
					$entry .= '<a target="_new" href ="'.
						$GLOBALS['egw']->link( '/index.php', 'menuaction=bookmarks.ui.redirect&bm_id='. $id ). '">'.
						$bm['name']. '</a>';
				}
			}
			$tree = html::tree((array)$bm_tree, false, false, "null", 'foldertree', '', '', false, '/', null);

			$this->t->set_var('body',$tree);
			$this->app_messages($this->t);
			$this->t->pfp('out','common_');
		}

		function showcat($cats)
		{
			while(list(,$cat) = @each($cats))
			{
				$cat_id = $cat['id'];
				$status = in_array($cat_id,$this->expandedcats);
				$tree .= "\n" .
					'<tr><td width="10%">' .
					'<img src="' .
					$this->img[$status ? "collapse" : "expand"] .
					'" onclick="toggle(this, \'' .
					$cat_id .
					'\')"></td><td><a style="font-weight:bold" title="' .
					$GLOBALS['egw']->strip_html($cat['description']) .
					'" href="' .
					$GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui._list&start=0&bm_cat=' . $cat_id) .
					'">' .
					$GLOBALS['egw']->strip_html($cat['name']) .
					'</a></td></tr>' .
					"\n";
				$subcats = $this->bo->categories->return_array('subs',0,False,'','','',True,$cat_id);
				$bookmarks = $this->bo->_list($cat_id,False,False,False);
				if ($subcats || $bookmarks)
				{
					$tree .= '<tr><td></td><td><table style="display:' .
						($status ? "block" : "none") .
						'" border="0" cellspacing="0" cellpadding="0" width="100%" id="'.
						$cat_id .
						'">';

					while(list($bm_id,$bookmark) = @each($bookmarks))
					{
						$tree .= '<tr><td colspan="2">';
						if ($this->bo->check_perms2($bookmark['owner'],$bookmark['access'],EGW_ACL_EDIT))
						{
							$maintain_url  = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.edit&bm_id=' . $bm_id);
							$maintain_link = sprintf(
								'<a href="%s"><img src="%s" align="top" border="0" alt="%s"></a>',
								$maintain_url,
								$this->img['edit'],
								lang('Edit this bookmark')
							);
						}
						else
						{
							$maintain_link = '';
						}

						$view_url      = $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.view&bm_id=' . $bm_id);
						$view_link     = sprintf(
							'<a href="%s"><img src="%s" align="top" border="0" alt="%s"></a>',
							$view_url,
							$this->img['view'],
							lang('View this bookmark')
						);

						$redirect_link = '<a href="' .
							$GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.redirect&bm_id='.$bm_id) .
							'" target="_new">' . $bookmark['name'] . '</a>';

						$tree .= $maintain_link . $view_link . $redirect_link .
							'</td></tr>';
					}

					if ($subcats)
					{
						$tree .= $this->showcat($subcats);
					}

					$tree .= '</table></td></tr>';
				}
			}
			return $tree;
		}

		function view()
		{
			if (isset($_GET['bm_id']))
			{
				$bm_id = $_GET['bm_id'];
			}
			elseif (is_array($this->location_info))
			{
				$bm_id = $this->location_info['bm_id'];
			}
			//if the user cancelled we go back to the view we came from
			if ($_POST['cancel_x'] || $_POST['cancel_y'])
			{
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}
			//delete bookmark and go back to view we came from
			if ($_POST['delete_x'] || $_POST['delete_y'])
			{
				$this->bo->delete($bm_id);
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}
			if ($_POST['edit_x'] || $_POST['edit_y'])
			{
				$this->edit();
				return;
			}
			if ($_POST['edit_category_x'] || $_POST['edit_category_y'])
			{
				$GLOBALS['egw']->redirect_link('/index.php','menuaction=preferences.uicategories.index&cats_app=bookmarks&cats_level=True&global_cats=True');
				return;
			}

			$bookmark = $this->bo->read($bm_id);

			if (!$bookmark[EGW_ACL_READ])
			{
				$this->bo->error_msg = lang('Bookmark not readable');
				unset($this->location_info['returnto2']);
				$this->init();
				return;
			}

			//store the view we are in
			$this->location_info['returnto2'] = 'view';
			$this->location_info['bm_id'] = $bm_id;
			$this->bo->save_session_data($this->location_info);

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header();

			$this->t->set_file(array(
				'common_' => 'common.tpl',
				'form'     => 'form.tpl',
			));

			$this->t->set_block('form','body');
			$this->t->set_block('form','form_info');

			$this->bo->date_information($this->t,$bookmark['info']);
			$this->app_template();

			$account =& CreateObject('phpgwapi.accounts',$bookmark['owner']);
			$ad      = $account->read_repository();
			$category  = $GLOBALS['egw']->strip_html($this->bo->categories->id2name($bookmark['category']));

			$this->t->set_var(array(
				'total_visits' => $bookmark['visits'],
				'owner_value' => $GLOBALS['egw']->common->display_fullname($ad['account_lid'],$ad['firstname'],$ad['lastname'])
			));
			$this->t->parse('info','form_info');
			$this->t->set_var(array(
				'form_info' => '',
				'form_action' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.view&bm_id=' . $bm_id),
				'lang_access' => lang('Access'),
				'input_access' => lang($bookmark['access']),
				'lang_header' => lang('View bookmark'),
				'input_url' => ('<a href="' . $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.redirect&bm_id='.$bm_id) .
					'" target="_new">' . $bookmark['url'] . '</a>'
				),
				'input_name' => $bookmark['name'],
				'input_desc' => nl2br($bookmark['desc']),
				'input_keywords' => $bookmark['keywords'],
				'input_rating' => ('<img src="' . EGW_IMAGES. '/bar-' . $bookmark['rating'] . '.jpg">'
				),
				'input_category' => $category,
				'edit_button' => ($this->bo->check_perms($bm_id,EGW_ACL_EDIT) ?
					('<input type="image" name="edit" title="' . lang('Edit') . '" src="'
						. $GLOBALS['egw']->common->image('bookmarks','edit') . '" border="0">'
					) :
				''
				),
				'delete_button' => ($this->bo->check_perms($bm_id,EGW_ACL_DELETE) ?
					('<input type="image" name="delete" title="' . lang('Delete') . '" src="'
						. $GLOBALS['egw']->common->image('bookmarks','delete') . '" border="0">'
					) :
					''
				)
			));
			$this->t->fp('body','form');
			$this->app_messages($this->t);
			$this->t->pfp('out','common_');
		}

		function mail()
		{
			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->app_header();

			$this->t->set_file(array(
				'common_' => 'common.tpl',
				'mail'    => 'maillink.body.tpl'
			));

			if ($_POST['send'])	// Send button clicked
			{
				$validate =& CreateObject('phpgwapi.validator');
				// Strip space and tab from anywhere in the To field
				$to = $validate->strip_space($_POST['to']);

				// Trim the subject
				$subject = $GLOBALS['egw']->strip_html(trim($_POST['subject']));

				$message = $GLOBALS['egw']->strip_html($_POST['message']);

				// Do we have all necessary data?
				if (empty($to) || empty($subject) || empty($message))
				{
					$this->bo->error_msg .= '<br>'.lang('Please fill out <B>To E-Mail Address</B>, <B>Subject</B>, and <B>Message</B>!');
				}
				else
				{
					// the To field may contain one or more email addresses
					// separated by commas. Check each one for proper format.
					$to_array = explode(",", $to);

					while (list($key, $val) = each($to_array))
					{
						// Is email address in the proper format?
						if (!$validate->is_email($val))
						{
							$this->bo->error_msg .= '<br>' .
								lang('To address %1 invalid. Format must be <strong>user@domain</strong> and domain must exist!',$val).
								'<br><small>'.$validate->ERROR.'</small>';
							break;
						}
					}
				}
				if (!isset ($this->bo->error_msg))
				{
					$send     =& CreateObject('phpgwapi.send');

					$from = $GLOBALS['egw_info']['user']['fullname'] . ' <'.$GLOBALS['egw_info']['user']['email'].'>';

					// send the message
					$send->msg('email',$to,$subject,$message ."\n". $this->bo->config['mail_footer'],'','','',$from);
					$this->bo->msg .= '<br>'.lang('mail-this-link message sent to %1.',$to);
				}
			}

			if (empty($subject))
			{
				$subject = lang('Found a link you might like');
			}

			if (empty($message))
			{
				if (is_array($_POST['item_cb']))
				{
					while (list(,$id) = each($_POST['item_cb']))
					{
						$bookmark = $this->bo->read($id);
						$links[] = array(
							'name' => $bookmark['name'],
							'url'  => $bookmark['url']
					);
					}
				}
				else
				{
					$bookmark = $this->bo->read($_GET['bm_id']);
					$links[] = array(
						'name' => $bookmark['name'],
						'url'  => $bookmark['url']
					);
				}
				$message = lang('I thought you would be interested in the following link(s):')."\n";
				while (list(,$link) = @each($links))
				{
					$message .= sprintf("%s - %s\n",$link['name'],$link['url']);
				}
			}

			$this->t->set_var(array(
				'th_bg' => $GLOBALS['egw_info']['theme']['th_bg'],
				'header_message' => lang('Send bookmark'),
				'lang_from' => lang('Message from'),
				'lang_to' => lang('To E-Mail Addresses'),
				'lang_multiple_addr' => lang('(comma separate multiple addresses)'),
				'lang_subject' => lang('Subject'),
				'lang_message' => lang('Message'),
				'lang_send' => lang('Send'),
				'from_name' => $GLOBALS['egw']->common->display_fullname(),
				'form_action' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.mail'),
				'to' => $to,
				'subject' => $subject,
				'message' => $message
			));
			$this->t->fp('body','mail');
			$this->app_messages();
			$this->t->pfp('out','common_');
		}

		function mass()
		{
			$item_cb = $_POST['item_cb'];
			if ($_POST['delete_x'] || $_POST['delete_y'])
			{
				if (is_array($item_cb))
				{
					$i = 0;
					while (list(,$id) = each($item_cb))
					{
						if ($this->bo->delete($id))
						{
							$i++;
						}
					}
					$this->bo->msg = lang('%1 bookmarks have been deleted',$i);
				}

				$this->_list();
			}
			elseif ($_POST['mail_x'] || $_POST['mail_y'])
			{
				$this->mail();
			}
		}

		function redirect()
		{
			$bm_id = $_GET['bm_id'];
			$bookmark = $this->bo->read($bm_id,False);	// dont htmlspecialchars the url (!)
			$ts = explode(",",$bookmark['info']);
			$newtimestamp = sprintf("%s,%s,%s",$ts[0],time(),$ts[2]);
			$this->bo->updatetimestamp($bm_id,$newtimestamp);
			$GLOBALS['egw']->redirect($bookmark['url']);
		}

		function export()
		{
			if ($_POST['export'])
			{
				#  header("Content-type: text/plain");
				header("Content-type: application/octet-stream");

				if ($_POST['exporttype'] == 'Netscape/Mozilla')
				{
					header("Content-Disposition: attachment; filename=bookmarks.html");
					echo $this->bo->export($_POST['bookmark']['category'],'ns');
				}
				else
				{
					header("Content-Disposition: attachment; filename=bookmarks.xbel");
					echo $this->bo->export($_POST['bookmark']['category'],'xbel');
				}
			}
			else
			{
				$GLOBALS['egw']->common->egw_header();
				echo parse_navbar();
				$this->t->set_file('body','export.body.tpl');
				$this->t->set_var(Array(
					'FORM_ACTION' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.export'),
					'input_categories' => $this->bo->categories_list(0,True),
					'lang_categories' => lang('Please select the categories to export'),
					'lang_format' => lang('Select the format you would like to export to'),
					'lang_export_bookmarks' => lang('Export bookmarks'),
				));
				$this->t->pfp('out','body');
				$GLOBALS['egw']->common->egw_footer();
			}
		}

		function import()
		{
			if ($_POST['import'])
			{
				$this->bo->import($_FILES['bkfile'],$_POST['bookmark']['category']);
			}

			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();
			$this->t->set_file('body','import.body.tpl');
			$this->t->set_var(Array(
				'FORM_ACTION' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.import'),
				'lang_name' => lang('Enter the name of the Netscape bookmark file<br>that you want imported into bookmarker below.'),
				'lang_file' => lang('Netscape Bookmark File'),
				'lang_import_button' => lang('Import Bookmarks'),
				'lang_note' => lang('<b>Note:</b> This currently works with netscape bookmarks only'),
				'lang_catchoose' => lang('To which category should the imported folder hierarchy be attached'),
				'input_categories' => $this->bo->categories_list(0),
			));
			$this->app_messages();
			$this->t->pfp('out','body');
			$GLOBALS['egw']->common->egw_footer();
		}
	}
