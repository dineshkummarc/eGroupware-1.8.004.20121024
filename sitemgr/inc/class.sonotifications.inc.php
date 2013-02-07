<?php
	/**
	* sitemgr - notification storage object
	*
	* @link http://www.egroupware.org
	* @author Jose Luis Gordo Romero <jgordor@gmail.com>
	* @package sitemgr
	* @copyright Jose Luis Gordo Romero <jgordor@gmail.com>
	* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	* @version $Id$
	*/

require_once(EGW_INCLUDE_ROOT.'/etemplate/inc/class.so_sql.inc.php');

class sonotifications extends so_sql
{
	/**
	 * Table-name for notifications
	 *
	 * @var string
	 */
	var $notifications_table = 'egw_sitemgr_notifications';
	var $messages_table = 'egw_sitemgr_notify_messages';
	
	function sonotifications()
	{
		$this->so_sql('sitemgr',$this->notifications_table);
	}

	function create_notification($email,$all_langs)
	{
		$values=array(
				'site_id' => $GLOBALS['sitemgr_info']['site_id'],
				'email' => $email
			);
		if (!$all_langs) {
			$values['site_language'] = $GLOBALS['sitemgr_info']['userlang'];
		}
		$this->db->insert($this->notifications_table,$values,False,__LINE__,__FILE__);
		
		return $this->db->get_last_insert_id($this->notifications_table,'notification_id');
	}

	function delete_notifications($email)
	{
		$this->db->delete($this->notifications_table,array('email'=>$email),__LINE__,__FILE__);
	}

	function get_notifications($site_id,$lang)
	{
		$this->db->select($this->notifications_table,array('email'),
			'site_id='.$site_id." AND site_language='".$lang."'",
			__LINE__,__FILE__);

		while($this->db->next_record())
		{
			$result[] = $this->db->f('email');
		}

		$this->db->select($this->notifications_table,array('email'),
			'site_id='.$site_id." AND site_language ='all'",
			__LINE__,__FILE__);

		while($this->db->next_record())
		{
			$result[] = $this->db->f('email');
		}

		return $result;
	}

	function get_message($site_id,$lang,$def_lang)
	{
		$this->db->select($this->messages_table,array('message','subject'),
			'site_id='.$site_id." AND language='".$lang."'",
			__LINE__,__FILE__);

		if($this->db->next_record())
		{
			return $this->db->Query_ID->fields;
		}

		//language not found, try default language
		$this->db->select($this->messages_table,array('message','subject'),
			'site_id='.$site_id." AND language='".$def_lang."'",
			__LINE__,__FILE__);

		if($this->db->next_record())
		{
			return $this->db->Query_ID->fields;
		}
		
		//even default language not found, state the default text
		return False;
	}

	function get_permissions($cat_id) 
	{
		$account=$GLOBALS['egw']->accounts->name2id(
			$GLOBALS['Common_BO']->sites->current_site['anonymous_user']);
			
		if ($account == $GLOBALS['egw_info']['user']['account_id'])
		{
			$acl =& $GLOBALS['egw']->acl;
		}
		else
		{
			$acl =& CreateObject('phpgwapi.acl',$account);
			$acl->read_repository();
		}
		return $acl->get_rights('L'.$cat_id,'sitemgr');
	}
}
	
?>