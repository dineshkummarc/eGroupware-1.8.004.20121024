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

   /* $Id: class.module_frame.inc.php 18359 2005-05-26 18:15:31Z ralfbecker $ */

class module_frame extends Module 
{
   function module_frame()
   {
      $this->arguments = array(
         'URL' => array(
            'type' => 'textfield',
            'params' => array('size' => 80),
            'label' => lang('The URL to display')
         ),
         'width' => array(
            'type' => 'textfield',
            'label' => lang('Width')
         ),
         'height' => array(
            'type' => 'textfield',
            'label' => lang('Height')
         ),
         'style' => array(
            'type' => 'textfield',
            'params' => array('size' => 80),
            'label' => lang('CSS styles')
         ),
      );
      $this->title = lang('HTML Frame');
      $this->description = lang('This module lets you show a given URL inside an IFRAME in the page.');
   }

   function get_content(&$arguments,$properties) 
   {
      return '<iframe width="'.htmlspecialchars($arguments['width']).'" height="'.htmlspecialchars($arguments['height']).'" src="'.$arguments['URL'].'"'.($arguments['style'] ? ' style="'.htmlspecialchars($arguments['style']).'"' : '').'></iframe>';
   }
}
