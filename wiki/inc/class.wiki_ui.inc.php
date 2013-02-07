<?php
/**
 * eGroupware Wiki - User interface
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (C) 2004-10 by RalfBecker-AT-outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.wiki_ui.inc.php 38310 2012-03-08 06:24:33Z ralfbecker $
 */

class wiki_ui extends wiki_bo
{
	var $public_functions = array(
		'edit' => True,
		'view' => True,	// only redirects to /wiki/index.php for the moment
		'search' => True,
	);
	var $anonymous;		// wiki is used anonymous

	/**
	 * Constructor
	 *
	 * @param int $wiki_id=null wiki_id to pass on to parent if specified, otherwise $_GET['wiki_id'] is used
	 */
	function __construct($wiki_id=null)
	{
		parent::__construct(isset($wiki_id) ? $wiki_id : $_GET['wiki_id']);

		$this->anonymous = $this->config['allow_anonymous'] && $this->config['anonymous_username'] == $GLOBALS['egw_info']['user']['account_lid'];

		$this->tpl = new etemplate();

		// should pages with wiki-syntax be converted to html automaticaly
		switch($this->AutoconvertPages)
		{
			case 'always':
			case 'never':
			case 'onrequest':
				$this->auto_convert = $this->AutoconvertPages == 'always';
				break;
			case 'auto':
			default:
				$this->auto_convert = html::htmlarea_availible();
		}
		if (get_magic_quotes_gpc())
		{
			foreach($_GET as $name => $val)
			{
				$_GET[$name] = stripslashes($val);
			}
		}
	}

	function uiwiki()
	{
		self::__construct();
	}

	function edit($content='')
	{
		//echo "<p>uiwiki::edit() content=<pre>".print_r($content,True)."</pre>\n";
		$this->rateCheck('edit',$_SERVER['REMOTE_ADDR']);

		if (!is_array($content))
		{
			$content['name'] = $content ? $content : $_GET['page'];
			$content['lang'] = $_GET['lang'];
			$content['version'] = $_GET['version'];
			$start = True;
		}
		list($action) = @each($content['action']);
		if (empty($content['name']))
		{
			$this->tpl->location('/wiki/');
		}
		$pg = $this->page($content['name'],$content['lang']);
		if ($content['version'] && $action != 'load')
		{
			$pg->version = $content['version'];
		}
		if ($pg->read() === False)	// new entry
		{
			$pg->lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
		}

		// acl checks
		if (!$pg->acl_check())	// no edit-rights
		{
			$GLOBALS['egw']->redirect($this->ViewURL($content));
		}
		elseif (!$pg->acl_check(True))	// no read-rights
		{
			$this->tpl->location('/wiki/');
		}
		if ($start || $action == 'load')
		{
			$content = $pg->as_array();
			$content['is_html'] = substr($content['text'],0,7) == "<html>\n" && substr($content['text'],-8) == "</html>\n";
		}
		if ($start || $action == 'load' || $action == 'convert')
		{
			if ($content['is_html'])
			{
				$content['text'] = substr($content['text'],7,-8);
			}
			elseif ($this->auto_convert || $action == 'convert')
			{
				$content['text'] = $this->parse($pg,'Convert');
				$content['is_html'] = True;
			}
		}

		if ($content['is_html'])
		{
			// some tavi stuff need to be at the line-end
			$content['text'] = preg_replace(array('/(.+)(<br \\/>)/i',"/(<br \\/>\n?)+$/i"),array("\\1\n\\2",''),$content['text']);

			$content['preview'] = $this->parse("<html>\n".$content['text']."\n</html>\n",'Parse',$content['name']);
		}
		else
		{
			$content['preview'] = $this->parse($content['text'],'Parse',$content['name']);
		}
		if (empty($content['title'])) $content['title'] = $content['name'];
		//echo "<p>uiwiki::edit() action='$action', content=<pre>".print_r($content,True)."</pre>\n";

		if ($action)
		{
			switch($action)
			{
				case 'delete':
					$content['text'] = '';
					$content['comment'] = lang('deleted');
					$content['is_html'] = False;	// else page is not realy empty
				case 'rename':
				case 'save':
				case 'apply':
					// do save
					if ($content['is_html'])
					{
						$content['text'] = "<html>\n".$content['text']."\n</html>\n";
					}
					if ($action == 'rename')
					{
						$this->rename($content,$content['old_name'],$content['old_lang']);
					}
					else
					{
						$this->write($content);
					}
					if ($content['is_html'])
					{
						$content['text'] = substr($content['text'],7,-8);
					}
			}
			switch($action)
			{
				case 'delete':
					$content = '';	// load the Homepage
				case 'save':
				case 'cancel':
					// return to view
					$GLOBALS['egw']->redirect($this->ViewURL($content));
					break;
			}
		}
		$acl_values = array(
			WIKI_ACL_ALL =>   lang('everyone'),
			WIKI_ACL_USER =>  lang('users'),
			WIKI_ACL_ADMIN => lang('admins'),
		);
		$this->tpl->read('wiki.edit');

		if ($content['is_html'] || $this->AutoconvertPages == 'never' || !html::htmlarea_availible())
		{
			$this->tpl->disable_cells('action[convert]');
			$content['upload_dir'] = $this->upload_dir;
			$content['rtfEditorFeatures'] = $GLOBALS['egw_info']['user']['preferences']['wiki']['rtfEditorFeatures'];
		}
		$GLOBALS['egw_info']['flags']['app_header'] = $GLOBALS['egw_info']['apps']['wiki']['title'] . ' - ' .
			lang('edit') . ' ' . $content['name'] .
			($content['lang'] && $content['lang'] != $GLOBALS['egw_info']['user']['preferences']['common']['lang'] ?
				':' . $content['lang'] : '').
			($content['name'] != $content['title'] ? ' - ' . $content['title'] : '');
		$this->tpl->exec('wiki.wiki_ui.edit',$content,array(
			'lang'     => array('' => lang('not set')) + $GLOBALS['egw']->translation->get_installed_langs(),
			'readable' => $acl_values,
			'writable' => $acl_values,
		),False,array(
			'wiki_id'  => $content['wiki_id'],
			'old_name' => isset($content['old_name']) ? $content['old_name'] : $content['name'],
			'old_lang' => isset($content['old_lang']) ? $content['old_lang'] : $content['lang'],
			'version'  => $content['version'],
			'is_html'  => $content['is_html'],
		));
	}

	/**
	 * Show a wiki page
	 *
	 * redirects to /wiki/index.php for the moment
	 */
	function view($return_content=false)
	{
		$this->rateCheck('view',$_SERVER['REMOTE_ADDR']);

		$page =& $this->page($_GET['page'] ? $_GET['page'] : $this->config['wikihome'],$_GET['lang']);
		if ($_GET['version']) $page->version = $_GET['version'];

		if ($page->read() === false)
		{
			$html = '<p><b>'.lang("Page '%1' not found !!!",'<i>'.html::htmlspecialchars($_GET['page'].
				($_GET['lang']?':'.$_GET['lang']:'')).'</i>')."</b></p>\n";
			$page = false;
		}
		if ($page && !$page->acl_check(True))	// no read-rights
		{
			$this->tpl->location('/wiki/');
		}
		$html = $this->header($page).$html;
		if ($page) $html .= $this->get($page,'',$this->wiki_id);
		$html .= $this->footer($page);

		if ($return_content) return $html;

		echo $html;
	}

	/**
	 * Show the page-header for the manual
	 *
	 * @param object/boolean $page sowikipage object or false
 	 * @param string $title title of the search
	 */
	function header($page=false,$title='')
	{
		// anonymous sessions have no navbar !!!
		$GLOBALS['egw_info']['flags']['nonavbar'] = $this->config['allow_anonymous'] != 'Navbar' && $this->anonymous;
		$GLOBALS['egw']->common->egw_header();

		if ($page)
		{
			$title = '<a href="'.$GLOBALS['egw']->link('/index.php',array(
				'menuaction' => 'wiki.wiki_ui.search',
				'search'     => $page->name,
			)).'">'.$page->title.'</a>';
		}
		$html = '<h1 style="margin:0px;" class="title">'.$title."</h1>\n";

		$html .= '<form action="'.$GLOBALS['egw']->link('/index.php',array('menuaction'=>'wiki.wiki_ui.search')).'" method="POST">'.
			'<a href="'.$this->viewURL($this->config['wikihome']).'">'.$this->config['wikihome'].'</a> | '.
			'<a href="'.$this->viewUrl('RecentChanges').'">'.lang('Recent Changes').'</a> | '.
			'<input name="search" value="'.html::htmlspecialchars($_REQUEST['search']).'" /> '.
			'<input type="submit" name="go" value="'.html::htmlspecialchars(lang('Search')).'" /></form>'."\n";
		$html .= "<hr />\n";

		return $html;
	}

	/**
	 * Show the page-footer for the manual
	 *
	 * @param object/boolean $page sowikipage object or false
 	 */
	function footer($page=false)
	{
		$parts = array();

		if ($page)
		{
			$parts[] = $page->acl_check() ? '<a href="'.htmlspecialchars($this->editURL($page->name,$page->lang,$page->version)).'">'.
				($page->supercede == $page->time ? lang('Edit this document') : lang('Edit this <em>ARCHIVE VERSION</em> of this document')).'</a>' :
				lang('This page can not be edited.');

			$parts[] = '<a href="'.htmlspecialchars($this->historyURL($page->name,false,$page->lang)).'">'.lang('View document history').'</a>';

			$parts[] = lang('Document last modified').': '.html_time($page->time);
		}
		return $parts ? "<hr />\n".implode(' | ',$parts) : '';
	}

	/**
	 * search the manual and display the result
	 */
	function search($return_content=false)
	{
		$this->rateCheck('search',$_SERVER['REMOTE_ADDR']);

		if (strlen(trim($_REQUEST['search']))==0) $_REQUEST['search'] = '*';

		$html = $this->header(false,lang('Search for').': '.(trim($_REQUEST['search'])=='*'?lang('everything'):html::htmlspecialchars($_REQUEST['search'])));

		$nothing_found = true;
		foreach($this->find(str_replace(array('*','?'),array('%','_'),$_REQUEST['search'])) as $page)
		{
			if ($nothing_found)
			{
				$nothing_found = false;
				$html .= "<ul>\n";
			}
			$item = '<li><a href="'.htmlspecialchars($this->viewURL($page['name'],$page['lang'])).'"><b>'.html::htmlspecialchars($page['title']).'</b></a>'.
				($page['lang'] != $this->lang ? ' <i>'.html::htmlspecialchars($GLOBALS['egw']->translation->lang2language($page['lang'])).'</i>' : '').'<br />'.
				html::htmlspecialchars($this->summary($page))."</li>\n";

			if ($page['lang'] != $this->lang)
			{
				$other_langs .= $item;
				continue;
			}
			$html .= $item;
		}
		if ($other_langs) $html .= $other_langs;

		if (!$nothing_found)
		{
			$html .= "</ul>\n";
		}
		else
		{
			$html .= '<p><i>'.lang('The search returned no result!')."</i></p>\n";
		}
		$html .= $this->footer();

		if ($return_content) return $html;

		echo $html;
	}
}
