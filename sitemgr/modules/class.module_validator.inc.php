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

	/* $Id: class.module_validator.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

class module_validator extends Module
{
	function module_validator()
	{
		$this->arguments = array(
			'validator_type' => array(
				'type' => 'select',
				'label' => lang('Choose the VALID HTML type (note: not all icons are available):'),
				'options' =>array(
					'1'=>'XHTML 1.1',
					'2'=>'XHTML Basic 1.0',
					'3'=>'XHTML 1.0 Strict',
					'4'=>'XHTML 1.0 Transitional',
					'5'=>'XHTML 1.0 Frameset',
					'6'=>'ISO/IEC 15445:2000 (ISO-HTML)',
					'7'=>'HTML 4.01 Strict',
					'8'=>'HTML 4.01 Transitional',
					'9'=>'HTML 4.01 Frameset',
					'10'=>'HTML 3.2',
					'11'=>'HTML 2.0'
				)
			)
		);
		$this->properties = array();
		$this->title = lang('Validator');
		$this->description = lang('Helps you respect HTML/XHTML standards.');
	}

	function get_content(&$arguments,$properties)
	{
		$icons=array(
					'1'=>'valid-xhtml11',
					'2'=>'valid-xhtml10',
					'3'=>'valid-xhtml10',
					'4'=>'valid-xhtml10',
					'5'=>'valid-xhtml10',
					'6'=>'valid-html401',
					'7'=>'valid-html401',
					'8'=>'valid-html401',
					'9'=>'valid-html40',
					'10'=>'valid-html32',
					'11'=>'valid-html32'
		);
					
		$content = '    <p>'."\n";
		$content .= '      <a href="http://validator.w3.org/check?uri=referer"><img border="0"'."\n";
		$content .= '          src="http://www.w3.org/Icons/'.
			$icons[$arguments["validator_type"]].'"'."\n";
		$content .= '          alt="Valid '.
			$this->arguments['validator_type']['options'].'!" height="31" width="88"></a>'."\n";
		$content .= '    </p>'."\n";

		return $content;
	}
}
