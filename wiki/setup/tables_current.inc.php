<?php
/**
 * eGroupware - Wiki
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package wiki
 * @subpackage setup
 * @version $Id: tables_current.inc.php 38579 2012-03-24 09:03:03Z ralfbecker $
 */

$phpgw_baseline = array(
	'egw_wiki_links' => array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '2','nullable' => False,'default' => '0'),
			'wiki_name' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'wiki_link' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_count' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0')
		),
		'pk' => array('wiki_id','wiki_name','wiki_lang','wiki_link'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_wiki_pages' => array(
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
		'ix' => array('wiki_title',array('wiki_body','options' => array('mysql' => 'FULLTEXT','mssql' => '','pgsql' => '','maxdb' => '','sapdb' => ''))),
		'uc' => array()
	),
	'egw_wiki_rate' => array(
		'fd' => array(
			'wiki_rate_ip' => array('type' => 'char','precision' => '20','nullable' => False,'default' => ''),
			'wiki_rate_time' => array('type' => 'int','precision' => '4'),
			'wiki_rate_viewLimit' => array('type' => 'int','precision' => '2'),
			'wiki_rate_searchLimit' => array('type' => 'int','precision' => '2'),
			'wiki_rate_editLimit' => array('type' => 'int','precision' => '2')
		),
		'pk' => array('wiki_rate_ip'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_wiki_interwiki' => array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'interwiki_prefix' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_name' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'interwiki_url' => array('type' => 'varchar','precision' => '255','nullable' => False,'default' => '')
		),
		'pk' => array('wiki_id','interwiki_prefix'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_wiki_sisterwiki' => array(
		'fd' => array(
			'wiki_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'sisterwiki_prefix' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_name' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_lang' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => ''),
			'sisterwiki_url' => array('type' => 'varchar','precision' => '255','nullable' => False,'default' => '')
		),
		'pk' => array('wiki_id','sisterwiki_prefix'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	),
	'egw_wiki_remote_pages' => array(
		'fd' => array(
			'wiki_remote_page' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => ''),
			'wiki_remote_site' => array('type' => 'varchar','precision' => '80','nullable' => False,'default' => '')
		),
		'pk' => array('wiki_remote_page','wiki_remote_site'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	)
);
