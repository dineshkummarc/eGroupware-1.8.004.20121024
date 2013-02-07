<?php
/**
 * SiteMgr - Default records for a new installation
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage setup
 * @author RalfBecker@outdoor-training.de
 * @copyright (c) 2004-10 by RalfBecker@outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: default_records.inc.php 36303 2011-08-25 08:51:13Z leithoff $
 */

$sitemgr_table_prefix = 'egw_sitemgr';
// give Admins group rights for sitemgr and for the created default-site
$admingroup = $GLOBALS['egw_setup']->add_account('Admins','Admin','Group',False,False);
$GLOBALS['egw_setup']->add_acl('sitemgr','run',$admingroup);
// give Default group rights for sitemgr-link
$defaultgroup = $GLOBALS['egw_setup']->add_account('Default','Default','Group',False,False);
$GLOBALS['egw_setup']->add_acl('sitemgr-link','run',$defaultgroup);

// Create anonymous user and NoGroup group for sitemgr
$GLOBALS['egw_setup']->add_account('NoGroup','No','Rights',False,False);
$anonymous = $GLOBALS['egw_setup']->add_account($anonuser='anonymous','SiteMgr','User',$anonpasswd='anonymous','NoGroup');
// give the anonymous user only sitemgr-link-rights
$GLOBALS['egw_setup']->add_acl('sitemgr-link','run',$anonymous);
$GLOBALS['egw_setup']->add_acl('phpgwapi','anonymous',$anonymous);

// register all modules and allow them in the following contentareas
// note '__PAGE__' is used for contentareas with NO module specialy selected, eg. only 'center' in this example !!!
$areas = array(
	'administration' => array('left','right'),
	'amazon' => array('left','right'),
	'bookmarks' => array('__PAGE__'),
	'calendar' => array('left','right'),
	'currentsection' => array('left','right'),
	'download' => array('__PAGE__'),
	'filecontents' => array('left','right','header','footer','__PAGE__'),
	'frame' => array('__PAGE__'),
	'forum' => array('__PAGE__'),
	'gallery' => array('left','right','__PAGE__'),
	'gallery_imageblock' => array('left','right','__PAGE__'),
	'google' => array('left','right'),
	'html' => array('left','right','header','footer','__PAGE__'),
	'lang_block' => array('left','right'),
	'login' => array('left','right'),
	'navigation' => array('left','right','__PAGE__'),
	'news_admin' => array('left','right','__PAGE__'),
	'notify' => array('left','right'),
	'phpbrain' => array('__PAGE__'),
	'polls' => array('left','right','__PAGE__'),
	'redirect' => array('__PAGE__'),
	'resources' => array('__PAGE__'),
	'search' => array('left','right','header','footer','__PAGE__'),
	'template' => array('left','right','__PAGE__'),
	'tracker' => array('__PAGE__'),
	'validator' => array('footer'),
	'wiki' => array('__PAGE__'),
);
$dir = dir(EGW_SERVER_ROOT);
$i = 1;
while(($app = $dir->read()))
{
	$moddir = EGW_SERVER_ROOT . '/' . $app . ($app == 'sitemgr' ? '/modules' : '/sitemgr');
	if (is_dir($moddir))
	{
		$d = dir($moddir);
		while (($file = $d->read()))
		{
			if (preg_match ('/class\.module_(.*)\.inc\.php$/', $file, $module))
			{
				$module = $module[1];

				if (preg_match('/\$this->description = lang\([\'"]([^'."\n".']*)[\'"]\);/',file_get_contents($moddir.'/'.$file),$parts))
				{
					$description = $GLOBALS['egw_setup']->db->db_addslashes(str_replace("\\'","'",$parts[1]));
				}
				else
				{
					$description = '';
				}
				$oProc->query("INSERT INTO {$sitemgr_table_prefix}_modules (module_name,description) VALUES ('$module','$description')",__LINE__,__FILE__);
				$id = $module_id[$module] = $oProc->m_odb->get_last_insert_id($sitemgr_table_prefix.'_modules','module_id');
				if (!$id) $id = $module_id[$module] = $i;
				// allow to display all modules, not mentioned above, on __PAGE__
				if (!isset($areas[$module]) && !in_array($module,array('hello','translation_status','xml')))
				{
					$areas[$module] = array('__PAGE__');
				}
				// stuff for {$sitemgr_table_prefix}_active_modules is somehow done within the xml import of the site, as site_id is not known at
				// this time, we take that out of the active code.
				/*
				foreach((array)$areas[$module] as $area)
				{
					$oProc->query("INSERT INTO {$sitemgr_table_prefix}_active_modules (area,cat_id,module_id) VALUES ('$area',$site_id,$id)",__LINE__,__FILE__);
				}
				*/
			}
		}
		$d->close();
	}
	$i++;
}
$dir->close();

// install default site from sitemgr/setup/default-site.xml
$reader = new XMLReader();
$reader->open(dirname(__FILE__).'/default-site.xml');
$import = new sitemgr_import_xml($reader);
define('EGW_ACL_READ',1);
define('EGW_ACL_ADD',2);

$site_id = $import->import_record(array($admingroup),array(
	$admingroup   => EGW_ACL_READ|EGW_ACL_ADD,
	$defaultgroup => EGW_ACL_READ,
	$anonymous    => EGW_ACL_READ,
),true);	// true = ignore acl

// store relative dir, currently used url and anon user/pw
$webserver_url = $oProc->query("SELECT config_value FROM ".$GLOBALS['egw_setup']->config_table." WHERE config_name='webserver_url'",__LINE__,__FILE__)->fetchColumn();
$site = array(
	'name' => 'Default Website',
	'url' => $webserver_url.'/sitemgr/sitemgr-site/',    // url need traling slash
	'dir' => 'sitemgr' . SEP . 'sitemgr-site',
	'anonuser' => $anonuser,
	'anonpasswd' => $anonpasswd,
);
$import->common->sites->so->update($site_id, $site);


