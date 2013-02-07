<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* Rewritten with the new db-functions and documented                       *
	* by RalfBecker-AT-outdoor-training.de                                     *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Content_SO.inc.php 36280 2011-08-23 15:21:36Z leithoff $ */

	require_once(EGW_SERVER_ROOT . '/sitemgr/inc/class.Block_SO.inc.php');

	class Content_SO
	{
		/**
		 * Clone of the global db-object
		 *
		 * @var egw_db
		 */
		var $db;
		// Name of the differnt tables, initialised in the constructor
		var $content_table,$content_lang_table,$blocks_table,$blocks_lang_table,$modules_table,$modules_lang_table;

		/**
		 * Constructor of the content-storage-object
		 */
		function Content_SO()
		{
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('sitemgr');
			foreach(array('content','content_lang','blocks','blocks_lang','modules','modules_lang') as $name)
			{
				$var = $name.'_table';
				$this->$var = 'egw_sitemgr_'.$name; // only reference to the db-prefix
			}
		}

		/**
		 * Add a new block
		 * @param object $block block-object
		 * @return int the new block-id
		 */
		function addblock($block)
		{
			$this->db->insert($this->blocks_table,array(
				'area'    => $block->area,
				'module_id' => $block->module_id,
				'page_id' => $block->page_id,
				'cat_id'  => $block->cat_id,
				'sort_order'=> 0,
				'viewable'  => 0,
			),False,__LINE__,__FILE__);

			return $this->db->get_last_insert_id($this->blocks_table,'block_id');
		}

		/**
		 * Updates the scope of a block (for which cat's and pages it gets displayed)
		 * @param int $blockid id of the block
		 * @param int $cat_id cat_id
		 * @param int $page_id page-id or 0 for a cat- or site-wide block
		 * @param int number of rows updated, eg. 1=success
		 * @return int changed rows
		 */
		function updatescope($blockid,$cat_id,$page_id)
		{
			$this->db->update($this->blocks_table,array(
				'cat_id'  => $cat_id,
				'page_id' => $page_id,
			),array(
				'block_id'  => $blockid,
			),__LINE__,__FILE__);

			return $this->db->affected_rows();
		}

		/**
		* Creates a new version (in state draft) for a block
		* @return boolean true on success
		* @param int $blockid block-id
		*/
		function createversion($blockid)
		{
			return $this->db->insert($this->content_table,array(
				'block_id'  => $blockid,
				'state'   =>  SITEMGR_STATE_DRAFT,
			),False,__LINE__,__FILE__);
		}

		/**
		 * Deletes a version (of a block content), version-id's are unique and NOT block-specific
		 * @param int $versionid version-id
		 * @return boolean true on success, false otherwise
		 */
		function deleteversion($versionid)
		{
			if (!$this->db->delete($this->content_table,array('version_id'=>$versionid),__LINE__,__FILE__))
			{
				return False;
			}
			return $this->db->delete($this->content_lang_table,array('version_id'=>$versionid),__LINE__,__FILE__);
		}

		/**
		 * Retrives the block-id from a version-id
		 * @param int $versionid version-id
		 * @return int block-id or false
		 */
		function getblockidforversion($versionid)
		{
			$this->db->select($this->content_table,'block_id',array('version_id'=>$versionid),__LINE__,__FILE__);

			return $this->db->next_record() ? $this->db->f('block_id') : false;
		}

		/**
		 * Deletes a block identified by its block-id
		 * @param int block-id
		 * @return boolean true on success, flase otherwise
		 */
		function removeblock($blockid)
		{
			if (!$this->db->delete($this->blocks_table,array('block_id'=>$blockid),__LINE__,__FILE__))
			{
				return false;
			}
			return $this->db->delete($this->blocks_lang_table,array('block_id'=>$blockid),__LINE__,__FILE__);
		}

		/**
		 * Retrives all block's for a given scope (cat,page)
		 * @param int $cat_id cat-id
		 * @param int $page_id page-id or 0 for cat- or site-wide blocks
		 * @return array of block-objects, with only block-id, module-id & -name and content-area set
		 */
		function &getblocksforscope($cat_id,$page_id)
		{
			$sql = $this->db->expression($this->blocks_table,"SELECT t1.block_id AS id,t1.module_id,module_name,area FROM $this->blocks_table AS t1,$this->modules_table AS t2 WHERE t1.module_id = t2.module_id AND ",array(
				'cat_id'  => $cat_id,
				'page_id' => $page_id,
			),' ORDER BY sort_order');

			$this->db->query($sql,__LINE__,__FILE__);

			$result = array();
			while (($row = $this->db->row(true)))
			{
				$result[$row['id']] = new Block_SO($row);
			}
			return $result;
		}

		/**
		 * Retrives all blocks for a given content-area, cat-list, page and language
		 * @param string $area name of content-area
		 * @param array $cat_list array of cat-ids
		 * @param int $page_id page-id or 0 for eg. an index-page
		 * @param string $lang 2-char language id
		 * @return array of block-objects
		 */
		function getallblocksforarea($area,$cat_list,$page_id,$lang)
		{
			$lang = $this->db->quote($lang);
			$sql = "SELECT t1.block_id AS id, area, cat_id, page_id, t1.module_id, module_name, sort_order, title, viewable AS view".
				" FROM $this->blocks_table AS t1".
				" LEFT JOIN $this->modules_table AS t2 ON t1.module_id=t2.module_id".
				" LEFT JOIN $this->blocks_lang_table AS t3 ON (t1.block_id=t3.block_id AND t3.lang=$lang) ".
				" WHERE ".$this->db->expression($this->block_table,array(
					'area' => $area,
					'('
				));

			$cat_list[] = CURRENT_SITE_ID;  // always included
			$sql .= $this->db->expression($this->block_table,array(
				'page_id' => 0,
				'cat_id'  => $cat_list,
			));
			if ($page_id)
			{
				$sql .= $this->db->expression($this->blocks_table,' OR ',array('page_id' => $page_id));
			}
			$sql .= ') ORDER BY sort_order';

			$this->db->query($sql,__LINE__,__FILE__);

			$result = array();
			while (($row = $this->db->row(true)))
			{
				$result[$row['id']] = new Block_SO($row);
			}
			return $result;
		}

		/**
		* Retrives the id's for all content-versions of a block
		* @param int $blockid block-id
		* @return array of int version-id's
		*/
		function getversionidsforblock($blockid)
		{
			$this->db->select($this->content_table,'version_id',array(
				'block_id' => $blockid
			),__LINE__,__FILE__);

			$result = array();
			while ($this->db->next_record())
			{
				$result[] = $this->db->f('version_id');
			}
			return $result;
		}

		/**
		 * Merge the serialized arrays of arguments and arguments_lang, taking into account
		 * that in case of htmlcontent arguments_lang contain just that and no serialized array
		 *
		 * @param string $args
		 * @param string $args_lang
		 * @return array
		 */
		private function merge_arguments_lang($args,$args_lang)
		{
			$args = (array) unserialize($args);

			if (($arr = unserialize($args_lang)) !== false)	// is a serialized value (we dont use serialize(false)!)
			{
				$args_lang = (array) $arr;
			}
			else
			{
				$args_lang = array('htmlcontent' => $args_lang);
			}
			return array_merge($args,$args_lang);
		}

		/**
		* Retrives all content-versions for a given block and language
		* @param int $blockid block-id
		* @param string $lang 2-char language id
		* @return array of version-id - version-information-array's
		*/
		function getallversionsforblock($blockid,$lang)
		{
			$lang = $this->db->quote($lang);
			$sql = "SELECT t1.version_id, arguments,arguments_lang,state".
				" FROM $this->content_table AS t1".
				" LEFT JOIN $this->content_lang_table AS t2 ON (t1.version_id=t2.version_id AND t2.lang=$lang)".
				" WHERE ".$this->db->expression($this->content_table,array('block_id' => $blockid));

			$this->db->query($sql,__LINE__,__FILE__);

			$result = array();
			while (($row = $this->db->row(true)))
			{
				$version['arguments'] = self::merge_arguments_lang($row['arguments'],$row['arguments_lang']);
				$version['state'] = $row['state'];
				$version['id'] = $row['version_id'];

				$result[$version['id']] = $version;
			}
			return $result;
		}

		/**
		 * Selects all blocks from a given cat_list + site-wide blocks that are in given states
		 * @param array $cat_list array of int cat-id's
		 * @param int/array $states state-id's
		 * @return array of block-objects, without content
		 */
		function &getallblocks($cat_list,$states)
		{
			$cat_list[] = CURRENT_SITE_ID;
			$sql = "SELECT COUNT(state) AS cnt,t1.block_id AS id,area,cat_id,page_id,viewable AS view,state FROM $this->blocks_table AS t1,$this->content_table as t2 WHERE ".
				$this->db->expression($this->content,array(
					't1.block_id=t2.block_id',
					'cat_id' => $cat_list,
					'state'  => $states,
				))." GROUP BY t1.block_id,area,cat_id,page_id,viewable,state";

			$this->db->query($sql,__LINE__,__FILE__);

			$result = array();
			while (($row = $this->db->row(true)))
			{
				//in cnt we retrieve the numbers of versions that are commitable for a block,
				//i.e. if there are more than one, it should normally be a prepublished version
				//that will replace a preunpublished version
				$result[$row['id']] = new Block_SO($row);
			}
			return $result;
		}

		/**
		 * Retrives the visible blocks in a content-area for given cat-list and page
		 * @param string $area name of content area
		 * @param array $cat_list array of int cat-id's
		 * @param int $page_id page-id or 0 for eg. an index page
		 * @param boolean $isadmin viewer is administrator
		 * @param boolean $isuser viewer is regular eGW user, not anonymous
		 * @return array of block-objects, with the latest version-id, but not the content
		 */
		function &getvisibleblockdefsforarea($area,$cat_list,$page_id,$isadmin,$isuser)
		{
			$viewable[] = SITEMGR_VIEWABLE_EVERBODY;
			$viewable[] = $isuser ? SITEMGR_VIEWABLE_USER : SITEMGR_VIEWABLE_ANONYMOUS;
			if ($isadmin) $viewable[] = SITEMGR_VIEWABLE_ADMIN;

			// show anonymous blocks in edit-mode too, else one could not edit them
			if ($GLOBALS['sitemgr_info']['mode'] == 'Edit')
			{
				$viewable[] = SITEMGR_VIEWABLE_ANONYMOUS;
			}
			$cat_list[] = CURRENT_SITE_ID;  // always included

			$sql = "SELECT t1.block_id AS id,area,cat_id,page_id,t1.module_id,module_name,state,version_id AS version,sort_order,viewable AS view " .
				"FROM $this->blocks_table AS t1,$this->modules_table AS t2,$this->content_table AS t3 " .
				"WHERE t1.module_id = t2.module_id AND t1.block_id=t3.block_id AND ".
				$this->db->expression($this->blocks_table,array(
					'area' => $area,
				),' AND (',array(
					'page_id' => 0,
					'cat_id'  => $cat_list,
				));

			if ($page_id)
			{
				$sql .= ' OR '.$this->db->expression($this->blocks_table,array(
					'page_id' => $page_id
				));
			}
			$sql .= ') AND '.$this->db->expression($this->blocks_table,array(
					'viewable' => $viewable,
				)) . ' AND '.$this->db->expression($this->content_table,array(
					'state'    => $GLOBALS['Common_BO']->visiblestates
				)) . ' ORDER BY sort_order';

			$this->db->query($sql,__LINE__,__FILE__);

			$result = array();
			while (($row = $this->db->row(true)))
			{
				$result[$row['id']] = new Block_SO($row);
			}
			return $result;
		}

		/**
		 * Retrives the availible languages for the title of a block
		 * @param int $block_id block-id
		 * @return array of 2-char language id's
		 */
		function getlangarrayforblocktitle($block_id)
		{
			$this->db->select($this->blocks_lang_table,'lang',array(
				'block_id' => $block_id
			),__LINE__,__FILE__);

			$retval = array();
			while ($this->db->next_record())
			{
				$retval[] = $this->db->f('lang');
			}
			return $retval;
		}

		/**
		 * Retrives the availible languages for a blocks content-version
		 * @param int $version_id version-id
		 * @return array of 2-char language id's
		 */
		function getlangarrayforversion($version_id)
		{
			$this->db->select($this->content_lang_table,'lang',array(
				'version_id' => $version_id
			),__LINE__,__FILE__);

			$retval = array();
			while ($this->db->next_record())
			{
				$retval[] = $this->db->f('lang');
			}
			return $retval;
		}

		/**
		 * Retrives the content (arguments) for a block's content-version and a language
		 * @param int $version_id version-id
		 * @param string/boolean $lang 2-char language-id or false for the first language found
		 * @return array/boolean arguments array or false if language not found
		 */
		function getversion($version_id,$lang=false)
		{
			$fields = "arguments" . ($lang ? ', arguments_lang' : '');
			$lang_join = $lang ? "LEFT JOIN $this->content_lang_table AS t2 ON (t1.version_id = t2.version_id AND t2.lang=".$this->db->quote($lang).')' : '';
			$sql = "SELECT $fields FROM $this->content_table AS t1 $lang_join".
				" WHERE t1.version_id = ".(int)$version_id;

			$this->db->query($sql,__LINE__,__FILE__);
			if ($this->db->next_record())
			{
				 return $lang ?
					self::merge_arguments_lang($this->db->f('arguments'),$this->db->f('arguments_lang')) :
					unserialize($this->db->f('arguments'));
			}
			return false;
		}

		/**
		 * Retrives a block for a given block-id and language
		 * @param int $block_id block-id
		 * @param string $lang 2-char language id
		 * @return object/boolean block-object or false if not found
		 */
		function &getblock($block_id,$lang)
		{
			if (!$lang) $nolang=true;
			$lang = $this->db->quote($lang);
			$sql = "SELECT t1.block_id AS id,area,cat_id,page_id,area,t1.module_id,module_name,sort_order,title,viewable AS view".
				" FROM $this->blocks_table AS t1".
				" LEFT JOIN $this->modules_table as t2 ON t1.module_id=t2.module_id".
				" LEFT JOIN $this->blocks_lang_table AS t3 ON (t1.block_id=t3.block_id ".($nolang?"":"AND t3.lang=$lang").")".
				" WHERE t1.block_id=".(int)$block_id;

			$this->db->query($sql,__LINE__,__FILE__);

			if (($row = $this->db->row(true)))
			{
				return new Block_SO($row);
			}
			return false;
		}

		/**
		 * Retrieves basic infos about a block
		 * @param int $block_id block-id
		 * @return object/boolean limited(!) block-object or false if not found
		 */
		function &getblockdef($block_id)
		{
			$sql = "SELECT t1.block_id AS id,cat_id,page_id,area,t1.module_id,module_name".
				" FROM $this->blocks_table AS t1,$this->modules_table AS t2".
				" WHERE t1.module_id=t2.module_id AND t1.block_id=".(int)$block_id;

			$this->db->query($sql,__LINE__,__FILE__);

			if (($row = $this->db->row(true)))
			{
				return new Block_SO($row);
			}
			return false;
		}

		/**
		 * Retrives the title of a block in a given language
		 * @param int $block_id block-id
		 * @return string/boolean title of the block or false if not found
		 */
		function getlangblocktitle($block_id,$lang)
		{
			$cols = array('block_id' => $block_id);
			if ($lang) $cols['lang'] = $lang;

			$this->db->select($this->blocks_lang_table,'title',$cols,__LINE__,__FILE__);

			return $this->db->next_record() ? $this->db->f('title') : false;
		}

		function getblockstate($block_id)
		{
			$cols = array('block_id' => $block_id);

			$this->db->select($this->content_table,'state',$cols,__LINE__,__FILE__);

			return $this->db->next_record() ? $this->db->f('title') : false;
		}

		/**
		 * Save block-data: sort-order and by whom viewable
		 * @param object $block block-object
		 * @return boolean true on success, false otherwise
		 */
		function saveblockdata($block)
		{
			return $this->db->update($this->blocks_table,array(
					'sort_order'  => $block->sort_order,
					'viewable'    => $block->view,
				),array('block_id' => $block->id),__LINE__,__FILE__);
		}

		/**
		 * Save block-data: the language dependent block-title
		 * @param int $block_id block-id
		 * @param string $title block-title in $lang
		 * @param string $lang 2-char language id
		 * @return boolean true on success, false otherwise
		 */
		function saveblockdatalang($block_id,$title,$lang)
		{
			return $this->db->insert($this->blocks_lang_table,array(
					'title' => $title
				),array(
					'block_id'  => $block_id,
					'lang'    => $lang,
				),__LINE__,__FILE__);
		}

		/**
		 * Save block-data: the language independent versionised block-content
		 * @param int $block_id block-id
		 * @param int $version_id version-id
		 * @param array $data array of block's content/arguments
		 * @return boolean true on success, false otherwise
		 */
		function saveversiondata($block_id,$version_id,$data)
		{
			//this is necessary because double slashed data breaks while serialized
			if (isset($data))
			{
				$this->remove_magic_quotes($data);
			}
			return $this->db->update($this->content_table,array(
					'arguments' => serialize($data),
				),array(
					'version_id' => $version_id,
					'block_id'   => $block_id,
				),__LINE__,__FILE__);
		}

		/**
		 * Save block-state
		 * @param int $block_id block-id
		 * @param int $version_id version-id
		 * @param int $state state
		 * @return boolean true on success, false otherwise
		 */
		function saveversionstate($block_id,$version_id,$state)
		{
			return $this->db->update($this->content_table,array(
					'state' => $state
				),array(
					'version_id'  => $version_id,
					'block_id'    => $block_id,
				),__LINE__,__FILE__);
		}

		/**
		 * Save block-data: the language dependent versionised of block-content
		 * If $data contains only a value for key htmlcontent, we store that value and not a serialized version of $data
		 *
		 * @param int $version_id version-id
		 * @param array $data array of language-dependent versionised block-content/arguments
		 * @param string $lang 2-char language id
		 * @return boolean true on success, false otherwise
		 */
		function saveversiondatalang($version_id,$data,$lang)
		{
			//this is necessary because double slashed data breaks while serialized
			if (isset($data))
			{
				$this->remove_magic_quotes($data);
			}
			return $this->db->insert($this->content_lang_table,array(
					'arguments_lang' => count($data) == 1 && isset($data['htmlcontent']) ? $data['htmlcontent'] : serialize($data)
				),array(
					'version_id'  => $version_id,
					'lang'      => $lang,
				),__LINE__,__FILE__);
		}

		/**
		 * Run string or each value of an array thought stripslashes, if magic_quotes_gpc is on
		 * @param string/array $data string or array of strings
		 */
		function remove_magic_quotes(&$data)
		{
			if (!get_magic_quotes_gpc())
			{
				return;
			}
			if (is_array($data))
			{
				foreach($data as $key => $val)
				{
					$this->remove_magic_quotes($data[$key]);
				}
			}
			else
			{
				$data = stripslashes($data);
			}
		}

		/**
		 * Commits changes on a block: moves from state prepublish to publish and preunpublish to archive
		 * @param int $block_id block-id
		 */
		function commit($block_id)
		{
			foreach(array(
				SITEMGR_STATE_PREPUBLISH => SITEMGR_STATE_PUBLISH,
				SITEMGR_STATE_PREUNPUBLISH => SITEMGR_STATE_ARCHIVE
			) as $from => $to)
			{
				$this->db->update($this->content_table,array(
						'state' => $to
					),array(
						'state' => $from,
						'block_id' => $block_id,
					), __LINE__,__FILE__);
			}
		}

		/**
		 * Reactivates a block: moves from state archive to draft
		 * @param int $block_id block-id
		 */
		function reactivate($block_id)
		{
			$this->db->update($this->content_table,array(
					'state' => SITEMGR_STATE_DRAFT
				),array(
					'state' => SITEMGR_STATE_ARCHIVE,
					'block_id' => $block_id,
				), __LINE__,__FILE__);
		}
}
