<?php
/**
 * eGroupWare - News admin
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package news_admin
 * @subpackage setup
 * @version $Id: tables_current.inc.php 26141 2008-10-10 11:44:47Z ralfbecker $
 */

$phpgw_baseline = array(
	'egw_news' => array(
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
			'news_is_html' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '1'),
			'news_source_id' => array('type' => 'int','precision' => '4'),
			'news_lang' => array('type' => 'varchar','precision' => '5')
		),
		'pk' => array('news_id'),
		'fk' => array(),
		'ix' => array('news_date','news_headline','cat_id','news_lang'),
		'uc' => array()
	),
	'egw_news_export' => array(
		'fd' => array(
			'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'export_type' => array('type' => 'int','precision' => '2','nullable' => True),
			'export_itemsyntax' => array('type' => 'int','precision' => '2','nullable' => True),
			'export_title' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_link' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_description' => array('type' => 'text','nullable' => True),
			'export_img_title' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_img_url' => array('type' => 'varchar','precision' => '255','nullable' => True),
			'export_img_link' => array('type' => 'varchar','precision' => '255','nullable' => True)
		),
		'pk' => array('cat_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	)
);
