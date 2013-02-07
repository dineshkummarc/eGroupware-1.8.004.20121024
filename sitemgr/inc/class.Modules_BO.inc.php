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

	/* $Id: class.Modules_BO.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */

	require_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.module.inc.php');

	class Modules_BO
	{
		var $so;

		function Modules_BO()
		{
			//all sitemgr BOs should be instantiated via a globalized Common_BO object,
			$this->so =& CreateObject('sitemgr.Modules_SO', true);
		}

		function getmoduleid($modulename)
		{
			return $this->so->getmoduleid($modulename);
		}

		function getmodule($module_id)
		{
			return $this->so->getmodule($module_id);
		}

		function savemoduleproperties($module_id,$element,$contentarea,$cat_id)
		{
			$module = $this->getmodule($module_id);
			$moduleobject =& $this->createmodule($module['module_name']);
			if ($moduleobject->validate_properties($element))
			{
				$this->so->savemoduleproperties($module_id,$element,$contentarea,$cat_id);
			}
		}

		function deletemoduleproperties($module_id,$contentarea,$cat_id)
		{
			$this->so->deletemoduleproperties($module_id,$contentarea,$cat_id);
		}

		/**
		 * instanciates the sitemgr module $modulename
		 *
		 * The module is stored either in sitemgr/modules or in $app/sitemgr in class.modules_$modulename.inc.php
		 * The appname is the modulename (eg. 'calendar') or the first part of the modulename (eg. 'calendar_day').
		 *
		 * @param string $modulename
		 * @return object/boolean reference to the instanciated class, or false on error
		 */
		function &createmodule($modulename)
		{
			$obj = false;
			$classname = 'module_' . $modulename;
			list($app) = explode('_',$modulename);
			$files = array();
	
			if (@file_exists($file = $files[] = EGW_INCLUDE_ROOT.'/'.$app.'/sitemgr/class.'.$classname.'.inc.php') ||
				@file_exists($file = $files[] = EGW_INCLUDE_ROOT.'/'.$modulename.'/sitemgr/class.'.$classname.'.inc.php') ||
				@file_exists($file = $files[] = EGW_INCLUDE_ROOT.'/sitemgr/modules/class.'.$classname.'.inc.php'))
			{
				include_once($file);
				
				$obj = new $classname;
			}
			else
			{
				die(lang("Module '%1' not found in: %2! --> exiting",$modulename,implode(', ',$files)));
			}
			return $obj;
		}


		function getallmodules()
		{
			return $this->so->getallmodules();
		}

		/**
		 * Searches sitemgr/modules and $app/sitemgr dirs for new modules
		 *
		 * @return array with modulename => modulename: description pairs
		 */
		function findmodules()
		{
			$new_modules = array();
			foreach($GLOBALS['egw_info']['apps'] as $app => $data)
			{
				$moddir = EGW_SERVER_ROOT . '/' . $app . ($app == 'sitemgr' ? '/modules' : '/sitemgr');
				if (is_dir($moddir))
				{
					$d = dir($moddir);
					while ($file = $d->read())
					{
						if (preg_match ("/class\.module_(.*)\.inc\.php$/", $file, $module))
						{
							$modulename = $module[1];
	
							$moduleobject =& $this->createmodule($modulename);
							if ($moduleobject)
							{
								$description = '';
								// we grab the description direct from the module source, as we need the untranslated one
								if (ereg('\$this->description = lang\(\'([^'."\n".']*)\'\);',implode("\n",file($moddir.'/'.$file)),$parts))
								{
									$description = str_replace("\\'","'",$parts[1]);
								}
								if ($this->so->registermodule($modulename,$description ? $description : $moduleobject->description))
								{
									$new_modules[$modulename] = $modulename.': '.$moduleobject->description;
								}
							}
							//echo "<p>Modules_BO::findmodules() found $modulename: $moduleobject->description</p>\n";
						}
					}
					$d->close();
				}
			}
			return $new_modules;
		}

		function savemodulepermissions($contentarea,$cat_id,$modules)
		{
			$this->so->savemodulepermissions($contentarea,$cat_id,$modules);
		}

		//this function looks for a configured value for the combination contentareara,cat_id
		function getpermittedmodules($contentarea,$cat_id)
		{
			return $this->so->getpermittedmodules($contentarea,$cat_id);
		}

		//this function looks for a module's configured propertiese for the combination contentareara,cat_id
		//if module_id is 0 the fourth argument should provide modulename
		function getmoduleproperties($module_id,$contentarea,$cat_id,$modulename=False)
		{
			return $this->so->getmoduleproperties($module_id,$contentarea,$cat_id,$modulename);
		}

		//this function calculates the permitted modules by asking first for a value contentarea/cat_id
		//if it does not find one, climbing up the category hierarchy until the site wide value for the same contentarea
		//and if it still does not find a value, looking for __PAGE__/cat_id, and again climbing up until the master list
		function getcascadingmodulepermissions($contentarea,$cat_id)
		{
			$cat_ancestorlist = ($cat_id !=  CURRENT_SITE_ID) ? $GLOBALS['Common_BO']->cats->getCategoryancestorids($cat_id) : array();
			$cat_ancestorlist[] = CURRENT_SITE_ID;

			$cat_ancestorlist_temp = $cat_ancestorlist;

			do
			{
				$cat_id = array_shift($cat_ancestorlist_temp);

				while($cat_id !== NULL)
				{
					$permitted = $this->so->getpermittedmodules($contentarea,$cat_id);
					if ($permitted)
					{
						return $permitted;
					}
					$cat_id = array_shift($cat_ancestorlist_temp);
				}
				$contentarea = ($contentarea != "__PAGE__") ? "__PAGE__" : False;
				$cat_ancestorlist_temp = $cat_ancestorlist;
			} while($contentarea);
			return array();
		}

		//this function calculates the properties by climbing up the hierarchy tree in the same way as 
		//getcascadingmodulepermissions does
		function getcascadingmoduleproperties($module_id,$contentarea,$cat_id,$modulename=False)
		{
			$cat_ancestorlist = ($cat_id !=  CURRENT_SITE_ID) ? $GLOBALS['Common_BO']->cats->getCategoryancestorids($cat_id) : array();
			$cat_ancestorlist[] = CURRENT_SITE_ID;

			$cat_ancestorlist_temp = $cat_ancestorlist;

			do
			{
				$cat_id = array_shift($cat_ancestorlist_temp);

				while($cat_id !== NULL)
				{
					$properties = $this->so->getmoduleproperties($module_id,$contentarea,$cat_id,$modulename);
					//we have to check for type identity since properties can be NULL in case of unchecked checkbox
					if ($properties !== false)
					{
						return $properties;
					}
					$cat_id = array_shift($cat_ancestorlist_temp);
				}
				$contentarea = ($contentarea != "__PAGE__") ? "__PAGE__" : False;
				$cat_ancestorlist_temp = $cat_ancestorlist;
			} while($contentarea);
		}
	}
