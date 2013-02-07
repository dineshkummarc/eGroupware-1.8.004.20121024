<?php
   /**************************************************************************\
   * eGroupWare - Registration                                                *
   * http://www.egroupware.org                                                *
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

   /* $Id: class.uireg.inc.php 26229 2008-10-16 06:01:21Z ralfbecker $ */

   class uireg
   {
	  var $template;
	  var $bomanagefields;
	  var $fields;
	  var $bo;
	  var $lang_code;
	  var $reg_id;
	  var $public_functions = array(
		 'step1_ChooseLangAndLoginName'   => True,
		 'step1_validate'   => True,
		 'step2_passAndInfo'   => True,
		 'step2_validate'   => True,
		 //'welcome_screen' => True,
		 //'ready_to_activate' => True,
		 'step4_activate_account'=> True,
		 'lostid_step1_ask_email'	=> True,
		 'lostid_step2_validate_send'	=> True,
		 'lostpw_step1_ask_login' => True,
		 'lostpw_step2_validate_send' => True,
		 'lostpw_step3_validate_reg_id' => True,
		 'lostpw_step4_changepass' => True,
		 'lostpw_step5_validate_newpass' => True,
		 'tos'     => True
	  );

	  function uireg()
	  {
		 $this->template = $GLOBALS['egw']->template;
		 $this->bo =& CreateObject ('registration.boreg');
		 $this->bomanagefields =& CreateObject ('registration.bomanagefields');
		 $this->fields = $this->bomanagefields->get_field_list ();

		 $this->set_lang_code();

		 $var = Array (
			'website_title' => $GLOBALS['egw_info']['server']['site_title'] .'[Registration]',
			'img_icon' => substr($GLOBALS['egw_info']['server']['login_logo_file'],0,4) == 'http' ?
			$GLOBALS['egw_info']['server']['login_logo_file'] :
			$GLOBALS['egw']->common->image('phpgwapi',$GLOBALS['egw_info']['server']['login_logo_file'] ?
			$GLOBALS['egw_info']['server']['login_logo_file'] : 'logo'),
			'logo_url' => $GLOBALS['egw_info']['server']['login_logo_url']?$GLOBALS['egw_info']['server']['login_logo_url']:'http://www.eGroupWare.org',
			'logo_title' => $GLOBALS['egw_info']['server']['login_logo_title']?$GLOBALS['egw_info']['server']['login_logo_title']:'www.eGroupWare.org',
		 ) ;
		 $this->template->set_var($var) ;

		 $_reg_id=$_GET['reg_id']?$_GET['reg_id']:$_POST['reg_id'];
		 $this->reg_id=trim($_reg_id?$_reg_id:'');
	  }

	  function set_lang_code($code='')
	  {
		 if($code)
		 {
			$this->lang_code=$code;
		 }
		 elseif($_GET['lang_code'])
		 {
			$this->lang_code=$_GET['lang_code'];
		 }
		 else
		 {
			$this->lang_code=$_POST['lang_code'];
		 }

		 if (!$this->lang_code)
		 {
			$this->lang_code=$GLOBALS['default_lang'];
		 }

		 if(!$this->lang_code)
		 {
			$this->lang_code='en';
		 }

		 $GLOBALS['egw_info']['user']['preferences']['common']['lang'] = $this->lang_code;
		 $GLOBALS['egw']->translation->init();
	  }

	  function set_header_footer_blocks()
	  {
		 $this->template->set_file(array(
			'_layout' => 'layout.tpl'
		 ));
		 $this->template->set_block('_layout','header');
		 $this->template->set_block('_layout','footer');
	  }

	  function header($head_subj='')
	  {
		 $GLOBALS['egw']->common->egw_header();
		 $this->set_header_footer_blocks();
		 if($head_subj)
		 {
			$this->template->set_var('lang_header',$head_subj);
		 }
		 else
		 {
			$this->template->set_var('lang_header',$GLOBALS['egw_info']['server']['site_title'].' - '.lang('Account registration'));
		 }

		 $this->template->pfp('out','header');
	  }

	  function footer()
	  {
		 $this->template->pfp('out','footer');
	  }

	  function create_option_string($selected,$values)
	  {
		 while (is_array($values) && list($var,$value) = each($values))
		 {
			$s .= '<option value="' . $var . '"';
			if ("$var" == "$selected")	// the "'s are necessary to force a string-compare
			{
			   $s .= ' selected';
			}
			$s .= '>' . $value . '</option>';
		 }
		 return $s;
	  }

	  function step1_ChooseLangAndLoginName($errors = '',$r_reg = '',$o_reg = '')
	  {
		 global $config;

		 //$this->bo->so->_cleanup_old_regs();
               if($config['enable_registration']!="True")
               {
			$this->header();
			echo '<br/><div align="center">';
			   echo lang('On-line registration is not activated. Please contact the site administrator for more information about registration.');
			   echo '</div><br/>';
			$this->footer();
			exit;
		 }

		 if ($errors && $config['username_is'] == 'http')
		 {
			$vars[message]=	lang('An error occured. Please contact our technical support and let them know.');
			$this->simple_screen ('error_general.tpl', $GLOBALS['egw']->common->error_list ($errors),$vars);
		 }
		 /* Note that check_select_username () may not return */
		 $select_username = $this->bo->check_select_username ();
		 if (!$select_username || is_string ($select_username))
		 {
			$vars[message]=	lang('An error occured. Please contact our technical support and let them know.');
			$this->simple_screen ('error_general.tpl', $GLOBALS['egw']->common->error_list (array ($select_username)),$vars);
		 }

		 $this->header();
		 $this->template->set_file(array(
			'_loginid_select' => 'loginid_select.tpl'
		 ));
		 $this->template->set_block('_loginid_select','form');

		 if ($errors)
		 {
			$this->template->set_var('errors',$GLOBALS['egw']->common->error_list($errors));
		 }

		 // temporary set all available langcodes
		 $langs = $GLOBALS['egw']->translation->get_installed_langs();
		 $comeback_code=$this->lang_code;
		 //echo $comeback_code;
		 foreach ($langs as $key => $name)	// if we have a translation use it
		 {
			unset($choosetrans);
			$this->set_lang_code($key);

			$choosetrans=lang('Choose your language');

			if($choosetrans!='Choose your language*' && $choosetrans!=$prevstring)
			{
			   if(!$italic)
			   {

				  $lang_choose_string .='<div style="margin:2px;">'.$choosetrans.'</div>';
				  $italic=true;
			   }
			   else
			   {
				  $lang_choose_string .='<div style="margin:2px;font-style: italic">'.$choosetrans.'</div>';
				  unset($italic);
			   }
			   $prevstring=$choosetrans;
			}

			$trans = lang($name);
			if ($trans != $name . '*')
			{
			   $langs[$key] = $trans;
			}
		 }
		 $this->set_lang_code($comeback_code);

		 $this->template->set_var('title',lang('Choose Language'));
		 $this->template->set_var('illustration',$GLOBALS['egw']->common->image('registration','screen0_language'));

		 $this->template->set_var('lang_choose_language',$lang_choose_string);
		 $this->template->set_var('lang_change',lang('change'));

		 $selected_lang=($this->lang_code?$this->lang_code:$GLOBALS[default_lang]);

		 $s .= $this->create_option_string($selected_lang,$langs);
		 $this->template->set_var('selectbox_languages','<select name="lang_code" onChange="this.form.langchanged.value=\'true\';this.form.submit()">'.$s.'</select>');

		 $this->template->set_var('form_action',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.step1_validate'));
		 $this->template->set_var('lang_username',lang('Username'));
		 $this->template->set_var('lang_submit',lang('Submit'));

		 $this->template->pfp('out','form');

		 $this->footer();
	  }

	  function step1_validate()
	  {
		 global $config;
		 $r_reg=$_REQUEST['r_reg'];

		 if($config['conv7bit'])
		 {
			$r_reg['loginid']=$this->bo->to7bit($r_reg['loginid']);
		 }

		 if($_POST['langchanged']=='true')
		 {
			$this->step1_ChooseLangAndLoginName('',$r_reg,$o_reg);
			exit;
               }


		 if (! $r_reg['loginid'])
		 {
			$errors[] = lang('You must enter a username');
		 }

		 if (! is_array($errors) && $this->bo->so->account_exists($r_reg['loginid']))
		 {
			$errors[] = lang('Sorry, that username is already taken.');
		 }

		 if (is_array($errors))
		 {
			$this->step1_ChooseLangAndLoginName($errors,$r_reg,$o_reg);
		 }
		 else
		 {
                        $GLOBALS['egw']->session->appsession('loginid','registration',$r_reg['loginid']);

                        #for cybro invite application
                        if ($GLOBALS['egw']->session->appsession('userinfo', 'registration'))
                        {
                                $GLOBALS['egw']->hooks->single('change_username', 'invite');

                                $this->step2_passAndInfo('', $GLOBALS['egw']->session->appsession('userinfo', 'registration'));
                                exit;
                        }

			$this->step2_passAndInfo();
		 }
	  }

	  function step2_passAndInfo($errors = '',$r_reg = '',$o_reg = '',$missing_fields='')
	  {
               global $config;
               if ($_GET['regcode'])
               {
                       $GLOBALS['egw']->hooks->single('checkcode', 'invite');

			$userinfo = $GLOBALS['egw']->session->appsession('userinfo', 'registration');
			if (empty($userinfo) || !is_array($userinfo))
			{
                               $warning_message = lang('Your registration code is missing or incorrect.');
                       }
			elseif ($GLOBALS['egw']->session->appsession('time', 'registration') == 'false')
			{
                               $warning_massage = lang('Your login is not available. Time is expired');
			}

			if ($warning_message)
			{
                               $this->header();
                               echo '<br/><div align="center">';
                               echo $warning_message;
                               echo '</div><br/>';
                               $this->footer();
                               exit;
			}
			$r_reg = $userinfo;
               }

		 $show_password_prompt = True;
		 $select_password = $this->bo->check_select_password ();
		 if (is_string ($select_password))
		 {
			$vars[message]=	lang('An error occured. Please contact our technical support and let them know.');
			$this->simple_screen ('error_general.tpl', $select_password,$vars);
		 }
		 elseif (!$select_password)
		 {
			$show_password_prompt = False;
		 }

		 if(!$GLOBALS['egw']->session->appsession('loginid','registration'))
		 {
			$vars['message'] =lang('An unknown error occured. <a href="%1">Please try registering again.</a>',$GLOBALS['egw']->link('/registration/index.php'));
			$this->simple_screen('error_general.tpl', '',$vars);
			exit;
		 }

		 $this->header();
		 $this->template->set_file(array(
			'_personal_info' => 'personal_info.tpl'
		 ));
		 $this->template->set_block('_personal_info','form');
		 $this->template->set_var('lang_code',$this->lang_code);
		 $this->template->set_var('lang_username',lang('Username'));

		 $this->template->set_var('value_username',$GLOBALS['egw']->session->appsession('loginid','registration'));

		 if ($errors)
		 {
			$this->template->set_var('errors',$GLOBALS['egw']->common->error_list($errors));
		 }

		 if ($missing_fields)
		 {
			while (list(,$field) = each($missing_fields))
			{
			   $missing[$field] = True;
			   $this->template->set_var('missing_' . $field,'<font color="#CC0000">*</font>');
			}
		 }

		 if (is_array($r_reg))
		 {
			while (list($name,$value) = each($r_reg))
			{
			   $post_values[$name] = $value;
			   $this->template->set_var('value_' . $name,$value);
			}
		 }

		 if (is_array($o_reg))
		 {
			while (list($name,$value) = each($o_reg))
			{
			   $post_values[$name] = $value;
			   $this->template->set_var('value_' . $name,$value);
			}
		 }

                $this->template->set_var('change_login_lid', $GLOBALS['egw']->link('/registration/index.php', 'menuaction=registration.uireg.step1_ChooseLangAndLoginName'));
                $this->template->set_var('lang_change_login_lid', lang('Change login'));
		 $this->template->set_var('form_action',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.step2_validate'));
		 $this->template->set_var('lang_password',lang('Password'));
		 $this->template->set_var('lang_reenter_password',lang('Re-enter password'));
		 $this->template->set_var('lang_submit',lang('Submit'));

		 if (!$show_password_prompt)
		 {
			$this->template->set_block ('form', 'password', 'empty');
		 }

		 $this->template->set_block ('form', 'other_fields_proto', 'other_fields_list');

		 reset ($this->fields);
		 while (list ($num, $field_info) = each ($this->fields))
		 {
			$input_field = $this->get_input_field ($field_info, $post_values);
			$var = array (
			   'missing_indicator' => $missing[$field_info['field_name']] ? '<font color="#CC0000">*</font>' : '',
			   'bold_start'  => $field_info['field_required'] == 'Y' ? '<b>' : '',
				  'bold_end'    => $field_info['field_required'] == 'Y' ? '</b>' : '',
			   'lang_displayed_text' => lang ($field_info['field_text']),
			   'input_field' => $input_field
			);

			$this->template->set_var ($var);

			$this->template->parse ('other_fields_list', 'other_fields_proto', True);
		 }

		 if ($config['display_tos'])
		 {
			$this->template->set_var('tos_link',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.tos'));
			$this->template->set_var('lang_tos_agree',lang('I have read the terms and conditions and agree by them.'));
			if ($r_reg['tos_agree'])
			{
			   $this->template->set_var('value_tos_agree', 'checked');
			}
		 }
		 else
		 {
			$this->template->set_block ('form', 'tos', 'blank');
		 }

		 $this->template->pfp('out','form');
		 $this->footer();
	  }

	  function step2_validate()
	  {
		 $r_reg=$_POST['r_reg'];
		 $o_reg=$_POST['o_reg'];

		 $lang_to_pass=$r_reg['lang_code'];
		 $this->set_lang_code($lang_to_pass);

		 $valid_arr=$this->bo->register_validate_fields($lang_to_pass);

		 if($valid_arr[errors])
		 {
			$this->step2_passAndInfo($valid_arr[errors],$valid_arr[r_reg],$valid_arr[o_reg],$valid_arr[missing_fields]);
		 }
		 elseif(!$valid_arr['reg_id'])
		 {
			$vars[message]=	lang('An error occured. Please remove your cookies and try again.');
			$this->simple_screen ('error_general.tpl', $GLOBALS['egw']->common->error_list ($errors),$vars);
		 }
		 else
		 {
			$this->step3_ready_to_activate($valid_arr['reg_id']);
		 }
	  }

	  function step3_ready_to_activate($reg_id='')
	  {
		 global $config;

		 if ($config['activate_account'] == 'email')
		 {
			$var[lang_email_confirm]=lang('We have sent a confirmation email to your email address. You must click on the link within 2 hours. If you do not, it may take a few days until your loginid will become available again.');

			$this->simple_screen('confirm_email_sent.tpl','',$var);
		 }
		 else
		 {
			/* ($config['activate_account'] == 'immediately') */
			$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/registration/index.php','aid='.$reg_id));
		 }
	  }

	  function step4_activate_account()
	  {
		 $reg_info = $this->bo->so->valid_reg($this->reg_id);
		 if (! is_array($reg_info))
		 {
			$vars['error_msg']=lang('Sorry, we are having a problem activating your account. Note that links sent by e-mail are only valid during two hours. If you think this delay was expired, just retry. Otherwise, please contact the site administrator.');

			$this->simple_screen('error_confirm.tpl','',$vars);

			return False;
		 }

		 $_fields = unserialize(base64_decode($reg_info['reg_info']));
		 $this->set_lang_code($_fields['lang_code']);

		 if($reg_info['reg_status']!='a')
		 {
			$this->bo->so->create_account($reg_info['reg_lid'],$reg_info['reg_info']);
			$GLOBALS['egw']->hooks->single('after_registration', 'invite');
		 }

		 $this->bo->so->set_activated($this->reg_id);

		 setcookie('sessionid');
		 setcookie('kp3');
		 setcookie('domain');
		 $this->step5_welcome_screen();
	  }

	  function step5_welcome_screen()
	  {
		 $this->header();

		 $login_url = $GLOBALS['egw']->link('/login.php');

		 $message = lang('Your account is now active!'). ' <a href="%s" style="font-weight:bold;">'. lang('Click to log into your account'). '</a>';
		 $message = sprintf($message,$login_url) ;

		 $this->template->set_file(array(
			'screen' => 'welcome_message.tpl'
		 ));

		 $this->template->set_var('lang_your_account_is_active',$message);

		 $this->template->pfp('out','screen');
		 $this->footer();
		 exit;
	  }


	  function lostpw_step1_ask_login($errors = '',$r_reg = '')
	  {
		 $this->header();
		 $this->template->set_file(array(
			'_lostpw_select' => 'lostpw_select.tpl'
		 ));
		 $this->template->set_block('_lostpw_select','form');

		 if ($errors)
		 {
			$this->template->set_var('errors',$GLOBALS['egw']->common->error_list($errors));
		 }

		 $this->template->set_var('lang_lost_password',lang('Lost Password')) ;
		 $this->template->set_var('form_action',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.lostpw_step2_validate_send'));
		 $this->template->set_var('lang_explain',lang('After you enter your username, instructions to change your password will be sent to you by e-mail to the address you gave when you registered.'));
		 $this->template->set_var('lang_username',lang('Username'));
		 $this->template->set_var('lang_submit',lang('Submit'));

		 $this->template->pfp('out','form');
		 $this->footer();
	  }

	  function lostpw_step2_validate_send()
	  {
		 $r_reg = $_REQUEST['r_reg'] ;
		 $errors=$this->bo->lostpassword_validate_login($r_reg);

		 if(is_array($errors))
		 {
			$this->lostpw_step1_ask_login($errors,$r_reg);
			exit;
		 }
		 else
		 {
			$errors =$this->bo->so->lostpassword_send_email($r_reg['loginid']);
			if(is_array($errors))
			{
			   $this->simple_screen ('error_general.tpl', $GLOBALS['egw']->common->error_list ($errors),$vars);
			}
			else
			{
			   $vars[message]=lang('We have sent a mail with instructions to change your password. You should follow the included link within two hours. If you do not, you will have to go to the lost password screen again.');
			   $this->simple_screen('confirm_email_sent_lostpw.tpl','',$vars);
			}
		 }
	  }

	  function lostpw_step3_validate_reg_id()
	  {
		 $reg_info = $this->bo->so->valid_reg($this->reg_id);

		 if (! is_array($reg_info))
		 {
			$vars['error_msg']=lang('Sorry, we are having a problem retrieving the information needed for changing your password. Note that links sent by e-mail are only valid during two hours. If you think this delay was expired, just retry. Otherwise, please contact the site administrator.');
			$this->simple_screen('error_confirm.tpl','',$vars);
			return exit;
		 }

		 $account_id = $GLOBALS['egw']->accounts->name2id($reg_info['reg_lid']);

		 $GLOBALS['egw']->session->appsession('loginid','registration',$reg_info['reg_lid']);
		 $GLOBALS['egw']->session->appsession('id','registration',$account_id);

		 $this->lostpw_step4_changepass('', '', $reg_info['reg_lid']);
		 return True;
	  }

	  function lostpw_step4_changepass($errors = '',$r_reg = '',$lid = '')
	  {
		 $this->header();
		 $this->template->set_file(array(
			'_lostpw_change' => 'lostpw_change.tpl'
		 ));
		 $this->template->set_block('_lostpw_change','form');

		 if ($errors)
		 {
			$this->template->set_var('errors',$GLOBALS['egw']->common->error_list($errors));
		 }

		 $this->template->set_var('form_action',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.lostpw_step5_validate_newpass'));
		 $this->template->set_var('value_username', $lid);
		 $this->template->set_var('lang_changepassword',lang("Change password for user"));
		 $this->template->set_var('lang_enter_password',lang('Enter your new password'));
		 $this->template->set_var('lang_reenter_password',lang('Re-enter your password'));
		 $this->template->set_var('lang_change',lang('Change'));

		 $this->template->pfp('out','form');
		 $this->footer();
	  }

	  function lostpw_step5_validate_newpass()
	  {
		 $r_reg = $_REQUEST['r_reg'] ;
		 $lid = $GLOBALS['egw']->session->appsession('loginid','registration');
		 $errors= $this->bo->lostpw_validate_newpass($r_reg, $lid);

		 if(! is_array($errors))
		 {
			$errors=$this->bo->so->change_password($lid, $r_reg['passwd']);
		 }


		 if (is_array($errors))
		 {
			$this->lostpw_step4_changepass($errors, $r_reg, $lid);
			exit;
		 }
		 else
		 {
			$this->header();
			$this->template->set_file(array(
			   'screen' => 'lostpw_changed.tpl'
			));

			$message=lang('Your password was changed.').' <a href="'.$GLOBALS['egw']->link('/login.php').'">'. lang('You can go back to the login page').'</a>';
			$this->template->set_var('message',$message);

			$this->template->pfp('out','screen');
			$this->footer();

		 }
	  }

	  function lostid_step1_ask_email($errors = '',$r_reg = '')
	  {
		 $this->header();
		 $this->template->set_file(array(
			'_lostid_select' => 'lostid_select.tpl'
		 ));
		 $this->template->set_block('_lostid_select','form');

		 if ($errors)
		 {
			$this->template->set_var('errors',$GLOBALS['egw']->common->error_list($errors));
		 }
		 $this->template->set_var('lang_lost_user_id',lang('Lost User Id')) ;
		 $this->template->set_var('form_action',$GLOBALS['egw']->link('/registration/index.php','menuaction=registration.uireg.lostid_step2_validate_send&lang_code='.$_GET['lang_code']));
		 $this->template->set_var('lang_explain',lang('After you enter your email address, the user accounts associated with this email address will be mailed to that address.'));
		 $this->template->set_var('lang_email',lang('email address'));
		 $this->template->set_var('lang_submit',lang('Submit'));

		 $this->template->pfp('out','form');
		 $this->footer();
	  }

	  function lostid_step2_validate_send()
	  {
		 $r_reg = $_REQUEST['r_reg'] ;

		 //check if email is associated
		 $errors=$this->bo->lostid_check_account_and_email($r_reg['email']);
		 if(is_array($errors))
		 {
			$this->lostid_step1_ask_email($errors);
			exit;
		 }

		 //try to send email
		 $errors = $this->bo->email_sent_lostid($r_reg['email']);
		 if(is_array($errors))
		 {
			$this->lostid_step1_ask_email($errors);
			exit;
		 }

		 $vars[message]=sprintf(lang("We have sent a mail to your email account: %s with your lost user ids."),$r_reg['email']).' <a href="'.$GLOBALS['egw']->link('/login.php').'">'. lang('You can go back to the login page').'</a>';
		 $this->simple_screen('confirm_email_sent_lostpw.tpl','',$vars);
	  }

	  function get_input_field ($field_info, $post_values)
	  {
		 $this->tplsav2 = CreateObject('phpgwapi.tplsavant2');

		 $r_regs=$_POST['r_reg'];
		 $o_regs=$_POST['o_reg'];

		 $post_value = $post_values[$field_info['field_name']];

		 $name = $field_info['field_name'];
		 $values = explode (",", $field_info['field_values']);
		 $required = $field_info['field_required'];
		 $type = $field_info['field_type'];

		 if (!$type)
		 {
			$type = 'text';
		 }

		 if ($type == 'gender')
		 {
			$values = array (
			   lang('Male'),
			   lang('Female')
			);

			$type = 'dropdown';
		 }

		 if ($required == 'Y')
		 {
			$a = 'r_reg';
		 }
		 else
		 {
			$a = 'o_reg';
		 }

		 if ($type == 'text' || $type == 'email' || $type == 'first_name' ||
		 $type == 'last_name' || $type == 'address' || $type == 'city' ||
		 $type == 'zip' || $type == 'phone')
		 {
			$rstring = '<input type=text name="' . $a . '[' . $name . ']" value="' . $post_value . '">';
		 }

		 if ($type == 'textarea')
		 {
			$rstring = '<textarea name="' . $a . '[' . $name . ']" value="' . $post_value . '" cols="40" rows="5">' . $post_value . '</textarea>';
		 }

		 if ($type == 'dropdown')
		 {
			if (!is_array ($values))
			{
			   $rstring = lang("Error: Dropdown list '%1' has no values",$name);
			}
			else
			{
			   $this->tplsav2->values = $values;
			   $this->tplsav2->inputname = $a . '[' . $name . ']';
			   $this->tplsav2->post_value = $post_value;
			   $rstring = $this->tplsav2->fetch('dropdown.tpl.php');
			}
		 }

		 if ($type == 'dropdownfromtable')
		 {
			if (!is_array($values) || count($values)<>3)
			{
			   $rstring = lang("Error: Dropdown From Table '%1' is not correctly configured",$name);
			}
			else
			{
			   $this->tplsav2->dropdown_arr=$this->bo->so->get_dropdownfromtable_values(trim($values[0]),trim($values[1]),trim($values[2]));
			   //_debug_array($this->tplsav2->dropdown_arr);

			   //$this->tplsav2->values = $values;
			   $this->tplsav2->inputname = $a . '[' . $name . ']';
			   $this->tplsav2->post_value = $post_value;
			   $rstring = $this->tplsav2->fetch('dropdownfromtable.tpl.php');
			}
		 }

		 if ($type == 'checkbox')
		 {
			unset ($checked);
			if ($post_value)
			$checked = "checked";

			$rstring = '<input type=checkbox name="' . $a . '[' . $name . ']" ' . $checked . '>';
		 }

		 if ($type == 'birthday' || $type == 'state' || $type == 'country')
		 {
			$sbox =& CreateObject ('registration.sbox');
		 }

		 if ($type == 'state')
		 {
			$rstring = $sbox->list_states ($a . '[' . $name . ']', $post_value);
		 }

		 if ($type == 'country')
		 {
			$vselected=$post_value;
			$aname=$a . '[' . $name . ']';

			$str = '<select name="'.$aname.'">'."\n"
			   . ' <option value="  "'.($vselected == '  '?' selected':'').'>'.lang('Select One').'</option>'."\n";
			   reset($sbox->country_array);
			   while(list($vkey,$vvalue) = each($sbox->country_array))
			   {
				  $str .= ' <option value="'.$vkey.'"'.($vselected == $vkey?' selected':'') . '>'.$vvalue.'</option>'."\n";
			   }
			   $str .= '</select>'."\n";

			$rstring = $str;
		 }

		 if ($type == 'birthday')
		 {
			$rstring = $sbox->getmonthtext ($a . '[' . $name . '_month]', $post_values[$name . '_month']);
			$rstring .= $sbox->getdays ($a . '[' . $name . '_day]', $post_values[$name . '_day']);
			$rstring .= $sbox->getyears ($a . '[' . $name . '_year]', $post_values[$name . '_year'], 1900, date ('Y') + 1);
		 }

		 return $rstring;
	  }


	  function simple_screen($template_file, $text = '',$vars=false,$head_subj='')
	  {
		 //$this->setLang();
		 $this->header($head_subj);

		 $this->template->set_file(array(
			'screen' => $template_file
		 ));

		 if ($text)
		 {
			$this->template->set_var ('extra_text', $text);
		 }

		 if(is_array($vars))
		 {
			$this->template->set_var ($vars);
		 }

		 $this->template->pfp('out','screen');
		 $this->footer();
		 exit;
	  }

	  function tos()
	  {
		 global $config;
		 $var[tos_text]= $config['tos_text'];
		 $var[lang_close_window]= '<a href="javascript:self.close()">'.lang('Close Window').'</a>';

		 $this->simple_screen('tos.tpl','',$var,lang('Terms of Service'));
	  }

	  /**
	  * hooks to build projectmanager's sidebox-menu plus the admin and preferences sections
	  *
	  * @param string/array $args hook args
	  */
	  function all_hooks($args)
	  {
		 $appname = 'registration';
		 $location = is_array($args) ? $args['location'] : $args;

		 if ($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'preferences')
		 {

			$title = $appname;
			$file = Array(
			   'Site Configuration'	=> $GLOBALS['egw']->link('/index.php', 'menuaction=admin.uiconfig.index&appname=' . $appname),
			   'Manage Fields'      => $GLOBALS['egw']->link ('/index.php', 'menuaction=' . $appname . '.uimanagefields.admin')
			);

			if ($location == 'admin')
			{
			   display_section($appname,$file);
			}
			else
			{
			   display_sidebox($appname,lang('Admin'),$file);
			}
		 }
	  }
   }
