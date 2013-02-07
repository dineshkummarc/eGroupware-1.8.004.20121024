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

	/* $Id: class.module_redirect.inc.php 32031 2010-09-12 12:30:49Z hjtappe $ */

class module_redirect extends Module
{
	function module_redirect()
	{
		$this->arguments = array(
			'URL' => array(
				'type' => 'textfield',
				'params' => array('size' => 50),
				'label' => lang('The URL to redirect to'),
			),
			'timeout' => array(
				'type' => 'textfield',
				'params' => array('size' => 1),
				'default' => 0,
				'label' => lang('Seconds before redirect'),
			),
			'showLink' => array(
				'type' => 'checkbox',
				'label' => lang('Show the link as text?'),
			),
		);
		$this->title = lang('Redirection');
		$this->description = lang('This module lets you define pages that redirect to another URL, if you use it, there should be no other block defined for the page');
	}

	function get_content(&$arguments,$properties)
	{
		$showLink = ((isset($arguments['showLink'])) && ($arguments['showLink'] == true));
		if ($GLOBALS['sitemgr_info']['mode'] != 'Edit')
		{
			if ($arguments['timeout'] == 0)
			{
				ob_end_clean();		// for mos templates, stop the output buffering
				$GLOBALS['egw']->redirect($arguments['URL']);
			}
			else
			{
				/* While it would be possible to use http-equiv=refresh, a javascript is added here
				 * because the meta data has already been sent..
				 */
				$html = '<script type="text/javascript">'."\n";
				$html .= 'window.setTimeout("js_redirect()", '.($arguments['timeout'] * 1000).');'."\n";
				$html .= 'function js_redirect()'."\n";
				$html .= '{'."\n";
				$html .= '	window.location.href="'.$arguments['URL'].'";'."\n";
				$html .= '}'."\n";
				$html .= '</script>'."\n";
				if (! $showLink)
				{
					$html .= '<noscript>'."\n";
				}
				$html .= "<div>".lang('The URL to redirect to').': <a href="'.$arguments['URL'].'">'.$arguments['URL'].'</a></div>';
				if (! $showLink)
				{
					$html .= '</noscript>'."\n";
				}
				return $html;
			}
		}
		else
		{
			$html = "";
			if (! $showLink)
			{
				$html .= "(";
			}
			$html .= lang('The URL to redirect to').': <a href="'.$arguments['URL'].'">'.$arguments['URL'].'</a>';
			if (! $showLink)
			{
				$html .= ")";
			}
			return $html;
		}
	}
}
