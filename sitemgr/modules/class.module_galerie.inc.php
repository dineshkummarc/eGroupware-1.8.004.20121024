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

	/* $Id: class.module_galerie.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

class module_galerie extends Module
{
	function module_galerie()
	{
		$this->i18n = True;
		$this->arguments = array(
			'imagedirurl' => array(
				'type' => 'textfield', 
				'label' => lang('URL pointing to the image-directory'),
				'params' => array('size' => 50),
			),
			'imagedirpath' => array(
				'type' => 'textfield', 
				'label' => lang('Filesystem path of the image-directory'),
				'params' => array('size' => 50),
			),
			'imagename' => array(
				'type' => 'textfield', 
				'label' => lang('common prefix of the image-name (a number starting with 1 will be appended)')
			),
			'imagetype' => array(
				'type' => 'select', 
				'label' => lang('image type'), 
				'options' => array(
					'jpeg' => 'jpeg',
					'jpg' => 'jpg',
					'gif' => 'gif',
					'png' => 'png'
				)
			),
		);
		$this->title = lang('Galerie');
		$this->post = array(
			'prev' => array(
				'type' => 'submit',
				'value' => "&lt;---"
			),
			'next' => array(
				'type' => 'submit',
				'value' => "---&gt;"
			)
		);
		$this->session = array('filenumber');
		$this->description = lang('A simple picture galery');
	}

	function get_user_interface()
	{
		$this->set_subtext_args();
		return parent::get_user_interface();
	}

	function get_translation_interface($fromblock,$toblock)
	{
		$this->set_subtext_args();
		return parent::get_translation_interface($fromblock,$toblock);
	}
	
	function set_subtext_args()
	{
		$defaults = $this->block->arguments;
		if ($defaults['imagedirpath'] && is_dir($defaults['imagedirpath']))
		{
			$i = 1;
			$this->arguments['subtext'] = array(
				'type' => "array",
			);
			while (file_exists($defaults['imagedirpath'] . SEP . $defaults['imagename'] . $i . '.' . $defaults['imagetype']))
			{
				$this->arguments['subtext'][$i-1] = array(
					'type' => 'textfield',
					'label' => lang('Subtext for image %1',$i) . '<br /><img src="' .
						$defaults['imagedirurl'] . SEP . $defaults['imagename'] . $i . '.' . $defaults['imagetype'] . '" />',
				);
				$i++;
			}
		}
	}

	function set_block(&$block,$produce=False)
	{
		parent::set_block($block,$produce);

		if ($produce)
		{
			if (!$this->block->arguments['filenumber'])
			{
				$this->block->arguments['filenumber'] = 1;
			}
			else
			{
				$this->block->arguments['filenumber'] = (int)$this->block->arguments['filenumber'];
			}
			if ($this->block->arguments['next'])
			{
				$this->block->arguments['filenumber']++;
			}
			elseif ($this->block->arguments['prev'])
			{
				$this->block->arguments['filenumber']--;
			}
			if ($this->block->arguments['filenumber'] < 1 || !file_exists(
					$this->block->arguments['imagedirpath'] . SEP . $this->block->arguments['imagename'] . 
					$this->block->arguments['filenumber'] . '.' . $this->block->arguments['imagetype']
				))
			{
				$this->block->arguments['filenumber'] = 1;
			}
			$prevlink = ($this->block->arguments['filenumber'] > 1) ? $this->build_post_element('prev') : '';
			$nextlink = 
				(file_exists(
					$this->block->arguments['imagedirpath'] . SEP . $this->block->arguments['imagename'] . 
					($this->block->arguments['filenumber'] + 1) . '.' . $this->block->arguments['imagetype']
				)) ?
				$this->build_post_element('next') : 
				'';
			require_once(EGW_INCLUDE_ROOT . SEP . 'sitemgr' . SEP . 'inc' . SEP . 'class.browser_transform.inc.php');
			$this->add_transformer(new browser_transform($prevlink,$nextlink));
		}
	}

	function validate(&$data)
	{
		// remove trailing slash
		foreach(array('imagedirpath','imagedirurl') as $name)
		{
			if (($last_char = substr($data[$name],-1) == '/') || $last_char == '\\')
			{
				$data[$name] = substr($data[$name],0,-1);
			}
		}
		if (!@is_dir($data['imagedirpath']) || !@is_readable($data['imagedirpath']))
		{
			$this->validation_error = lang("Path to image-directory '%1' is not valid or readable by the webserver !!!",$data['imagedirpath']);
			return False;
		}
		return True;
	}

	function get_content(&$arguments,$properties)
	{
		$content .= '<div align="center"><img  hspace="20" align="absmiddle" src="'. $arguments['imagedirurl'] . SEP . $arguments['imagename'] . $arguments['filenumber'] . '.' . $arguments['imagetype'] . '" /></div>';
		$content .= '<div align="center" style="margin:5mm">' . $arguments['subtext'][$arguments['filenumber']-1] . '</div>';
		return $content;
	}
}
