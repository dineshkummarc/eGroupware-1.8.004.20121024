<?php
/**
 * EGroupware SiteMgr CMS - Site templates aka themes
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @copyright Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.Theme_BO.inc.php 39858 2012-07-18 12:20:54Z ralfbecker $
 */

/**
 * Site templates aka themes
 */
class Theme_BO
{
	/**
	 * List installed templates
	 *
	 * @return array name => template-infos from getTemplateInfos
	 */
	function getAvailableThemes()
	{
		$templates_dir = $GLOBALS['Common_BO']->sites->current_site['site_dir'] . SEP . 'templates' . SEP;
		$result_array=array();

		if (($handle = @opendir($templates_dir)))
		{
			while ($file = readdir($handle))
			{
				if (is_dir($templates_dir . $file) && $file != '..' && $file != '.' && $file != 'CVS' && $file != '.svn')
				{
					if ($info = $this->getThemeInfos($file))
					{
						$result_array[$file] = $info;
					}
				}
			}
			closedir($handle);

			uksort($result_array,'strcasecmp');
		}
		//echo "<p>Theme_BO::getAvailableThemes('$templates_dir')=".print_r(array_keys($result_array),true)."</p>";
		return $result_array ? $result_array : array(array('value'=>'','display'=>lang('No templates found.')));
	}

	/**
	 * Get information ab a template
	 *
	 * @param string $theme template name
	 * @return array
	 */
	function getThemeInfos($theme)
	{
		static $info;	// some caching in the request

		if (is_array($info) && $info['value'] === $theme)
		{
			return $info;
		}
		//echo "<p>Theme_BO::getThemeInfos('$theme')</p>";
		if ($theme[0] == '/')
		{
			$dir = $GLOBALS['egw_info']['server']['files_dir'];
		}
		else
		{
			$dir = $GLOBALS['Common_BO']->sites->current_site['site_dir'] . SEP . 'templates' . SEP;
		}
		if (!is_dir($dir .= $theme))
		{
			return False;
		}
		$info = array(
			'value' => $theme,
			'directory' => $dir,
			'version' => '',
			'author' => '',
			'creationDate' => '',
			'authorUrl' => '',
			'authorUrl2' => '',
			'copyright' => '',
			'license' => '',
		);
		if (file_exists($dir . SEP . 'main.tpl'))
		{
			$info['type'] = 'SiteMgr';
		}
		elseif (file_exists($dir . SEP . 'index.php') && file_exists($xml_details = $dir . SEP . 'templateDetails.xml'))
		{
			$info['type'] = 'Mambo';
		}
		else
		{
			$info = false;
		}
		if ($info)
		{
			if (file_exists($xml_details))
			{
				libxml_use_internal_errors(true);	// suppress warnings: some templates put the Joomla version in the xml version, causing a warning
				if (($details = simplexml_load_file($xml_details)))
				{
					//echo "<pre>".htmlspecialchars(file_get_contents($xml_details))."</pre>\n";
					foreach($details->attributes() as $name => $value)
					{
						if ($name == 'type' && $value != 'template') return false;
						if ($name == 'version')
						{
							$info['joomla-version'] = (string)$value;
							$info['type'] = 'Joomla '.$value;
						}
					}
					foreach($details as $name => $value)
					{
						if(!$value->children())
						{
							if ($name == 'description') $name = 'title';
							$info[$name] = (string)$value;
							if ($name == 'authorUrl')
							{
								if (substr($info['authorUrl'],0,4) != 'http')
								{
									$info['authorUrl'] = 'http://'.$info['authorUrl'];
								}
								static $replace = array(
									'http://www.joomlart.com' => 'http://www.joomlart.com/affiliate/idevaffiliate.php?id=1520'
								);
								$info['authorUrl2'] = strtr($info['authorUrl'],$replace);
								$info['authorUrl'] = parse_url($info['authorUrl'],PHP_URL_HOST);
							}
						}
						elseif($name == 'params')
						{
							//_debug_array($value);
							$info['params'] = array();
							foreach($value as $param)
							{
								$arr = array();
								foreach($param->attributes() as $name => $val)
								{
									$arr[$name] = (string)$val;
								}
								foreach($param as $name => $val)
								{
									if ($name == 'option')
									{
										$arr[$name][(string)$val['value']] = (string)$val;
									}
								}
								$info['params'][] = $arr;
							}
						}
					}
				}
			}
			foreach(array('copyright','license','author','version') as $name)
			{
				$info[$name.'_style'] = empty($info[$name]) ? ' style="display: none"' : '';
			}
			if (file_exists($dir . SEP . 'template_thumbnail.png'))
			{
				$info['thumbnail'] = $GLOBALS['Common_BO']->sites->current_site['site_url']."templates/$theme/template_thumbnail.png";
			}
			if (!isset($info['name']) || !$info['name'])
			{
				$info['name'] = $info['value'];
			}
			// "create" some nicer names
			$info['name'] = ucwords(str_replace('_',' ',$info['name']));
			$info['display'] = $info['name'] . " ($info[type])";
		}
		return $info;
	}
}
