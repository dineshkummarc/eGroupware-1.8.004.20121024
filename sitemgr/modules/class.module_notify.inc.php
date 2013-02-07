<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.module_notify.inc.php 24357 2007-08-07 17:04:51Z jgordor $ */

	class module_notify extends Module
	{
		function module_notify()
		{
			$this->post = array(
				'email' => array('type' => 'textfield'),
				'unsubscribe' => array('type'=>'checkbox'),
				'all_langs' => array('type'=>'checkbox')
			);

			$this->properties = array();
			$this->arguments = array();
			$this->title = lang('Update Notification');
			$this->description = lang('Enter the email to be notified about changes of the website.');
		}

		function prepare(&$data)
		{
			if (isset($data['email']))
			{
				$bo=CreateObject("sitemgr.bonotifications");
				if (isset($data['unsubscribe'])) {
					$bo->delete_notifications($data['email']);
					return $data['email']." has been successfully unsubscribed from notifications about changes of this site.";
				}
				$bo->create_notification($data['email'],isset($data['all_langs']));

				return $data['email']." has been successfully subscribed to notifications about changes of this site.";
			}
			return FALSE;
		}
		
		function get_content(&$arguments,$properties)
		{

			$content=$this->prepare($arguments);
			if (!$content) {
				$content = '<form method="post">'."\n";
				$content .= lang('Enter the email to be notified about changes of the website:').'<br />'."\n";
				$content .= $this->build_post_element('email','')."\n";
				$content .= '<div style="margin-top: 5px;">'."\n";
				$content .= $this->build_post_element('unsubscribe','').lang('Unsubscribe')."\n";  

				//allow subscribing to all languages only if there are more of them
				if (strpos($GLOBALS['sitemgr_info']['site_languages'],',')!=FALSE) {
					$content .= "<br/>".$this->build_post_element('all_langs','').lang('All languages')."\n";  
				}
				$content .= '</div>'."\n";
				$content .= '</form>'."\n";
			}
			
			return $content;
		}
	}
