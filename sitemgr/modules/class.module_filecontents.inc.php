<?php
/**
 * EGroupware SiteMgr CMS - filecontents module
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage modules
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_filecontents.inc.php 34694 2011-04-15 10:42:04Z ralfbecker $
 */

/**
 * Filecontents module: displays/includes content of files or urls
 *
 * If Zend Framework is install, you can also use a css query to get a part of the included html.
 * Eg. "div#id" or "div.class"
 */
class module_filecontents extends Module
{
	/**
	 * Pearl regular expression to replace html page with content between body tags (replace='$1')
	 *
	 * @var string
	 */
	const GRAB_BODY = '/^.*<body[^>]*>(.*)<\/body>.*$/si';

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->arguments = array(
			'filepath' => array(
				'type' => 'textfield',
				'label' => lang('The complete URL or path to a file to be included'),
				'params' => array('size' => 50),
			),
		);
		// if Zend Framework is installed, allow css queries to get parts of html
		if (@include_once('Zend/Dom/Query.php'))
		{
			$this->arguments['css_query'] = array(
				'type' => 'textfield',
				'label' => lang('CSS selector to use only part of html file').'<br />'.
					lang('eg. %1 or %2','"div#id", "table.classname"','"div[attr=\'value\'] > h1"'),
				'params' => array('size' => 50),
			);
		}
		$this->arguments['cache_time'] = array(
			'type' => 'textfield',
			'label' => lang('How long to cache downloaded content (seconds)'),
			'params' => array('size' => 5),

		);
		$this->title = lang('File contents');
		$this->description = lang('This module includes the contents of an URL or file (readable by the webserver and in its docroot !)');
	}

	/**
	 * Get module content
	 *
	 * @see Module::get_content()
	 * @param array &$arguments
	 * @param array $properties
	 * @return string
	 */
	function get_content(&$arguments,$properties)
	{
		if ((int)$arguments['cache_time'] &&
			($ret = egw_cache::getInstance('sitemgr', $cache_token = md5(serialize($arguments)))))
		{
			return $ret;
		}
		$url = parse_url($path = $arguments['filepath']);

		if (empty($path))
		{
			return '';
		}
		if (!$this->validate($arguments))
		{
			return $this->validation_error;
		}
		$is_html = preg_match('/\.html?$/i',$path);

		if ($this->is_script($path) || @$url['scheme'])
		{
			if (!@$url['scheme'])
			{
				$url['scheme'] = $_SERVER['HTTPS'] ? 'https' : 'http';
				if (empty($url['host'])) $url['host'] = $_SERVER['HTTP_HOST'];
				$path = $url['scheme'].'://'.$url['host'].str_replace($_SERVER['DOCUMENT_ROOT'],'',$path);
			}
			$http_options = array(
				'timeout' => 5,	// default is 60s
			);
			// use a proxy, if one is configured in EGroupware setup >> configuration
			if ($GLOBALS['egw_info']['server']['httpproxy_server'])
			{
				$http_options['proxy'] = 'tcp://'.
					($GLOBALS['egw_info']['server']['httpproxy_server_username'] ? $GLOBALS['egw_info']['server']['httpproxy_server_username'].
						($GLOBALS['egw_info']['server']['httpproxy_server_password'] ? ':'.$GLOBALS['egw_info']['server']['httpproxy_server_password'] : '').'@' : '').
					$GLOBALS['egw_info']['server']['httpproxy_server'].
					($GLOBALS['egw_info']['server']['httpproxy_port'] ? ':'.$GLOBALS['egw_info']['server']['httpproxy_port'] : '');
				$http_options['request_fulluri'] = true;	// some proxy require that
			}
			if (($ret = file_get_contents($path,false,stream_context_create(array('http' => $http_options)))))
			{
				if ($url['scheme'] == 'http' || $url['scheme'] == 'https')
				{
					foreach($http_response_header as $header)
					{
						if (substr($header,0,14) == 'Content-Type: ')
						{
							list($content_type,$charset) = explode('; charset=',substr($header,14));
							break;
						}
					}
				}
				$is_html = $content_type === 'text/html' || !$content_type && substr($path,-4) != '.txt';
			}
		}
		else
		{
			$ret = file_get_contents($path);
		}
		if ($ret === false)
		{
			$ret = lang('File %1 is not readable by the webserver !!!',$path);
		}
		//echo "<p>$header --> content_type=$content_type, charset=$charset, is_html=".array2string($is_html)."</p>\n";
		if ($is_html)
		{
			if ($charset || preg_match('/<meta http-equiv="content-type" content="text\/html; ?charset=([^"]+)"/i',$ret,$parts) && ($charset = $parts[1]))
			{
				$ret = translation::convert($ret,$charset);
				// fix the charset in content-type, as Zend_Dom_Query relies on it
				$ret = str_replace('charset='.$charset.'"','charset='.translation::charset().'"',$ret);
			}
			// replace images and links with correct host
			if ($is_html && ($url['scheme'] == 'http' || $url['scheme'] == 'https'))
			{
				$ret = strtr($ret,array(
					'src="/' => 'src="'.$url['scheme'].'://'.$url['host'].'/',
					'href="/' => 'href="'.$url['scheme'].'://'.$url['host'].'/',
				));
				// deal with relative pathes
				if (preg_match('/(src|href)="(?!https?:\/\/)/',$ret))
				{
					// check for a possible base href
					if (preg_match('/<base href="([^"]+)"/si',$ret,$matches))
					{
						$base = $matches[1];
						if (substr($base,-1) != '/') $base .= '/';
					}
					else	// otherwise use directory of $path (egw_vfs::dirname deals with url's correctly)
					{
						$base = egw_vfs::dirname($path).'/';
					}
					$ret = preg_replace('/(src|href)="(?!https?:\/\/)/i','\\1="'.$base,$ret);
				}
			}
			// for html use css query if given AND Zend Framework available
			if ($arguments['css_query'] && @include_once('Zend/Dom/Query.php'))
			{
				$dom = new Zend_Dom_Query();	// specifying document direct in constructor uses Xml,
				$dom->setDocumentHtml($ret);	// if document has xml head and does NOT work, if not valid xml
				$dom->setEncoding(translation::charset());
				$ret = '';
				foreach($dom->query($arguments['css_query']) as $element)
				{
					$ret .= simplexml_import_dom($element)->asXML()."\n";
				}
			}
			elseif (preg_match('/<body>(.*)<\/body>/si',$ret,$matches))
			{
				$ret = $matches[1];
			}
		}
		if(substr($path,-4) == '.txt' || $content_type === 'text/plain')
		{
			$ret = "<pre style='white-space: pre-wrap; text-align: left;'>\n".
				html::htmlspecialchars($ret)."\n</pre>\n";
		}
		if (isset($cache_token))
		{
			$ok = egw_cache::setInstance('sitemgr', $cache_token, $ret, (int)$arguments['cache_time']);
		}
		if (empty($ret)) $ret = ' ';	// otherwise block outline is not display and block can not be edited

		return $ret;
	}

	/**
	 * test if $path lies within the webservers document-root
	 *
	 * @param string $path
	 * @return boolean
	 */
	function in_docroot($path)
	{
		$docroots = array(EGW_SERVER_ROOT,$_SERVER['DOCUMENT_ROOT']);
		$path = realpath($path);

		foreach ($docroots as $docroot)
		{
			$len = strlen($docroot);

			if ($docroot == substr($path,0,$len))
			{
				$rest = substr($path,$len);

				if (!strlen($rest) || $rest[0] == DIRECTORY_SEPARATOR)
				{
					return True;
				}
			}
		}
		return False;
	}

	/**
	 * Check if url refers to a script
	 *
	 * @param string $url
	 * @return boolean
	 */
	function is_script($url)
	{
		$url = parse_url($url);

		return preg_match('/\.(php.?|pl|py)$/i',$url['path']);
	}

	/**
	 * Validate given parameters: url or path and regular expression
	 *
	 * @see Module::validate()
	 * @param array &$data arguments of block
	 * @return boolean
	 */
	function validate(&$data)
	{
		// check if regular expression contains /e modifier, we can NOT allow that!
		if (!empty($data['preg']))
		{
			$parts = explode($data['preg'][0],$data['preg']);
			if (strpos(array_pop($parts),'e') !== false)
			{
				$this->validation_error = lang('Regular expression modifier "%1" in "%2" is NOT allowed!','e',htmlspecialchars($data['preg']));
				return false;
			}
		}
		$url = parse_url($data['filepath']);
		$allow_url_fopen = ini_get('allow_url_fopen');

		if ($url['scheme'] || $this->is_script($data['filepath']) && !$allow_url_fopen)
		{
			if (!$allow_url_fopen)
			{
				$this->validation_error = lang("Can't open an URL or execute a script, because allow_url_fopen is not set in your php.ini !!!");
				return false;
			}
			return True;
		}
		if (!is_readable($url['path']))
		{
			$this->validation_error = lang('File %1 is not readable by the webserver !!!',$data['filepath']);
			return false;
		}
		if (!$this->in_docroot($data['filepath']))
		{
			$this->validation_error = lang('File %1 is outside the docroot of the webserver !!!<br>This module does NOT allow - for security reasons - to open files outside the docroot.',$data['filepath']);
			return false;
		}
		return true;
	}
}
