<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* Rewritten with the new db-functions by RalfBecker-AT-outdoor-training.de *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Modules_SO.inc.php 19638 2005-11-06 11:16:31Z ralfbecker $ */

	class Modules_SO
	{
		var $db;
		var $properties_table,$modules_table,$active_modules_table;

		function Modules_SO()
		{
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('sitemgr');
			foreach(array('properties','modules','active_modules') as $name)
			{
				$var = $name.'_table';
				$this->$var = 'egw_sitemgr_'.$name;	// only reference to the db-prefix
			}
		}

		function savemoduleproperties($module_id,$data,$contentarea,$cat_id)
		{
			$this->db->insert($this->properties_table,array(
				'properties'=> serialize($data),
			),array(
				'area'		=> $contentarea,
				'cat_id'	=> $cat_id,
				'module_id'	=> $module_id,
			),__LINE__,__FILE__);
		}

		function deletemoduleproperties($module_id,$contentarea,$cat_id)
		{
			$this->db->delete($this->properties_table,array(
				'area'		=> $contentarea,
				'cat_id'	=> $cat_id,
				'module_id'	=> $module_id,
			),__LINE__,__FILE__);
		}

		function getmoduleproperties($module_id,$contentarea,$cat_id,$modulename)
		{
			if ($module_id)
			{
				$this->db->select($this->properties_table,'properties',array(
						'area'		=> $contentarea,
						'cat_id'	=> $cat_id,
						'module_id'	=> $module_id,
					),__LINE__,__FILE__);
			}
			else
			{
				$this->db->query("SELECT properties FROM $this->properties_table AS t1".
					" LEFT JOIN $this->modules_table AS t2 ON t1.module_id=t2.module_id".
					" WHERE ".$this->db->expression($this->properties_table,array(
						'area'		=> $contentarea,
						'cat_id'	=> $cat_id,
					)).' AND '.$this->db->expression($this->modules_table,array(
						'module_name' => $modulename
					)),__LINE__,__FILE__);
			}
			if ($this->db->next_record())
			{
				return unserialize($this->db->f('properties'));
			}
			return false;
		}

		function registermodule($modulename,$description)
		{
			$newly = !$this->getmoduleid($modulename);

			$this->db->insert($this->modules_table,array(
				'description' => $description
			),array(
				'module_name' => $modulename
			),__LINE__,__FILE__);

			return $newly;	// returns True on a new insert
		}

		function getallmodules()
		{
			$this->db->select($this->modules_table,'*',false,__LINE__,__FILE__,false,'ORDER BY module_name');

			return $this->constructmodulearray();
		}

		function getmoduleid($modulename)
		{
			$this->db->select($this->modules_table,'module_id',array(
					'module_name' => $modulename
				),__LINE__,__FILE__);
				
			return $this->db->next_record() ? $this->db->f('module_id') : False;
		}

		function getmodule($module_id)
		{
			$this->db->select($this->modules_table,'*',array(
				'module_id' => $module_id
			),__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				return array(
					'id'			=> $this->db->f('module_id'),
					'module_name'	=> $this->db->f('module_name'),
					'description'	=> stripslashes($this->db->f('description')),
				);
			}
			return false;
		}

		function constructmodulearray()
		{
			$result = array();
			while ($this->db->next_record())
			{
				$id = $this->db->f('module_id');
				$result[$id]['module_name'] = $this->db->f('module_name');
				$result[$id]['description'] = stripslashes($this->db->f('description'));
			}
			return $result;
		}

		function savemodulepermissions($contentarea,$cat_id,$modules)
		{
			$this->db->delete($this->active_modules_table,array(
					'area'	=> $contentarea,
					'cat_id'=> $cat_id,
				),__LINE__,__FILE__);
			
			if (!is_array($modules)) return;
			
			foreach($modules as $module_id)
			{
				$this->db->insert($this->active_modules_table,array(
						'module_id' => $module_id,
						'area'	=> $contentarea,
						'cat_id'=> $cat_id,
					),False,__LINE__,__FILE__);
			}
		}

		function getpermittedmodules($contentarea,$cat_id)
		{
			$this->db->query("SELECT * from $this->modules_table AS t1".
				" LEFT JOIN $this->active_modules_table AS t2 ON t1.module_id=t2.module_id".
				" WHERE ".$this->db->expression($this->active_modules_table,array(
					'area'	=> $contentarea,
					'cat_id'=> $cat_id,
				)),__LINE__,__FILE__);
			
			return $this->constructmodulearray();
		}
	}
