<?php
   /**************************************************************************\
   * eGroupWare - Registration                                                *
   * http://www.eGroupWare.org                                                *
   * This application written by Joseph Engo <jengo@phprgoupware.org>         *
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

   /* $Id: class.boreg.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */

   class boreg
   {
	  var $template;
	  var $bomanagefields;
	  var $fields;
	  var $so;
	  var $lang_code;
	  var $reg_id;

	  function boreg()
	  {
		 $this->so =& CreateObject ('registration.soreg');
		 $this->bomanagefields =& CreateObject ('registration.bomanagefields');
		 $this->fields = $this->bomanagefields->get_field_list ();

		 $_reg_id=$_GET['reg_id']?$_GET['reg_id']:$_POST['reg_id'];
		 $this->reg_id=$_reg_id?$_reg_id:'';
	  }

	  function to7bit($text,$from_enc) 
	  {
		 $text = mb_convert_encoding($text,'HTML-ENTITIES',$from_enc);
		 $text = preg_replace( array( '/&szlig;/','/&(..)lig;/', '/&([aouAOU])uml;/','/&(.)[^;]*;/'), array('ss',"$1","$1".'e',"$1"),
		 $text);
		 return $text;
	  }  

	  function register_validate_fields()
	  {
		 global $config, $r_reg, $o_reg;

		 $r_reg=$_POST['r_reg'];
		 $o_reg=$_POST['o_reg'];

		 if ($config['password_is'] == 'http')
		 {
			$r_reg['passwd'] = $r_reg['passwd_confirm'] = $_SERVER['PHP_AUTH_PW'];
		 }

		 if (($config['display_tos']) && ! $r_reg['tos_agree'])
		 {
			$missing_fields[] = 'tos_agree';
		 }

		 while (list($name,$value) = each($r_reg))
		 {
			if (! $value)
			{
			   $missing_fields[] = $name;
			}
			$fields[$name] = $value;
		 }

		 reset($r_reg);

		 if ($r_reg['adr_one_countryname'] == '  ')
		 {
			$missing_fields[] = 'adr_one_countryname';
		 }

		 if ($r_reg['passwd'] != $r_reg['passwd_confirm'])
		 {
			$errors[] = lang("The passwords you entered don't match");
			$missing_fields[] = 'passwd';
			$missing_fields[] = 'passwd_confirm';
		 }

		 reset ($this->fields);
		 while (list (,$field_info) = each ($this->fields))
		 {
			$name = $field_info['field_name'];
			$text = $field_info['field_text'];
			$values = explode (',', $field_info['field_values']);
			$required = $field_info['field_required'];
			$type = $field_info['field_type'];

			if ($required == 'Y')
			{
			   $a = $r_reg;
			}
			else
			{
			   $a = $o_reg;
			}

			$post_value = $a[$name];

			if ($type == 'email')
			{
			   if ($post_value && (strpos($post_value,'@') === false || ! preg_match ('/'."\.".'/', $post_value)))
			   {
				  if ($required == 'Y')
				  {
					 $errors[] = lang('You have entered an invalid email address');
					 $missing_fields[] = $name;
				  }
			   }
			}

			if ($type == 'birthday')
			{
			   if (!checkdate ((int) $a[$name . '_month'], (int) $a[$name . '_day'], (int) $a[$name . '_year']))
			   {
				  if ($required == 'Y')
				  {
					 $errors[] = lang ('You have entered an invalid birthday');
					 $missing_fields[] = $name;
				  }
			   }
			   else
			   {
				  $a[$name] = sprintf ('%s/%s/%s', $a[$name . '_month'], $a[$name . '_day'], $a[$name . '_year']);
			   }
			}

			if ($type == 'dropdown')
			{
			   if ($post_value)
			   {
				  foreach($values as $value)
				  {
					 if (trim($value) == trim($post_value))
					 {
						$ok = 1;
					 }
				  }

				  if (!$ok)
				  {
					 $errors[] = lang ('You specified a value for %1 that is not a choice',$text);

					 $missing_fields[] = $name;
				  }
			   }
			}
		 }

		 while (is_array($o_reg) && list($name,$value) = each($o_reg))
		 {
			$fields[$name] = $value;
		 }

		 if (is_array ($o_reg))
		 {
			reset($o_reg);
		 }

		 if (is_array($missing_fields))
		 {
			$errors[] = lang('You must fill in all of the required fields');
		 }

		 if (is_array($errors))
		 {
			$ret_arr['errors']=$errors;
			$ret_arr['r_reg']=$r_reg;
			$ret_arr['o_reg']=$o_reg;
			$ret_arr['missing_fields']=$missing_fields;
		 }
		 else
		 {
			$ret_arr['reg_id'] = $this->so->sendActivationLink($fields,$config['activate_account'] == 'email',$lang_to_pass);
		 }

		 return $ret_arr;
	  }

	  function lostpassword_validate_login($r_reg)
	  {
		 if (! $r_reg['loginid'])
		 {
			$errors[] = lang('You must enter a username');
		 }
		 if (! is_array($errors) && !$GLOBALS['egw']->accounts->exists($r_reg['loginid']))
		 {
			$errors[] = lang('Sorry, that username does not exist.');
		 }
		 if (is_array($errors))
		 {
			return $errors;
		 }
	  }

	  function lostpw_validate_newpass($r_reg,$lid)
	  {
		 if(!$lid) 
		 {
			$error[] = lang('Wrong session');
		 }

		 if ($r_reg['passwd'] != $r_reg['passwd_2'])
		 {
			$errors[] = lang('The two passwords are not the same');
		 }

		 if (! $r_reg['passwd'])
		 {
			$errors[] = lang('You must enter a password');
		 }

		 if (is_array($errors))
		 {
			return $errors;
		 } 
		 else
		 {
			return false;
		 }
	  }

	  function check_select_username ()
	  {
		 global $config;

		 if ($config['username_is'] == 'choice')
		 {
			return True;
		 }
		 elseif ($config['username_is'] == 'http')
		 {
			if (!$_SERVER['PHP_AUTH_USER'])
			{
			   return "HTTP username is not set";
			}
			else
			{
			   $GLOBALS['egw']->redirect($GLOBALS['egw']->link ('/registration/index.php', 'menuaction=registration.uireg.step1_validate&r_reg[loginid]=' . $_SERVER['PHP_AUTH_USER']));
			}
		 }

		 return True;
	  }

	  function lostid_check_account_and_email($email)
	  {
		 if (!$email)
		 {
			$errors[] = lang('You must enter an email address');
		 }
		 if (! is_array($errors))
		 {
			$userids=$GLOBALS['egw']->accounts->name2id($email,'account_email');
			if (!$userids || count($userids) == 0)
			{
			   $errors[] = lang('Sorry, no account exists for '.$email);
			}
		 }

		 if(! is_array($errors))
		 {
			return false; 
		 }
		 else
		 {
			return $errors;
		 }
	  }

	  function email_sent_lostid($email)
	  {
		 global $config;

		 //$tplsav2= CreateObject('phpgwapi.tplsavant2');

		 $smtp =& CreateObject('phpgwapi.send');

		 $GLOBALS['egw']->template->set_file(array('message' => 'lostid_email.tpl'));

		 $account_id = $GLOBALS['egw']->accounts->name2id($email,'account_email');
		 $info = array(
			'firstname' => $GLOBALS['egw']->accounts->id2name($account_id,'account_firstname'),
			'lastname' => $GLOBALS['egw']->accounts->id2name($account_id,'account_lastname'),
			'email' => $GLOBALS['egw']->accounts->id2name($account_id,'account_email'),
		 );
		 if (is_null($info['firstname']))
		 $info['firstname'] = lang('[Unknown first name]') ;

		 if (is_null($info['lastname']))
		 $info['lastname'] = lang('[Unknown last name]') ;

		 $GLOBALS['egw']->template->set_var('hi',lang('Hi'));
		 $GLOBALS['egw']->template->set_var('firstname',$info['firstname']);
		 $GLOBALS['egw']->template->set_var('lastname',$info['lastname']);
		 $GLOBALS['egw']->template->set_var('message1', lang('lost_user_id_message'));

		 // Send the mail that tell the user id
		 $GLOBALS['egw']->template->set_var('lostids',$GLOBALS['egw']->accounts->id2name($account_id));

		 $subject = $config['subject_lostid'] ? lang($config['subject_lostpid']) : lang('Lost user account retrieval');

		 $ret = $smtp->msg('email',$info['email'],$subject,$GLOBALS['egw']->template->fp('out','message'),'','','',$this->so->noreply);

		 if ($ret != true)
		 {
			$errors[] =lang("Problem Sending Email:").$smtp->desc;
			$errors[] =lang("Please Contact the site administrator.");
		 }

		 if(is_array($errors))
		 {
			return $errors;
		 }
	  }

	  function check_select_password ()
	  {
		 global $config;

		 if ($config['password_is'] == 'choice')
		 {
			return True;
		 }
		 elseif ($config['password_is'] == 'http')
		 {
			if (!$_SERVER['PHP_AUTH_PW'])
			{
			   return "HTTP password is not set";
			}
			else
			{
			   return False;
			}
		 }

		 return True;
	  }

	  function check_challenge()
	  {
		 return True;
	  }
   }
