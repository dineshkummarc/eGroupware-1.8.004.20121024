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

	 /* $Id: class.module_applet.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

class module_applet extends Module 
{
	 function module_applet()
	 {
			$this->arguments = array(
				 'code' => array(
						'type' => 'textfield',
						'params' => array('size' => 100),
						'label' => lang('Code file')
				 ),
				 'codebase' => array(
						'type' => 'textfield',
						'params' => array('size' => 100),
						'label' => lang('Codebase URL')
				 ),
				 'width' => array(
						'type' => 'textfield',
						'label' => lang('Width')
				 ),
				 'height' => array(
						'type' => 'textfield',
						'label' => lang('Height')
				 )
			);
			$this->title = lang('Applet');
			$this->description = lang('This module lets you include an applet into the page.');
	 }

	 function get_content(&$arguments,$properties) 
	 {
			return '<applet code="'.$arguments['code'].'" codebase="'.$arguments['codebase'].
				'" width="'.$arguments['width'].'" height="'.$arguments['height'].'"></applet>';
	 }
}
