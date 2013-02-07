<?php
/**
 * news_admin - admin user interface
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package news_admin
 * @copyright (c) 2007 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.news_admin_ui.inc.php 29103 2010-02-04 02:20:55Z ralfbecker $
 */

require_once(EGW_INCLUDE_ROOT.'/news_admin/inc/class.bonews.inc.php');

/**
 * Admin user interface of the news_admin
 */
class news_admin_ui extends bonews
{
	/**
	 * Methods callable via menuaction
	 *
	 * @var array
	 */
	var $public_functions = array(
		'cat'  => true,
		'cats' => true,
	);
	/**
	 * Instance of the etemplate object
	 *
	 * @var etemplate
	 */
	var $tpl;

	/**
	 * Constructor
	 *
	 * @return uinews
	 */
	function news_admin_ui()
	{
		$this->bonews();

		$this->tpl =& CreateObject('etemplate.etemplate');
	}

	/**
	 * Edit a news
	 *
	 * @param array $content=null submitted etemplate content
	 * @param string $msg=''
	 */
	function cat($content=null,$msg='')
	{
		if (!is_array($content))
		{
			if (!(int) $_GET['cat_id'] || !($content = $this->read_cat($_GET['cat_id'])))
			{
				$content = array(
					'cat_writable' => $this->user,
					'cat_owner' => isset($GLOBALS['egw_info']['user']['apps']['admin']) ? -1 : $this->user,
				);
			}
		}
		else
		{
			if ($content['button'])
			{
				list($button) = each($content['button']);
				unset($content['button']);
			}
			elseif($content['delete'])
			{
				list($id) = each($content['button']);
				unset($content['delete']);
				$button = 'delete';
			}

			switch($button)
			{
				case 'delete':
					if ($this->delete_cat($content))
					{
						$msg = lang('Category deleted.');
						echo "<html><body><script>var referer = opener.location;opener.location.href = referer+(referer.search?'&':'?')+'msg=".
							addslashes(urlencode($msg))."'; window.close();</script></body></html>\n";
						$GLOBALS['egw']->common->egw_exit();
					}
					break;

				case 'apply':
				case 'save':
					if ($content['import_url'] && $content['cat_writable'])
					{
						$content['cat_writable'] = '';
						$msg = lang('Imported feeds can NOT be writable!').' ';
					}
					if (($content['cat_id'] = $this->save_cat($content)))
					{
						$msg .= lang('Category saved.');
						$js = "opener.location.href=opener.location.href+'&msg=".addslashes(urlencode($msg))."';";
					}
					else
					{
						$msg .= lang('Error saving the category!');
						$button = '';
					}
					if ($button == 'save')
					{
						$js .= 'window.close();';
						echo "<html>\n<body>\n<script>\n$js\n</script>\n</body>\n</html>\n";
						$GLOBALS['egw']->common->egw_exit();
					}
					elseif ($js)
					{
						$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\n$js\n</script>\n";
					}
					break;

				case 'import':
					require_once(EGW_INCLUDE_ROOT.'/news_admin/inc/class.news_admin_import.inc.php');
					$import = new news_admin_import($this);
					if ((list($imported,$newly) = $import->import($content['cat_id'],$content['import_url'])) === false)
					{
						$msg = lang('Error importing the feed!');
					}
					else
					{
						$msg = lang('%1 news imported (%2 new).',$imported,$newly);
						$js = "opener.location.href=opener.location.href+'&msg=".addslashes(urlencode($msg))."';";
						$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\n$js\n</script>\n";
					}
					break;

				case 'cancel':	// should never happen
					break;
			}
		}
		$preserve = $content;
		$content['msg'] = $msg;
		$content['is_admin'] = isset($GLOBALS['egw_info']['user']['apps']['admin']);
		$content['import_available'] = $this->import_available();
		if (!$content['import_frequency']) $content['import_frequency'] = 4;	// every 4h

		$readonlys = array();
		if ($content['cat_id'] && !$this->admin_cat($content))
		{
			foreach($content as $name => $value)
			{
				$readonlys[$name] = true;
			}
			$readonlys['button[import]'] = $readonlys['button[delete]'] = $readonlys['button[save]'] = $readonlys['button[apply]'] = true;
		}
		if (!$content['cat_id']) $readonlys['button[delete]'] = true;
		if ($content['cat_id']) $readonlys['cat_owner'] = true;	// cat class can only set owner when creating new cats
		if (!$content['import_url'] || !$content['cat_id']) $readonlys['button[import]'] = true;

		$this->tpl->read('news_admin.cat');
		return $this->tpl->exec('news_admin.news_admin_ui.cat',$content,$sel_options,$readonlys,$preserve,2);
	}

	/**
	 * List the categories to administrate them
	 *
	 * @param array $content=null submitted etemplate content
	 * @param string $msg=''
	 * @return string
	 */
	function cats($content=null,$msg='')
	{
		if ($_GET['msg']) $msg = $_GET['msg'];

		if ($content['nm']['rows']['delete'])
		{
			list($id) = each($content['nm']['rows']['delete']);
			if ($this->delete_cat($id))
			{
				$msg = lang('Category deleted.');
			}
		}
		$content = array(
			'msg' => $msg,
			'nm'  => $GLOBALS['egw']->session->appsession('cats','news_admin'),
		);
		if (!is_array($content['nm']))
		{
			$content['nm'] = array(
				'get_rows'       =>	'news_admin.news_admin_ui.get_cats',	// I  method/callback to request the data for the rows eg. 'notes.bo.get_rows'
//				'header_right'   => 'news_admin.index.right',
				'bottom_too'     => false,		// I  show the nextmatch-line (arrows, filters, search, ...) again after the rows
				'start'          =>	0,			// IO position in list
				'no_cat'         =>	true,		// IO category, if not 'no_cat' => True
				'search'         =>	'',			// IO search pattern
				'order'          =>	'news_date',// IO name of the column to sort after (optional for the sortheaders)
				'sort'           =>	'DESC',		// IO direction of the sort: 'ASC' or 'DESC'
				'col_filter'     =>	array(),	// IO array of column-name value pairs (optional for the filterheaders)
				'no_filter'      => true,
				'no_filter2'     => true,
			);
		}
		$this->tpl->read('news_admin.cats');
		return $this->tpl->exec('news_admin.news_admin_ui.cats',$content,array(
		),$readonlys);
	}

	/**
	 * rows callback for index nextmatch
	 *
	 * @internal
	 * @param array &$query
	 * @param array &$rows returned rows/cups
	 * @param array &$readonlys eg. to disable buttons based on acl
	 * @return int total number of contacts matching the selection
	 */
	function get_cats(&$query_in,&$rows,&$readonlys,$id_only=false)
	{
		$GLOBALS['egw']->session->appsession('cats','news_admin',$query=$query_in);

		$total = parent::get_cats($query,$rows);

		$readonlys = array();
		foreach($rows as $k => $row)
		{
			$readonlys['edit['.$row['cat_id'].']']   = $readonlys['delete['.$row['cat_id'].']'] = !$this->admin_cat($row);
		}
		//_debug_array($rows);
		return $total;
	}
}
