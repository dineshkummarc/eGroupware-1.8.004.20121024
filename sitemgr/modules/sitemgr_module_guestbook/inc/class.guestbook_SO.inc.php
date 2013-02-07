<?php

	class guestbook_SO
	{
		var $db;

		function guestbook_SO()
		{
			$this->db = $GLOBALS['egw']->db;
			$this->db->app = 'sitemgr_module_guestbook';	// as we run as sitemgr !
			$this->books_table = 'phpgw_sitemgr_module_guestbook_books';
			$this->entries_table = 'phpgw_sitemgr_module_guestbook_entries';
		}


		function create_book($title)
		{
			$this->db->insert($this->books_table,array('book_title' => $title),False,__LINE__,__FILE__);
			return $this->db->get_last_insert_id($this->books_table,'book_id');
		}


		function add_entry($name,$comment,$book_id)
		{
			$this->db->insert($this->entries_table,array(
				'name' => $name,
				'book_id' => $book_id,
				'comment' => $comment,
				'timestamp' => time()
			),False,__LINE__,__FILE__);
		}

		function get_entries($book_id)
		{
			$this->db->select($this->entries_table,array('name','comment','timestamp'),
				$this->db->expression($this->table,array('book_id' => $book_id),' ORDER BY timestamp DESC'),
				__LINE__,__FILE__);

			while($this->db->next_record())
			{
				foreach(array('name','comment','timestamp') as $field)
				{
					$entry[$field] = $this->db->f($field);
				}
				$result[] = $entry;
			}
			return $result;
		}

		function get_books()
		{
			$this->db->select($this->books_table,'*',False,__LINE__,__FILE__);
			while($this->db->next_record())
			{
				$result[$this->db->f('book_id')] = $this->db->f('book_title');
			}
			return $result;
		}

		function delete_book($book_id)
		{
			$this->db->delete($this->entries_table,array('book_id'=>$book_id),__LINE__,__FILE__);
			$this->db->delete($this->books_table,array('book_id'=>$book_id),__LINE__,__FILE__);
		}

		function save_book($book_id,$title)
		{
			$this->db->update($this->books_table,array('book_title'=>$title),array('book_id'=>$book_id),__LINE__,__FILE__);
		}
	}
