<?php
/**
 * sitemgr - Notifications User Interface Object
 *
 * @link http://www.egroupware.org
 * @author Jose Luis Gordo Romero <jgordor-AT-gmail.com>
 * @package sitemgr
 * @copyright (c) 2007 by Jose Luis Gordo Romero <jgordor-AT-gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

require_once(EGW_INCLUDE_ROOT.'/sitemgr/inc/class.bontfmess.inc.php');

class uintfmess extends bontfmess
{
	/**
	 * Functions callable via menuaction
	 *
	 * @var array
	 */
	var $public_functions = array(
		'edit'  => true,
		'index' => true,
	);

	var $all_sites = array();

	/**
	 * Constructor
	 *
	 */
	function uintfmess()
	{
		$this->bontfmess();
		$this->get_all_sites();
	}


	function get_all_sites()
	{
		foreach ($this->bosite->list_sites() as $site)
		{
			$sites[$site['site_id']]=$site['site_name'];
		}

		$this->all_sites = array(lang('All'))+$sites;
	}

	/**
	 * Edit a notification in a popup
	 *
	 * @param array $content=null eTemplate content
	 * @param string $msg=''
	 * @param boolean $popup=true use or not use a popup
	 * @return string html-content, if sitemgr otherwise null
	 */
	function edit($content=null,$msg='',$popup=true)
	{
		if (!is_array($content))
		{
			// edit or new?
			if ((int)$_GET['message_id'])
			{
				if (!$this->read((int)$_GET['message_id']))
				{
					$msg = lang('Notification Message not found !!!');
					$this->init();
				}
			}
			else	// new item
			{
				$this->init();
			}

			if ($_GET['nopopup']) $popup = false;

			if ($popup)
			{
				$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\nwindow.focus();\n</script>\n";
			}

		}
		else	// submitted form
		{
			list($button) = @each($content['button']); unset($content['button']);
			$popup = $content['popup']; unset($content['popup']);

			$this->data = $content;

			switch($button)
			{
				case 'save':
					if ($this->save() == 0)
					{
						$msg = lang('Entry saved');
						$js = "opener.location.href=opener.location.href.replace(/&tr_id=[0-9]+/,'')+(opener.location.href.indexOf('?')<0?'?':'&')+'msg=".
							addslashes(urlencode($msg))."&site_id=$content[site_id]';";
					}
					else
					{
						$msg = lang('Error saving the entry!!!');
					}
					if ($popup)
					{
						$js .= 'window.close();';
						echo "<html>\n<body>\n<script>\n$js\n</script>\n</body>\n</html>\n";
						$GLOBALS['egw']->common->egw_exit();
					}
					else
					{
						unset($_GET['message_id']);	// in case it's still set
						return $this->index(null,null,$msg);
					}
					break;
				case 'delete':
					if ($this->delete())
					{
						$msg = lang('Entry deleted');
						$js = "opener.location.href=opener.location.href+'&msg=$msg';";
					}
					else
					{
						$msg = lang('Error deleting the entry!!!');
						break;	// dont close window
					}
					// fall-through for save
				case 'cancel':
					if ($popup)
					{
						$js .= 'window.close();';
						echo "<html>\n<body>\n<script>\n$js\n</script>\n</body>\n</html>\n";
						$GLOBALS['egw']->common->egw_exit();
					}
					else
					{
						unset($_GET['message_id']);	// in case it's still set
						return $this->index(null,null,$msg);
					}
					break;

				default:
					break;
			}
		}

		$tpl = new etemplate('sitemgr.ntfmess.edit');

		$preserv = $content = $this->data;

		$sel_options = array(
							'site_id' => array_diff($this->all_sites,array(lang('All'))),
							);

		$preserv['popup'] = $popup;

		$readonlys=array();

		return $tpl->exec('sitemgr.uintfmess.edit',$content,$sel_options,$readonlys,$preserv,$popup ? 2 : 0);
	}

	function index($content=null,$msg='')
	{
		if (!is_array($content))
		{
			if (!$site_id && $_GET['site_id']) $site_id = $_GET['site_id'];
			if ($_GET['message_id'])
			{
				if (!$this->read($_GET['message_id']))
				{
					$msg = lang('Message not found !!!');
				}
				else
				{
					return $this->edit(null,'',false);	// false = use no popup
				}
			}
		}

		if (!$msg and $_GET['msg']) $msg = $_GET['msg'];

		$tpl = new etemplate('sitemgr.ntfmess.index');

		$content = $content['nm']['rows'];

		if ($content['delete'])
		{
			$message_id = array_search('pressed',$content['delete']);

			if ($this->delete(array('message_id' => $message_id)))
			{
				$msg .= lang('Entry deleted');
			}
			else
			{
				$msg .= lang('Error deleting the entry!!!');
			}
		}


		$sel_options = array(
			'filter'   => $this->all_sites,
		);

		if (!is_array($content['nm']))
		{
			$content['nm'] = array(
				'get_rows'       =>	'sitemgr.uintfmess.get_rows',
				'no_cat'         => true,
				'no_filter2'     => true,
				'msg'			 => $msg,
				'filter'         => $site_id ? $site_id : '0', // all
				'filter_label'   => lang('Site'),
				'filter_no_lang' => true,
				'filter2'		 => 1,
				'order'          =>	'message_id',// IO name of the column to sort after (optional for the sortheaders)
				'sort'           =>	'DESC',// IO direction of the sort: 'ASC' or 'DESC'4
				'header_right'   =>	'sitemgr.ntfmess.index.right',
			);
		}

		return $tpl->exec('sitemgr.uintfmess.index',$content,$sel_options,$readonlys);
	}

	/**
	 * query rows for the nextmatch widget
	 *
	 * @param array $query with keys 'start', 'search', 'order', 'sort', 'col_filter'
	 *	For other keys like 'filter', 'cat_id' you have to reimplement this method in a derived class.
	 * @param array &$rows returned rows/competitions
	 * @param array &$readonlys eg. to disable buttons based on acl
	 * @return int total number of rows
	 */
	function get_rows(&$query_in,&$rows,&$readonlys)
	{
		$query=$query_in;

		if ($query['filter'] == 0)
		{
			unset ($query['col_filter']['site_id']);
			$site_desc = lang ("All Sites");
		}
		else
		{
			$query['col_filter']['site_id'] = $query['filter'];
			$site_desc = $this->all_sites[$query['filter']];
		}

		$total = parent::get_rows($query,$rows);

		$GLOBALS['egw_info']['flags']['app_header'] = lang('Sitemgr').' ('.lang('Manage Notification Messages').'): '.$site_desc;
		return $total;
	}
}
