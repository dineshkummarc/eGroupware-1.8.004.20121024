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

	/* $Id: class.NtfMessages_UI.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

include_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.generic_list_ui.inc.php');
$GLOBALS['egw_info']['flags']['included_classes']['generic_list_ui'] = True;

define("MAX_STR_LENGTH",200);

class NtfMessages_UI extends generic_list_ui
{

	var $site_bo;
	var $site;

	function NtfMessages_UI()
	{
		$common_ui =& CreateObject('sitemgr.Common_UI',True);
		$this->bo=CreateObject('sitemgr.NtfMessages_BO');
		$this->site=False;
		
		$this->generic_list_ui('sitemgr', 'NtfMessages_UI', 'sitemgr.notify_messages', 
			'sitemgr.notify_messages.edit', 'sitemgr.notify_messages.delete');
	}
	
	function get_sel_fields($content,$template)
	{

		if ($template==$this->edit_template) {
			 //is site_id defined?
			if ($this->limited) {
				$data=$this->bo->get_data();
				if (empty($data)) {
					$res=$this->bo->get_new_message_langs();
					if (empty($res)) {
							$this->list_all('',lang('Messages are defined for all site languages.'));
							exit;
					}
				}
				else
					$res=$this->bo->get_site_langs();
			}  
			else $res=array();
			return(
				array(    // the options for our selectboxes for states
						'language' => $res
				)
			);
		}
		return array();
	}

	function get_readonly_fields($content,$template)
	{
		if (($template==$this->edit_template)&& isset($content['language'])) {
			return array('language'=>True);
		}
		else if ($template==$this->list_template) {
			$res=$this->bo->get_new_message_langs();
			$ro=array();
			if (empty($res)) {
				$ro+=array('add'=>True);
			}
			if (!$GLOBALS['Common_BO']->acl->is_admin()){
				$ro+=array('notifications'=>True);
			}
			return $ro;
		}
		return array();
	}
	
	function preprocess_content($content,$template)
	{
		if ($template==$this->list_template) {
			$langs=$this->bo->get_site_langs();
			foreach ((array)$content['entry'] as $key => $value) {
				$content['entry'][$key]['language']=$langs[$content['entry'][$key]['language']];
				if (empty($content['entry'][$key]['cat_id'])) {
					if (strlen($content['entry'][$key]['message'])>MAX_STR_LENGTH) {
						$content['entry'][$key]['message']=str_replace(array("\n","\r"),
							" ",substr($content['entry'][$key]['message'],0,197));
						$i=strrpos($content['entry'][$key]['message']," ");
						if (!($i===False)) {
							$content['entry'][$key]['message']=
								substr($content['entry'][$key]['message'],0,$i);
						}
						$content['entry'][$key]['message']=$content['entry'][$key]['message']."...";
					}
				}
			}
		}
		else if ($template==$this->delete_template) {
			$langs=$this->bo->get_site_langs();
			$content['language']=$langs[$content['language']];
		}
		
		
		return $content;
	}

	function edit_elt($content='',$msg = '')
	{
		if (isset($content['notifications'])) {
			ExecMethod('sitemgr.Notifications_UI.list_all',$content);   
		}
		else {
			parent::edit_elt($content,$msg);
		}
	}
}
