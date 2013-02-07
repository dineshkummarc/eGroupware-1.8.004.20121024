<?php
/**
 * EGroupware SiteMgr CMS - Mambo Open Source 4.5 and Joomla 1.0 template support
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage sitemgr-site
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @copyright Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.mos_ui.inc.php 33188 2010-11-28 20:27:05Z ralfbecker $ 
 */

function mosCountModules($contentarea)
{
	global $objui;
	return (int)$objui->t->count_blocks($contentarea);

}

/**
 * http://help.joomla.org/content/view/1565/155/
 *
 * @param unknown_type $contentarea
 * @param unknown_type $_style
 */
function mosLoadModules($contentarea,$_style=0)
{
	global $objui;
	global $mos_style;
	$mos_style = $_style;
	echo $objui->t->process_blocks($contentarea);
}

function mosLoadComponent($component)
{
	return '';
}

function initEditor()
{
}

function sefreltoabs($url)
{
	echo $url;
}

function mosShowHead()
{
	global $objui,$mosConfig_sitename;

	echo "\t\t<title>$mosConfig_sitename</title>\n";
	$objui->t->loadfile(realpath(dirname(__FILE__).'/../mos-compat/metadata.tpl'));
	echo $objui->t->parse();
}

function mosPathWay($suppress_hide_pages=false)
{
	global $objui;
	if ($suppress_hide_pages==true) $suppress_hide='&suppress_hide_pages=on';
	$module_navigation_path = array('','navigation','nav_type=8&no_show_sep=on'.$suppress_hide);

	echo $objui->t->exec_module($module_navigation_path);
}

/**
* Returns current date according to current local and time offset
* @param string format optional format for strftime
* @returns current date
*/
function mosCurrentDate( $format="" )
{
	$tz_offset_s = $GLOBALS['egw']->datetime->tz_offset;

	if ($format=="") {
		$format = _DATE_FORMAT_LC;
	}
	$date = strftime( $format, time() + ($tz_offset_s) );
	return $date;
}

function mosMainBody()
{
	global $mosConfig_live_site;
	global $objui;

	// message passed via the url
	$mosmsg = strval($_GET['mosmsg']);
	$popMessages = false;

	// Browser Check
	$browserCheck = 0;
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && isset( $_SERVER['HTTP_REFERER'] ) && !empty( $mosConfig_live_site )   && strpos($_SERVER['HTTP_REFERER'], $mosConfig_live_site) !== false ) {
		$browserCheck = 1;
	}

	// limit mosmsg to 150 characters
	if ( strlen( $mosmsg ) > 150 ) {
		$mosmsg = substr( $mosmsg, 0, 150 );
	}

	// mosmsg outputed within html
	if ($mosmsg && !$popMessages && $browserCheck) {
		echo "\n<div class=\"message\">$mosmsg</div>";
	}

	// mosmsg outputed in JS Popup
	if ($mosmsg && $popMessages && $browserCheck) {
		echo "\n<script language=\"javascript\">alert('$mosmsg');</script>";
	}

	// load the center module
	if (!file_exists($file = $objui->templateroot.'/mainbody.tpl'))
	{
		$file = realpath(dirname(__FILE__).'/../mos-compat/mainbody.tpl');
	}
	$objui->t->loadfile($file);
	echo $objui->t->parse();
}

// this is just to make some templates work, it does nothing actually atm.
class mos_database
{
	function setQuery($query)
	{
	}

	function loadObjectList($ids)
	{
		require_once(dirname(__FILE__).'/../mos-compat/class.joomla.inc.php');
		$joomla = new joomla();
		$rows = $joomla->getmenu($ids);
		return $rows;
	}
}

class ui
{
	var $t;

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
	}

	function displayPageByName($page_name)
	{
		global $objbo;
		global $page;
		$objbo->loadPage($GLOBALS['Common_BO']->pages->so->PageToID($page_name));
		$this->generatePage();
	}

	function displayPage($page_id)
	{
		global $objbo;
		$objbo->loadPage($page_id);
		$this->generatePage();
	}

	function displayIndex()
	{
		global $objbo;
		$objbo->loadIndex();
		$this->generatePage();
	}

	function displayTOC($categoryid=false)
	{
		global $objbo;
		$objbo->loadTOC($categoryid);
		$this->generatePage();
	}

	function displaySearch($search_result,$lang,$mode,$options)
	{
		global $objbo;
		$objbo->loadSearchResult($search_result,$lang,$mode,$options);
		$this->generatePage();
	}

	function generatePage()
	{
		global $database;
		global $objui;
		$database = new mos_database;

		// add a content-type header to overwrite an existing default charset in apache (AddDefaultCharset directiv)
		header('Content-type: text/html; charset='.$GLOBALS['egw']->translation->charset());

		// define global $mosConfig vars
		global $mosConfig_sitename,$mosConfig_live_site,$mosConfig_absolute_path,$mosConfig_offset,$cur_template;
		$mosConfig_sitename = $this->t->get_meta('sitename').': '.$this->t->get_meta('title');
		$mosConfig_live_site = substr($GLOBALS['sitemgr_info']['site_url'],0,-1);
		$mosConfig_offset = (int) $GLOBALS['egw_info']['user']['preferences']['common']['tz_offset'];
		$mosConfig_absolute_path = $GLOBALS['sitemgr_info']['site_dir'];
		$cur_template = basename($GLOBALS['sitemgr_info']['themesel']);
		define('_DATE_FORMAT_LC',str_replace(array('d','m','M','Y'),array('%d','%m','%b','%Y'),
			$GLOBALS['egw_info']['user']['preferences']['common']['dateformat']).
			($GLOBALS['egw_info']['user']['preferences']['common']['timeformat']=='12'?' %I:%M %p' : ' %H:%M'));
		define('_DATE_FORMAT',$GLOBALS['egw_info']['user']['preferences']['common']['dateformat'].
			($GLOBALS['egw_info']['user']['preferences']['common']['timeformat']=='12'?' h:i a' : ' H:i'));
		define('_SEARCH_BOX',lang('Search').' ...');
		define( '_ISO','charset='.$GLOBALS['egw']->translation->charset());
		define( '_VALID_MOS',True );
		define( '_VALID_MYCSSMENU',True );
		ini_set('include_path',$this->mos_compat_dir.(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? ';' : ':').ini_get('include_path'));

		ob_start();		// else some modules like the redirect wont work
		include($this->templateroot.'/index.php');
		$website = ob_get_contents();
		ob_clean();
		// regenerate header (e.g. js includes)
		$objui->t->loadfile(realpath(dirname(__FILE__).'/../mos-compat/metadata.tpl'));
		if (file_exists($this->templateroot.'/metadata.tpl'))
		{
			$objui->t->loadfile($this->templateroot.'/metadata.tpl');
		}
		echo preg_replace('@<!-- metadata.tpl starts here -->.*?<!-- metadata.tpl ends here -->@si',$objui->t->parse(),$website);
	}
}
