<?php
/**
 * news_admin - hooks
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package news_admin
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.news_admin_hooks.inc.php 30060 2010-05-05 11:01:46Z leithoff $
 */

/**
 * Static hooks for news admin
 */
class news_admin_hooks
{
	/**
	 * Settings hook
	 *
	 * @param array|string $hook_data
	 */
	static public function settings($hook_data)
	{
		$show_entries = array(
			0 => lang('No'),
			1 => lang('Yes'),
			2 => lang('Yes').' - '.lang('small view'),
		);
		$_show_entries = array(
			0 => lang('No'),
			1 => lang('Yes'),
		);

		return array(
			'homeShowLatest' => array(
				'type'   => 'select',
				'label'  => 'Show news articles on main page?',
				'name'   => 'homeShowLatest',
				'values' => $show_entries,
				'help'   => 'Should News_Admin display the latest article headlines on the main screen.',
				'xmlrpc' => True,
				'admin'  => False,
				'default'=> '2',
			),
			'homeShowLatestCount' => array(
				'type'    => 'input',
				'label'   => 'Number of articles to display on the main screen',
				'name'    => 'homeShowLatestCount',
				'size'    => 3,
				'maxsize' => 10,
				'help'    => 'Number of articles to display on the main screen',
				'xmlrpc'  => True,
				'admin'   => False,
				'default' => 5,
			),
			'homeShowCats' => array(
				'type'   => 'multiselect',
				'label'  => 'Categories to displayed on main page?',
				'name'   => 'homeShowCats',
				'values' => ExecMethod('news_admin.bonews.rights2cats',EGW_ACL_READ),
				'help'   => 'Which news categories should be displayed on the main screen.',
				'xmlrpc' => True,
				'admin'  => False,
			),
			'rtfEditorFeatures' => array(
				'type'   => 'select',
				'label'  => 'Features of the editor?',
				'name'   => 'rtfEditorFeatures',
				'values' => array(
					'simple'   => lang('Simple'),
					'extended' => lang('Regular'),
					'advanced' => lang('Everything'),
				),
				'help'   => 'You can customize how many icons and toolbars the editor shows.',
				'xmlrpc' => True,
				'admin'  => False,
				'default'=> 'extended',
			),
		);
	}

	/**
	 * Hook for admin menu
	 *
	 * @param array|string $hook_data
	 */
	public static function admin($hook_data)
	{
		$appname = 'news_admin';
		$file = Array
		(
			'Site Configuration' => egw::link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
			'Global Categories'	=> egw::link('/index.php','menuaction=admin.uicategories.index&appname=' . $appname),
			'Configure Access Permissions' => egw::link('/index.php','menuaction=news_admin.uiacl.acllist'),
			'Configure RSS exports' => egw::link('/index.php','menuaction=news_admin.uiexport.exportlist'),
		);
		display_section($appname,$appname,$file);
	}

	/**
	 * Hook for preferences menu
	 *
	 * @param array|string $hook_data
	 */
	public static function preferences($hook_data)
	{
		$appname = 'news_admin';
		$title = $appname;
		$file = array(
			'Preferences' => egw::link('/index.php','menuaction=preferences.uisettings.index&appname=' . $appname)
		);
		display_section($appname,$title,$file);
	}

	/**
	 * Hook for sidebox menu
	 *
	 * @param array|string $hook_data
	 */
	public static function sidebox_menu($hook_data)
	{
		$appname = 'news_admin';
		$categories = new categories('',$appname);
		$enableadd = false;
		foreach((array)$categories->return_sorted_array(0,False,'','','',false) as $cat)
		{
			if ($categories->check_perms(EGW_ACL_EDIT,$cat))
			{
				$enableadd = true;
				break;
			}
		}
		$menu_title = $GLOBALS['egw_info']['apps'][$appname]['title'] . ' '. lang('Menu');
		$file = array();
		if ($enableadd)
		{
			$file = Array(
				array(
					'text' => '<a class="textSidebox" href="'.egw::link('/index.php',array('menuaction' => 'news_admin.uinews.edit')).
						'" onclick="window.open(this.href,\'_blank\',\'dependent=yes,width=700,height=580,scrollbars=yes,status=yes\');
						return false;">'.lang('Add').'</a>',
					'no_lang' => true,
					'link' => false
				));
		}
		$file['Read news'] = egw::link('/index.php',array('menuaction' => 'news_admin.uinews.index'));

		display_sidebox($appname,$menu_title,$file);

		$title = lang('Preferences');
		$file = array();
		if ($GLOBALS['egw_info']['user']['apps']['preferences'])
		{
			$file['Preferences'] = egw::link('/index.php','menuaction=preferences.uisettings.index&appname=' . $appname);
			$file['Categories'] = egw::link('/index.php','menuaction=news_admin.news_admin_ui.cats');
			display_sidebox($appname,$title,$file);
		}

		if($GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$title = lang('Administration');
			$file = Array(
				'Site Configuration' => egw::link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
				'Configure RSS exports' => egw::link('/index.php','menuaction=news_admin.uiexport.exportlist')
			);

			display_sidebox($appname,$title,$file);
		}
	}
}
