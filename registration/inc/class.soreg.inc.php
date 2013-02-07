<?php
   /**************************************************************************\
   * eGroupWare - Registration                                                *
   * http://www.eGroupWare.org                                                *
   * This application written by Joseph Engo <jengo@phpgroupware.org>         *
   * Modified by Jason Wies (Zone) <zone@users.sourceforge.net>               *
   * Modified by Loic Dachary <loic@gnu.org>                                  *
   * Modified by Pim Snel <pim@egroupware.org>                                *
   * --------------------------------------------                             *
   * Funding for this program was provided by http://www.checkwithmom.com     *
   * --------------------------------------------                             *
   *  This program is free software; you can redistribute it and/or modify it *
   *  under the terms of the GNU General Public License as published by the   *
   *  Free Software Foundation; either version 2 of the License, or (at your  *
   *  option) any later version.                                              *
   \**************************************************************************/

   /* $Id: class.soreg.inc.php 27799 2009-09-09 11:57:40Z ralfbecker $ */

   class soreg
   {
   	  /**
   	   * Expiration timeout for registration app in seconds (7200 = 2h)
   	   */
   	  const EXPIRE_TIMEOUT = 7200;

	  var $reg_id;
	  /**
	   * @var egw_db
	   */
	  var $db;
	  var $reg_table = 'egw_reg_accounts';

	  var $noreply;

	  function soreg()
	  {
		 error_reporting(E_ERROR);
		 global $config;

		 $this->db = clone($GLOBALS['egw']->db);
		 $this->db->set_app('registration');

		 $nobody_name= ( $config['name_nobody'] ?  $config['name_nobody']  : 'No reply' );
		 $nobody_email= ( $config['mail_nobody'] ?  $config['mail_nobody']  : 'noreply@' . $_SERVER['SERVER_NAME'] );
		 $this->noreply = $nobody_name .' <' . $nobody_email . '>';
	  }

	  function account_exists($account_lid)
	  {
		 $this->db->select($this->reg_table,'reg_dla',array(
			'reg_lid' => $account_lid,
		 ),__LINE__,__FILE__);
		 $this->db->next_record();

		 /* Run check method from auth class */
		 $auth =& CreateObject('phpgwapi.auth');
		 if(method_exists($auth,'registration_account_exists'))
		 {
			if($auth->registration_account_exists($account_lid))
			{
			   return true;
			}
		 }

		 if ( $GLOBALS['egw']->accounts->exists($account_lid) || ( $this->db->f(0) && (time()-$this->db->f(0)) < self::EXPIRE_TIMEOUT))
		 {
			return True;
		 }
		 else
		 {
			// To prevent race conditions, reserve the account_lid
			$this->db->insert($this->reg_table,array(
			   'reg_id'   => '',
			   'reg_lid'  => $account_lid,
			   'reg_info' => '',
			   'reg_dla'  => time(),
			),false,__LINE__,__FILE__);
			$GLOBALS['egw']->session->appsession('loginid','registration',$account_lid);
			return False;
		 }
	  }

	  //split up in two functions
	  function sendActivationLink($fields,$send_mail=True,$lang_code)
	  {
		 global $config;
		 $smtp =& CreateObject('phpgwapi.send');

		 // We are not going to use link(), because we may not have the same sessionid by that time
		 // If we do, it will not affect it
		 $url = $GLOBALS['egw_info']['server']['webserver_url']. '/registration/index.php';
		 if ($url{0} == '/') $url = ($_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].$url;

		 $account_lid  = $GLOBALS['egw']->session->appsession('loginid','registration');

		 //an error occured because the session could not be retrieved
		 if(!$account_lid)
		 {
			return false;
		 }

		 $this->reg_id = md5(time() . $account_lid . $GLOBALS['egw']->common->randomstring(32));


		 $this->db->update($this->reg_table,array(
			'reg_id' => $this->reg_id,
			'reg_dla' => time(),
			'reg_info' => base64_encode(serialize($fields))
		 ),array(
			'reg_lid' => $account_lid,
		 ),__LINE__,__FILE__);

		 $GLOBALS['egw']->template->set_file(array(
			'message' => 'confirm_email.tpl'
		 ));

		 $GLOBALS['egw']->template->set_var('Hi',lang('Hi'));
		 $GLOBALS['egw']->template->set_var('message1',lang('This is a confirmation email for your new account.  Click on the following link to finish activating your account. This link will expire in 2 hours.'));

		 $GLOBALS['egw']->template->set_var('message2',lang('If you did not request this account, simply ignore this message.'));

		 if ($fields['n_given'])
		 {
			$GLOBALS['egw']->template->set_var ('firstname', $fields['n_given'] . ' ');
		 }

		 if ($fields['n_family'])
		 {
			$GLOBALS['egw']->template->set_var ('lastname', $fields['n_family']);
		 }

		 $GLOBALS['egw']->template->set_var ('activate_url',$url . '?aid='.$this->reg_id);

		 if ($config['support_email'])
		 {
			$GLOBALS['egw']->template->set_var ('support_email_text', lang ('Report all problems and abuse to'));
			$GLOBALS['egw']->template->set_var ('support_email', $config['support_email']);
		 }

		 $subject = $config['subject_confirm'] ? lang($config['subject_confirm']) : lang('Account registration');

		 if ($send_mail)
		 {
			$ret = $smtp->msg('email',$fields['email'],$subject,$GLOBALS['egw']->template->fp('out','message'),'','','',$this->noreply);
			if ($ret != True)
			{
			   $errors[] =lang("Problem Sending Email:").$smtp->desc;
			   $errors[] =lang("Please Contact the site administrator.");
			   return $errors ;
			}
		 }
		 return $this->reg_id;
	  }

	  // new password
	  function change_password($account_lid, $passwd)
	  {
		 $auth =& CreateObject('phpgwapi.auth');
		 if($auth->change_password(false, $passwd, $GLOBALS['egw']->session->appsession('id','registration'),true))
		 {
			$this->db->delete($this->reg_table,array('reg_lid' => $account_lid),__LINE__,__FILE__);
			return;
		 }
		 else
		 {
			$errors[]=lang('An error occured while updating your password. Please contact the site administrator.');
			return $errors;
		 }
	  }

	  /**
	  * cleanup_old_regs: cleanup regs older then two hours
	  *
	  * @access private
	  * @return void
	  */
	  function _cleanup_old_regs()
	  {
		 $sql='DELETE FROM '.$this->reg_table.' WHERE ('.time().'- reg_dla) > '.(int)self::EXPIRE_TIMEOUT;
		 $this->db->query($sql,__LINE__,__FILE__);
	  }

	  /**
	   * get_dropdownfromtable_values
	   *
	   * @param string $table_name
	   * @param string $valcolumn
	   * @param string $displaycolumn
	   * @access public
	   * @return return array with select values. If query fails return false
	   */
	   function get_dropdownfromtable_values($table_name,$valcolumn,$displaycolumn)
	   {
		  $qry=$this->db->select($table_name,"$valcolumn,$displaycolumn",'',__LINE__,__FILE__);
		  if(!$qry)
		  {
			 return false;
		  }
		  $ret_arr=array();
		  while($this->db->next_record())
		  {
			 $_arr['value'] = $this->db->f($valcolumn);
			 $_arr['display'] = $this->db->f($displaycolumn);
			 $ret_arr[]=$_arr;
		  }
		  return $ret_arr;
	   }

	  function valid_reg($reg_id)
	  {
		 $this->db->select($this->reg_table,'*',array('reg_id' => $reg_id),__LINE__,__FILE__);

		 if (!$this->db->next_record())
		 {
			return false;
		 }

		 //activation string is expired after 2 hours
		 if($this->db->f(3) && (time()-$this->db->f(3)) > self::EXPIRE_TIMEOUT)
		 {
			return false;
		 }

		 return array(
			'reg_id'   => $this->db->f('reg_id'),
			'reg_lid'  => $this->db->f('reg_lid'),
			'reg_info' => $this->db->f('reg_info'),
			'reg_dla'  => $this->db->f('reg_dla'),
			'reg_status'  => $this->db->f('reg_status')
		 );
	  }

	  /**
	  * set_activated change status to activated so Registration can tell the user to login
	  *
	  * @param string $reg_id registration id sent by mail
	  * @note status x means not activated, status a means created and activated
	  * @access public
	  * @return void
	  */
	  function set_activated($reg_id)
	  {
		 $this->db->update($this->reg_table,array(
			'reg_status' => 'a',
			'reg_dla' => time()
		 ),
		 array(
			'reg_id' => $reg_id,
		 ),__LINE__,__FILE__);
	  }

	  function delete_reg_info($reg_id)
	  {
		 $this->db->delete($this->reg_table,array('reg_id' => $reg_id),__LINE__,__FILE__);
	  }

	  function create_account($account_lid,$_reg_info)
	  {
		 global $config, $reg_info;

		 $fields = unserialize(base64_decode($_reg_info));
		 $fields['lid'] = "*$account_lid*";
		 //$fields['lid'] = $account_lid;

		 $reg_info['lid']    = $account_lid;
		 $reg_info['fields'] = $fields;

		 /* Run create account method from auth class if it exists*/
		 $auth =& CreateObject('phpgwapi.auth');
		 if(method_exists($auth,'registration_create_account'))
		 {
			if(!$auth->registration_create_account($account_lid,$fields['passwd'],$fields))
			{
			   echo lang("13: error occured");
			   exit;
			}
		 }

		 $this->_cleanup_old_regs();

		 $GLOBALS['auto_create_acct'] = array(
			'firstname' => $fields['n_given'],
			'lastname'  => $fields['n_family'],
			'email'     => $fields['email'],
		 );

		 // FIXME something with the hooks goes wrong in autoadd!!!!
		 // #$setup_info['felamimail']['hooks']['addaccount']   = 'felamimail.bofelamimail.addAccount';
		 $this->workaround_felami_register_hooks(false);
		 $account_id = $GLOBALS['egw_info']['user']['account_id'] = $GLOBALS['egw']->accounts->auto_add($account_lid,$fields['passwd'],True,False,0,'A');
		 //	 $this->workaround_felami_register_hooks(true);

		 if (!$account_id)
		 {
			return False;
		 }

		 $accounts   =& CreateObject('phpgwapi.accounts',$account_id);
		 $contacts   =& CreateObject('phpgwapi.contacts');

		 $this->db->transaction_begin();

		 $contact_fields = $fields;

		 if ($contact_fields['bday_day'])
		 {
			$contact_fields['bday'] = $contact_fields['bday_month'] . '/' . $contact_fields['bday_day'] . '/' . $contact_fields['bday_year'];
		 }

		 /* There are certain things we don't want stored in contacts */
		 unset ($contact_fields['passwd']);
		 unset ($contact_fields['passwd_confirm']);
		 unset ($contact_fields['bday_day']);
		 unset ($contact_fields['bday_month']);
		 unset ($contact_fields['bday_year']);

		 /* Don't store blank values either */
		 foreach ($contact_fields as $num => $field)
		 {
			if (!$contact_fields[$num])
			{
			   unset ($contact_fields[$num]);
			}
		 }
		 $contacts->add($account_id,$contact_fields,0,'P');

		 $this->db->transaction_commit();

		 $accounts->read_repository();
		 if ($config['trial_accounts'] != "False")
		 {
			$accounts->data['expires'] = time() + ((60 * 60) * ($config['days_until_trial_account_expires'] * 24));
		 }
		 else
		 {
			$accounts->data['expires'] = -1;
		 }
		 $accounts->save_repository();

		 if(@stat(EGW_SERVER_ROOT . '/messenger/inc/hook_registration.inc.php'))
		 {
			include(EGW_SERVER_ROOT . '/messenger/inc/hook_registration.inc.php');
		 }
	  }

	  /**
	  * workaround_register_hooks: fixes a blank screen bug when a autoadd hook for felamimail in processed
	  *
	  * @param mixed $with_felami
	  * @access public
	  * @return void
	  */
	  function workaround_felami_register_hooks($with_felami=false)
	  {
		 if($with_felami)
		 {
			$GLOBALS['egw']->hooks->hooks();
			$GLOBALS['egw']->hooks->register_all_hooks();

			if (method_exists($GLOBALS['egw'],'invalidate_session_cache'))	// egw object in setup is limited
			{
			   $GLOBALS['egw']->invalidate_session_cache();	// in case with cache the egw_info array in the session
			}
			$GLOBALS['egw']->hooks->hooks();
		 }
		 else
		 {
			$GLOBALS['egw']->hooks->hooks();
			$table = 'egw_hooks';
			$this->db->delete($table,array('hook_appname' => 'felamimail'),__LINE__,__FILE__);
			$GLOBALS['egw']->hooks->hooks();
		 }
	  }

	  function lostpassword_send_email($account_lid)
	  {
		 global $config;

		 $url = $GLOBALS['egw_info']['server']['webserver_url']. '/registration/index.php';
		 if ($url{0} == '/') $url = ($_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].$url;

		 $error = '';

		 // Remember md5 string sent by mail
		 $reg_id = md5(time() . $account_lid . $GLOBALS['egw']->common->randomstring(32));
		 $this->db->insert($this->reg_table,array(
			'reg_id'   => $reg_id,
			'reg_lid'  => $account_lid,
			'reg_info' => '',
			'reg_dla'  => time(),
		 ),false,__LINE__,__FILE__);

		 // Send the mail that will allow to change the password
		 $account_id = $GLOBALS['egw']->accounts->name2id($account_lid);

		 if ($account_id)
		 {
			$info = array(
			   'firstname' => $GLOBALS['egw']->accounts->id2name($account_id,'account_firstname'),
			   'lastname'  => $GLOBALS['egw']->accounts->id2name($account_id,'account_lastname'),
			   'email'     => $GLOBALS['egw']->accounts->id2name($account_id,'account_email'),
			);
			$smtp =& CreateObject('phpgwapi.send');

			$GLOBALS['egw']->template->set_file(array(
			   'message' => 'lostpw_email.tpl'
			));

			$GLOBALS['egw']->template->set_var('hi',lang('Hi'));
			$GLOBALS['egw']->template->set_var('message1',lang('You requested to change your password. Please follow the URL below to do so. This URL will expire in two hours. After this delay you should go thru the lost password procedure again.'));

			$GLOBALS['egw']->template->set_var('message2',lang('If you did not request this change, simply ignore this message.'));

			$GLOBALS['egw']->template->set_var('firstname',$info['firstname']);
			$GLOBALS['egw']->template->set_var('lastname',$info['lastname']);
			$GLOBALS['egw']->template->set_var('activate_url',$url . '?pwid=' . $reg_id);

			$subject = $config['subject_lostpw'] ? lang($config['subject_lostpw']) : lang('Account password retrieval');

			$ret = $smtp->msg('email',$info['email'],$subject,$GLOBALS['egw']->template->fp('out','message'),'','','',$this->noreply);
			if ($ret != True)
			{
			   $errors[] =lang("Problem Sending Email:").$smtp->desc;
			   $errors[] =lang("Please Contact the site administrator.");
			   return $errors;
			}
			else
			{
			   return;
			}
		 }
		 else
		 {
			$errors[] = lang("Account %1 record could not be found, report to site administrator", $account_lid);
		 }

		 return $errors;
	  }
   }
