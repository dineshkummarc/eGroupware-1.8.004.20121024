<?php
/**
 * EGroupware - News admin
 *
 * The old version of this program was sponsored by Golden Glair productions
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package news_admin
 * @subpackage setup
 * @version $Id: tables_update.inc.php 37681 2012-01-09 11:54:49Z leithoff $
 */

function news_admin_upgrade0_0_1()
{
	return $setup_info['news_admin']['currentver'] = '0.8.1';
}


function news_admin_upgrade0_8_1()
{
	$GLOBALS['egw_setup']->oProc->RenameTable('webpage_news','phpgw_news');

	return $setup_info['news_admin']['currentver'] = '0.8.1.001';
}


function news_admin_upgrade0_8_1_001()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_news','news_cat',array('type' => 'int','precision' => 4,'nullable' => True));
	$GLOBALS['egw_setup']->oProc->query("update phpgw_news set news_cat='0'",__LINE__,__FILE__);

	return $setup_info['news_admin']['currentver'] = '0.8.1.002';
}


function news_admin_upgrade0_8_1_002()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_news','news_teaser',array(
		'type' => 'varchar',
		'precision' => '255',
		'nullable' => True
	));

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '0.9.14.500';
}


function news_admin_upgrade0_9_14_500()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_news_export',array(
		'fd' => array(
			'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'export_type' => array('type' => 'int','precision' => '2','nullable' => True),
			'export_itemsyntax' => array('type' => 'int','precision' => '2','nullable' => True),
			'export_title' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_link' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_description' => array('type' => 'text', 'nullable' => True),
			'export_img_title' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_img_url' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_img_link' => array('type' => 'varchar','precision' => '255','nullable' => True),
		),
		'pk' => array('cat_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '0.9.14.501';
}


function news_admin_upgrade0_9_14_501()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_news','news_begin',array(
		'type' => 'int','precision' => '4','nullable' => True
	));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_news','news_end',array(
		'type' => 'int','precision' => '4','nullable' => True
	));
	$db2 = $GLOBALS['egw_setup']->db;
	$GLOBALS['egw_setup']->oProc->query('SELECT news_id,news_status FROM phpgw_news');
	while($GLOBALS['egw_setup']->oProc->next_record())
	{
		$unixtimestampmax = 2147483647;
		$db2->query('UPDATE phpgw_news SET news_begin=news_date,news_end=' .
			(($GLOBALS['egw_setup']->oProc->f('news_status') == 'Active') ? $unixtimestampmax : 'news_date') .
			' WHERE news_id=' . $GLOBALS['egw_setup']->oProc->f('news_id'));
	}
	$newtbldef = array(
		'fd' => array(
			'news_id' => array('type' => 'auto','nullable' => False),
			'news_date' => array('type' => 'int','precision' => '4','nullable' => True),
			'news_subject' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'news_submittedby' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'news_content' => array('type' => 'blob','nullable' => True),
			'news_begin' => array('type' => 'int','precision' => '4','nullable' => True),
			'news_end' => array('type' => 'int','precision' => '4','nullable' => True),
			'news_cat' => array('type' => 'int','precision' => '4','nullable' => True),
			'news_teaser' => array('type' => 'varchar','precision' => '255','nullable' => True)
		),
		'pk' => array('news_id'),
		'fk' => array(),
		'ix' => array('news_date','news_subject'),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_news',$newtbldef,'news_status');

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '0.9.14.502';
}


function news_admin_upgrade0_9_14_502()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_news','is_html',array(
		'type' => 'int',
		'precision' => '2',
		'nullable' => False,
		'default' => '0'
	));

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '0.9.14.503';
}


function news_admin_upgrade0_9_14_503()
{
	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.0.0';
}


function news_admin_upgrade1_0_0()
{
	$GLOBALS['egw_setup']->oProc->AlterColumn('phpgw_news','news_begin',array(
		'type' => 'int',
		'precision' => '4',
		'nullable' => False,
		'default' => '0',
	));

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.0.0.001';
}


function news_admin_upgrade1_0_0_001()
{
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_news','egw_news');
	// timestamps have to be 64bit=8byte
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_date',array(
		'type' => 'int',
		'precision' => '8',
	));
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_begin',array(
		'type' => 'int',
		'precision' => '8',
		'nullable' => False,
		'default' => '0',
	));
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_end',array(
		'type' => 'int',
		'precision' => '8',
	));
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_news_export','egw_news_export');

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.2';
}


function news_admin_upgrade1_2()
{
	$GLOBALS['egw_setup']->oProc->RenameColumn('egw_news','news_subject','news_headline');
	$GLOBALS['egw_setup']->oProc->RenameColumn('egw_news','news_cat','cat_id');
	$GLOBALS['egw_setup']->oProc->RenameColumn('egw_news','is_html','news_is_html');
	/* done by RefreshTable() anyway
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_headline',array(
		'type' => 'varchar',
		'precision' => '128'
	));*/
	/* done by RefreshTable() anyway
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_submittedby',array(
		'type' => 'int',
		'precision' => '4'
	));*/
	/* done by RefreshTable() anyway
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_content',array(
		'type' => 'text'
	));*/
	/* done by RefreshTable() anyway
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_news','news_teaser',array(
		'type' => 'text'
	));*/
	$GLOBALS['egw_setup']->oProc->RefreshTable('egw_news',array(
		'fd' => array(
			'news_id' => array('type' => 'auto','nullable' => False),
			'news_date' => array('type' => 'int','precision' => '8'),
			'news_headline' => array('type' => 'varchar','precision' => '128'),
			'news_submittedby' => array('type' => 'int','precision' => '4'),
			'news_content' => array('type' => 'text'),
			'news_begin' => array('type' => 'int','precision' => '8','nullable' => False,'default' => '0'),
			'news_end' => array('type' => 'int','precision' => '8'),
			'cat_id' => array('type' => 'int','precision' => '4'),
			'news_teaser' => array('type' => 'text'),
			'news_is_html' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '1')
		),
		'pk' => array('news_id'),
		'fk' => array(),
		'ix' => array('news_date','news_headline'),
		'uc' => array()
	));

	// replace former no end-date value with NULL
	$GLOBALS['egw_setup']->db->query('UPDATE egw_news SET news_end=NULL WHERE news_end=2147483647',__LINE__,__FILE__);

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.3.001';
}


function news_admin_upgrade1_3_001()
{
	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.4';
}


function news_admin_upgrade1_4()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_news','news_source_id',array(
		'type' => 'int',
		'precision' => '4'
	));
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_news','news_lang',array(
		'type' => 'varchar',
		'precision' => '5'
	));

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.5.001';
}


function news_admin_upgrade1_5_001()
{
	// adding two necessary indexes (without them the new translation of news causes query times of more then 10min for ~15000 rows)
	$GLOBALS['egw_setup']->oProc->CreateIndex('egw_news','cat_id',false);
	$GLOBALS['egw_setup']->oProc->CreateIndex('egw_news','news_lang',false);

	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.5.002';
}


function news_admin_upgrade1_5_002()
{
	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.6';
}


function news_admin_upgrade1_6()
{
	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.8';
}

function news_admin_upgrade1_8()
{
	// Update egroupware.org news category importing news from egroupware.org readable by everyone
	$dataOld = serialize(array(
		'import_url' => 'http://www.egroupware.org//index.php?module=news_admin&cat_id=95,200',
		'import_frequency' => 4,
		'keep_imported' => 0,
	));
	$data = serialize(array(
		'import_url' => 'http://www.egroupware.org/index.php?module=news_admin&cat_id=3,200',
		'import_frequency' => 4,
		'keep_imported' => 0,
	));
	$GLOBALS['egw_setup']->oProc->query("update {$GLOBALS['egw_setup']->cats_table} set cat_data='$data' where cat_appname='news_admin' and cat_data='$dataOld'",__LINE__,__FILE__);
	return $GLOBALS['setup_info']['news_admin']['currentver'] = '1.8.001';
}

