<?php
/**************************************************************************\
* eGroupWare - IWATS - Intelligent Web Agent Ticket System                *
* http://www.eGroupWare.org                                               *
* Written by Drago Bokal <drago DOT bokal AT fmf DOT uni-lj DOT si>       *
*            Joe Stewart <joestewart AT users DOT sourceforge DOT net>    *
* --------------------------------------------                            *
*  This program is free software; you can redistribute it and/or modify it*
*  under the terms of the GNU General Public License as published by the  *
*  Free Software Foundation; either version 2 of the License, or (at your *
*  option) any later version.                                             *
\**************************************************************************/

/* $ Id: class.et_media.inc.php,v 1.2 2002/10/19 11:11:03 ralfbecker Exp $ */

include_once(EGW_INCLUDE_ROOT . '/etemplate/inc/class.so_sql.inc.php');
$GLOBALS['egw_info']['flags']['included_classes']['so_sql'] = True;


/**!
@class generic_list
@author dragob
@abstract given a table and some e-templates enables listing, adding, editing and deleting the entries of the table
@discussion How to use generic_list
@discussion 1. Create a database table (using eTemplate DBTools). 
@discussion    Table must have an integer primary key.
@discussion    Table may have an integer foreign key which represent a master-slave relationship
@discussion    (in a one to many relationship, our table is on the "many" or "slave" side).
@discussion 2. Create a list entry template using eTemplate.
@discussion    Template must contain a set of labels whose Name field must be set to
@discussion    "${row}[field_name]", where field_name could be any field in the created table
@discussion    Template must have Edit and Delete buttons, whose Name field must be set
@discussion    edit[$row_cont[key_field]] and delete[$row_cont[key_field]] 
@discussion    Template may have other command buttons, whose Name field must be set to
@discussion    command[$row_cont[key_field]] 
@discussion 3. Create a list template using eTemplate.
@discussion    Template must have a message row for displaying variable "msg"
@discussion    Template must use list entry template as a subtemplate, and its Options must be 
@discussion    "entry".
@discussion    Template must have an Add button whose Name is set to "add".
@discussion    Template may have other command buttons, whose Name field must be set to
@discussion    "command"
@discussion 4. Create an edit template using eTemplate.
@discussion    Template must have a message row for displaying variable "msg"
@discussion    Template must assume the variables will have the names of the table fields.
@discussion    Template must have a Save button whose Name is set to "save".
@discussion    Template must have a Cancel button whose Name is set to "cancel".
@discussion    Template may have other command buttons, whose Name field must be set to
@discussion    "command"
@discussion 5. Create an delete template using eTemplate.
@discussion    Template must have a message row for displaying variable "msg"
@discussion    Template must assume the variables will have the names of the table fields.
@discussion    Template must have a Yes button whose Name is set to "yes".
@discussion    Template must have a No button whose Name is set to "no".
@discussion    Template may have other command buttons, whose Name field must be set to
@discussion    "command"
@discussion 6. Create a file class.ui_myclass.inc.php looking like
@discussion 
@discussion include_once(EGW_INCLUDE_ROOT . '/.../inc/class.generic_list.inc.php');
@discussion $GLOBALS['egw_info']['flags']['included_classes']['generic_list'] = True;
@discussion 
@discussion class ui_myclass extends generic_list
@discussion {
@discussion 
@discussion   function ui_myclass()
@discussion   {
@discussion     parent::create('my_application', 'my_table', 
@discussion       'ui_myclass', 'key_field', 'my_list_template', 
@discussion       'my_edit_template', 'my_add_template', 'my_delete_template', 
@discussion       'master_fk_field');
@discussion   }
@discussion 
@discussion }
@discussion 7. If the list or edit templates contain optional command buttons, 
@discussion    override the edit_elt method
@discussion      function edit_elt($content='',$msg = '')
@discussion      {
@discussion        if (isset($content['my_command1'])) {
@discussion          //process my_command1;
@discussion        }
@discussion        else if (isset($content['my_command2'])) {
@discussion          //process my_command2;
@discussion        }
@discussion        else {
@discussion          parent::edit_elt($content,$msg);
@discussion        }
@discussion      }
@discussion 8. If the delete templates contain optional command buttons, 
@discussion    override the delete_elt method in a similar fashion.
@discussion 
@discussion 9. If any of the templates contain select lists, 
@discussion    override the get_sel_fields method.
@discussion
@discussion  function get_sel_fields($content,$template)
@discussion  {
@discussion    if ($template==$this->edit_template) {
@discussion      return(
@discussion        array(    // the options for our selectboxes for states
@discussion            'field_name1' => array(
@discussion                          'key1'=>'value1',
@discussion                          'key2'=>'value2',
@discussion                          'key3'=>'value3'
@discussion                       ),
@discussion            'field_name2' => array(
@discussion                          'key1'=>'value1',
@discussion                          'key2'=>'value2',
@discussion                          'key3'=>'value3'
@discussion                       )
@discussion        )
@discussion      );
@discussion    }
@discussion    return array();
@discussion  }
@discussion
@discussion 10. If any of the templates contains fields that are not table fields, 
@discussion     override the preprocess_content method. This is useful if some integer 
@discussion     foreign key fields are to be replaced with their string descriptors.
@discussion
@discussion  function preprocess_content($content,$template)
@discussion  {
@discussion    if ($template==$this->list_template) {
@discussion      $content+=array(
@discussion        'new_field1'=>'value1',
@discussion        'new_field2'=>'value2',
@discussion        'new_field3'=>'value3'
@discussion      );
@discussion    }
@discussion    else if ($template==$this->edit_template) {
@discussion      $content+=array(
@discussion      ...
@discussion      );
@discussion    }
@discussion    return $content;
@discussion  }
@discussion}
@discussion 11. If your table is in a master-slave relationship to some other, call
@discussion     your class using some code similar to:
@discussion           
@discussion           ExecMethod('my_application.my_class.list_all',
@discussion             array('master_key_field'=>master_key_value));
@discussion           exit;
@discussion 12. If your table is not in a master-slave relationship to some other, call
@discussion     your class using some code similar to:
@discussion           
@discussion           ExecMethod('my_application.my_class.list_all');
@discussion           exit;
*/
 
class generic_list_so extends so_sql
{
	 var $application;
	 var $table;
	 var $class_name;
	 var $id;
	 var $master;
	 var $master_id;
	 
	 function generic_list_so($app, $tab, $cls, $id,  $mas='', $mas_val=0)
	 {
			$this->application=$app;
			$this->table=$tab;
			$this->class_name=$app.'.'.$cls;
			$this->id=$id;
			$this->master=$mas;
			$this->master_id=$mas_val;
			
			$this->so_sql($this->application,$this->table);   // sets up our storage layer using the table 
			$this->empty_on_write = "''";    // what to write in the db, if a column is empty, the default is NULL

	}
	
	function get_master_id()
	{
		return $this->master_id;
	}

	function check_master($content)
	{
			if (($this->master!='') && ($this->master_id==0)) {
				if (isset($content[$this->master]))
					$this->master_id=$content[$this->master];
				else
					$this->master_id=$_GET[$this->master];
			}
			return $this->master_id>0;
	}
	
	
	function list_elts()
	{
		if ($this->master_id>0) {
			return $this->search($this->master.'='.$this->master_id,False);
		}
		else {
			return $this->search('',False);
		}
	}  
	
	function get_data()
	{
		return $this->data;
	}

	function process_content(&$content)
	{
		if ($this->master_id>0) {
		 $content = $content+$this->get_master_array();
		}

		if ($content[$this->id] > 0)      // if we have an id --> read the entry
		{
			$this->read($content);
		}
		$this->data_merge($content);  // merge content with our internal data-array ($this->data)
		
		return $content;
	}  

	function get_master_array()
	{
		return $this->master_id>0?array($this->master => $this->master_id):array();
	}

	function get_id_array($master)
	{
		if ($master) {
			return array($this->id => $this->data[$this->id])+$this->get_master_array();
		}
		else {
			return array($this->id => $this->data[$this->id]);
		}
	}
	
	function get_id()
	{
		return $this->data[$this->id];
	}

	function read_id($content) 
	{
		if ($content[$this->id]>0) {
			$this->read($content); 
			$this->data_merge($content); 
			return $content[$this->id];
		}
		else {
			$this->read(array($this->id=>$content));     
			$content[$this->id];      
		}
		return False;
	}
	
}

