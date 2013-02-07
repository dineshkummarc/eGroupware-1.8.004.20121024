<?php
/**
 * SiteMgr - Default records for a new installation
 *
 * @link http://www.egroupware.org
 * @package news_admin
 * @subpackage setup
 * @author RalfBecker@outdoor-training.de
 * @copyright (c) 2010 by RalfBecker@outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: default_records.inc.php 37681 2012-01-09 11:54:49Z leithoff $
 */

// give Admins group rights for news_admin
$admingroup = $GLOBALS['egw_setup']->add_account('Admins','Admin','Group',False,False);
$GLOBALS['egw_setup']->add_acl('news_admin','run',$admingroup);
// give Default group rights for news_admin
$defaultgroup = $GLOBALS['egw_setup']->add_account('Default','Default','Group',False,False);
$GLOBALS['egw_setup']->add_acl('news_admin','run',$defaultgroup);

// Create anonymous user and NoGroup group for news_admin
$GLOBALS['egw_setup']->add_account('NoGroup','No','Rights',False,False);
$anonymous = $GLOBALS['egw_setup']->add_account($anonuser='anonymous','SiteMgr','User',$anonpasswd='anonymous','NoGroup');
$GLOBALS['egw_setup']->add_acl('news_admin','run',$anonymous);
$GLOBALS['egw_setup']->add_acl('phpgwapi','anonymous',$anonymous);

// Create news category "news" writable be Admins group and readable by every user (not anonymous)
$global_cat_owner = categories::GLOBAL_ACCOUNT;
$oProc->query("INSERT INTO {$GLOBALS['egw_setup']->cats_table} (cat_parent,cat_owner,cat_access,cat_appname,cat_name,cat_description,last_mod)
	VALUES (0,$global_cat_owner,'public','news_admin','News','Category for news',".time().")");
$cat_id = $oProc->m_odb->get_last_insert_id($GLOBALS['egw_setup']->cats_table,'cat_id');
foreach(array(
	$admingroup => 3,
	$defaultgroup => 1,
) as $user => $rights)
{
	$GLOBALS['egw_setup']->add_acl('news_admin','L'.$cat_id,$user,$rights);
}

// Create egroupware.org news category importing news from egroupware.org readable by everyone
$data = serialize(array(
	'import_url' => 'http://www.egroupware.org/index.php?module=news_admin&cat_id=3,200',
	'import_frequency' => 4,
	'keep_imported' => 0,
));
$oProc->query("INSERT INTO {$GLOBALS['egw_setup']->cats_table} (cat_parent,cat_owner,cat_access,cat_appname,cat_name,cat_description,last_mod,cat_data)
	VALUES (0,$global_cat_owner,'public','news_admin','egroupware.org','News from egroupware.org',".time().",'$data')");
$cat_id = $oProc->m_odb->get_last_insert_id($GLOBALS['egw_setup']->cats_table,'cat_id');
foreach(array(
	$admingroup => 1,
	$defaultgroup => 1,
	$anonymous => 1,
) as $user => $rights)
{
	$GLOBALS['egw_setup']->add_acl('news_admin','L'.$cat_id,$user,$rights);
}
// add import job
$async = new asyncservice();
$async->set_timer(array('hour' => '*'),'news_admin-import','news_admin.news_admin_import.async_import',null);
