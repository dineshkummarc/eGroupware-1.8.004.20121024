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

	/* $Id: class.Notifications_UI.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

include_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.generic_list_ui.inc.php');
$GLOBALS['egw_info']['flags']['included_classes']['generic_list_ui'] = True;

class Notifications_UI extends generic_list_ui
{

	var $site_bo;
	var $site;

	function Notifications_UI()
	{
		$common_ui =& CreateObject('sitemgr.Common_UI',True);
		$this->bo=CreateObject("sitemgr.Notifications_BO");
		$this->site=False;
		
		$this->generic_list_ui('sitemgr', 'Notifications_UI', 'sitemgr.notifications', 
			'sitemgr.notifications.edit', 'sitemgr.notifications.delete');
	}
	
	function get_sel_fields($content,$template)
	{

		if ($template==$this->edit_template) {
			 //is site_id defined?
			if ($this->limited) {
				$res=array('all'=>lang('All languages'))+$this->bo->get_site_langs();
			}  
			return(
				array(    // the options for our selectboxes for states
						'site_language' => $res
				)
			);
		}
		return array();
	}

	function preprocess_content($content,$template)
	{
		$langs=$this->bo->get_site_langs();
		$content['multilingual']=(count($langs)==1);  

		if ($template==$this->list_template) {
			foreach ((array)$content['entry'] as $key => $value) {
				if (empty($content['entry'][$key]['cat_id'])) {
					$content['entry'][$key]['cat_title']=lang('all categories');
				}
				if (strcmp($content['entry'][$key]['site_language'],'all')==0) {
					$content['entry'][$key]['site_language']=lang('all languages');
				}
				$content['entry'][$key]['multilingual']=$content['multilingual'];
			}
		}
		
		return $content;
	}


	function edit_elt($content='',$msg = '')
	{
		if (isset($content['messages'])) {
			ExecMethod('sitemgr.NtfMessages_UI.list_all',$content);   
		}
		else {
			parent::edit_elt($content,$msg);
		}
	}
}
