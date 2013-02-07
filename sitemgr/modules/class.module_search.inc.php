<?php
	/**
	* sitemgr - search module
	*
	* @link http://www.egroupware.org
	* @author Jose Luis Gordo Romero <jgordor@gmail.com>
	* @package sitemgr
	* @copyright Jose Luis Gordo Romero <jgordor@gmail.com>
	* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	* @version $Id$
	*/
	
class module_search extends Module
{
	function module_search()
	{
		$this->arguments = array(
			'lang' => array(
				'type'    => 'select', 
				'label'   => lang('Search language options'),
				'options' => array(
					'all'    => lang('All available Languages'),
					'current'=> lang('Use current user selected language'),
				),
			),
			'mode' => array(
				'type'    => 'select', 
				'label'   => lang('Search modes'),
				'options' => array(
					'any'   => lang('Any words'),
					'all'   => lang('All words'),
					'exact' => lang('Exact Phrase'),
				),
			),
			'options' => array(
				'type'    => 'select', 
				'label'   => lang('Options in search result page'),
				'options' => array(
					'adv'       => lang('Anvanced: Show all search modes'),
					'advgoogle' => lang('Advanced plus google: Also show google search'),
					'simple'    => lang('Simple: Only selected mode, no more options displayed'),
				),
			),	
		);
		$this->properties = array();
		$this->title = lang('Search');
		$this->description = lang('This module search throw the content (Page title/description and html content)');
	}

	function get_content(&$arguments,$properties)
	{
		if (!$arguments['show_results'])
		{
			$onblur = "if(this.value=='') this.value='".lang('search')."';";
			$onfocus = "if(this.value=='".lang('search')."') this.value='';";
			$content = '<form name="search_form" method="post" action="'.sitemgr_link2('/index.php').'">' . "\n".
					   '<input type="text" height="16" size="15" name="searchword" value="'.lang('search').'" '.
					   'onblur ="'.$onblur.'" onfocus="'.$onfocus.'">';
			if ($arguments['lang'] == "current")
			{
				$user_lang= $GLOBALS['sitemgr_info']['userlang'];				
			}
			else
			{
				$user_lang = "all";
			}
			$content .= "\n".'<input type="hidden" name="search_lang" value="'.$user_lang.'">';
			$content .= "\n".'<input type="hidden" name="search_mode" value="'.$arguments['mode'].'">';
			$content .= "\n".'<input type="hidden" name="search_options" value="'.$arguments['options'].'">';
			$content .= "\n".'<input type="submit" name="search" value="'.lang('Go').'">';
			$content .= "\n".'</form>'."\n";
		}
		else
		{
			$content = $arguments['search_result']['result'];
		}
		return $content;
	}

}
?>