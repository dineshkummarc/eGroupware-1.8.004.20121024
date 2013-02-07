<?php
	/**************************************************************************\
	* eGroupWare - News                                                        *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	* --------------------------------------------                             *
	\**************************************************************************/

	/* $Id: class.soexport.inc.php 19799 2005-11-13 22:28:19Z ralfbecker $ */

	class soexport
	{
		var $db;
		var $table = 'egw_news_export';

		function soexport()
		{
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('news_admin');
		}

		function readconfig($cat_id)
		{
			$this->db->select($this->table,'*',array('cat_id' => $cat_id),__LINE__,__FILE__);
			
			return $this->db->row(true,'export_');
		}

		function saveconfig($cat_id,$config)
		{
			$this->db->insert($this->table,array(
				'export_type' => $config['type'],
				'export_itemsyntax' => $config['itemsyntax'],
				'export_title'      => $config['title'],
				'export_link'       => $config['link'],
				'export_description'=> $config['description'],
				'export_img_title'  => $config['img_title'],
				'export_img_url'    => $config['img_url'],
				'export_img_link'   => $config['img_link'],
			),array('cat_id' => $cat_id),__LINE__,__FILE__);
		}
	}
