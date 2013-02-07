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

	/* $Id: class.Modules_UI.inc.php 25174 2008-03-25 16:12:51Z ralfbecker $ */

	class Modules_UI
	{
		var $common_ui;
		var $t;
		var $bo;
		var $acl;
		var $modules;
		var $errormsg;

		var $public_functions = array
		(
			'manage' => True,
			'findmodules' => True,
			'configure' => True
		);

		function Modules_UI()
		{
			$this->common_ui =& CreateObject('sitemgr.Common_UI',True);
			$this->t = $GLOBALS['egw']->template;
			$this->bo = &$GLOBALS['Common_BO']->modules;
			$this->acl = &$GLOBALS['Common_BO']->acl;
			$this->catbo = &$GLOBALS['Common_BO']->cats;
		}

		function manage($msg = '')
		{
			if ($this->acl->is_admin())
			{
				$GLOBALS['Common_BO']->globalize(array('btnselect','inputmodules','inputarea'));
				global $btnselect,$inputmodules,$inputarea;
				$cat_id = $_GET['cat_id'];

				$this->modules = $this->bo->getallmodules();

				if ($cat_id != CURRENT_SITE_ID)
				{
						$cat = $this->catbo->getCategory($cat_id);
						$cat_name = $cat->name;
						$managelink = $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Categories_UI.manage');
						$goto = lang('Category manager');
						$scopename = lang('Category');
				}
				else
				{
					$scopename = lang('Site');
				}

				$this->t->set_file('Managemodules', 'manage_modules.tpl');
				$this->t->set_block('Managemodules','Contentarea','CBlock');
				$this->t->set_var(array(
//					'module_manager' => lang('%1 module manager', $scopename),
					'lang_help_module_manager' => lang('You can choose the modules that can be used on the site. The first list is a sort of master list, that is consulted if you do not configure lists specific to contentareas or (sub)categories. Then you can choose lists specific to each content area. In the category manager these lists can be overriden for each (sub)category.'),
					'lang_findmodules' => lang('Register new modules'),
					'lang_select_allowed_modules' => lang('Select allowed modules'),
					'lang_configure_module_properties' => lang('Configure module properties'),
//					'cat_name' => ($cat_name ? (' - ' . $cat_name) : ''),
					'managelink' => ($managelink ? ('<a href="' . $managelink . '">&lt; ' . lang('Go to') . ' ' . $goto . ' &gt;</a>') : ''),
					'action_url' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Modules_UI.manage','cat_id'=>$cat_id)),
				));
				$link_data['cat_id'] = $cat_id;
				$link_data['menuaction'] = "sitemgr.Modules_UI.findmodules";
				$this->t->set_var('findmodules', $GLOBALS['egw']->link('/index.php',$link_data));
				$link_data['menuaction'] = "sitemgr.Modules_UI.configure";
				$this->t->set_var('configureurl', $GLOBALS['egw']->link('/index.php',$link_data));
				$contentareas = $GLOBALS['Common_BO']->content->getContentAreas();
				if (!is_array($contentareas))
				{
					$contentareas = array();
				}
				array_unshift($contentareas,'__PAGE__');

				if ($btnselect)
				{
					$this->bo->savemodulepermissions($inputarea,$cat_id,$inputmodules);
				}

				foreach ($contentareas as $contentarea)
				{
					$permittedmodulesconfigured = $this->bo->getpermittedmodules($contentarea,$cat_id);
					$permittedmodulescascading = $this->bo->getcascadingmodulepermissions($contentarea,$cat_id);

					$this->t->set_var(Array(
						'title' => ($contentarea == '__PAGE__') ?
							lang('Master list of permitted modules') :
							lang('List of permitted modules specific to content area %1',$contentarea),
						'contentarea' => $contentarea,
						'selectmodules' => $this->moduleselect($this->modules,array_keys($permittedmodulesconfigured),'inputmodules',6),
						'configuremodules' => $this->moduleselect($permittedmodulescascading,false,'inputmodule_id',-8),
						'error' => ($contentarea == $inputarea && $this->errormsg) ? $this->errormsg : '',
					));
					$this->t->parse('CBlock','Contentarea', true);
				}
				$this->common_ui->DisplayHeader(lang('%1 module manager', $scopename).($cat_name ? (' - ' . $cat_name) : ''));

				if (!empty($msg))
				{
					echo '<p style="color: red; text-align: center;">'.$msg."</p>\n";
				}

				$this->t->pfp('out', 'Managemodules');
			}
			else
			{
				$this->common_ui->DisplayHeader();

				echo lang("You must be an admin to manage module properties.") ."<br><br>";
			}
			$this->common_ui->DisplayFooter();
		}

		function findmodules()
		{
			$new_modules = $this->bo->findmodules();
			$this->manage(count($new_modules) ? lang('Following new modules registed: %1',implode('<br>',$new_modules)) :
				lang('No new modules found !!!'));
		}

		function configure()
		{
			if ($this->acl->is_admin())
			{
				$GLOBALS['Common_BO']->globalize(array('btnSaveProperties','btnDeleteProperties','inputmodule_id','inputarea','element'));
				global $btnSaveProperties,$btnDeleteProperties,$inputarea,$inputmodule_id,$element;

				if (!$inputmodule_id)
				{
					$this->errormsg = lang("You did not choose a module.");
					$this->manage();
					return;
				}
				$cat_id = $_GET['cat_id'];

				if ($btnSaveProperties)
				{
					$this->bo->savemoduleproperties($inputmodule_id,$element,$inputarea,$cat_id);
					$this->manage();
					return;
				}
				elseif ($btnDeleteProperties)
				{
					$this->bo->deletemoduleproperties($inputmodule_id,$inputarea,$cat_id);
					$this->manage();
					return;
				}

				$this->common_ui->DisplayHeader();
				
				if ($cat_id != CURRENT_SITE_ID)
				{
						$cat = $this->catbo->getCategory($cat_id);
						$cat_name = $cat->name;
				}

				$this->t->set_file('Editproperties', 'edit_properties.tpl');
				$this->t->set_block('Editproperties','EditorElement','EBlock');

				$module = $this->bo->getmodule($inputmodule_id);
				$moduleobject =& $this->bo->createmodule($module['module_name']);
				$blockcontext =& CreateObject('sitemgr.Block_SO',True);
				$blockcontext->module_id = $inputmodule_id;
				$blockcontext->area = $inputarea;
				$blockcontext->cat_id = $cat_id;
				$moduleobject->set_block($blockcontext);

				$editorstandardelements = array(
					array('label' => lang('Title'),
							'form' => $moduleobject->title
					)
				);
				$editormoduleelements = $moduleobject->properties ? $moduleobject->get_admin_interface() : False;
				$interface = array_merge($editorstandardelements,(array)$editormoduleelements);
				while (list(,$element) = @each($interface))
				{
					$this->t->set_var(Array(
						'label' => $element['label'],
						'form' => $element['form'])
					);
					$this->t->parse('EBlock','EditorElement', true);				
				}

				$this->t->set_var(Array(
					'module_edit' => lang('Edit properties of module %1 for %2 with scope %3',
						$module['module_name'],
						($inputarea == '__PAGE__' ? lang('the whole page') : (lang('Contentarea') . $inputarea)),
						(($cat_id != CURRENT_SITE_ID) ? ('category ' . $cat_name) : ' the whole site')
					),
					'module_id' => $inputmodule_id,
					'contentarea' => $inputarea,
					'savebutton' => ($editormoduleelements ? 
						'<input type="submit" value="'.lang('Save').'" name="btnSaveProperties" />' :
						lang('There are no properties defined for this module')
					),
					'deletebutton' => $properties === False ? '' : '<input type="submit" value="'.lang('Delete').'" name="btnDeleteProperties" />',
					'action_url' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Modules_UI.configure','cat_id'=>$cat_id)),
					)
				);
				$this->t->set_var('backlink',
					'<a href="' . $GLOBALS['egw']->link('/index.php',array(
						'menuaction' => 'sitemgr.Modules_UI.manage',
						'cat_id' => $cat_id
					)) . '">&lt; ' . lang('Back to module manager') . ' &gt;</a>'
				);

				$this->t->pfp('out', 'Editproperties');
			}
			else
			{
				$this->common_ui->DisplayHeader();
				echo lang("You must be an admin to manage module properties.") ."<br><br>";
			}
			$this->common_ui->DisplayFooter();
		}

		function moduleselect($modules,$selected,$name,$multiple=0)
		{
			$options = array();
			foreach($modules as $id => $module)
			{
				$options[$id] = array(
					'label' => $module['module_name'],
					'title' => $module['description'],
				);
			}
			if ($multiple <= 0)
			{
				static $label_sort;
				if (!isset($label_sort)) $label_sort = create_function('$a,$b', 'return strcasecmp($a["label"],$b["label"]);');
				uasort($options,$label_sort);
			}
			$method = $multiple > 0 ? 'checkbox_multiselect' : 'select';
			return html::$method($name,$selected,$options,true,
				$multiple < 0 ? ' style="width: 100%"' : '',$multiple);
		}
	}
