<?php
/**
 * EGroupware SiteMgr CMS - index.php for generated website
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage sitemgr-site
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: index.php 33188 2010-11-28 20:27:05Z ralfbecker $
 */

$site_id=0;
/**
 * Determine the site from the URL ($_SERVER['PHP_SELF'])
 *
 * @param array &$anon_account anon account_info with keys 'user', 'passwd' and optional 'passwd_type'
 * @return boolean true if a site is found or dies if not site defined for the URL
 */
function sitemgr_get_site(&$anon_account)
{
	global $site_url, $site_id, $sitemgr_info;

	$site_urls[] = $path = preg_replace('/\/[^\/]*$/','',$_SERVER['PHP_SELF']) . '/';
	$site_urls[] = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['SERVER_ADDR'] . $path;
	$site_urls[] = $site_url  = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $path;

	//echo "<p>sitemgr_get_site('$site_url')</p>\n";
	$GLOBALS['egw']->db->select('egw_sitemgr_sites','anonymous_user,anonymous_passwd,site_id',
		array('site_url' => $site_urls),__LINE__,__FILE__,false,'','sitemgr');

	if ($GLOBALS['egw']->db->next_record())
	{
		$anon_account = array(
			'login'  => $GLOBALS['egw']->db->f('anonymous_user'),
			'passwd' => $GLOBALS['egw']->db->f('anonymous_passwd'),
			'passwd_type' => 'text',
		);

		$sitemgr_info['anonymous_user'] = $anon_account['login'];

		if($GLOBALS['egw_info']['server']['allow_cookie_auth'])
		{
			$eGW_remember = explode('::::',stripslashes($_COOKIE['eGW_remember']));

			if (count($eGW_remember) == 3 && $GLOBALS['egw']->accounts->name2id($eGW_remember[0],'account_lid','u'))
			{
				$anon_account = array(
					'login' => $eGW_remember[0],
					'passwd' => $eGW_remember[1],
					'passwd_type' => $eGW_remember[2],
				);
			}
		}
		if (!$anon_account['login'])
		{
			die(lang('NO ANONYMOUS USER ACCOUNTS INSTALLED.  NOTIFY THE ADMINISTRATOR.'));
		}
		$site_id = $GLOBALS['egw']->db->f('site_id');

		//echo "<p>sitemgr_get_site('$site_url') site_id=$site_id, anon_account=".print_r($anon_account,true)."</p>\n";
		return true;
	}
	die(lang('THERE IS NO WEBSITE CONFIGURED FOR URL %1.  NOTIFY THE ADMINISTRATOR.',$site_url.' ('.$GLOBALS['egw_info']['server']['default_domain'].')'));
}

$GLOBALS['egw_info'] = array(
	'flags' => array(
		'disable_Template_class' => True,
		'noheader'   => True,
		'currentapp' => 'sitemgr-link',
		'autocreate_session_callback' => 'sitemgr_get_site',
));

include('./config.inc.php');
if (!file_exists($sitemgr_info['egw_path'] . 'header.inc.php'))
{
	die("Header file not found.  Either your path to eGroupWare in the config.inc.php file is bad, or you have not setup eGroupWare.");
}
// do we use a different domain and are already loged in?
require_once($sitemgr_info['egw_path'].'phpgwapi/inc/class.egw_session.inc.php');
if (isset($GLOBALS['egw_info']['server']['default_domain']) &&
	egw_session::get_request('domain') != $GLOBALS['egw_info']['server']['default_domain'])
	//$_COOKIE['domain'] != $GLOBALS['egw_info']['server']['default_domain'])
{
	// force our default domain
	$_GET['domain'] = $_COOKIE['domain'] = $_REQUEST['domain'] = $GLOBALS['egw_info']['server']['default_domain'];
	unset($_GET['sessionid']);
	unset($_COOKIE['sessionid']);
	unset($_REQUEST['sessionid']);
}
include($sitemgr_info['egw_path'] . 'header.inc.php');

if ($sitemgr_info['webserver_url'])
{
	$GLOBALS['egw_info']['server']['webserver_url'] = $sitemgr_info['webserver_url'];
}
if (!$site_id)
{
	sitemgr_get_site($anon_account);
}

// switch to current website.
if ($GLOBALS['egw_info']['user']['preferences']['sitemgr']['currentsite'] != $site_id)
{
	$GLOBALS['egw_info']['user']['preferences']['sitemgr']['currentsite'] = $site_id;
	$GLOBALS['egw']->preferences->change('sitemgr','currentsite', $site_id);
	$GLOBALS['egw']->preferences->save_repository(True);
}

if ($GLOBALS['egw_info']['server']['usecookies'] && $_COOKIE['sessionid'] != $GLOBALS['egw_info']['user']['sessionid'] &&
	(!$GLOBALS['egw_info']['server']['cookiedomain'] || // set SiteMgr cookie only if eGW's cookiedomain does not fit
	substr($_SERVER['SERVER_NAME'],-strlen($GLOBALS['egw_info']['server']['cookiedomain'])) != $GLOBALS['egw_info']['server']['cookiedomain']))
{
	if (count(explode('.',$domain = $_SERVER['SERVER_NAME'])) <= 1) $domain = '';
	// we dont sue session::egw_setcookie() as it would set the domain and path of the eGW install and not the one from sitemgr
	setcookie('sessionid',$GLOBALS['egw_info']['user']['sessionid'],0,'/',$domain);
	setcookie('kp3',$GLOBALS['egw_info']['user']['kp3'],0,'/',$domain);
	setcookie('domain',$GLOBALS['egw_info']['user']['domain'],0,'/',$domain);
}
include('./functions.inc.php');

$Common_BO =& CreateObject('sitemgr.Common_BO');
require_once './inc/class.sitebo.inc.php';
$objbo = new sitebo;
$Common_BO->sites->set_currentsite($site_url,$objbo->getmode());
if($objbo->getmode() != 'Production')
{
	// we need this to avoid the "attempt to access ..." errors if users contribute to multiple websites.
	// This does not solve the Problem if they work simultanus in two browsers :-(
	$GLOBALS['egw']->preferences->change('sitemgr','currentsite', $Common_BO->sites->urltoid($site_url));
	$GLOBALS['egw']->preferences->save_repository(True);
}
$sitemgr_info = $sitemgr_info + $Common_BO->sites->current_site;
$sitemgr_info['sitelanguages'] = explode(',',$sitemgr_info['site_languages']);
$objbo->setsitemgrPreferredLanguage();
$GLOBALS['egw']->translation->add_app('common');	// as we run as sitemgr-site
$GLOBALS['egw']->translation->add_app('sitemgr');	// as we run as sitemgr-site

if ($GLOBALS['sitemgr_info']['themesel'][0] == '/')
{
	$templateroot = $GLOBALS['egw_info']['server']['files_dir'] . $GLOBALS['sitemgr_info']['themesel'];
}
else
{
	$templateroot = $GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates' . SEP . $GLOBALS['sitemgr_info']['themesel'];
}

include_once './inc/class.Template3.inc.php';
if (file_exists($templateroot.'/main.tpl'))			// native sitemgr template
{
	include_once './inc/class.ui.inc.php';
}
elseif (file_exists($templateroot.'/index.php'))	// Joomla or Mambo Open Source template
{
	$version =& egw_cache::getSession('sitemgr', 'template_version');	// cache it in session
	if (is_null($version) || !isset($version[$GLOBALS['sitemgr_info']['themesel']]))
	{
		$theme_info = $Common_BO->theme->getThemeInfos($GLOBALS['sitemgr_info']['themesel']);
		$version[$GLOBALS['sitemgr_info']['themesel']] = $theme_info['joomla-version'];
	}
	if (version_compare($version[$GLOBALS['sitemgr_info']['themesel']], '1.3','>='))	// joomla 1.5+ template
	{
		include_once './inc/class.joomla_ui.inc.php';
	}
	else
	{
		include_once './inc/class.mos_ui.inc.php';

		if (file_exists($templateroot.'/joomla.xml.php'))	// Joomla 1.0 template
		{
			include_once './mos-compat/class.joomla.inc.php';
			include_once './mos-compat/class.JFilterOutput.inc.php';
			include_once './mos-compat/joomla_Legacy_function.inc.php';
		}
	}
}
else
{
	echo '<h3>'.lang("Invalid template directory '%1' !!!",$templateroot)."</h3>\n";
	if (!is_dir($GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates') || !is_readable($GLOBALS['sitemgr_info']['site_dir'] . SEP . 'templates'))
	{
		echo lang("The filesystem path to your sitemgr-site directory '%1' is probably wrong. Go to SiteMgr --> Define Websites and edit/fix the concerned Site.",$GLOBALS['sitemgr_info']['site_dir']);
	}
	elseif (!is_dir($templateroot) || !is_readable($templateroot))
	{
		echo lang("You may have deinstalled the used template '%1'. Reinstall it or go to SiteMgr --> Configure Website and select an other one.",$GLOBALS['sitemgr_info']['themesel']);
	}
	common::egw_exit();
}
$objui = new ui;

$page =& CreateObject('sitemgr.Page_SO');

$page_id = $_GET['page_id'];
$page_name = $_GET['page_name'];
$category_id = $_GET['category_id'];
$toc = $_GET['toc'];
$index = $_GET['index'];
$search_content = $_POST['searchword'] ? $_POST['searchword'] : $_GET['searchword'];

// Check for explicit modules calls
if ($_GET['module'])
{
	// we (miss-)use the addcontent handler to call the module
	$GLOBALS['egw']->session->appsession('addcontent','sitemgr',array(array(
		'module_name' => $_GET['module'],
		'arguments' => array(),
		'page' => false,
		'area' => false,
		'sort_order' => false
	)));
}

if ($page_name && $page_name != 'index.php')
{
	$objui->displayPageByName($page_name);
}
elseif($category_id)
{
	$cat = $Common_BO->cats->getCategory($category_id);
	if ($cat->index_page_id > 0)
	{
		$page = $Common_BO->pages->getPage($cat->index_page_id);
		if ($page->id)
		{
			$objui->displayPage($page->id);
		}
	}
	if (!$cat->index_page_id || !is_object($page) || !$page->id)	// fallback to regular toc if index-page is missing
	{
		$objui->displayTOC($category_id);
	}
}
elseif ($page_id)
{
	$objui->displayPage($page_id);
}
elseif (isset($index))
{
	$objui->displayIndex();
}
elseif (isset($toc))
{
	$objui->displayTOC();
}
elseif ($search_content)
{
	// Make compatibility with mos search boxes, if not lang, mode and view option, default all languages
	// any words mode and view options advgoogle
	$search_lang = $_POST['search_lang'] ? $_POST['search_lang'] : "all";
	$search_mode = $_POST['search_mode'] ? $_POST['search_mode'] : "any";
	$search_options = $_POST['search_options'] ? $_POST['search_options'] : "advgoogle";
	$search_ui =& CreateObject('sitemgr.search_ui');
	$search_result = $search_ui->search($search_content,$search_lang,$search_mode,$search_options);
	$objui->displaySearch($search_result,$search_lang,$search_mode,$search_options);
}
else
{
	if ($sitemgr_info['home_page_id'])
	{
		$objui->displayPage($sitemgr_info['home_page_id']);
	}
	else
	{
		$index = 1;
		$objui->displayIndex();
	}
}
