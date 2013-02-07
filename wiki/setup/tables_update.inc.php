<?php
/**
 * EGroupware - Wiki
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package wiki
 * @subpackage setup
 * @version $Id: tables_update.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $
 */

function wiki_upgrade0_9_15_001()
{
	// this will also create the new colums, with its default values and discards the not longer used mutable column
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_wiki_pages',array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '0'),
			'name' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'version' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '1'),
			'time' => array('type' => 'int','precision' => '4'),
			'supercede' => array('type' => 'int','precision' => '4'),
			'readable' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'writable' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'username' => array('type' => 'varchar','precision' => '80'),
			'hostname' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'comment' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'title' => array('type' => 'varchar','precision' => '80'),
			'body' => array('type' => 'text'),
		),
		'pk' => array('wiki_id','name','lang','version'),
		'fk' => array(),
		'ix' => array('title',array('body', 'options' => array('mysql' => 'FULLTEXT'))),
		'uc' => array()
	),array(
		'name' => 'title',		// new name column with same content as the title
		'writable' => "CASE WHEN mutable != 'on' THEN -2 ELSE 0 END",	// migrate mutable to new acl
		'hostname' => 'author',	// rename column
	));

	return $GLOBALS['setup_info']['wiki']['currentver'] = '0.9.15.002';
}


function wiki_upgrade0_9_15_002()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_wiki_links',array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '0'),
			'page' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'link' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'count' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0')
		),
		'pk' => array('wiki_id','page','lang','link'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	return $GLOBALS['setup_info']['wiki']['currentver'] = '0.9.15.003';
}


function wiki_upgrade0_9_15_003()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_wiki_interwiki',array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'prefix' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'where_defined_page' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'where_defined_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'url' => array('type' => 'varchar','precision' => '255','nullable' => False,'default' => '')
		),
		'pk' => array('wiki_id','prefix'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),array(
		'where_defined_page' => 'where_defined'
	));

	return $GLOBALS['setup_info']['wiki']['currentver'] = '0.9.15.004';
}


function wiki_upgrade0_9_15_004()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_wiki_sisterwiki',array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'prefix' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'where_defined_page' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'where_defined_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'url' => array('type' => 'varchar','precision' => '255','nullable' => False,'default' => '')
		),
		'pk' => array('wiki_id','prefix'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),array(
		'where_defined_page' => 'where_defined'
	));

	return $GLOBALS['setup_info']['wiki']['currentver'] = '0.9.15.005';
}


function wiki_upgrade0_9_15_005()
{
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0';
}


function wiki_upgrade1_0_0()
{
	// drop the index on the page-content, as it limites the content to 2700 chars
	if ($GLOBALS['egw_setup']->oProc->sType == 'pgsql')
	{
		$GLOBALS['egw_setup']->oProc->DropIndex('phpgw_wiki_pages',array('body'));
	}
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.001';
}


function wiki_upgrade1_0_0_001()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_links','page','wiki_name');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_links','lang','wiki_lang');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_links','link','wiki_link');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_links','count','wiki_count');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.002';
}


function wiki_upgrade1_0_0_002()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','name','wiki_name');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','lang','wiki_lang');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','version','wiki_version');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','time','wiki_time');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','supercede','wiki_supercede');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','readable','wiki_readable');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','writable','wiki_writable');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','username','wiki_username');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','hostname','wiki_hostname');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','comment','wiki_comment');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','title','wiki_title');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_pages','body','wiki_body');
	// deleted wiki-pages are now marked as NULL, not longer just as '', as MaxDB cant compare the LONG column agains ''
	$GLOBALS['egw_setup']->oProc->query("UPDATE phpgw_wiki_pages SET wiki_body=NULL WHERE wiki_body LIKE ''",__LINE__,__FILE__);

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.003';
}


function wiki_upgrade1_0_0_003()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_rate','ip','wiki_rate_ip');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_rate','time','wiki_rate_time');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_rate','viewLimit','wiki_rate_viewLimit');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_rate','searchLimit','wiki_rate_searchLimit');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_rate','editLimit','wiki_rate_editLimit');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.004';
}


function wiki_upgrade1_0_0_004()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_interwiki','prefix','interwiki_prefix');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_interwiki','where_defined_page','wiki_name');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_interwiki','where_defined_lang','wiki_lang');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_interwiki','url','interwiki_url');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.005';
}


function wiki_upgrade1_0_0_005()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_sisterwiki','prefix','sisterwiki_prefix');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_sisterwiki','where_defined_page','wiki_name');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_sisterwiki','where_defined_lang','wiki_lang');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_sisterwiki','url','sisterwiki_url');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.006';
}


function wiki_upgrade1_0_0_006()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_remote_pages','page','wiki_remote_page');
	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_wiki_remote_pages','site','wiki_remote_site');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.0.007';
}


function wiki_upgrade1_0_0_007()
{
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_links','egw_wiki_links');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_pages','egw_wiki_pages');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_rate','egw_wiki_rate');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_interwiki','egw_wiki_interwiki');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_sisterwiki','egw_wiki_sisterwiki');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_wiki_remote_pages','egw_wiki_remote_pages');

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.0.1.001';
}


function wiki_upgrade1_0_1_001()
{
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.2';
}


function wiki_upgrade1_2()
{
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.4';
}


function wiki_upgrade1_4()
{
	/* done by RefreshTable() anyway
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_wiki_pages','wiki_body',array(
		'type' => 'longtext'
	));*/
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_wiki_pages',array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '0'),
			'wiki_name' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'wiki_version' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '1'),
			'wiki_time' => array('type' => 'int','precision' => '4'),
			'wiki_supercede' => array('type' => 'int','precision' => '4'),
			'wiki_readable' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'wiki_writable' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'wiki_username' => array('type' => 'varchar','precision' => '80'),
			'wiki_hostname' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_comment' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_title' => array('type' => 'varchar','precision' => '80'),
			'wiki_body' => array('type' => 'longtext')
		),
		'pk' => array('wiki_id','wiki_name','wiki_lang','wiki_version'),
		'fk' => array(),
		'ix' => array('wiki_title',array('wiki_body','options' => array('mysql' => 'FULLTEXT',' mssql' => '',' pgsql' => '',' maxdb' => '',' sapdb' => ''))),
		'uc' => array()
	));

	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.5.001';
}


function wiki_upgrade1_5_001()
{
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.6';
}


function wiki_upgrade1_6()
{
	return $GLOBALS['setup_info']['wiki']['currentver'] = '1.8';
}
