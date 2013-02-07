<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* -------------------------------------------------                        *
	* This was originaly a phpNuke block, the amazon_id has been changed       *
	* Copyright (C) 2004 RalfBecker@outdoor-training.de                        *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.module_amazon.inc.php 15190 2004-05-08 14:21:37Z ralfbecker $ */

class module_amazon extends Module
{
	function module_amazon()
	{
		$this->arguments = array(
			'amazon_id' => array(
				'type' => 'textfield',
				'label' => lang('Own Amazon partner-id or empty to donate to the eGroupWare project')
			),
			'amazon_server' => array(
				'type' => 'textfield',
				'label' => lang('Amazon server (without www), eg. amazon.com')
			),
			'search' => array(
				'type' => 'checkbox',
				'label' => lang('Show searchbox')
			),
			'asins' => array(
				'type' => 'textarea',
				'params' => array('rows' => 10,'cols' => 20),
				'label' => lang("ASIN[=title] pairs (title is optional)")
			),
		);
		//$this->post = array('asin' => array('type' => 'textfield'));
		//$this->session = array('asin');
		$this->properties = array();
		$this->title = lang('Amazon');
		$this->description = lang('Use this module for displaying book ads for the amazon web site');
	}

	function get_content(&$arguments,$properties)
	{
		$asins = explode("\n",$arguments['asins']);
		if (!count($asins) || empty($asins[0]))
		{
			$asins = array('1565926102=Programming PHP');
		}
		//echo "<p>module_amazon('$arguments[asins]') = ".print_r($asins,True)."</p>";

		mt_srand((double)microtime()*1000000);
		$asin = count($asins) > 1 ? $asins[mt_rand(0, count($asins)-1)] : $asins[0];
		list($asin,$title) = explode('=',$asin);

		$amazon_id = $arguments['amazon_id'] ? $arguments['amazon_id'] : "egroupware-21";
		$server = $arguments['amazon_server'] ? $arguments['amazon_server'] : 'amazon.com';
		$buy_at = ($title ? ': ' : '') . lang('Buy at %1',$server);
		$base_url = ($_SERVER['HTTPS'] ? 'https://ssl-' : 'http://')."images.amazon.com/images/P";
		$content = "<div align=\"center\"><a href=\"http://www.$server/exec/obidos/ASIN/$asin/$amazon_id\" target=\"_blank\">" .
			"<img src=\"$base_url/$asin.03.MZZZZZZZ.jpg\" border=\"0\" title=\"$title$buy_at\"><br>$title</a>\n";

		if ($arguments['search'])
		{
			$content .= "<p><form method=\"get\" action=\"http://www.$server/exec/obidos/external-search?tag=$amazon_id\" target=\"_blank\">".
				'<INPUT type="text" name="keyword" size="10" value=""><input type="submit" value="'.lang('Search').'"></form>';
		}
		else
		{
			$content .= '<br>';
		}
		$content .= '</div>';
		return $content;
	}
}
