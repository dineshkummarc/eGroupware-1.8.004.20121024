<?php
/**
 * EGroupware SiteMgr CMS
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.Common_UI.inc.php 33188 2010-11-28 20:27:05Z ralfbecker $
 */

/**
 * User interface for site configuration
 */
class Common_UI
{
	/**
	 * Instance of template class
	 *
	 * @var Template
	 */
	public $t;
	/**
	 * Reference to ACL object of Common_BO
	 *
	 * @var ACL_BO
	 */
	public $acl;
	/**
	 * Reference to themes object of Common_BO
	 *
	 * @var Theme_BO
	 */
	public $theme;
	/**
	 * Reference to ACL pages of Common_BO
	 *
	 * @var Pages_BO
	 */
	public $pages_bo;
	/**
	 * Reference to cats object of Common_BO
	 *
	 * @var Categories_BO
	 */
	public $cat_bo;
	/**
	 * Reference to cats sites of Common_BO
	 *
	 * @var Sites_BO
	 */
	public $sites;

	public $do_sites_exist, $menu;

	/**
	 * Functions callable via menuaction $_GET parameter
	 *
	 * @var array name => true
	 */
	public $public_functions = array(
		'DisplayPrefs' => True,
		'DisplayMenu' => True,
		'DisplayIFrame' => True,
		'templatePrefs' => true,
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$GLOBALS['Common_BO'] = CreateObject('sitemgr.Common_BO');
		$this->do_sites_exist = $GLOBALS['Common_BO']->sites->set_currentsite(False,'Administration');
		$this->t = new Template($GLOBALS['egw']->common->get_tpl_dir('sitemgr'));
		$this->acl = $GLOBALS['Common_BO']->acl;
		$this->theme = $GLOBALS['Common_BO']->theme;
		$this->pages_bo = $GLOBALS['Common_BO']->pages;
		$this->cat_bo = $GLOBALS['Common_BO']->cats;
		$this->sites = $GLOBALS['Common_BO']->sites;
	}

	/**
	 * Edit Joomla 1.5+ template preferences
	 *
	 * @param array $content=null
	 * @param string $msg=''
	 */
	public function templatePrefs(array $content=null,$msg='')
	{
		if (!is_array($content))
		{
			if (!($content = $this->theme->getThemeInfos($_GET['template'])))
			{
				$msg = lang('Theme "%1" not found!');
				$readonlys['button[save]'] = $readonlys['button[apply]'] = true;
			}
			else
			{
				if (!$content['params'] || !is_array($content['params']))
				{
					$msg = lang('Template has no parameters!');
					$content['tabs'] = 'css';
					$content['params'] = array();
				}
				self::store_params_as_cf($content['params'],$content['directory']);
				$content += $this->get_params($content['value'],$content['params'],$content['directory']);

				$content['custom_css_help'] = lang('Custom CSS will be included in each page as last style-sheet in the header.').'<br />'.
					lang('You can use %1 to fetch the above defined URL of your logo.','$$logo_url$$');
			}
		}
		elseif ($content['button'])
		{
			list($button) = each($content['button']);
			unset($content['button']);

			switch($button)
			{
				case 'save':
				case 'apply':
					$this->set_params($content['value'],$content);
					$msg = lang('Parameters saved.');
					break;
			}
			if ($button == 'save')
			{
				echo "<html>\n<head>\n<script>\nwindow.close();\n</script>\n</head>\n<body>\n</body>\n</html>\n";
				common::egw_exit();
			}
		}
		$content['msg'] = $msg;

		$GLOBALS['egw_info']['flags']['app_header'] = lang('Edit template preferences for %1',$content['name']);
		$tpl = new etemplate('sitemgr.templateprefs');
		$tpl->sitemgr = false;	// otherwise etemplate thinks it runs inside sitemgr (because of Common_BO)!
		$tpl->exec('sitemgr.Common_UI.templatePrefs',$content,$sel_options,$readonlys,$content,2);
	}

	/**
	 * Store parameter in sitemgr configuration
	 *
	 * @param string $template template-name
	 * @param array $content param names prefixed with '#' like custom fields
	 * @param int $site_id=null
	 */
	public function set_params($template,array $content,$site_id=null)
	{
		if (is_null($site_id))
		{
			$site = $this->sites->current_site;
		}
		else
		{
			$site = $this->sites->read($site_id);
		}
		require_once(EGW_SERVER_ROOT.'/sitemgr/sitemgr-site/inc/class.joomla_ui.inc.php');
		$jparam = new JParameter('','',$template);
		$jparam->loadINI($site['params_ini'],JParameter::ALL_NAMESPACES);

		$arr = array();
		foreach($content as $name => $value)
		{
			if ($name[0] == '#')
			{
				$jparam->set(substr($name,1),$value);
			}
		}
		return $this->sites->so->update_logo_css_params($site['site_id'],array(
			'logo_url' => $content['logo_url'],
			'custom_css' => $content['custom_css'] == Common_BO::CUSTOM_CSS_DEFAULT ? null : $content['custom_css'],
			'params_ini' => $jparam->getINI(JParameter::ALL_NAMESPACES),
		));
	}

	/**
	 * Read parameters from ini file and sitemgr configuration
	 *
	 * @param string $template template-name
	 * @param array $params
	 * @param string $template_dir directory of template for ini-file
	 * @param int $site_id=null
	 * @return array params with names prefixed with '#' like custom fields
	 */
	public function get_params($template,array $params,$template_dir,$site_id=null)
	{
		require_once(EGW_SERVER_ROOT.'/sitemgr/sitemgr-site/inc/class.joomla_ui.inc.php');
		$jparam = new JParameter(@file_get_contents($template_dir.SEP.'params.ini'),'',$template);
		if (is_null($site_id))
		{
			$site = $this->sites->current_site;
		}
		else
		{
			$site = $this->sites->read($site_id);
		}
		if (!empty($site['params_ini']))
		{
			$jparam->loadINI($site['params_ini'],JParameter::ALL_NAMESPACES);
		}

		$arr = array();
		foreach($jparam->toArray() as $name => $value)
		{
			$arr['#'.$name] = $value;
		}
		foreach($params as $param)
		{
			if ($param['name'] && !isset($arr['#'.$param['name']]))
			{
				$arr['#'.$param['name']] = $param['default'];
			}
		}

		// query custom css and logo-url
		$arr['custom_css'] = $GLOBALS['Common_BO']->get_custom_css(false,$arr['logo_url']);

		return $arr;
	}

	/**
	 * Store supported Joomla template parameters as EGroupware custom fields,
	 * to be able to use eTemplate custom field widget to edit them
	 *
	 * @link http://docs.joomla.org/Standard_parameter_types
	 * @param array $params
	 * @param string $template_dir
	 */
	static function store_params_as_cf(array $params,$template_dir)
	{
		$cfs = array();
		foreach($params as $param)
		{
			switch($param['type'])
			{
				case 'japaramhelper':
					if ($param['name'] != '@title')
					{
						//error_log("Not yet implemented Joomla template parameter type '$param[type]' with name '$param[name] --> ignored!");
						break;
					}
					$param['default'] = $param['label'];
					// fall through
				case 'spacer':
					$cfs[] = array(
						'type' => 'label',
						'label' => str_replace('_',' ',$param['default']),
						'order' => ++$order,
					);
					break;
				case 'filelist':
				case 'folderlist':
				case 'imagelist':
					$param['option'] = array();
					if (!isset($param['hide_default']) || !$param['hide_default'])
					{
						$param['option'][''] = '- '.lang('Use default').' -';
					}
					if (!isset($param['hide_none']) || !$param['hide_none'])
					{
						$param['option']['-1'] = '- '.lang('None selected').' -';
					}
					$dir = $template_dir.SEP.implode('/',array_slice(explode('/',$param['directory']),2));
					foreach(scandir($dir) as $file)
					{
						if ($file[0] == '.' || $file == 'index.html') continue;
						if (is_dir($dir.'/'.$file) != ($param['type'] == 'folderlist')) continue;
						if ($param['type'] == 'imagelist' && !preg_match('/\.(jpe?g|png|gif|ico|bmp)$/i',$file)) continue;
						if (isset($param['filter']) && !preg_match('/'.$param['filter'].'/',$file)) continue;
						if (isset($param['exclude']) && preg_match('/'.$param['exclude'].'/',$file)) continue;
						if (isset($param['stripext']) && $param['stripext'] && preg_match('/^(.*)\.[^.]+$/',$file,$matches))
						{
							$file = $matches[1];
						}
						$param['option'][$file] = $file;
					}
					// fall through
				case 'list':
				case 'radio':
					$cfs[$param['name']] = array(
						'type' => /*$param['type'] == 'radio' ? 'radio' :*/ 'select',	// radio looks ugly currently
						'label' => $param['label'],
						'order' => ++$order,
						'values' => $param['option'],
					);
					break;
				case 'text':
				case 'textarea':
				case 'password':	// password is NOT implemented as cf
					$cfs[$param['name']] = array(
						'type' => 'text',
						'label' => $param['label'],
						'order' => ++$order,
						'len' => $param['type'] == 'textarea' ? $param['cols'] : $param['size'],
						'rows' => $param['rows'],
					);
					break;
				case 'integer':
					$options = array();
					if ((int)$param['first'] <= (int)$param['last'])
					{
						for ($n = (int)$params['first']; $n <= (int)$params['last']; $n += (int)$params['step'] ? (int)$params['step'] : 1)
						{
							$options[(string)$n] = (string)$n;
						}
					}
					$cfs[$param['name']] = array(
						'type' => 'select',
						'label' => $param['label'],
						'order' => ++$order,
						'values' => $options,
					);
					break;
				case 'calendar':
					$cfs[$param['name']] = array(
						'type' => 'date',
						'label' => $param['label'],
						'order' => ++$order,
						'format' => empty($param['format']) ? 'Y-m-d' : str_replace('%','',$param['format']),
					);
					break;
				default:
					//throw new egw_exception_assertion_failed("Not yet implemented Joomla template parameter type '$param[type]'!");
					error_log("Not yet implemented Joomla template parameter type '$param[type]' --> ignored!");
					break;
			}
		}
		config::save_value('custom_fields', $cfs, 'sitemgr');
	}

	function DisplayMenu()
	{
		$this->DisplayHeader();
		$this->t->set_file('MainMenu','mainmenu.tpl');
		$this->t->set_block('MainMenu','switch','switchhandle');
		$this->t->set_block('MainMenu','menuentry','entry');
		$this->t->set_var('lang_sitemenu',lang('Website') . ' ' . $GLOBALS['Common_BO']->sites->current_site['site_name']);
		foreach($GLOBALS['Common_BO']->get_sitemenu() as $display => $value)
		{
			if ($display == '_NewLine_')
			{
				continue;
			}
			$this->t->set_var(array('value'=>$value,'display'=>lang($display)));
			$this->t->parse('sitemenu','menuentry', true);
		}
		if (($othermenu = $GLOBALS['Common_BO']->get_othermenu()))
		{
			$this->t->set_var('lang_othermenu',lang('Other websites'));
			foreach($othermenu as $display => $value)
			{
				if ($display === '_NewLine_')
				{
					continue;
				}
				if (is_array($value))
				{
					$this->t->set_var(array(
						'display' => $value['no_lang'] ? $value['text'] : lang($value['text']),
						'value'   => $value['link']
					));
				}
				else
				{
					$this->t->set_var(array(
						'display' => lang($display),
						'value'   => $value
					));
				}
				$this->t->parse('othermenu','menuentry', true);
			}
			$this->t->parse('switchhandle','switch');
		}
		else
		{
			$this->t->set_var('switchhandle','testtesttest');
		}
		$this->t->pfp('out','MainMenu');
		$this->DisplayFooter();
	}

	function DisplayIFrame()
	{
		if (($site = $GLOBALS['Common_BO']->sites->read(CURRENT_SITE_ID)) && $site['site_url'])
		{
			$site['site_url'] .= '?mode=Edit&sessionid='.@$GLOBALS['egw_info']['user']['sessionid'] .
				'&kp3=' . @$GLOBALS['egw_info']['user']['kp3'] .
				'&domain=' . @$GLOBALS['egw_info']['user']['domain'];

			common::egw_header();
			parse_navbar();
			// jdots already uses an iframe, so no need to create an other one
			if ($GLOBALS['egw']->framework->template == 'jdots')
			{
				echo "<script type='text/javascript'>\nwindow.setTimeout(\"location='{$site['site_url']}';\",10);\n</script>\n";
			}
			else
			{
				echo "\n".'<div style="width: 100%; height: 100%; min-width: 800px; height: 600px">';
				echo "\n\t".'<iframe src="'.$site['site_url'].'" name="site" width="100%" height="100%" frameborder="0" marginwidth="0" marginheight="0"><a href="'.$site['site_url'].'">'.$site['site_url'].'</a></iframe>';
				echo "\n</div>\n";
			}
		}
		else
		{
			$this->DisplayMenu();
		}
	}

	function DisplayPrefs()
	{
		$this->DisplayHeader();
		if ($this->acl->is_admin())
		{
			if ($_POST['btnlangchange'])
			{
				echo '<p>';
				while (list($oldlang,$newlang) = each($_POST['change']))
				{
					if ($newlang == "delete")
					{
						echo '<b>' . lang('Deleting all data for %1',$GLOBALS['Common_BO']->getlangname($oldlang)) . '</b><br>';
						$this->pages_bo->removealllang($oldlang);
						$this->cat_bo->removealllang($oldlang);
					}
					else
					{
						echo '<b>' . lang('Migrating data for %1 to %2',
								$GLOBALS['Common_BO']->getlangname($oldlang),
								$GLOBALS['Common_BO']->getlangname($newlang)) .
						'</b><br>';
						$this->pages_bo->migratealllang($oldlang,$newlang);
						$this->cat_bo->migratealllang($oldlang,$newlang);
					}
				}
				echo '</p>';
			}

			if ($_POST['btnSave'])
			{
				$oldsitelanguages = $GLOBALS['Common_BO']->sites->current_site['site_languages'];

				if ($oldsitelanguages && ($oldsitelanguages != $_POST['pref']['site_languages']))
				{
					$oldsitelanguages = explode(',',$oldsitelanguages);
					$newsitelanguages = preg_split('/ ?, ?/',trim($_POST['pref']['site_languages']));
					$replacedlang = array_diff($oldsitelanguages,$newsitelanguages);
					$addedlang = array_diff($newsitelanguages,$oldsitelanguages);
					if ($replacedlang)
					{
						echo lang('You removed one ore more languages from your site languages.') . '<br>' .
						lang('What do you want to do with existing translations of categories and pages for this language?') . '<br>';
						if ($addedlang)
						{
							echo lang('You can either migrate them to a new language or delete them') . '<br>';
						}
						else
						{
							echo lang('Do you want to delete them?'). '<br>';
						}
						echo '<form action="' .
						$GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Common_UI.DisplayPrefs') .
						'" method="post"><table>';
						foreach ($replacedlang as $oldlang)
						{
							$oldlangname = $GLOBALS['Common_BO']->getlangname($oldlang);
							echo "<tr><td>" . $oldlangname . "</td>";
							if ($addedlang)
							{
								foreach ($addedlang as $newlang)
								{
									echo '<td><input type="radio" name="change[' . $oldlang .
									']" value="' . $newlang . '"> Migrate to ' .
									$GLOBALS['Common_BO']->getlangname($newlang) . "</td>";
								}
							}
							echo '<td><input type="radio" name="change[' . $oldlang . ']" value="delete"> delete</td></tr>';
						}
						echo '<tr><td><input type="submit" name="btnlangchange" value="' .
						lang('Submit') . '"></td></tr></table></form>';
					}
				}

				$oldsitelanguages = $oldsitelanguages ? explode(',',$oldsitelanguages) : array("en");

				$prefs = $_POST['pref'];
				if ($prefs['default_theme'] == 'custom')
				{
					$themedir = $GLOBALS['egw_info']['server']['files_dir'].'/'.$prefs['themedir'];
					if (empty($prefs['themedir']) || !file_exists($themedir) || !is_dir($themedir))
					{
						$error = lang('Custom template directory does NOT exist or is NOT accessible by webserver!');
					}
					$prefs['themesel'] = '/'.$prefs['themedir'];
				}
				else
				{
					$prefs['themesel'] = $prefs['default_theme'];
				}
				unset($prefs['default_theme']);
				unset($prefs['themedir']);
				if (!$error) $GLOBALS['Common_BO']->sites->saveprefs($prefs);

				echo '<p><b>' . ($error ? '<font color="red">'.$error.'</font>' : lang('Changes Saved.')) . '</b></p>';
			}

			foreach ($GLOBALS['Common_BO']->sites->current_site['sitelanguages'] as $lang)
			{
				$langname = $GLOBALS['Common_BO']->getlangname($lang);
				$preferences['site_name_' . $lang] = array(
					'title'=>lang('Site name'). ' ' . $langname,
					'note'=>lang('This is used chiefly for meta data and the title bar. If you change the site languages below you have to save before being able to set this preference for a new language.'),
					'default'=>lang('New sitemgr site')
				);
				 $preferences['site_desc_' . $lang] = array(
					'title'=>lang('Site description'). ' ' . $langname,
					'note'=>lang('This is used chiefly for meta data. If you change the site languages below you have to save before being able to set this preference for a new language.'),
					'input'=>'textarea'
				);
			}
			$preferences['home_page_id'] = array(
				'title'=>lang('Default home page ID number'),
				'note'=>lang('This should be a page that is readable by everyone. If you leave this blank, the site index will be shown by default.'),
				'input'=>'option',
				'options'=>$this->pages_bo->getPageOptionList()
			);
			$theme = $GLOBALS['Common_BO']->sites->current_site['default_theme'];
			if ($theme[0] == '/') $GLOBALS['Common_BO']->sites->current_site['default_theme'] = 'custom';
			$theme_info = $GLOBALS['egw']->link('/sitemgr/theme_info.php');
			$theme_info .= (strpos($theme_info,'?') !== false ? '&' : '?').'theme=';
			$edit_link = egw::link('/index.php',array(
				'menuaction' => 'sitemgr.Common_UI.templatePrefs',
				'template' => '',
			));
			$preferences['default_theme'] = array(
				'title'=>lang('Template select'),
				'note'=>lang('Choose your site\'s theme or template.  Note that if you changed the above checkbox you need to save before choosing a theme or template.').'<br /><br />'.
					'<b>'.lang('Want more templates?')."</b><br />\n".
					lang('Just download one from our %1template gallery%2 on %3.',
						'<a href="http://www.eGroupWare.org/sitemgr" target="_blank"><b>','</b>','www.egroupware.org</a>').' '.
					lang('Or use a template compatible with %1.',
						'<a href="http://www.joomla.org" target="_blank">Joomla 1.0-1.5</a> '.lang('or').' '.
						'<a href="http://www.mamboserver.com" target="_blank">Mambo Open Source 4.5</a>')."<br />\n".
					lang('Unpack the downloaded template in your templates directory (%1) or use a custom template directory.',
						$GLOBALS['Common_BO']->sites->current_site['site_dir'] . SEP . 'templates').
					'<p><b>'.lang('Template preferences and custom CSS').'</b><br />'.
						html::input('template_prefs',lang('Edit'),'button',
						'onclick="egw_openWindowCentered2(\''.$edit_link.'\'+this.form.elements[\'pref[default_theme]\'].value,\'_blank\',720,530); return false;"').'</p>',
				'input'=>'option',
				'options'=>$this->theme->getAvailableThemes()+array(
						'custom' => array(
							'value' => 'custom',
							'display' => lang('Custom template directory'),
						),
				),
				'extra'=> 'onchange="'.
					" document.getElementById('themedir').style.display=this.value=='custom'?'block':'none';".
					" document.getElementById('TemplateInfoIframe').style.display=this.value!='custom'?'block':'none';".
					" if(this.value!='custom')frames.TemplateInfo.location='$theme_info'+this.value;".'"',
				'below' => '<iframe name="TemplateInfo" id="TemplateInfoIframe" style="display:'.
					($theme[0]=='/'?'none':'block').'" width="100%" height="180" src="'.
					$theme_info.($theme ? $theme : 'idots').'" frameborder="0" scrolling="auto"></iframe>'.
					'<div id="themedir"'.($theme[0] != '/' ? ' style="display: none"' : '').'>'.
					'<p>'.lang("Template directory is relative to EGroupware's files directory. You have to map that direcotory with an alias, so it is accessible like any stock template!")."</p>\n".
					htmlspecialchars($GLOBALS['egw_info']['server']['files_dir']).'/'.
					'<input name="pref[themedir]" value="'.($theme[0] == '/' ? htmlspecialchars(substr($theme,1)) : '').'" maxlength="55" size="55" />'.
					($error ? '<br /><font color="red">'.$error.'</font>' : '').
					'</div>',
				'default'=>'idots'
			);
			$preferences['upload_dir'] = array(
				'title'=>lang('Image directory relative to document root (use / !), example:').' /images',
				'note'=> lang('An existing AND by the webserver readable directory enables the image browser and upload.').'<br />'.
					lang('Upload requires the directory to be writable by the webserver!').
					$this->check_upload_dir($GLOBALS['Common_BO']->sites->current_site['upload_dir']),
			);
			$preferences['site_languages'] = array(
				'title'=>lang('Languages the site user can choose from'),
				'note'=>lang('This should be a comma-separated list of language-codes.'),
				'default'=>'en'
			);

			$this->t->set_file('sitemgr_prefs','sitemgr_preferences.tpl');
			$this->t->set_var('formaction',$GLOBALS['egw']->link(
				'/index.php','menuaction=sitemgr.Common_UI.DisplayPrefs'));
			$this->t->set_var(array(
				'options' => lang('SiteMgr Options'),
				'lang_save' => lang('Save'),
			));

			$this->t->set_block('sitemgr_prefs','PrefBlock','PBlock');
			foreach($preferences as $name => $details)
			{
				$inputbox = '';
				switch($details['input'])
				{
					case 'htmlarea':
						$inputbox = $this->inputhtmlarea($name);
						break;
					case 'textarea':
						$inputbox = $this->inputtextarea($name);
						break;
					case 'checkbox':
						$inputbox = $this->inputCheck($name);
						break;
					case 'option':
						$inputbox = $this->inputOption($name,
							$details['options'],$details['default'],@$details['extra']);
						break;
					case 'inputbox':
					default:
						$inputbox = $this->inputText($name,
							$details['input_size'],$details['default']);
				}
				if ($inputbox)
				{
					if (isset($details['below']))
					{
						$inputbox .= "<br />".$details['below'];
					}
					$this->PrefBlock($details['title'],$inputbox,$details['note']);
				}
			}
			$this->t->pfp('out','sitemgr_prefs');
		}
		else
		{
			echo lang("You must be an administrator to setup the Site Manager.") . "<br><br>";
		}
		$this->DisplayFooter();
	}


	function check_upload_dir($dir)
	{
		if ($dir)
		{
			if (!is_dir($_SERVER['DOCUMENT_ROOT'].$dir) || !file_exists($_SERVER['DOCUMENT_ROOT'].$dir.'/.'))
			{
				$msg = lang('Directory does not exist, is not readable by the webserver or is not relative to the document root!');
			}
			elseif(!is_writable($_SERVER['DOCUMENT_ROOT'].$dir))
			{
				$msg = lang('Directory is NOT writable by the webserver --> disabling upload');
			}
		}
		return $msg ? '<br /><font color="red">'.$msg.'</font>'  : '';
	}

	function inputText($name='',$size=40,$default='')
	{
		if (!is_int($size))
		{
			$size=40;
		}
		$val = $GLOBALS['Common_BO']->sites->current_site[$name];
		if (!$val)
		{
			$val = $default;
		}

		return '<input type="text" size="'.$size.
			'" name="pref['.$name.']" value="'.htmlspecialchars($val).'">';
	}

	function inputtextarea($name,$cols=80,$rows=5,$default='')
	{
		$val = $GLOBALS['Common_BO']->sites->current_site[$name];
		if (!$val)
		{
			$val = $default;
		}

		return '<textarea cols="' . $cols . '" rows="' . $rows .
			'" name="pref['.$name.']">'. $GLOBALS['egw']->strip_html($val).'</textarea>';
	}

	function inputhtmlarea($name,$cols=80,$rows=5,$default='')
	{
		return html::htmlarea("pref[$name]",$default,'',$GLOBALS['Common_BO']->sites->current_site['site_url']);
	}

	function inputCheck($name = '')
	{
		$val = $GLOBALS['Common_BO']->sites->current_site[$name];
		if ($val)
		{
			$checked_yes = ' checked="1"';
			$checked_no = '';
		}
		else
		{
			$checked_yes = '';
			$checked_no = ' checked="1"';
		}
		return '<input type="radio" name="pref['.$name.']" value="1"'.
			$checked_yes.'>Yes</input>'."\n".
			'<input type="radio" name="pref['.$name.']" value="0"'.
			$checked_no.'>No</input>'."\n";
	}

	function inputOption($name = '', $options='', $default = '',$extra='')
	{
		if (!is_array($options) || count($options)==0)
		{
			return lang('No options available.');
		}
		$val = $GLOBALS['Common_BO']->sites->current_site[$name];
		if(!$val)
		{
			$val = $default;
		}
		$returnValue = '<select name="pref['.$name.']" '.$extra.'>'."\n";

		foreach($options as $option)
		{
			$selected='';
			if ($val == $option['value'])
			{
				$selected = 'selected="1" ';
			}
			$returnValue.='<option '.($val == $option['value'] ? 'selected="1" ':'').
				(isset($option['title']) ? 'title="'.$option['title'].'" ':'').
				'value="'.$option['value'].'">'.$option['display'].'</option>'."\n";
		}
		$returnValue .= '</select>';
		return $returnValue;
	}

	function PrefBlock($title,$input,$note)
	{
		//$this->t->set_var('PBlock','');
		$this->t->set_var('pref-title',$title);
		$this->t->set_var('pref-input',$input);
		$this->t->set_var('pref-note',$note);
		$this->t->parse('PBlock','PrefBlock',true);
	}

	function DisplayHeader($extra_title='')
	{
		$GLOBALS['egw_info']['flags']['app_header'] = $GLOBALS['egw_info']['apps']['sitemgr']['title'].
			($extra_title ? ' - '.$extra_title : '');
		common::egw_header();
		parse_navbar();
	}

	function DisplayFooter()
	{
		// is empty atm
	}
}

