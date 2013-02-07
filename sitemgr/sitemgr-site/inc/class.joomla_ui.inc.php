<?php
/**
 * EGroupware SiteMgr CMS - Joomla 1.5 template support
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage sitemgr-site
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @copyright Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.joomla_ui.inc.php 38583 2012-03-24 12:24:04Z ralfbecker $
 */

/**
 * UI Object for Joomla 1.5 templates
 *
 * It also emulates some of the JDocumentHTML methods
 */
class ui extends JObject
{
	/**
	 * Instance of template object
	 *
	 * @var Template3
	 */
	protected $t;
	/**
	 * Directory of current template
	 *
	 * @var string
	 */
	public $templateroot;
	/**
	 * Directory for MOS compatibilty files
	 *
	 * @ToDo is this still needed?
	 * @var string
	 */
	protected $mos_compat_dir;

	/**
	 * Template name
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Url of SiteMgr site
	 *
	 * @var string
	 */
	public $baseurl;

	/**
	 * Param store
	 *
	 * @var JParameter
	 */
	public $params;

	/**
	 * Current site language
	 *
	 * @var string
	 */
	public $language = 'en';

	/**
	 * Language direction: ltr or rtl
	 *
	 * @var string
	 */
	public $direction = 'ltr';

	/**
	 * Site name
	 *
	 * @var string
	 */
	public $sitename;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$themesel = $GLOBALS['sitemgr_info']['themesel'];
		if ($themesel[0] == '/')
		{
			$this->templateroot = $GLOBALS['egw_info']['server']['files_dir'] . $themesel;
		}
		else
		{
			$this->templateroot = $GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates' . SEP . $themesel;
		}
		$this->t = new Template3($this->templateroot);
		$this->t->transformer_root = $this->mos_compat_dir = realpath(dirname(__FILE__).'/../mos-compat');

		// attributes used by Joomla 1.5
		$this->template = basename($themesel);
		$this->baseurl = $GLOBALS['sitemgr_info']['site_url'];
		$this->sitename = $this->t->get_meta('sitename').': '.$this->t->get_meta('title');
		if (in_array($dir=lang('language_direction_rtl'),array('rtl','ltr'))) $this->direction = $dir;
		$this->language = $this->t->get_meta('lang');

		// init JParameter from site or ini.file, if site has no prefs for this template
		if (strpos($GLOBALS['Common_BO']->sites->current_site['params_ini'],'['.$this->template.']') !== false)
		{
			$ini_string = $GLOBALS['Common_BO']->sites->current_site['params_ini'];
		}
		else
		{
			$ini_string = @file_get_contents($this->templateroot.SEP.'params.ini');
		}
		$this->params = new JParameter($ini_string,'',$this->template);

		// global mainframe object used by some templates
		$GLOBALS['mainframe'] = new JObject();
	}

	/**
	 * Displays page by name (SiteMgr UI method)
	 *
	 * @param string $page_name
	 */
	function displayPageByName($page_name)
	{
		global $objbo;
		global $page;
		$objbo->loadPage($GLOBALS['Common_BO']->pages->so->PageToID($page_name));
		$this->generatePage();
	}

	/**
	 * Displays page by id (SiteMgr UI method)
	 *
	 * @param int $page_id
	 */
	function displayPage($page_id)
	{
		global $objbo;
		$objbo->loadPage($page_id);
		$this->generatePage();
	}

	/**
	 * Displays index (SiteMgr UI method)
	 */
	function displayIndex()
	{
		global $objbo;
		$objbo->loadIndex();
		$this->generatePage();
	}

	/**
	 * Displays TOC (SiteMgr UI method)
	 *
	 * @param int $categoryid=false
	 */
	function displayTOC($categoryid=false)
	{
		global $objbo;
		$objbo->loadTOC($categoryid);
		$this->generatePage();
	}

	/**
	 * Displays search (SiteMgr UI method)
	 */
	function displaySearch($search_result,$lang,$mode,$options)
	{
		global $objbo;
		$objbo->loadSearchResult($search_result,$lang,$mode,$options);
		$this->generatePage();
	}

	/**
	 * Generate page using the template (SiteMgr UI method)
	 */
	function generatePage()
	{
		// add a content-type header to overwrite an existing default charset in apache (AddDefaultCharset directiv)
		header('Content-type: text/html; charset='.translation::charset());

		// Joomla 1.5 defines
		define( '_JEXEC', True );
		define('JVERSION','1.5');
		define('JPATH_SITE',$GLOBALS['sitemgr_info']['site_dir']);
		define('JPATH_BASE',$GLOBALS['sitemgr_info']['site_dir']);
		define('DS',DIRECTORY_SEPARATOR);
		global $mainframe;

		ini_set('include_path',$this->mos_compat_dir.(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? ';' : ':').ini_get('include_path'));

		// read module helpers: modChrome_* functions
		require_once $GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates/system/html/modules.php';
		if (file_exists($file = $this->templateroot.'/html/modules.php'))
		{
			require_once $file;
		}
/*
		// support for JA T3 framework installed as Joomla plugin
		if (file_exists($this->templateroot.'/info.xml') &&	// check if template is a JA T3 template
			file_exists($file = $GLOBALS['sitemgr_info']['site_dir'].'/plugins/system/jat3.php'))
		{
			define('T3_ACTIVE_TEMPLATE',$this->template);
			include_once($file);
			$t3 = new plgSystemJAT3($jdispatcher = new JObject(),array());
			$t3->onAfterInitialise();
			$t3->onAfterRoute();
			//$t3->onAfterRender();
			t3import('core.extendable');
			t3import('core.parameter');
			t3import('core.cache');
			t3import('core.template');
		}
*/
		ob_start();
		include($this->templateroot.'/index.php');
		$website = ob_get_contents();
		ob_clean();

		// replace <jdoc:include type="modules" name="XXXX" /> with content of content-area XXXX
		$website = preg_replace_callback('/<jdoc:([a-z]+) type="([^"]+)" (name="([^"]+)")?[^>]*>/', array($this,'jdoc_replace'), $website);

		// regenerate header (e.g. js includes)
		$this->t->loadfile(realpath(dirname(__FILE__).'/../mos-compat/metadata.tpl'));
		if (file_exists($this->templateroot.'/metadata.tpl'))
		{
			$this->t->loadfile($this->templateroot.'/metadata.tpl');
		}

		$custom_css = '';
		// replace breadcrump li bullet with arrow, as Joomla does it
		if (file_exists($this->templateroot.'/images/arrow.png'))
		{
			$custom_css .= "#navigation-path-nosep ul li {
	background: transparent url({$this->baseurl}templates/$this->template/images/arrow.png) no-repeat scroll 10px 7px;
}\n";
		}
		// inject custom CSS (incl. site logo)
		$custom_css .= $GLOBALS['Common_BO']->get_custom_css();
		if (!empty($custom_css))
		{
			$website = str_replace('</head>',"\t".'<style type="text/css">'."\n".$custom_css."\n\t</style>\n</head>",$website);
		}
		echo preg_replace('@<!-- metadata.tpl starts here -->.*?<!-- metadata.tpl ends here -->@si',$this->t->parse(),$website);
	}

	/**
	 * Replaces <jdoc:include
	 *
	 * @link http://docs.joomla.org/Jdoc_statements
	 * @param array $matches 0: whole jdoc tag, 1: jdoc:type, eg. "include", 2: type, eg. "module", 4: name of content-area
	 */
	public function jdoc_replace($matches)
	{
		list($all,$jdoc_type,$type,,$name) = $matches;

		if ($jdoc_type == 'include')
		{
			switch($type)
			{
				case 'modules':		// content-area $name
					$style = null;
					if (preg_match('/style="([^"]+)"/',$all,$m))
					{
						$style = $m[1];
					}
					return "<!-- BEGIN: CONTENTAREA $name -->\n".$this->t->process_blocks($name,$style)."\n<!-- END: CONTENTAREA $name -->";

				case 'component':	// load the center module
					if (!file_exists($file = $objui->templateroot.'/mainbody.tpl'))
					{
						$file = realpath(dirname(__FILE__).'/../mos-compat/mainbody.tpl');
					}
					$this->t->loadfile($file);
					return $this->t->parse();

				case 'head':
					$this->t->loadfile(realpath(dirname(__FILE__).'/../mos-compat/metadata.tpl'));
					return "\t\t<title>".$this->t->get_meta('sitename').': '.$this->t->get_meta('title')."</title>\n".
						$this->t->parse();

				case 'message':		// used for error-messages in Joomla
					return '';

				case 'module':
					switch($name)
					{
						case 'breadcrumbs':
							//if ($suppress_hide_pages) $suppress_hide='&suppress_hide_pages=on';
							$module_navigation_path = array('','navigation','nav_type=8&no_show_sep=on'.$suppress_hide);
							return $this->t->exec_module($module_navigation_path);
						case 'login':
						case 'search':
							return $this->t->exec_module(array('',$name));
					}
			}
		}
		// log unkown types and return them unchanged
		error_log(__METHOD__.'('.array2string($matches).') unknown jdoc tag!');
		return $matches[0];
	}

	/**
	 * JDocumentHTML compatibility methods
	 */

	/**
	 * Count the modules based on the given condition
	 *
	 * @param  string 	$condition	The condition to use, eg. "user2", "left and right", "user1 or user2 or user3"
	 * @return integer  Number of modules found
	 */
	function countModules($condition)
	{
		$words = explode(' ', $condition);
		for($i = 0; $i < count($words); $i+=2)
		{
			// odd parts (modules)
			$name		= strtolower($words[$i]);
			$words[$i]	= (int)$this->t->count_blocks($name);
		}

		if (count($words) == 1)
		{
			$ret = $words[0];
		}
		else
		{
			$str = 'return '.implode(' ', $words).';';
			$ret = eval($str);
		}
		//error_log(__METHOD__."('$condition') returning ".($str ? "eval('$str') = " : '').array2string($ret));
		return $ret;
	}

	/**
	 * Get URL of template directory
	 *
	 * @return string
	 */
	function templateurl()
	{
		return $this->baseurl.'templates/'.$this->template;
	}

	/**
	 * Get path of template directory
	 *
	 * @return string
	 */
	function templatepath()
	{
		return $this->templateroot;
	}
}

/**
 * Import a Joomla class: ignores most requests to import files!
 *
 * File have to be in sitemgr/sitemgr-site/mos-compat which get added to PHP include_path
 *
 * @param string $name
 */
function jimport($name)
{
	global $objui;

	switch($name)
	{
		case 'libraries.joomla.utilities.date':
			require_once str_replace('.', SEP, $name).'.php';
			break;

		default:
			if ($objui->debug) error_log(__FUNCTION__."('$name')");
			break;
	}
}

/**
 * Block transformer for contentarea left, right or center
 *
 * Uses modChrome_$style from templates/server/html/module.php or templates/$template/html/module.php
 */
class joomla_transformer
{
	/**
	 * Style to apply to blocks
	 *
	 * @var string
	 */
	private $style = 'none';

	/**
	 * Constructor
	 *
	 * @param string $style=null style attribute from '<jdoc:include  style="...">'
	 */
	public function __construct($style=null)
	{
		if ($style) $this->style = $style;
	}

	/**
	 * Apply joomla transformer adding a div with certain class around each block
	 *
	 * @param string $title
	 * @param string $content
	 * @param Block_SO $block
	 * @return string html content
	 */
	public function apply_transform($title,$content,Block_SO $block)
	{
		/**
		 * @var ui
		 */
		global $objui;

		$module = (object)array(
			'title'   => $title,
			'content' => $content,
			'style'   => $this->style,
			'showtitle' => !empty($title),
			'id'      => $block->id,
		);
		foreach(explode(' ', $this->style) as $style)
		{
			$chromeMethod = 'modChrome_'.$style;

			// Apply chrome and render module
			if (function_exists($chromeMethod))
			{
				$attribs = array();
				ob_start();
				$chromeMethod($module, $objui->params, $attribs);
				$module->content = ob_get_contents();
				ob_end_clean();
			}
		}
		return $module->content;
	}
}

class left_bt extends joomla_transformer { }
class right_bt extends joomla_transformer { }
class center_bt extends joomla_transformer { }

/**
 * Object which allows to call every method (returning null) and set and read every property
 *
 * All access or calls can be logged via error_log()
 */
class JObject
{
	/**
	 * Store for attribute values
	 */
	protected $data = array();

	/**
	 * Enable or disable logging
	 *
	 * @var boolean
	 */
	protected $debug = false;
	/**
	 * Enable or disable logging for static calls
	 *
	 * @var boolean
	 */
	static protected $debug_static = false;

	public function __get($name)
	{
		if ($this->debug) error_log(__METHOD__."('$name') returning ".array2string($this->data[$name]).' '.function_backtrace());

		return $this->data[$name];
	}

	public function __set($name,$value)
	{
		if ($this->debug) error_log(__METHOD__."('$name',".array2string($value).') '.function_backtrace());

		$this->data[$name] = $value;
	}

	public function __isset($name)
	{
		if ($this->debug) error_log(__METHOD__."('$name') returning ".array2string(isset($this->data[$name])).' '.function_backtrace());

		return isset($this->data[$name]);
	}

	public function __call($name,$params)
	{
		if ($this->debug) error_log(__METHOD__."('$name',".array2string($params).') '.function_backtrace());

		return null;
	}

	/**
	 * By __callstatic already (in this request) added static methods
	 *
	 * @var array
	 */
	protected static $already_added = array();
	/**
	 * Code to add, if __callstatic can add the method to this file
	 *
	 * @var string
	 */
	public static $call_static_code = 'return null;';

	/**
	 * Called for all static method calls, requires PHP5.3 !!!
	 *
	 * To overcome PHP5.3 requirement, the class adds the method to this file, IF it is writable.
	 *
	 * @param string $name
	 * @param array $params
	 *
	 */
	public static function __callstatic($name,$params)
	{
		$method = get_called_class().'::'.$name;
		if (self::$debug_static) error_log($method."('$name',".array2string($params).') '.function_backtrace());

		// add called static methods to class, to be able to use it with PHP < 5.3 not supporting __callstatic()
		if (is_writable(__FILE__) && !in_array($method,self::$already_added))
		{
			$file = file_get_contents(__FILE__);
			$file = preg_replace('/(class '.preg_quote(get_called_class()).' extends JObject'."\n{)/m",
				'$1'."\n\tpublic static function $name() { ".self::$call_static_code.' }',$file);
			file_put_contents(__FILE__, $file);
		}

		return null;
	}
}

/**
 * Joomla 1.5 compatibilty classes
 */
class JVersion extends JObject
{

}

class JFactory extends JObject
{
	public static function getConfig() { return new JObject(); }
	public static function getApplication() { return new JObject(); }
	public static function getUser() { return new JObject(); }
	public static function getDBO() { return new JObject(); }

	/**
	 * JFactory static method return only objects, requires PHP5.3 !!!
	 *
	 * @param string $name
	 * @param array $params
	 */
	public static function __callstatic($name,$params)
	{
		$backup = parent::$call_static_code;
		parent::$call_static_code = 'return new JObject();';
		parent::__callstatic($name, $params);
		parent::$call_static_code = $backup;

		return new JObject();
	}
}

class JParameter extends JObject
{
	public static function def() { return null; }

	protected $_defaultNameSpace = '_default';

	/**
	 * Store for parameter values
	 *
	 * @var array
	 */
	private $values = array();

	/**
	 * Constructor
	 *
	 * @access	protected
	 * @param	string The raw parms text
	 * @param	string Path to the xml setup file
	 * @since	1.5
	 */
	function __construct($data, $path = '', $_defaultNamespace = '_default')
	{
		if ($this->debug) error_log(__METHOD__."('$data','$path','$_defaultNamespace') ".function_backtrace());


		$this->_defaultNameSpace = $_defaultNamespace;
		//echo "<p>__construct(,,'$_defaultNamespace)</p>\n";

		if (trim( $data ))
		{
			$this->loadINI($data);
		}
	}

	const ALL_NAMESPACES='*all*';

	/**
	 * Load an INI string into the registry into the given namespace [or default if a namespace is not given]
	 *
	 * @access	public
	 * @param	string	$data		INI formatted string to load into the registry
	 * @param	string	$namespace	Namespace to load, default $this->_defaultNameSpace
	 * 	or self::ALL_NAMESPACES to read all sections
	 * @return	boolean True on success
	 * @since	1.5
	 */
	function loadINI($data, $namespace = null)
	{
		if ($data === false) return false;

		if (is_null($namespace)) $namespace = $this->_defaultNameSpace;
		$section = $namespace == self::ALL_NAMESPACES ? $this->_defaultNameSpace : $namespace;

		foreach(preg_split("/[\n\r]+/", (string)$data) as $line)
		{
			if ($line)
			{
				if ($line[0] == '[' && substr($line,-1) == ']')	// section header
				{
					$section = substr($line,1,-1);
					continue;
				}
				if ($namespace == self::ALL_NAMESPACES || $section == $namespace)
				{
					list($key,$value) = explode('=',$line,2);
					$this->set($key,$value,$section);
				}
			}
		}
		//echo "loadINI('$data','$namespace') default=$this->_defaultNameSpace, values="; _debug_array($this->values);
		return true;
	}

	/**
	 * Get INI string from registry
	 *
	 * @param string $namespace=null default $this->_defaultNameSpace or
	 * 	self::ALL_NAMESPACES to return each namespace as separate section: [namespace]
	 * @return string
	 */
	function getINI($namespace = null)
	{
		if (is_null($namespace)) $namespace = $this->_defaultNameSpace;

		if ($namespace === self::ALL_NAMESPACES) ksort($this->values);
		//echo "getINI('$namespace') values="; _debug_array($this->values);

		$ini = $section = '';
		foreach($this->values as $key => $value)
		{
			list($ns,$name) = explode('.',$key,2);
			if ($namespace == self::ALL_NAMESPACES && $section != $ns)
			{
				$ini .= '['.($section=$ns)."]\n";
			}
			if ($namespace == self::ALL_NAMESPACES || $ns == $namespace)
			{
				$ini .= $name.'='.$value."\n";
			}
		}
		//echo "ini:<pre>$ini</pre>\n";
		return $ini;
	}

	/**
	 * Set a value
	 *
	 * @access	public
	 * @param	string The name of the param
	 * @param	string The value of the parameter
	 * @return	string The set value
	 * @since	1.5
	 */
	function set($key, $value = '', $ns = null)
	{
		if (is_null($ns)) $ns = $this->_defaultNameSpace;

		if ($this->debug) error_log(__METHOD__."('$key','$value','$ns')");

		return $this->values[$ns.'.'.$key] = (string) $value;
	}

	/**
	 * Get a value
	 *
	 * @access	public
	 * @param	string The name of the param
	 * @param	mixed The default value if not found
	 * @return	string
	 * @since	1.5
	 */
	function get($key, $default = '', $ns=null)
	{
		if (is_null($ns)) $ns = $this->_defaultNameSpace;

		$value = $this->values[$ns.'.'.$key];

		$result = (empty($value) && ($value !== 0) && ($value !== '0')) ? $default : $value;

		if ($this->debug) error_log(__METHOD__."('$key','$default','$ns') returning ".array2string($result));

		return $result;
	}

	/**
	 * Transforms a namespace to an array
	 *
	 * @access	public
	 * @param	string	$namespace	Namespace to return [optional: null returns the default namespace]
	 * @return	array	An associative array holding the namespace data
	 * @since	1.5
	 */
	function toArray($namespace = null)
	{
		if (is_null($namespace)) $namespace = $this->_defaultNameSpace;

		$arr = array();
		foreach($this->values as $key => $value)
		{
			list($ns,$name) = explode('.',$key,2);
			if ($ns == $namespace)
			{
				$arr[$name] = $value;
			}
		}
		return $arr;
	}
}

class JRequest extends JObject
{
	public static function getURI() { return null; }
	public static function getCmd() { return null; }
	public static function getInt() { return null; }
	public static function getVar() { return null; }

}

class JHTML extends JObject
{
	public static function stylesheet() { return null; }
	public static function _($what)
	{
		switch($what)
		{
			case 'behavior.mootools':
				self::script('mootools.js');
				break;
			case 'behavior.caption':
				self::script('caption.js');
				break;
		}
		return null;
	}

	/**
	 * Write a <script></script> element
	 *
	 * @param	string 	The name of the script file
	 * @param	string 	The relative or absolute path of the script file
	 * @param	boolean If true, the mootools library will be loaded
	 * @since	1.5
	 */
	function script($filename, $path = 'templates/system/js/', $mootools = true)
	{
		static $loaded = array();
		/**
		 * @var ui
		 */
		global $objui;

		if (in_array($path.$filename,$loaded))
		{
			return;
		}
		$loaded[] = $path.$filename;

		if ($mootools && $filename != 'mootools.js')
		{
			self::_('behavior.mootools');
		}
		if (strpos($path, 'http') !== 0 && !file_exists($file=$GLOBALS['sitemgr_info']['site_dir'].SEP.$path.$filename))
		{
			error_log(__METHOD__."('$filename', '$path', $mootools) $file NOT found!");
			return;
		}
		echo '<script type="text/javascript" src="'.htmlspecialchars(
			(strpos($path, 'http') !== 0 ? $objui->baseurl : '').$path.$filename).'"></script>'."\n";
	}
}

class JURI extends JObject
{
	/**
	 * Instance returned by singleton
	 *
	 * @var JMenu
	 */
	private static $instance;

	/**
	 * Singelton
	 *
	 * @return JMenu
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new JURI();
		}
		return self::$instance;
	}

	/**
	 * Get base url of site
	 *
	 * @return string
	 */
	public static function base()
	{
		/**
		 * @var ui
		 */
		global $objui;

		return $objui->baseurl;
	}
}

class JText extends JObject
{
	public function _($str)
	{
		return lang($str);
	}
}

class JConfig extends JObject
{
	function __get($name)
	{
		/**
		 * @var ui
		 */
		global $objui;

		switch($name)
		{
			case 'sitename':
				return $objui->sitename;
		}
		return parent::__get($name);
	}
}

class JSite extends JObject
{
	public static function getRouter()
	{
		if (self::$debug_static) error_log(__METHOD__.substr(array2string(func_get_args()),5));

		return new JObject();
	}

	public static function getMenu()
	{
		if (self::$debug_static) error_log(__METHOD__.substr(array2string(func_get_args()),5));

		return JMenu::getInstance();
	}
}

class JMenu extends JObject
{
	/**
	 * Instance returned by singleton
	 *
	 * @var JMenu
	 */
	private static $instance;

	/**
	 * Singelton
	 *
	 * @return JMenu
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new JMenu();
		}
		return self::$instance;
	}

	/**
	 * Gets menu items by attribute
	 *
	 * @access public
	 * @param string 	The field name
	 * @param string 	The value of the field
	 * @param boolean 	If true, only returns the first item found
	 * @return array
	 */
	function getItems($attribute, $value, $firstonly = false)
	{
		if ($this->debug) error_log(__METHOD__.substr(array2string(func_get_args()),5));

		$cat_id = 0;
		$depth = 0;
		$tree = array();	// stack/path mit cat_id's
		foreach($GLOBALS['objbo']->getIndex(false,false,true) as $page)
		{
			//_debug_array($page);
			$id = 2 * $page['cat_id'];			// cat's have even id's
			if ($id != $cat_id)		// new cat
			{
				$cat_id = $id;
				if($depth == $page['catdepth'])	// same level -> remove last element
				{
					array_pop($tree);
				}
				elseif($depth > $page['catdepth'])	// we are going back (maybe multiple levels)
				{
					$tree = array_slice($tree,0,$page['catdepth']-1);
				}
				$tree[] = $cat_id;
				$depth = $page['catdepth'];
				$cat_path = implode('/',$tree);
				$parent = (int)$tree[$depth-2];
				$name = $page['catname'];
				$title = $page['catdescrip']?$page['catdescrip']:$page['catname'];
				$url = $page['cat_url'];
				$rows[] = (object)$this->set_menu($page,$parent,$depth,$tree,$name,$id,$url,$title);
				//echo "<p>new cat $page[cat_id]=$cat_id ($depth: /$cat_path, parent=$parent): $page[catname]:</p>\n";
			}
			if ($page['page_id'])
			{
				$id = 2 * $page['page_id'] + 1;	// pages have odd id's
				$page_path = $cat_path.'/'.$id;
				$name=$page['pagetitle']?$page['pagetitle']:$page['pagename'];
				$parent = (int)$tree[$depth-1];
				$url= $page['page_url'];
				$rows[] = (object)$this->set_menu($page,$parent,$depth,$tree,$name,$id,$url);
				//echo "- page: $page[page_id]=$id (".($depth+1).": /$page_path, parent=$cat_id): $page[pagename]<br/>\n";
			}
		}
		//_debug_array($rows);
		return $rows;
	}

	/**
	 * Generate one category or page entry
	 *
	 * @param array $page as returned by objbo->getIndex()
	 * @param int $parent parent cat_id
	 * @param int $sublevel
	 * @param array $tree cat_id "path"
	 * @param string $name page or category name
	 * @param int $id unique id (cat's use event, pages odd numbers)
	 * @param string $url for the a href
	 * @param string $title
	 * @return array
	 */
	private function set_menu($page,$parent,$sublevel,$tree,$name,$id,$url,$title=null)
	{
		return array(
			'id' => $id,
			'menutype' => 'mainmenu',
			'name' => $name,
			'alias' => $name,
			'link' => $url,
			'title' => $title ? $title : $name,
	  		'type' => 'url',	//'component' uses JSite::getRouter()->getMode(), while 'url' is left alone!
			'published' => 1,
			'parent' => $parent,
			'componentid' => 20,
			'sublevel' => $sublevel,
			'ordering' => 1,
			'checked_out' => 0,
			'checked_out_time' => '0000-00-00 00:00:00',
			'pollid' => 0,
			'browserNav' => 0,
			'access' => 0,
			'utaccess' => 3,
			/* seems NOT to be used
 			'params' => 'show_page_title=1
page_title=Welcome to the Frontpage
show_description=0
show_description_image=0
num_leading_articles=1
num_intro_articles=4
num_columns=2
num_links=4
show_title=1
pageclass_sfx=
menu_image=-1
secure=0
orderby_pri=
orderby_sec=front
show_pagination=2
show_pagination_results=1
show_noauth=0
link_titles=0
show_intro=1
show_section=0
link_section=0
show_category=0
link_category=0
show_author=1
show_create_date=1
show_modify_date=1
show_item_navigation=0
show_readmore=1
show_vote=0
show_icons=1
show_pdf_icon=1
show_print_icon=1
show_email_icon=1
show_hits=1


',*/
			'lft' => 0,
			'rgt' => 0,
			'home' => 0,//1,	// home == link to home-page, replaces link with JURI::base()
			'component' => 'com_content',
			'tree' => $tree,
		  	'route' => 'home',
		  	'query' => array('option' => 'com_content','view' => 'frontpage'),
			'url'  => $url,	// will be overwritten by 'link' value!
			'_idx' => 0,
		);
	}
}

class JRoute extends JObject
{
	public static function _() { return null; }

}

class JPlugin extends JObject
{

	/**
	 * Required as JA T3 plugin calls parent::__construct()
	 *
	 * @param JObject $subject
	 * @param array $config
	 */
	public function __construct(JObject $subject,array $config)
	{

	}
}

class JCache extends JObject
{

}

class JFolder extends JObject
{

	/**
	 * Utility function to read the files in a folder.
	 *
	 * @param	string	The path of the folder to read.
	 * @param	string	A filter for file names.
	 * @param	mixed	True to recursively search into sub-folders, or an
	 * integer to specify the maximum depth.
	 * @param	boolean	True to return the full path to the file.
	 * @param	array	Array with names of files which should not be shown in
	 * the result.
	 * @return	array	Files in the given folder.
	 * @since 1.5
	 */
	function files($path, $filter = '.', $recurse = false, $fullpath = false, $exclude = array('.svn', 'CVS'))
	{
		// Initialize variables
		$arr = array();

		// Check to make sure the path valid and clean
		$path = JPath::clean($path);

		// Is the path a folder?
		if (!is_dir($path)) {
			JError::raiseWarning(21, 'JFolder::files: ' . JText::_('Path is not a folder'), 'Path: ' . $path);
			return false;
		}

		// read the source directory
		$handle = opendir($path);
		while (($file = readdir($handle)) !== false)
		{
			if (($file != '.') && ($file != '..') && (!in_array($file, $exclude))) {
				$dir = $path . DS . $file;
				$isDir = is_dir($dir);
				if ($isDir) {
					if ($recurse) {
						if (is_integer($recurse)) {
							$arr2 = JFolder::files($dir, $filter, $recurse - 1, $fullpath);
						} else {
							$arr2 = JFolder::files($dir, $filter, $recurse, $fullpath);
						}

						$arr = array_merge($arr, $arr2);
					}
				} else {
					if (preg_match("/$filter/", $file)) {
						if ($fullpath) {
							$arr[] = $path . DS . $file;
						} else {
							$arr[] = $file;
						}
					}
				}
			}
		}
		closedir($handle);

		asort($arr);
		return $arr;
	}
}

class JPath extends JObject
{

	/**
	 * Function to strip additional / or \ in a path name
	 *
	 * @static
	 * @param	string	$path	The path to clean
	 * @param	string	$ds		Directory separator (optional)
	 * @return	string	The cleaned path
	 * @since	1.5
	 */
	public static function clean($path, $ds=DS)
	{
		$path = trim($path);

		if (empty($path)) {
			$path = JPATH_ROOT;
		} else {
			// Remove double slashes and backslahses and convert all slashes and backslashes to DS
			$path = preg_replace('#[/\\\\]+#', $ds, $path);
		}

		return $path;
	}
}

class JFile extends JObject
{

	public static function read($path)
	{
		return file_get_contents($path);
	}
}

class JUtility extends JObject
{
	public static function getToken() { return null; }

}

class JPluginHelper extends JObject
{

}
