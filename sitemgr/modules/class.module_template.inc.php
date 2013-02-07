<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* Written and (c) by RalfBecker@outdoor-training.de                        *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.module_template.inc.php 33188 2010-11-28 20:27:05Z ralfbecker $ */

	class module_template extends Module
	{
		function module_template()
		{
			$this->arguments = array();
			$this->title = lang('Choose template');
			$this->themes = $GLOBALS['Common_BO']->theme->getAvailableThemes();
			$this->arguments = array(
				'only_allowed' => array(
					'type' => 'checkbox',
					'label' => lang('Show only (in the next field) selected templates'),
				),
				'allowed' => array(
					'type' => 'select',
					'multiple' => True,
					'label' => lang('Select the templates the user is allowed to see'),
					'options' => $this->themes,
					'default' => array_keys($this->themes),		// all
				),
				'show' => array(
					'type' => 'select',
					'label' => lang('Show a template-gallery (thumbnail and informations)'),
					'options' => array(
						1 => lang('No, chooser only (for side-areas)'),
						8 => lang('No, chooser with preview'),
						3 => lang('Gallery plus chooser'),
						7 => lang('Gallery plus chooser and download'),
						2 => lang('Only gallery'),
						6 => lang('Gallery plus download'),
					),
				),
				'zip' => array(
					'type' => 'textfield',
					'label' => lang('Path to zip binary if not in path of the webserver'),
					'default' => 'zip',
				),
			);
			$this->description = lang('This module lets the users choose a template or shows a template gallery');
		}

		function get_content(&$arguments,$properties)
		{
			$show = $arguments['show'] ? $arguments['show'] : 1;
			$download = @$_GET['download'];

			if (($show & 4) && $download && ($arguments['only_allowed'] && in_array($download,$arguments['allowed'])) ||
				(!$arguments['only_allowed'] && isset($this->themes[$download])))
			{
				$zip = @$arguments['zip'] ? $arguments['zip'] : 'zip';
				ob_end_clean();	// discard all previous output
				html::content_header($download.'.zip','application/zip');
				passthru('cd '.$GLOBALS['sitemgr_info']['site_dir'].'/templates; '.$zip.' -qr - '.$download);
				exit;
			}
			if (count($arguments['only_allowed'] ? $arguments['allowed'] : $this->themes) > 1)
			{
				if ($show == 1 || $show == 8)	// only chooser or chooser with preview
				{
					$link = $this->link();
					$link .= (strchr($link,'?') ? '&' : '?') . 'themesel=';
					$content .= '<form name="themeselect" method="post">'."\n";
					if ($show == 8)
					{
						$content .= '<img width="140" height="110" name="preview" src="'.$this->themes[$GLOBALS['sitemgr_info']['themesel']]['thumbnail'].'"><br />'."\n";
						$content .= '<select onChange="document.images.preview.src=\'templates/\'+this.value+\'/template_thumbnail.png\'" name="themesel">'."\n";
					}
					else
					{
						$content .= '<select onChange="location.href=\''.$link.'\'+this.value" name="themesel">'."\n";
					}
					foreach ($this->themes as $name => $info)
					{
						if ($arguments['only_allowed'] && !in_array($name,$arguments['allowed']))
						{
							continue;
						}
						$title = $info['title'] ? ' title="'.$info['title'].'"' : '';
						$content .= '<option ' . ($name == $GLOBALS['sitemgr_info']['themesel'] ? 'selected="1" ' : '') .
							'value="' . $name . '"'.
							($info['title'] ? ' title="'.$info['title'].'"' : '').'>'. $info['name'] . '</option>'."\n";
					}
					$content .= '</select>'."\n";
					if ($show == 8)
					{
						$content .= '<br ><input type="submit" value="'.lang('Select').'" onclick="location.href=\''.$link.'\'+this.form.themesel.value; return false;">'."\n";
					}
					$content .= '</form>'."\n";
				}
				if ($show & 2)	// gallery
				{
					$t = new Template($GLOBALS['egw']->common->get_tpl_dir('sitemgr'),'remove');
					$t->set_file('theme_info','theme_info.tpl');
					$t->set_block('theme_info','info_block');
					$content .= '<table>'."\n";
					foreach ($this->themes as $name => $info)
					{
						if ($arguments['only_allowed'] && !in_array($name,$arguments['allowed']))
						{
							continue;
						}
						if ($further) $content .= '<tr><td colspan="2"><hr style="width: 30%" /></td></tr>'."\n";
						$further = True;

						$info['thumbnail'] = $info['thumbnail'] ?  '<img src="'.$info['thumbnail'].'" border="0" hspace="5"/>' :
							'<div style="text-align:center; font-weight:bold; border:2px ridge black; background-color:white; padding-top:60px; padding-bottom:60px; width:200px;">'.lang('No thumbnail availible').'</div>';
						if ($show & 1)	// chooser
						{
							$info['thumbnail'] = '<a href="'.sitemgr_link(array('themesel'=>$name)+$_GET).'" title="'.
								lang('View template on this site').'">'.$info['thumbnail'].'</a>';
						}

						if ($show & 4)	// download
						{
							$info['license'] .= "</p>\n".'<p><a href="'.
								sitemgr_link(array('download'=>$name)+$_GET).'">'.'<img src="images/zip.gif" border="0" /> '.
								lang('download as ZIP-archiv').'</a>';
						}
						$t->set_var($info);
						$t->set_var(array(
							'lang_author' => lang('Author'),
							'lang_copyright' => lang('Copyright'),
							'lang_license' => lang('License'),
						));
						$content .= $t->parse('out','info_block');
					}
					$content .= '</table>'."\n";
				}
				return $content;
			}
			return lang('No templates found.');
		}
	}
