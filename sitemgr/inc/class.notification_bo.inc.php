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

	/* $Id: class.notification_bo.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */

	class notification_bo
	{
	 
		var $so;

		function notification_bo()
		{
			$this->so =& CreateObject("sitemgr.notification_so");
		}

		function create_notification($email,$all_langs)
		{
			return $this->so->create_notification($email,$all_langs);
		}

		function delete_notifications($email)
		{
			$this->so->delete_notifications($email);
		}

		function get_notifications($site_id,$lang)
		{
			return $this->so->get_notifications($site_id,$lang);
		}

		function translate($data,$transl=False,$lang='') {
			if (!$transl) {
				$transl=CreateObject('phpgwapi.translation',array('sitemgr',$lang));
			}
			$msg='';
			foreach($data as $val) {
				if (is_array($val)) {
					if ($val['translate'])
						$msg.=$transl->translate($val['text']);
					else
						$msg.=implode('',$val);
				}
				else $msg.=$val;
			}
			return $msg;
		}
		
		function prepare_url($extravars = '')
		{
			// Change http://xyz/index.php?page_name=page1 to
			// http://xyz/page1/ if the htaccess stuff is enabled
			if (!is_array($extravars))
			{
				parse_str($extravars,$extravarsnew);
				$extravars = $extravarsnew;
			}

			if ($extravars['page_name'] != '' && $GLOBALS['sitemgr_info']['htaccess_rewrite'])
			{
				$url = '/'.$extravars['page_name'];
				unset($extravars['page_name']);
			}

			// In certain instances (wouldn't it be better to fix these instances? MT)
			// a url may look like this: 'http://xyz//hi.php' or
			// like this: '//index.php?blahblahblah' -- so the code below will remove
			// the inappropriate double slashes and leave appropriate ones
			$url = $GLOBALS['Common_BO']->sites->current_site['site_url'] . $url;
			if (!strpos($url,"://")) {
				$url="http://".$GLOBALS['egw_info']['server']['hostname'].$url;
			}

			$url = substr(preg_replace('/([^:])\\/\\//','\1/','s'.$url),1);

			// build the extravars string from a array
			$vars = array();
			foreach($extravars as $key => $value)
			{
				$vars[] = urlencode($key).'='.urlencode($value);
			}
			return $url . (count($vars) ? '?'.implode('&',$vars) : '');
		}
		
		function prepare_message($site_id,$lang,$def_lang,$url,$data)
		{
			$msg = $this->so->get_message($site_id,$lang,$def_lang);

//echo __FILE__.__LINE__."<PRE>";
//print_r($msg);
//print_r($GLOBALS['egw']->translation);
//echo "</PRE>";

			if (!$msg) { // the message template is not in the database; use the default message.
				if ($lang==$GLOBALS['lang']) {
					$msg['message']=lang('The website has changed. Too see the change, follow this URL: $URL.');
					$msg['subject']=lang('Automatically generated notification');
				}
				else {
					$transl=CreateObject('phpgwapi.translation',array('sitemgr',$lang));
					$msg['message']=$transl->translate('The website has changed. Too see the change, follow this URL: $URL .');
					$msg['subject']=$transl->translate('Automatically generated notification');
					if (is_array($data))
						$data=$this->translate($data,$transl);
				}
			}
			
			if (is_array($data))
				$data=$this->translate($data,False,$lang);
				
			$url=$this->prepare_url($url);
			
			return str_replace(array('$URL','$DATA','$SITE'),array($url,$data,
				$GLOBALS['Common_BO']->sites->current_site['site_name_'.$lang]),$msg);
		}
		
		function shall_send($cat_id,$state) 
		{
			//only send a message if the anonymous user may read the category and
			//if the version is either published or preunpublished.
			
			if (is_array($state)){
				$Shall=False;
				foreach($state as $versionid => $s)
				{
					$Shall = $Shall || 
						($s==SITEMGR_STATE_PUBLISH || $s==SITEMGR_STATE_PREUNPUBLISH);
				}
//        if (!$Shall) {
//          echo __FILE__.__LINE__."\None of the states is visible. \nCat:|$cat_id|, Sta:|";print_r($state);echo "|<BR>";
//        }
				return $Shall;
			}
			else if ($state<0) {
				$cat_so=CreateObject("sitemgr.Categories_SO");
				if (!$cat_so->isactive($cat_id,array(SITEMGR_STATE_PUBLISH,SITEMGR_STATE_PREUNPUBLISH))) {
//          echo __FILE__.__LINE__."\nCategory invisible. \nCat:|$cat_id|, Sta:|$state|<BR>";
 
					return False;
				}
			}
			else if ($state!=SITEMGR_STATE_PUBLISH && $state!=SITEMGR_STATE_PREUNPUBLISH) {
//         echo __FILE__.__LINE__."\nChange invisible. \nCat:|$cat_id|, Sta:|$state|<BR>";
			
				 return False;
			}
			
			return $this->so->get_permissions($cat_id) & EGW_ACL_READ;
		}

		function notify_users($site_id,$cat_id,$state,$lang,$def_lang,$url,$data="N/A") 
		{
			if (!$this->shall_send($cat_id,$state)) {
//        echo __FILE__.__LINE__."\nMessages not sent due to permission errors. \nCat:|$cat_id|, Sta:|$state|<BR>";
				return;
			}
				
			$addresses=$this->get_notifications($site_id,$lang);

			if (count($addresses)) {
				$msg=$this->prepare_message($site_id,$lang,$def_lang,$url,$data);

				$smtp=CreateObject("phpgwapi.send");
				$smtp->Subject=$msg['subject'];
				$smtp->Body=$msg['message'];
				$smtp->WordWrap=$msg['WordWrap'];
				
				foreach($addresses as $value) {
					$smtp->AddAddress($value);
//echo __FILE__.__LINE__."<PRE>\n";        
//print_r($smtp->Subject);
//echo "\n========\n";
//print_r($smtp->Body);
//echo "\n</PRE>";
					if (!$smtp->Send()) {
//echo __FILE__.__LINE__.$value.": $smtp->ErrorInfo <BR/>";             
					}
					$smtp->ClearAddresses();
				}
			}
		}
	}

