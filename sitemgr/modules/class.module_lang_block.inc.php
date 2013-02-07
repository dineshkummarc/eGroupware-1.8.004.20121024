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

/* $Id: class.module_lang_block.inc.php 26314 2008-11-06 13:57:55Z ralfbecker $ */

class module_lang_block extends Module
{
	function module_lang_block()
	{
		$this->arguments = array(
			'layout' => array(
                                    'type' => 'select',
                                    'label' => lang('Select layout for lang selection'),
                                    'options' => array(
                                            'plain' => lang('Plain selectbox'),
                                            'flags' => lang('Flag symbols').' (images/*.gif)',
                                            'flags.png' => lang('Flag symbols').' (images/*.png)',
				),
			),
		);

		$this->properties = array();
		$this->title = lang('Choose language');
		$this->description = lang('This module lets users choose language');
	}

	function get_content(&$arguments,$properties)
	{
		if ($GLOBALS['sitemgr_info']['sitelanguages'])
		{
			if (substr($arguments['layout'],0,5) == 'flags')
			{
				$content = '
					<div id="langsel_flags">
				 		<ul>';
				foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
                                    {
					$content .= '
							<li><a href="#." onClick="location.href=\''. str_replace('&','&amp;',$this->link(array(),array('lang'=>$lang))) . 
								'\' ">'. '<img src="images/'. $lang. 
								($arguments['layout'] == 'flags' ? '.gif' : '.png').
								'" class="langsel_flags_image">'. '</a></li>';
				}
				$content .= '
						</ul>
					</div>';
			}
			else
			{
				$content = '<form name="langselect" method="post" action="">';
				$content .= '<select onChange="location.href=this.value" name="language">';
				foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
				{
					$selected='';
					if ($lang == $GLOBALS['sitemgr_info']['userlang'])
					{
						$selected = 'selected="1" ';
					}
					$content .= '<option ' . $selected . 'value="' . str_replace('&','&amp;',$this->link(array(),array('lang'=>$lang))) . '">'.$GLOBALS['Common_BO']->getlangname($lang) . '</option>';
				}
				$content .= '</select>';
				$content .= '</form>';
			}

			return $content;
		}
		return lang('No sitelanguages configured');
	}
}
