<?php
	/**************************************************************************\
	* eGroupWare - Bookmarks                                                   *
	* http://www.egroupware.org                                                *
	* Based on Bookmarker Copyright (C) 1998  Padraic Renaghan                 *
	*                     http://www.renaghan.com/bookmarker                   *
	* Ported to phpgroupware by Joseph Engo                                    *
	* Ported to three-layered design by Michael Totschnig                      *
	* SQL reworked by RalfBecker@outdoor-training.de to get everything quoted  *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.so.inc.php 19868 2005-11-19 09:40:52Z ralfbecker $ */

	class so
	{
		var $db;
		var $total_records;

		function so()
		{
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('bookmarks');
			$this->table = 'egw_bookmarks';
			$this->user = $GLOBALS['egw_info']['user']['account_id'];
		}

		function _list($cat_list,$public_user_list,$start,$where_clause)
		{
			$where = $this->db->expression($this->table,'(',array('bm_owner'=>$this->user),
				(boolean) $public_user_list,' OR (',array(
					'bm_access'=>'public',
					'bm_owner' => $public_user_list,
				),'))',(boolean)$cat_list,' AND ',array(
					'bm_category' => $cat_list,
				),(boolean)$where_clause,' AND ',$where_clause);

			if ($start !== False)
			{
				$this->db->select($this->table,'count(*)',$where,__LINE__,__FILE__);
				$this->total_records = $this->db->next_record() ? $this->db->f(0) : 0;
				$this->db->select($this->table,'*',$where.' ORDER BY bm_category, bm_name',__LINE__,__FILE__,$start);
			}
			else
			{
				$this->db->select($this->table,'*',$where.' ORDER BY bm_category, bm_name',__LINE__,__FILE__);
				$this->total_records = $this->db->num_rows();
			}
			while ($this->db->next_record())
			{
				$result[$this->db->f('bm_id')] = $this->_db2bookmark();
			}
			return $result;
		}

		function _db2bookmark($do_htmlspecialchars = True)
		{
			foreach(array('name','url','desc','keywords','owner','access','category','rating','visits','info') as $name)
			{
				$bookmark[$name] = $this->db->f('bm_'.$name);
			}
			if ($do_htmlspecialchars)
			{
				foreach(array('name','url','desc','keywords') as $name)
				{
					$bookmark[$name] = $GLOBALS['egw']->strip_html($bookmark[$name]);
				}
			}
			return $bookmark;
		}

		function read($id,$do_htmlspecialchars=True)
		{
			$this->db->select($this->table,'*',array('bm_id'=>$id),__LINE__,__FILE__);
			if (!$this->db->next_record())
			{
				return False;
			}
			return $this->_db2bookmark($do_htmlspecialchars);
		}

		function exists($url)
		{
			$this->db->select($this->table,'count(*)',array('bm_url'=>$url,'bm_owner'=>$this->user),__LINE__,__FILE__);
			$this->db->next_record();

			return (bool)$this->db->f(0);
		}

		function add($values)
		{
			$columns = $this->_bookmark2db($values,$values['timestamps'] ? $values['timestamps'] : time() . ',0,0');
			$columns['bm_owner'] = (int) $GLOBALS['egw_info']['user']['account_id'];
			$columns['bm_visits'] = 0;

			if (!$this->db->insert($this->table,$columns,False,__LINE__,__FILE__))
			{
				return False;
			}
			return $this->db->get_last_insert_id($this->table,'bm_id');
		}

		function update($id, $values)
		{
			#echo "so::update<pre>".htmlspecialchars(print_r($values,True))."</pre>\n";

			$this->db->select($this->table,'bm_info',array('bm_id'=>$id),__LINE__,__FILE__);
			$this->db->next_record();
			$ts = explode(',',$GLOBALS['egw']->db->f('bm_info'));
			$ts[2] = time();

			$columns = $this->_bookmark2db($values,implode(',',$ts));

			// Update bookmark information.
			if (!$this->db->update($this->table,$columns,array('bm_id'=>$id),__LINE__,__FILE__))
			{
				return False;
			}
			return True;
		}

		function _bookmark2db($values,$timestamps)
		{
			if ($values['access'] != 'private')
			{
				$values['access'] = 'public';
			}
			foreach(array('name','url','desc','keywords','access','category','rating') as $name)
			{
				$columns['bm_'.$name] = $values[$name];
			}
			$columns['bm_info'] = $timestamps;

			return $columns;
		}

		function updatetimestamp($id,$timestamp)
		{
			$this->db->update($this->table,array(
				'bm_info'=>$timestamp,
				'bm_visits=bm_visits+1'
			),array('bm_id'=>$id),__LINE__,__FILE__);
		}

		function delete($id)
		{
			$this->db->delete($this->table,array('bm_id'=>$id),__LINE__,__FILE__);
			if ($this->db->Errno != 0)
			{
				return False;
			}
			return True;
		}
	}
