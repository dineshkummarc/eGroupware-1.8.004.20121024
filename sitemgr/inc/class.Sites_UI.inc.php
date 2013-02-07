<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Sites_UI.inc.php 33188 2010-11-28 20:27:05Z ralfbecker $ */

	//copied from class admin.uiservers
	class Sites_UI
	{
		var $common_ui;
		var $public_functions = array(
			'list_sites' => True,
			'edit'         => True,
			'delete'       => True,
			'export'	=> True,
			'import'	=> True,
		);

		var $start = 0;
		var $query = '';
		var $sort  = '';
		var $order = '';

		var $debug = False;

		var $bo = '';
		var $nextmatchs = '';

		function Sites_UI()
		{
			$this->common_ui =& CreateObject('sitemgr.Common_UI',True);
			$this->bo = &$GLOBALS['Common_BO']->sites;
			$this->nextmatchs = createobject('phpgwapi.nextmatchs');

			$this->start = $this->bo->start;
			$this->query = $this->bo->query;
			$this->order = $this->bo->order;
			$this->sort = $this->bo->sort;
			if($this->debug) { $this->_debug_sqsof(); }
			/* _debug_array($this); */
		}

		function _debug_sqsof()
		{
			$data = array(
				'start' => $this->start,
				'query' => $this->query,
				'sort'  => $this->sort,
				'order' => $this->order
			);
			echo '<br>UI:';
			_debug_array($data);
		}

		function save_sessiondata()
		{
			$data = array(
				'start' => $this->start,
				'query' => $this->query,
				'sort'  => $this->sort,
				'order' => $this->order
			);
			$this->bo->save_sessiondata($data);
		}

		function list_sites()
		{
			$this->common_ui->DisplayHeader();

			if (!$GLOBALS['egw']->acl->check('run',1,'admin'))
			{
				$this->deny();
			}

			$GLOBALS['egw']->template->set_file(array('site_list_t' => 'listsites.tpl'));
			$GLOBALS['egw']->template->set_block('site_list_t','site_list','list');

			$GLOBALS['egw']->template->set_var('add_action',$GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.edit'));
			$GLOBALS['egw']->template->set_var('import_action',$GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.import'));
			$GLOBALS['egw']->template->set_var('lang_add',lang('Add'));
			$GLOBALS['egw']->template->set_var('lang_import',lang('Import'));
			$GLOBALS['egw']->template->set_var('title_sites',lang('Sitemgr Websites'));
			$GLOBALS['egw']->template->set_var('lang_search',lang('Search'));
			$GLOBALS['egw']->template->set_var('actionurl',$GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.list_sites'));
			$GLOBALS['egw']->template->set_var('lang_done',lang('Done'));
			$GLOBALS['egw']->template->set_var('doneurl',$GLOBALS['egw']->link('/admin/index.php'));

			if(!$this->start)
			{
				$this->start = 0;
			}

			$this->save_sessiondata();
			$sites = $this->bo->list_sites();

			$left  = $this->nextmatchs->left('/index.php',$this->start,$this->bo->total,'menuaction=sitemgr.Sites_UI.list_sites');
			$right = $this->nextmatchs->right('/index.php',$this->start,$this->bo->total,'menuaction=sitemgr.Sites_UI.list_sites');

			$GLOBALS['egw']->template->set_var(array(
				'left' => $left,
				'right' => $right,
				'lang_showing' => $this->nextmatchs->show_hits($this->bo->total,$this->start),
				'th_bg' => $GLOBALS['egw_info']['theme']['th_bg'],
				'lang_edit' => lang('Edit'),
				'lang_delete' => lang('Delete'),
				'lang_export' => lang('Export'),
				'sort_name' => $this->nextmatchs->show_sort_order(
					$this->sort,'site_name',$this->order,'/index.php',lang('Name'),'&menuaction=sitemgr.Sites_UI.list_sites'
				),
				'sort_url' => $this->nextmatchs->show_sort_order(
					$this->sort,'site_url',$this->order,'/index.php',lang('URL'),'&menuaction=sitemgr.Sites_UI.list_sites'
				)
			));

			while(list($site_id,$site) = @each($sites))
			{
				$tr_color = $this->nextmatchs->alternate_row_color($tr_color);

				$GLOBALS['egw']->template->set_var(array(
					'tr_color' => $tr_color,
					'site_name' => $GLOBALS['egw']->strip_html($site['site_name']),
					'site_url' => $site['site_url'],
					'edit' => $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.edit&site_id=' . $site_id),
					'lang_edit_entry' => lang('Edit'),
					'delete' => $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.delete&site_id=' . $site_id),
					'lang_delete_entry' => lang('Delete'),
					'export' => $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.export&site_id=' . $site_id),
					'lang_export_entry' => lang('Export')
				));
				$GLOBALS['egw']->template->parse('list','site_list',True);
			}

			$GLOBALS['egw']->template->parse('out','site_list_t',True);
			$GLOBALS['egw']->template->p('out');
			$this->common_ui->DisplayFooter();
		}

		/* This function handles add or edit */
		function edit()
		{
			if ($_POST['done'])
			{
				return $this->list_sites();
			}
			$this->common_ui->DisplayHeader();

			if (!$GLOBALS['egw']->acl->check('run',1,'admin'))
			{
				$this->deny();
			}
			if ($_POST['delete'])
			{
				return $this->delete();
			}

			$site_id = get_var('site_id',array('POST','GET'));
			if(!is_numeric($site_id)) $site_id = false;

			$GLOBALS['egw']->template->set_file(array('form' => 'site_form.tpl'));
			$GLOBALS['egw']->template->set_block('form','add','addhandle');
			$GLOBALS['egw']->template->set_block('form','edit','edithandle');

			if ($_POST['save'])
			{
				$site = $_POST['site'];
				if (substr($site['dir'],-1) == '/' || substr($site['dir'],-1) == '\\')
				{
					$site['dir'] = substr($site['dir'],0,-1);
				}
				if (substr($site['url'],-1) != '/')
				{
					$site['url'] .= '/';
				}
				$site['anonuser'] = $GLOBALS['egw']->accounts->id2name($site['anonuser']);

				if (($site_dir=$site['dir']) == 'sitemgr'.SEP.'sitemgr-site')
				{
					$site_dir = EGW_SERVER_ROOT.SEP.'sitemgr'.SEP.'sitemgr-site';
				}
				if (!$site['name'])
				{
					$GLOBALS['egw']->template->set_var('message','<font color="red">'.lang('Please enter a name for that site !').'</font>');
				}
				elseif (!is_dir($site_dir) || !is_readable($site_dir.'/config.inc.php'))
				{
					$GLOBALS['egw']->template->set_var('message','<font color="red">'.lang("'%1' is no valid sitemgr-site directory !!!",$site['dir']).'</font>');
				}
				elseif (!empty($site_id))
				{
					$this->bo->update($site_id,$site);
					$GLOBALS['egw']->template->set_var('message',lang('Site %1 has been updated',$site['_name']));
				}
				else
				{
					$site_id = $this->bo->add($site);
					// save some default prefs, so that the site works instantly
					$this->bo->saveprefs(array(
						'home_page_id' => 0,	// Index
						'themesel' => 'idots',
						'site_languages' => $GLOBALS['egw_info']['user']['preferences']['common']['lang']
					),$site_id);
					// allow all modules for the whole page
					$GLOBALS['Common_BO']->modules->savemodulepermissions('__PAGE__',$site_id,array_keys($GLOBALS['Common_BO']->modules->getallmodules()));

					$GLOBALS['egw']->template->set_var('message',lang('Site %1 has been added, you need to %2configure the site%3 now',
						$site['_name'],'<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Common_UI.DisplayPrefs','siteswitch'=>$site_id)).'">','</a>'));
				}
			}
			else
			{
				$GLOBALS['egw']->template->set_var('message','');
			}
			if ($site_id && !isset($site))
			{
				$site = $this->bo->read($site_id);
				if (substr($site['site_dir'],-20) == 'sitemgr'.SEP.'sitemgr-site')
				{
					$site['site_dir'] = 'sitemgr'.SEP.'sitemgr-site';
				}
			}
			else
			{
				$site = array(
					'site_name' => $site['name'] ? $site['name'] : '',
					'site_dir' => $site['dir'] ? $site['dir'] : 'sitemgr'.SEP.'sitemgr-site',
					'site_url' => $site['url'] ? $site['url'] : $GLOBALS['egw_info']['server']['webserver_url'] . '/sitemgr/sitemgr-site/',
					'anonymous_user' => $site['anonuser'] ? $site['anonuser'] : 'anonymous',
					'anonymous_passwd' => $site['anonpasswd'] ? $site['anonpasswd'] : 'anonymous',
					'adminlist' => is_array($site['adminlist']) ? $site['adminlist'] : array($GLOBALS['egw']->accounts->name2id('Admins')),
				);
			}
			$GLOBALS['egw']->template->set_var('title_sites',$site_id ? lang('Edit Website') : lang('Add Website'));

			$GLOBALS['egw']->template->set_var('actionurl',$GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.edit'));

			$GLOBALS['egw']->template->set_var(array(
				'lang_name' => lang('Site name'),
				'lang_sitedir' => lang('Filesystem path to sitemgr-site directory'),
				'lang_siteurl' => lang('URL to sitemgr-site'),
				'lang_anonuser' => lang('Anonymous user\'s username'),
				'lang_anonpasswd' => lang('Anonymous user\'s password'),
				'note_name' => lang('This is only used as an internal name for the website.'),
				'note_dir' => lang('This must be an absolute directory location.  <b>No trailing slash</b>.'),
				'note_url' => lang('The URL must be absolute and end in a slash, for example http://mydomain.com/mysite/'),
				'note_anonuser' => lang('If you haven\'t done so already, create a user that will be used for public viewing of the site.  Recommended name: anonymous.'),
				'note_anonpasswd' => lang('Password that you assigned for the anonymous user account.'),
				'note_adminlist' => lang('Select persons and groups that are entitled to configure the website.')
			));

			$GLOBALS['egw']->template->set_var('lang_adminlist',lang('Site administrators'));
			$GLOBALS['egw']->template->set_var('lang_save',lang('Save'));
			$GLOBALS['egw']->template->set_var('lang_add',lang('Add'));
			$GLOBALS['egw']->template->set_var('lang_default',lang('Default'));
			$GLOBALS['egw']->template->set_var('lang_reset',lang('Clear Form'));
			$GLOBALS['egw']->template->set_var('lang_done',lang('Cancel'));
			$GLOBALS['egw']->template->set_var('lang_delete',lang('Delete'));

			$GLOBALS['egw']->template->set_var($site);

			$GLOBALS['egw']->template->set_var(array(
				'site_anonuser'  => $GLOBALS['egw']->uiaccountsel->selection('site[anonuser]','anonuser',
					$GLOBALS['egw']->accounts->name2id($site['anonymous_user'])),
				'site_adminlist' => $GLOBALS['egw']->uiaccountsel->selection('site[adminlist]','adminlist',
					$this->adminlist($site_id,$site['adminlist']),'both',5),
			));

			if ($site_id)
			{
				$GLOBALS['egw']->template->parse('edithandle','edit');
				$GLOBALS['egw']->template->set_var('addhandle','');
			}
			else
			{
				$GLOBALS['egw']->template->set_var('edithandle','');
				$GLOBALS['egw']->template->parse('addhandle','add');
			}
			$GLOBALS['egw']->template->pparse('phpgw_body','form');
			$this->common_ui->DisplayFooter();

		}

		function adminlist($site_id,$admins='')
		{
			if (!$admins) $admins = array();

			if (!$site_id)
			{
				if (($admin_grp = $GLOBALS['egw']->accounts->name2id('Admins')) && !in_array($admin_grp,$admins))
				{
					$admins[] = $admin_grp;
				}
			}
			else
			{
				foreach($this->bo->get_adminlist($site_id) as $account_id => $rights)
				{
					if ($rights == SITEMGR_ACL_IS_ADMIN && !in_array($account_id,$admins))
					{
						$admins[] = $account_id;
					}
				}
			}
			return $admins;
		}

		function delete()
		{
			if (!$GLOBALS['egw']->acl->check('run',1,'admin'))
			{
				$GLOBALS['egw']->common->egw_header();
				echo parse_navbar();
				$this->deny();
			}

			$site_id = get_var('site_id',array('POST','GET'));
			if ($_POST['yes'] || $_POST['no'])
			{
				if ($_POST['yes'])
				{
					$this->bo->delete($site_id);
				}
				$GLOBALS['egw']->redirect_link('/index.php','menuaction=sitemgr.Sites_UI.list_sites');
			}
			else
			{
				$GLOBALS['egw']->common->egw_header();
				echo parse_navbar();

				$site = $this->bo->read($site_id);

				$GLOBALS['egw']->template->set_file(array('site_delete' => 'delete_common.tpl'));

				$GLOBALS['egw']->template->set_var(array(
					'form_action' => $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.delete'),
					'hidden_vars' => '<input type="hidden" name="site_id" value="' . $site_id . '"><script>document.yesbutton.yesbutton.focus()</script>',
					'messages' => lang('Are you sure you want to delete site %1 and all its content? You cannot retrieve it if you continue.',$site['site_name']),
					'no' => lang('No'),
					'yes' => lang('Yes'),
				));
				$GLOBALS['egw']->template->pparse('phpgw_body','site_delete');
			}
		}

		public function export()
		{
			if (!$GLOBALS['egw']->acl->check('run',1,'admin'))
			{
				$GLOBALS['egw']->common->egw_header();
				echo parse_navbar();
				$this->deny();
			}

			$site_id = get_var('site_id',array('POST','GET'));
			if($site_id) {
				$site = $this->bo->read($site_id);
				$name = urlencode($site['site_name']);
				header('Content-type: application/xml');
				header("Content-Disposition: attachment; filename=$name.xml");
				$writer = xmlwriter_open_uri('php://output');
				xmlwriter_set_indent_string($writer, "\t");
				xmlwriter_set_indent($writer, true);
				$export = new sitemgr_export_xml($writer);
				$export->export_record($site_id);
				common::egw_exit();
			}
		}

		public function import()
		{
			if (!$GLOBALS['egw']->acl->check('run',1,'admin'))
			{
				$GLOBALS['egw']->common->egw_header();
				echo parse_navbar();
				$this->deny();
			}
			$GLOBALS['egw']->redirect_link('/index.php', array(
				'menuaction' => 'sitemgr.sitemgr_import_xml.ui_import',
			));
		}

		function deny()
		{
			echo '<p><center><b>'.lang('Access not permitted').'</b></center>';
			$GLOBALS['egw']->common->egw_exit(True);
		}
	}
?>
