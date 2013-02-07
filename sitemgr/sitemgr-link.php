<?php
/**
 * SiteMgr - View website
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage sitemgr-link
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: sitemgr-link.php 26336 2008-11-11 08:46:44Z ralfbecker $
 */

$GLOBALS['egw_info'] = array(
	'flags' => array(
		'currentapp' => 'sitemgr-link',
		'noheader'   => True,
		'nonavbar'   => True,
	)
);
include('../header.inc.php');

$sites_bo = CreateObject('sitemgr.Sites_BO');
// switch to current website in case of website login
if(isset($_GET['location']))
{
	list($location,$query) = explode('?',$_GET['location']);

	$dest_site_id =  $sites_bo->urltoid($location);
	if($dest_site_id)
	{
		$GLOBALS['egw_info']['user']['preferences']['sitemgr']['currentsite'] = $dest_site_id;
		$GLOBALS['egw']->preferences->change('sitemgr','currentsite', $dest_site_id);
		$GLOBALS['egw']->preferences->save_repository(True);
	}
}
$siteinfo = $sites_bo->get_currentsiteinfo();
if (!$location) $location = $siteinfo['site_url'];
if ($location && file_exists($siteinfo['site_dir'] . '/functions.inc.php'))
{
	$location .= '?sessionid='.@$GLOBALS['egw_info']['user']['sessionid'] .
				'&kp3=' . @$GLOBALS['egw_info']['user']['kp3'] .
				'&domain=' . @$GLOBALS['egw_info']['user']['domain'];
	// preserve page at login from website
	if($query) $location .= '&'. $query;

	//error_log("_GET[location]=$_GET[location], siteinfo[site_url]=$siteinfo[site_url] --> $location");
	$GLOBALS['egw']->redirect($location);
	exit;
}

$GLOBALS['egw']->common->egw_header();
echo parse_navbar();
$aclbo =& CreateObject('sitemgr.ACL_BO', True);
echo '<table width="50%"><tr><td>';
if ($aclbo->is_admin())
{
	echo lang('Before the public web site can be viewed, you must configure the various locations and preferences.  Please go to the sitemgr setup page by following this link:') .
		'<a href="' .
		$GLOBALS['egw']->link('/index.php', 'menuaction=sitemgr.Common_UI.DisplayPrefs') .
		'">' .
		lang('sitemgr setup page') .
		'</a>. ' .
		lang('Note that you may get this message if your preferences are incorrect.  For example, if config.inc.php is not found in the directory that you specified.');
}
else
{
	echo lang('Your administrator has not yet setup the web content manager for public viewing.  Go bug your administrator to get their butt in gear.');
}
echo '</td></tr></table>';
$GLOBALS['egw']->common->egw_footer();
