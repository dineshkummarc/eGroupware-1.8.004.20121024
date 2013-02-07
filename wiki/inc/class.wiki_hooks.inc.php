<?php
/**
 * eGroupware Wiki - Hooks
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (C) 2004-9 by RalfBecker-AT-outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.wiki_hooks.inc.php 35742 2011-07-14 07:17:31Z leithoff $
 */

/**
 * Static hooks for wiki
 */
class wiki_hooks
{
	/**
	 * Settings hook
	 *
	 * @param array|string $hook_data
	 */
	static public function settings($hook_data)
	{
		return array(
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
		$title = $appname = 'wiki';
		$file = Array(
			'Site Configuration' => egw::link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
		//	'Lock / Unlock Pages' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&locking=1'),
			'Block / Unblock hosts' => egw::link('/wiki/index.php','action=admin&blocking=1'),
			'Rebuild Links' => egw::link('/wiki/index.php','menuaction=wiki.wiki_hooks.rebuildlinks'),
		);
		//Do not modify below this line
		display_section($appname,$title,$file);
	}

	/**
	 * Hook for sidebox menu
	 *
	 * @param array|string $hook_data
	 */
	public static function sidebox_menu($hook_data)
	{
		$appname = 'wiki';
		$menu_title = lang('Wiki Menu');
		$file = Array(
			'Recent Changes' => $GLOBALS['egw']->link('/wiki/index.php','page=RecentChanges'),
			'Preferences' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'preferences.uisettings.index','appname'=>'wiki')),
		);
		display_sidebox($appname,$menu_title,$file);

		if ($GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$menu_title = lang('Wiki Administration');
			$file = Array(
				'Site Configuration' => $GLOBALS['egw']->link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
			//	'Lock / Unlock Pages' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&locking=1'),
				'Block / Unblock Hosts' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&blocking=1')
			);
			display_sidebox($appname,$menu_title,$file);
		}
	}

	/**
	 * Hook called by link-class to include infolog in the appregistry of the linkage
	 *
	 * @param array/string $location location and other parameters (not used)
	 * @return array with method-names
	 */
	static function search_link($location)
	{
		return array(
			'query'      => 'wiki.wiki_bo.link_query',
			'title'      => 'wiki.wiki_bo.link_title',
			'view'       => array(
				'menuaction' => 'wiki.wiki_ui.view',
			),
			'view_id'    => 'page',
		);
	}

	/**
	 * rebuildlinks
	 *
	 */
	function rebuildlinks()
	{
		@set_time_limit(0);

		if (!$GLOBALS['egw_info']['user']['apps']['admin'])
		{
			// error_log( 'Rebuilding Links ... -> Access not allowed ');
			$GLOBALS['egw']->redirect_link('/index.php');
		}
		error_log(__METHOD__.__LINE__. ' Rebuilding EGW Link Table Entries.');
		$bo = new wiki_bo;
		global $pagestore, $page, $ParseEngine, $Entity, $ParseObject;
		if ($bo->debug) error_log(__METHOD__.__LINE__. ' Read all Artikles - ... ');
		$i=0;
		$l=0;
		foreach($bo->find(str_replace(array('*','?'),array('%','_'),'%')) as $p)
		{
			$i++;
			$Entity=array(); // this one grows like hell, and eats time as we loop, so we reset that one on each go
			$page = $p;
			if ($bo->debug) error_log(__METHOD__.__LINE__.'['.$i.']' .' Processing '.$p['name'].' - '.$p['title'].' ('.$p['lang'].') ...');
			// delete the links of the page
			if ($bo->debug) $starrt = microtime(true);
			$bo->clear_link($p);
			$start = microtime();
			$j = count($Entity);

			if ($bo->debug) $start = microtime(true);
			// do not resolve makros, as it makes no sense to store the resolved stuff with the link table
			foreach ($ParseEngine as $k => $method) if ($method=='parse_macros' || $method=='parse_transclude' || $method=='parse_elements') array_splice($ParseEngine,$k,1);
			//error_log(__METHOD__.__LINE__.' Method:'.array2string($ParseEngine));
			parseText($p['text'], $ParseEngine, $ParseObject);
			if ($bo->debug) 
			{
				$end = microtime(true);
				$time= $end - $start;
				error_log(__METHOD__.__LINE__.'['.$j.']' ." Action parseText took ->$time seconds");
			}

			if ($bo->debug) $start = microtime(true);
			for(; $j < count($Entity); $j++)
			{
				if($Entity[$j][0] == 'ref')
					{$l++;$pagestore->new_link($page, $Entity[$j][1]); }
			}
			if ($bo->debug)
			{
				$end = microtime(true);
				$time= $end - $start;
				error_log(__METHOD__.__LINE__.'['.$j.']' ." Action loop and link took ->$time seconds");
			
				$ennd = microtime(true);
				$time= $ennd - $starrt;
				error_log(__METHOD__.__LINE__.' ['.$i.']' ." Action for ".$p['name']." ".$p['title']." ( ".$p['lang']." ) took ->$time seconds");
			}

			//if ($i >100) break;
		}
		error_log(__METHOD__.__LINE__.' '.$i." Pages processed. $l Links inserted (or count updated).");
		error_log(__METHOD__.__LINE__. ' Redirect back to Admin Page ');
		$GLOBALS['egw']->redirect_link('/admin/index.php');
	}
}
