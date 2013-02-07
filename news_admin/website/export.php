<?php
	/**
	 * eGroupWare - Webpage news admin
	 *
	 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	 * @package news_admin
	 * @link http://www.egroupware.org
	 * @maintainer Cornelius Weiss <nelius@cwtech.de>
	 * @version $Id: export.php 33224 2010-11-30 19:32:35Z ralfbecker $
	 */


	/**
	 * Check if we allow anon access and with which creditials
	 *
	 * @param array &$anon_account anon account_info with keys 'login', 'passwd' and optional 'passwd_type'
	 * @return boolean true if we allow anon access, false otherwise
	 */
	function registration_check_anon_access(&$anon_account)	{
		// quick hack for std installations to reach news stand alone
		// via egroupware/news_admin/website/export.php
		$anon_account = array(
			'login'  => 'anonymous',
			'passwd' => 'anonymous',
			'passwd_type' => 'text',
		);
		return true;
	}

	// check if we are loaded via sitemgr's news_module
	if (!(isset($GLOBALS['egw_info']) && isset($GLOBALS['egw_info']['flags']) && isset($GLOBALS['egw_info']['flags']['currentapp']) && $GLOBALS['egw_info']['flags']['currentapp'] == 'sitemgr-link')) {
		$GLOBALS['egw_info']['flags'] = array(
			'noheader'  => True,
			'nonavbar' => True,
			'currentapp' => 'sitemgr-link',
			'autocreate_session_callback' => 'registration_check_anon_access',
		);
		include('../../header.inc.php');
	}

	$news_obj =& CreateObject('news_admin.bonews');
	$export_obj =& CreateObject('news_admin.soexport');
	$cats_obj =& CreateObject('phpgwapi.categories');
	$tpl =& CreateObject('phpgwapi.Template');

	$cat_id = (int)$_GET['cat_id'];
	$limit	= (isset($_GET['limit']) ? trim($_GET['limit']) : 10);
	$reqFormat = (isset($_GET['format']) ? strtolower(trim($_GET['format'])) : null);

	$filter = $cat_id > 0 ? array('cat_id' => $cat_id) : false;
	$news = $news_obj->search('',false,'news_date DESC','','',false,'AND',array(0,$limit),$filter);

	if (empty($news)) {
		die('
			There are no news, sorry! Either this system is missconfigured,
			you are trying something nasty, or there are really no news.

			Ask the webmaster to make shure, the access permissions in the
			news_admin application are set in a way website visitors can access news.
		');
	}
	$formats = array(
		1 => 'rss091',
		2 => 'rss1',
		3 => 'rss2'
	);

	$itemsyntaxs = array(
		0 => '?item=',
		1 => '&amp;item=',
		2 => '?news%5Bitem%5D=',
		3 => '&amp;news%5Bitem%5D='
	);

	$feedConf = $export_obj->readconfig($cat_id);

	$format = $reqFormat ? $reqFormat : (
		$formats[$feedConf['type']] ? $formats[$feedConf['type']] : (
		'rss2' ));

	$feedConf['title'] = $feedConf['title'] ? $feedConf['title'] : (
		$sitemgr_info['site_name'] ? $sitemgr_info['site_name'] : (
		$GLOBALS['egw_info']['server']['site_title'] ? $GLOBALS['egw_info']['server']['site_title'] :
		lang('News')));

	$feedConf['link'] = $feedConf['link'] ? $feedConf['link'] :
		( $GLOBALS['sitemgr_info']['site_url'] ? ( stripos($GLOBALS['sitemgr_info']['site_url'],'http') !== false ? $GLOBALS['sitemgr_info']['site_url'] : (
			( stripos($_SERVER['SERVER_PROTOCOL'],'https') !== false ? 'https' : 'http'). '://'. $_SERVER['HTTP_HOST']. $GLOBALS['sitemgr_info']['site_url'] )) :
		'' /* add link to news_admin here... maybe we could add an item support in export.php? */ );

	if ( !$feedConf['description'] ) {
		if ( $cat_id > 0 ) {
			$cat = $cats_obj->return_single($cat_id);
			$feedConf['description'] = $cat[0]['description'];
		} else {
			$feedConf['description'] = $feedConf['title'];
		}
	}
	// keep already set itemsyntax from included news_admin module
	if ($feedConf && isset($itemsyntaxs[$feedConf['itemsyntax']]) || $_REQUEST['itemsyntax']==$itemsyntax)	// gard against register_globals=On
	{
		$itemsyntax = $itemsyntaxs[$feedConf['itemsyntax']];
	}
	$tpl->root = EGW_SERVER_ROOT. '/news_admin/website/templates/';
	$tpl->set_file(array('news' => $format . '.tpl'));
	$tpl->set_block('news', 'item', 'items');
	if($format == 'rss1') {
		$tpl->set_block('news', 'seq', 'seqs');
	}

	$tpl->set_var('encoding', $GLOBALS['egw']->translation->charset());
	$tpl->set_var($feedConf);


	if(is_array($news))	{
		foreach($news as $news_data) {
			$tpl->set_var('content',$news_data['news_content']);
			$tpl->set_var('subject',$news_data['news_headline']);
			$tpl->set_var('teaser',$format == 'rss2' && $news_data['news_content'] ? '<p><b>'.$news_data['news_teaser']."</b></p>\n" :
				$news_data['news_content']);

			$tpl->set_var('item_link',htmlspecialchars($news_data['link'] ? $news_data['link'] : $feedConf['link'] . $itemsyntax . $news_data['news_id']));
			$tpl->set_var('pub_date', date("r",$news_data['news_date']));
			if($format == 'rss1')
			{
				$tpl->parse('seqs','seq',True);
			}

			$tpl->parse('items','item',True);
		}
	}
	else {
		$tpl->set_var('items', '');
	}

	header('Content-type: text/xml; charset='.$GLOBALS['egw']->translation->charset());
	$tpl->pparse('out','news');
?>
