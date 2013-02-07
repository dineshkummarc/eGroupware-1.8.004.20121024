<?php
/**
 * eGroupWare - Online User manual
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package manual
 * @copyright (c) 2004-9 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.uimanual.inc.php 35059 2011-05-28 07:52:05Z ralfbecker $
 */

class uimanual extends wiki_ui
{
	var $public_functions = array(
		'view'   => True,
		'search' => True,
	);
	var $manual_config;

	function __construct()
	{
		$this->manual_config = config::read('manual');
		if (!is_array($this->manual_config) || !isset($this->manual_config['manual_update_url']))	// empty never get's stored
		{
			foreach(array(
				'manual_remote_egw_url'     => 'http://manual.egroupware.org/egroupware',
				'manual_update_url'         => 'http://manual.egroupware.org/egroupware/wiki/index.php?page=Manual&action=xml',
				'manual_wiki_id'            => 1,
				'manual_allow_anonymous'    => '',		// no
				'manual_anonymous_user'     => 'anonymous',
				'manual_anonymous_password' => 'anonymous',
			) as $name => $default)
			{
				if (!isset($this->manual_config[$name]) ||
					$name == 'manual_update_url' && $this->manual_config[$name] == 'http://egroupware.org/egroupware/wiki/index.php?page=Manual&action=xml')
				{
					config::save_value($name,$this->manual_config[$name]=$default,'manual');
				}
			}
		}
		$this->wiki_id = (int) $this->manual_config['manual_wiki_id'];

		// use https to not get page contains unsave content warnings
		if ($_SERVER['HTTPS'] && $this->manual_config[manual_remote_egw_url] == 'http://manual.egroupware.org/egroupware')
		{
			$this->manual_config[manual_remote_egw_url] = 'https://manual.egroupware.org/egroupware';
		}
		// set a language given in the URL as session preference
		if ($this->manual_config['manual_allow_anonymous'] && isset($_REQUEST['lang']) && preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$_REQUEST['lang']) &&
			$_REQUEST['lang'] != $GLOBALS['egw_info']['user']['preferences']['common']['lang'])
		{
			$GLOBALS['egw']->preferences->add('common','lang',$_REQUEST['lang'],'session');
			$GLOBALS['egw_info']['user']['preferences']['common']['lang']=$_REQUEST['lang'];
		}
		$this->lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];

		parent::__construct($this->wiki_id);
	}

	/**
	 * reimplemented that the we stay inside the manual app
	 */
	function viewURL($page, $lang='', $version='', $full = '')
	{
		$args = array(
			'menuaction' => 'manual.uimanual.view',
		);
		if ($lang || @$page['lang'])
		{
			$args['lang'] = $lang ? $lang : @$page['lang'];
			if ($args['lang'] == $GLOBALS['egw_info']['user']['prefereces']['common']['lang']) unset($args['lang']);
		}
		if ($version)
		{
			$args['version'] = $version;
		}
		if ($full)
		{
			$args['full'] = 1;
		}
		// the page-parameter has to be the last one, as the old wiki code only calls it once with empty page and appends the pages later
		return $GLOBALS['egw']->link('/index.php',$args).'&page='.urlencode(is_array($page) ? $page['name'] : $page);
	}

	/**
	 * reimplemented to disallow editing
	 */
	function editURL($page, $lang='',$version = '')
	{
		return False;
	}

	/**
	 * Show the page-header for the manual
	 *
	 * @param object/boolean $page sowikipage object or false
	 * @param string $title title of the search
	 */
	function header($page=false,$title='')
	{
		$GLOBALS['egw']->common->egw_header();

		// let the (existing) window pop up
		$html .= "<script language=\"JavaScript\">\n\twindow.focus();\n</script>\n";
		$html .= '<div id="divMain">'."\n";

		if ($page && ($app = preg_match('/^Manual([A-Z]{1}[a-z]+)[A-Z]+/',$page->name,$matches) ? $matches[1] : false))
		{
			$app_page =& $this->page('Manual'.$app);
			if ($app_page->read() === False) $app = false;
		}
		$html .= '<form action="'.$GLOBALS['egw']->link('/index.php',array('menuaction'=>'manual.uimanual.search')).'" method="POST">'.
			(isset($_GET['referer']) ? html::image('phpgwapi','left-grey',lang('Back')) :
			html::a_href(html::image('phpgwapi','left',lang('Back')),'','','onclick="history.back(); return false;"')).' | '.
			'<a href="'.htmlspecialchars($this->viewURL('Manual')).'">'.lang('Index').'</a> | '.
			($app ? '<a href="'.htmlspecialchars($this->viewUrl('Manual'.$app)).'">'.lang($app).'</a> | ' : '').
			'<input name="search" value="'.html::htmlspecialchars($_REQUEST['search']).'" />&nbsp;'.
			'<input type="submit" name="go" value="'.html::htmlspecialchars(lang('Search')).'" /></form>'."\n";
		$html .= "<hr />\n";

		if ($title) $html .= '<p><b>'.$titel."</b></p>\n";

		return $html;
	}

	/**
	 * Show the page-footer for the manual
	 *
	 * @param object/boolean $page sowikipage object or false
	 */
	function footer($page=false)
	{
		return "\n</div>\n";
	}

	/**
	 * view a manual page
	 */
	function view()
	{
		if ($this->manual_config['manual_remote_egw_url'])
		{
			if (isset($_GET['referer']))
			{
				$_SERVER['HTTP_REFERER'] = $_GET['referer'];
			}
			$referer = $GLOBALS['egw']->common->get_referer();
			$url = $this->manual_config['manual_remote_egw_url'].'/manual/index.php?referer='.
				urlencode($this->manual_config['manual_remote_egw_url'].$referer).
				(isset($_GET['page']) ? '&page='.urlencode($_GET['page']): '').
				'&lang='.urlencode($GLOBALS['egw_info']['user']['preferences']['common']['lang']);
			//echo htmlentities($url); exit;
			$GLOBALS['egw']->redirect($url);
		}
		if (isset($_GET['page']))
		{
			$pages[] = $_GET['page'];
		}
		if (isset($_GET['referer']) || !isset($_GET['page']))
		{
			// use the referer
			$referer = $GLOBALS['egw']->common->get_referer('',$_GET['referer']);
			list($referer,$query) = explode('?',$referer,2);
			parse_str($query,$query);
			//echo "<p>_GET[referer]='$_GET[referer]', referer='$referer', query=".print_r($query,True)."</p>\n";

			if (isset($query['menuaction']) && $query['menuaction'])
			{
				list($app,$class,$function) = explode('.',$query['menuaction']);
				// for acl-preferences use the app-name from the query and acl as function
				if ($app == 'preferences' && $class == 'uiaclprefs')
				{
					$app = $query['acl_app'] ? $query['acl_app'] : $_GET['acl_app'];
					$pages[] = 'Manual'.ucfirst($app).'Acl';
				}
				elseif ($app == 'preferences' && $class == 'uisettings')
				{
					$app = $query['appname'] ? $query['appname'] : $_GET['appname'];
					$pages[] = 'Manual'.ucfirst($app).'Preferences';
				}
				elseif ($app == 'admin' && $class == 'uiconfig')
				{
					$app = $query['appname'] ? $query['appname'] : $_GET['appname'];
					$pages[] = 'Manual'.ucfirst($app).'Config';
				}
				$pages[] = 'Manual'.ucfirst($app).ucfirst($class).ucfirst($function);
				$pages[] = 'Manual'.ucfirst($app).ucfirst($function);
				$pages[] = 'Manual'.ucfirst($app).ucfirst($class);
			}
			else
			{
				$parts = explode('/',$referer);
				unset($parts[0]);
				$app  = array_shift($parts);
				$file = str_replace('.php','',array_pop($parts));
				if (empty($file)) $file = 'index';
				// for preferences use the app-name from the query
				if ($app == 'preferences' && $file == 'preferences')
				{
					$app = $query['appname'] ? $query['appname'] : $_GET['appname'];
				}
				$pages[] = 'Manual'.ucfirst($app).ucfirst($file);
			}
			$pages[] = 'Manual'.ucfirst($app);
		}
		// show the first page-hit
		foreach($pages as $name)
		{
			$page =& $this->page($name);
			if ($page->read() !== False)
			{
				break;
			}
			$page = false;
		}
		//echo "<p>page='".(is_object($page) ? $page->name : $page)."' from ".implode(', ',$pages)."</p>\n";
		if (!$page)
		{
			$html = '<p><b>'.lang("Page(s) %1 not found !!!",'<i>'.implode(', ',$pages).'</i>')."</b></p>\n";
			// show the Manual startpage
			$page =& $this->page('Manual');
			if ($page->read() === false) $page = false;
		}
		$html = $this->header($page).$html;
		$html .= $this->get($page,'',$this->wiki_id);
		$html .= $this->footer();

		echo $html;
	}
}
