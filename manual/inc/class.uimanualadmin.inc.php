<?php
/**
 * eGroupWare - Online User manual
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package manual
 * @copyright (c) 2004-9 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.uimanualadmin.inc.php 27533 2009-07-23 19:06:46Z ralfbecker $
 */

class uimanualadmin extends wiki_xml
{
	var $public_functions = array(
		'import' => True,
	);
	var $manual_config;

	function __construct()
	{
		CreateObject('manual.uimanual');	// sets the default config

		$this->manual_config = config::read('manual');

		$this->wiki_id = (int) $this->manual_config['manual_wiki_id'];
		parent::__construct($this->wiki_id);	// call the constructor of the class we extend
	}

	function import()
	{
		$url = $this->manual_config['manual_update_url'];
		$from = explode('/',$url);
		$from = count($from) > 2 ? $from[2] : $url;

		if (($langs = $GLOBALS['egw']->translation->get_installed_langs()))
		{
			$langs = implode(',',array_keys($langs));
			$url .= (strpos($url,'?') === False ? '?' : '&').'lang='.$langs;
		}
		// only do an incremental update if the langs are unchanged and we already did an update
		if ($langs == $this->manual_config['manual_langs'] && $this->manual_config['manual_updated'])
		{
			$url .= (strpos($url,'?') === False ? '?' : '&').'modified='.(int) $this->manual_config['manual_updated'];
		}

		$GLOBALS['egw_info']['flags']['app_header'] = lang('manual').' - '.lang('download');
		$GLOBALS['egw']->common->egw_header();
		parse_navbar();
		echo str_pad('<h3>'.lang('Starting import from %1, this might take several minutes (specialy if you start it the first time) ...',
			'<a href="'.$url.'" target="_blank">'.$from.'</a>')."</h3>\n",4096);	// dirty hack to flushes the buffer;
		@set_time_limit(0);

		$status = wiki_xml::import($url,True);

		config::save_value('manual_update',$this->manual_config['manual_updated'] = $status['meta']['exported'],'manual');
		config::save_value('manual_langs',$this->manual_config['manual_langs'] = $langs,'manual');

		echo '<h3>'.lang('%1 manual page(s) added or updated',count($status['imported']))."</h3>\n";

		$GLOBALS['egw']->common->egw_footer();
	}

	function menu($args)
	{
		display_section('manual','manual',array(
			'Site Configuration' => $GLOBALS['egw']->link('/index.php','menuaction=admin.uiconfig.index&appname=manual'),
			'install or update the manual-pages' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'manual.uimanualadmin.import')),
		));
	}

	function config($args)
	{
		$GLOBALS['egw_info']['server']['found_validation_hook'] = True;

		return true;
	}
}

function final_validation($settings)
{
	//echo "final_validation()"; _debug_array($settings);
	if ($settings['manual_allow_anonymous'])
	{
		// check if anon user set and exists
		if (!$settings['manual_anonymous_user'] || !($anon_user = $GLOBALS['egw']->accounts->name2id($settings['manual_anonymous_user'])))
		{
			$GLOBALS['config_error'] = 'Anonymous user does NOT exist!';
		}
		else	// check if anon user has run-rights for manual
		{
			$locations = $GLOBALS['egw']->acl->get_all_location_rights($anon_user,'manual');
			if (!$locations['run'])
			{
				$GLOBALS['config_error'] = 'Anonymous user has NO run-rights for the application!';
			}
		}
	}
}
