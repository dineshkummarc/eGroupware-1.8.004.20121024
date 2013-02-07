<?php
/**
 * EGroupware Sitemgr - Web Content Management
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.Template3.inc.php 38595 2012-03-24 14:35:23Z ralfbecker $
 */

require_once(EGW_INCLUDE_ROOT.'/sitemgr/inc/class.module.inc.php');

class Template3
{
	/**
	 * @var Content_BO
	 */
	var $bo;
	/**
	 * @var Modules_BO
	 */
	var $modulebo;
	/**
	 *  'yes' => halt, 'report' => report error, continue, 'no' => ignore error quietly
	 *
	 * @var string
	 */
	var $halt_on_error = 'yes';
	/**
	 * Filename of template loaded with loadfile
	 *
	 * @var string
	 */
	var $file;
	/**
	 * Content of template loaded with loadfile
	 *
	 * @var string
	 */
	var $template;
	/**
	 * Cache of Modules
	 *
	 * @var array name => module-object pairs
	 */
	var $modules = array();
	var $permitted_modules;
	var $sitename;
	/**
	 * Transformer class for Draft mode
	 *
	 * @var draft_transform
	 */
	var $draft_transformer;
	/**
	 * Transformer class for Edit mode
	 *
	 * @var edit_transform
	 */
	var $edit_transformer;

	/**
	 * Constructor
	 *
	 * @param string $root
	 */
	function __construct($root)
	{
		$this->set_root($root);
		$file = $this->root . SEP . 'main.tpl';
		if (file_exists($file)) $this->loadfile($file);

		$this->bo = $GLOBALS['Common_BO']->content;
		$this->modulebo = $GLOBALS['Common_BO']->modules;

		$this->addcontent = $GLOBALS['egw']->session->appsession('addcontent','sitemgr');
		$GLOBALS['egw']->session->appsession('addcontent','sitemgr',false);

		// Mode specific {edit|draft}_transformer can be already loaded, supplied by template or default one is used
		switch ($GLOBALS['sitemgr_info']['mode'])
		{
		case 'Draft':
			if (!class_exists('draft_transform'))
			{
				$transformerfile = $this->root . SEP . 'draft_transform.inc.php';
				if (!file_exists($transformerfile))
				{
					$transformerfile = EGW_SERVER_ROOT . '/sitemgr/sitemgr-site/templates/default/draft_transform.inc.php';
				}
				if (file_exists($transformerfile))
				{
					include_once($transformerfile);
				}
			}
			if (class_exists('draft_transform'))
			{
				$this->draft_transformer = new draft_transform();
			}
			break;

		case 'Edit':
			if (!class_exists('edit_transform'))
			{
				$transformerfile = $this->root . SEP . 'edit_transform.inc.php';
				if (!file_exists($transformerfile))
				{
					$transformerfile = EGW_SERVER_ROOT . '/sitemgr/sitemgr-site/templates/default/edit_transform.inc.php';
				}
				if (file_exists($transformerfile))
				{
					include_once($transformerfile);
				}
			}
			if (class_exists('edit_transform'))
			{
				$this->edit_transformer = new edit_transform();
			}
			break;
		}
	}

	/**
	 * Set template directory
	 *
	 * @param string $root
	 * @return boolean
	 */
	function set_root($root)
	{
		if (!is_dir($root))
		{
			$this->halt("set_root: $root is not a directory.");
			return false;
		}
		$this->transformer_root = $this->root = $root;
		return true;
	}

	/**
	 * Load a template
	 *
	 * @param unknown_type $file
	 * @return unknown
	 */
	function loadfile($file)
	{
		$this->file = $file;
		$str = file_get_contents($this->file);
		if (empty($str))
		{
			$this->halt("loadfile: $this->file does not exist or is empty.");
			return false;
		}
		$this->template = $str;
	}

	/**
	 * Halt execution with an error-message
	 *
	 * @param string $msg error message to show
	 */
	function halt($msg)
	{
		$this->last_error = $msg;

		if ($this->halt_on_error != 'no')
		{
			$this->haltmsg($msg);
		}

		if ($this->halt_on_error == 'yes')
		{
			echo('<b>Halted.</b>');
		}

		common::egw_exit(True);
	}

	/**
	 * Output error message
	 *
	 * @param string $msg error message to show
	 */
	function haltmsg($msg)
	{
		printf("<b>Template Error:</b> %s<br>\n", $msg);
	}

	/**
	 * Parse the template
	 *
	 * @return string
	 */
	function parse()
	{
		global $page;
		//get block content for contentareas
		$str = preg_replace_callback(
			"/\{contentarea:([^{ ]+)\}/",
			array($this,'process_blocks'),
			$this->template);
		$this->permitted_modules = array_keys($this->modulebo->getcascadingmodulepermissions('__PAGE__',$page->cat_id));
		//process module calls hardcoded into template of form {modulename?arguments}
		$str = preg_replace_callback(
			"/\{([[:alnum:]_-]+)\?([^{ ]+)?\}/",
			array($this,'exec_module'),
			$str);
		//{?page_id=4} is a shortcut for calling the link functions
		$str = preg_replace_callback(
			"/\{\?((sitemgr|egw|phpgw):)?([^{ ]*)\}/",
			array($this,'make_link'),
			$str);
		$str = preg_replace_callback(
			"/\{lang_([^{]+)\}/",
			array($this,'lang'),
			$str);
		//all template variables that survive look for metainformation
		return preg_replace_callback(
			"/\{([^{ ]+)\}/",
			array($this,'get_meta'),
			$str);
	}

	/**
	 * Get the visible blocks of an area for the current page
	 *
	 * Wrapper to request the blocks only once from the database (get cached here)
	 *
	 * @param string|array $vars name of content array or array with name as value for index 1
	 * @return array
	 */
	function getvisibleblockdefsforarea($vars)
	{
		global $page;
		global $objbo;
		static $cache;

		$areaname = is_array($vars) ? $vars[1] : $vars;
		$blocks =& $cache[$areaname];

		if (!isset($blocks) || !is_array($blocks))
		{
			$blocks = $this->bo->getvisibleblockdefsforarea($areaname,$page->cat_id,$page->id,$objbo->is_admin(),$objbo->isuser);
		}
		//error_log(__METHOD__.'('.array2string($vars).') = '.array2string($blocks));
		return $blocks;
	}

	/**
	 * Count blocks in a certain content area
	 *
	 * @param string|array $vars name of content array or array with name as value for index 1
	 * @return int
	 */
	function count_blocks($vars)
	{
		$n_blks = count($this->getvisibleblockdefsforarea($vars));

		// make sure each block is displayed in edit mode
		if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' && $n_blks < 1) $n_blks = 1;

		//error_log(__METHOD__.'('.array2string($vars).') = '.$n_blks);
		return $n_blks;
	}

	/**
	* processes all blocks for a given contentarea
	*
	* @param $vars string contenarea name
	* @param $style=null passed to transformer constructor, currently only used for Joomla 1.5 templates
	* @return string html content
	*/
	function process_blocks($vars,$style=null)
	{
		global $page;
		global $objbo;
		static $cache;

		$areaname = is_array($vars) ? $vars[1] : $vars;
		if (is_array($cache) && isset($cache[$areaname]))
		{
			return $cache[$areaname];
		}
		$this->permitted_modules = array_keys($this->modulebo->getcascadingmodulepermissions($areaname,$page->cat_id));

		$transformername = $areaname . '_bt';

		$transformerfile = $this->transformer_root . SEP . $transformername . '.inc.php';
		if (!class_exists($transformername) && file_exists($transformerfile))
		{
			include_once($transformerfile);
		}
		if (class_exists($transformername))
		{
			$transformer = new $transformername($style);
		}
		//compatibility with former sideblocks template
		elseif (($areaname == "left" || $areaname == "right") && file_exists($this->root . SEP . 'sideblock.tpl'))
		{
			$t = new Template();
			$t->set_root($this->root);
			$t->set_file('SideBlock','sideblock.tpl');
			$transformer = new sideblock_transform($t);
		}
		$content = '';

		$blocks = $this->getvisibleblockdefsforarea($areaname);

		// get addcontent blocks
		if(is_array($this->addcontent))
		{
			foreach($this->addcontent as $num => $add_block)
			{
				if (!(in_array((string)$num,explode(',',$_GET['addcontent'])))) continue;
				if (!(($add_block['area'] == $areaname) || (!$add_block['area'] && $areaname == 'center'))) continue;
				$add_block['module_id'] = $this->modulebo->getmoduleid($add_block['module_name']);
				$add_block['addcontents'] = true;
				if ($add_block['sort_order'])
				{
					$blocks[] = (object)$add_block;
					// FIXME we need to resort here
				}
				else
				{
					// contentarea gets blanked if no sortorder is given
					$blocks = array((object)$add_block);
				}
			}
		}

		// if we are in the center area, we append special blocks
		if ($areaname == "center" && $page->block)
		{
			array_unshift($blocks,$page->block);
		}
		if ($blocks)
		{
			foreach($blocks as $block)
			{
				if (in_array($block->module_id,$this->permitted_modules))
				{
					// we maintain an array of modules we have already used, so we do not have to create them anew.
					// getmodule returns now a clone of the original module, otherwise PHP5 would use an implicit reference
					$moduleobject =& $this->getmodule($block->module_name);

					if ($block->id)
					{
						$block->title = $this->getblocktitlewrapper($block->id);
						$block->arguments = $moduleobject->i18n ?
							$this->getversionwrapper($block->version) : $this->bo->getversion($block->version);
					}

					$moduleobject->set_block($block,True);

					if (($block->state == SITEMGR_STATE_PREPUBLISH) && is_object($this->draft_transformer))
					{
						$moduleobject->add_transformer($this->draft_transformer);
					}
					if (isset($transformer))
					{
						$moduleobject->add_transformer($transformer);
					}
					if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' &&
						$block->id && is_object($this->edit_transformer) &&
						$GLOBALS['Common_BO']->acl->can_write_category($block->cat_id))
					{
						$moduleobject->add_transformer($this->edit_transformer);
					}

					$output = $moduleobject->get_output();

					//process module calls embedded into output
					$content .= preg_replace_callback(
						"/\{([[:alnum:]_-]*)\.([[:alnum:]_-]*)(\?([^{ ]+))?\}/",
						array($this,'exec_module'),
						$output);
				}
				else
				{
					$content .= lang('Module %1 is not permitted in this context!',$block->module_name);
				}
			}
		}
		if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' &&
			is_object($this->edit_transformer) &&
			$GLOBALS['Common_BO']->acl->can_write_category($page->cat_id) &&
			method_exists($this->edit_transformer,'area_transform'))
		{
			return $cache[$areaname] = $this->edit_transformer->area_transform($areaname,$content,$page);
		}
		return $cache[$areaname] = $content;
	}

	/**
	 * Execute a singe module
	 *
	 * @param array $vars array(1 => $modulename,2 => $query)
	 * @return string
	 */
	function exec_module($vars)
	{
		global $page;
		$this->permitted_modules = array_keys($this->modulebo->getcascadingmodulepermissions('__PAGE__',$page->cat_id));
		list(,$modulename,$query) = $vars;
		$moduleid = $this->modulebo->getmoduleid($modulename);
		if (!in_array($moduleid,$this->permitted_modules))
		{
			return lang('Module %1 is not permitted in this context!',$modulename);
		}
		$moduleobject = $this->getmodule($modulename);
		if ($moduleobject)
		{
			parse_str($query,$arguments);
			//we set up a block object so that the module object can retrieve the right arguments and properties
			$block =& CreateObject('sitemgr.Block_SO',True);
			$block->module_id = 0;
			$block->area = '__PAGE__';
			$block->cat_id = $page->cat_id;
			$block->module_name = $modulename;
			$block->arguments = $arguments;
			$moduleobject->set_block($block,True);
			return $moduleobject->get_output();
		}
	}

	function make_link($vars)
	{
		switch($vars[2])
		{
			case 'egw':
			case 'phpgw':
				$params=explode(',',$vars[3]);
				switch(count($params))
				{
					case 0:
						return '';
					case 1:
						return phpgw_link($params[0]);
					case 2:
						return phpgw_link($params[0],$params[1]);
				}
				return $vars[0];
		}
		return sitemgr_link($vars[3]);
	}

	/**
	 * Translate a string with underscores replaced with space
	 *
	 * @param array $vars array(1 => $string)
	 * @return string
	 */
	function lang($vars)
	{
		return lang(str_replace('_',' ',$vars[1]));
	}

	/**
	 * Retrieve several page metadata
	 *
	 * @param array|string $vars name or array(1 => name)
	 * @return string
	 */
	function get_meta($vars)
	{
		global $page,$objbo;

		switch ($var = is_array($vars) ? $vars[1] : $vars)
		{
			case 'title':
			case 'page_title':
				return html::htmlspecialchars($page->title);
			case 'editicons':
				// edit icons are now displayed by edit_transform of contentarea center
				return '';
			case 'subtitle':
			case 'page_subtitle':
				return html::htmlspecialchars($page->subtitle);
			case 'sitename':
			case 'site_name':
				return html::htmlspecialchars($GLOBALS['sitemgr_info']['site_name_' . $GLOBALS['egw_info']['user']['preferences']['common']['lang']]);
			case 'description':
				/* not available in used database schema :-(
				if (!empty($page->description))
				{
					return html::htmlspecialchars($page->description);
				}*/
				if ($page->cat_id && $page->cat_id != CURRENT_SITE_ID)
				{
					if (!isset($page->category)) $page->category = $objbo->getcatwrapper($page->cat_id);

					if (!empty($page->category->description))
					{
						return html::htmlspecialchars($page->category->description);
					}
				}
				// fall thought to site-description
			case 'slogan':
				//this one is often used as tag for description in our templates for description in metatags
			case 'sitedesc':
			case 'site_desc':
				return html::htmlspecialchars($GLOBALS['sitemgr_info']['site_desc_' . $GLOBALS['egw_info']['user']['preferences']['common']['lang']]);
			case 'keywords':
				/* not available in used database schema :-(
				if (!empty($page->keywords))
				{
					return html::htmlspecialchars($page->keywords);
				}
				if ($page->cat_id && $page->cat_id != CURRENT_SITE_ID)
				{
					if (!isset($page->category)) $page->category = $objbo->getcatwrapper($page->cat_id);

					if (!empty($page->category->keywords))
					{
						return html::htmlspecialchars($page->category->keywords);
					}
				}
				if (!empty($GLOBALS['sitemgr_info']['site_keywords_' . $GLOBALS['egw_info']['user']['preferences']['common']['lang']]))
				{
					return html::htmlspecialchars($GLOBALS['sitemgr_info']['site_keywords_' . $GLOBALS['egw_info']['user']['preferences']['common']['lang']]);
				}*/
				return 'EGroupware';
			case 'user':
				return $GLOBALS['egw_info']['user']['account_lid'];
			case 'charset':
				return translation::charset();
			case 'lang':
				return $GLOBALS['sitemgr_info']['userlang'];
			case 'year':
				return date('Y');	// nice to keep all copyrights up to date
			case 'editmode_styles':
				return $GLOBALS['sitemgr_info']['mode'] == 'Edit' ?
					'<link href="templates/default/style/editmode.css" type="text/css" rel="StyleSheet" />' : '';
			case 'java_script':
				return common::get_java_script();
			case 'body_attribs':
				return common::get_body_attribs();
			case 'need_footer':
				return $GLOBALS['egw_info']['flags']['need_footer'];
			case 'default_css':
				return '<link href="templates/default/style/default.css" type="text/css" rel="StyleSheet" />';
			case 'version':
				return $GLOBALS['egw_info']['apps']['sitemgr']['version'];
			default:
				return '{'.$var.'}';	// leave it unchanged, happens eg. with js-code
		}
	}

	/**
	 * return clone of module $modulename
	 *
	 * we have to return a clone, as PHP5 would use an implicte reference otherwise
	 *
	 * @param string $modulename
	 * @return object
	 */
	function &getmodule($modulename)
	{
		if (!isset($this->modules[$modulename]))
		{
			$this->modules[$modulename] =& $this->modulebo->createmodule($modulename);
		}
		return clone($this->modules[$modulename]);
	}

	function getblocktitlewrapper($block_id)
	{
		$availablelangsforblocktitle = $this->bo->getlangarrayforblocktitle($block_id);
		if (in_array($GLOBALS['sitemgr_info']['userlang'],$availablelangsforblocktitle))
		{
			return $this->bo->getlangblocktitle($block_id,$GLOBALS['sitemgr_info']['userlang']);
		}
		else
		{
			// try useing the availible translation, if we have an english title
			if (in_array('en',$availablelangsforblocktitle))
			{
				$en_title = $this->bo->getlangblocktitle($block_id,'en');
				$title = lang($en_title);
				if ($title != $en_title.'*')
				{
					return $title;
				}
			}
			foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
			{
				if (in_array($lang,$availablelangsforblocktitle))
				{
					return $this->bo->getlangblocktitle($block_id,$lang);
				}
			}
		}
	}

	function getversionwrapper($version_id)
	{
		$availablelangsforversion = $this->bo->getlangarrayforversion($version_id);
		if (in_array($GLOBALS['sitemgr_info']['userlang'],$availablelangsforversion))
		{
			return $this->bo->getversion($version_id,$GLOBALS['sitemgr_info']['userlang']);
		}
		else
		{
			foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
			{
				if (in_array($lang,$availablelangsforversion))
				{
					return $this->bo->getversion($version_id,$lang);
				}
			}
		}
	}
}

class sideblock_transform
{
	function sideblock_transform(&$template)
	{
		$this->template = $template;
	}

	function apply_transform($title,$content)
	{
		$this->template->set_var(array(
			'block_title' => $title,
			'block_content' => $content
		));
		return $this->template->parse('out','SideBlock');
	}
}
