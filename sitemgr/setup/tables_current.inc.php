<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: tables_current.inc.php 25382 2008-04-30 21:03:57Z ralfbecker $ */

	$phpgw_baseline = array(
		'egw_sitemgr_pages' => array(
			'fd' => array(
				'page_id' => array('type' => 'auto','nullable' => False),
				'cat_id' => array('type' => 'int','precision' => '4'),
				'sort_order' => array('type' => 'int','precision' => '4'),
				'hide_page' => array('type' => 'int','precision' => '4'),
				'name' => array('type' => 'varchar','precision' => '100'),
				'state' => array('type' => 'int','precision' => '2')
			),
			'pk' => array('page_id'),
			'fk' => array(),
			'ix' => array('cat_id',array('state','cat_id','sort_order'),array('name','cat_id')),
			'uc' => array()
		),
		'egw_sitemgr_pages_lang' => array(
			'fd' => array(
				'page_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'lang' => array('type' => 'varchar','precision' => '5','nullable' => False),
				'title' => array('type' => 'varchar','precision' => '255'),
				'subtitle' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('page_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_categories_state' => array(
			'fd' => array(
				'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'state' => array('type' => 'int','precision' => '2'),
				'index_page_id' => array('type' => 'int','precision' => '4','default' => '0')
			),
			'pk' => array('cat_id'),
			'fk' => array(),
			'ix' => array(array('cat_id','state')),
			'uc' => array()
		),
		'egw_sitemgr_categories_lang' => array(
			'fd' => array(
				'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'lang' => array('type' => 'varchar','precision' => '5','nullable' => False),
				'name' => array('type' => 'varchar','precision' => '100'),
				'description' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('cat_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_modules' => array(
			'fd' => array(
				'module_id' => array('type' => 'auto','precision' => '4','nullable' => False),
				'module_name' => array('type' => 'varchar','precision' => '25'),
				'description' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('module_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_blocks' => array(
			'fd' => array(
				'block_id' => array('type' => 'auto','nullable' => False),
				'area' => array('type' => 'varchar','precision' => '50'),
				'cat_id' => array('type' => 'int','precision' => '4'),
				'page_id' => array('type' => 'int','precision' => '4'),
				'module_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'sort_order' => array('type' => 'int','precision' => '4'),
				'viewable' => array('type' => 'int','precision' => '4')
			),
			'pk' => array('block_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_blocks_lang' => array(
			'fd' => array(
				'block_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'lang' => array('type' => 'varchar','precision' => '5','nullable' => False),
				'title' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('block_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_content' => array(
			'fd' => array(
				'version_id' => array('type' => 'auto','nullable' => False),
				'block_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'arguments' => array('type' => 'text'),
				'state' => array('type' => 'int','precision' => '2')
			),
			'pk' => array('version_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_content_lang' => array(
			'fd' => array(
				'version_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'lang' => array('type' => 'varchar','precision' => '5','nullable' => False),
				'arguments_lang' => array('type' => 'text'),
			),
			'pk' => array('version_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_active_modules' => array(
			'fd' => array(
				'area' => array('type' => 'varchar','precision' => '50','nullable' => False),
				'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'module_id' => array('type' => 'int','precision' => '4','nullable' => False)
			),
			'pk' => array('area','cat_id','module_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_properties' => array(
			'fd' => array(
				'area' => array('type' => 'varchar','precision' => '50','nullable' => False),
				'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'module_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'properties' => array('type' => 'text')
			),
			'pk' => array('area','cat_id','module_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		),
		'egw_sitemgr_sites' => array(
			'fd' => array(
				'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'site_name' => array('type' => 'varchar','precision' => '255'),
				'site_url' => array('type' => 'varchar','precision' => '255'),
				'site_dir' => array('type' => 'varchar','precision' => '255'),
				'themesel' => array('type' => 'varchar','precision' => '50'),
				'site_languages' => array('type' => 'varchar','precision' => '50'),
				'home_page_id' => array('type' => 'int','precision' => '4'),
				'anonymous_user' => array('type' => 'varchar','precision' => '50'),
				'anonymous_passwd' => array('type' => 'varchar','precision' => '50'),
				'upload_dir' => array('type' => 'varchar','precision' => '255'),
				'upload_url' => array('type' => 'varchar','precision' => '255')
			),
			'pk' => array('site_id'),
			'fk' => array(),
			'ix' => array('site_url'),
			'uc' => array()
		),
		'egw_sitemgr_notifications' => array(
			'fd' => array(
				'notification_id' => array('type' => 'auto','nullable' => False),
				'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'site_language' => array('type' => 'varchar','precision' => '5','nullable' => False,'default' => 'all'),
				'cat_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
				'email' => array('type' => 'varchar','precision' => '255','nullable' => False)
			),
			'pk' => array('notification_id'),
			'fk' => array('site_id' => 'egw_sitemgr_sites'),
			'ix' => array('email'),
			'uc' => array()
		),
		'egw_sitemgr_notify_messages' => array(
			'fd' => array(
				'message_id' => array('type' => 'auto','nullable' => False),
				'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
				'language' => array('type' => 'varchar','precision' => '5'),
				'message' => array('type' => 'text','nullable' => False),
				'subject' => array('type' => 'text','nullable' => False)
			),
			'pk' => array('message_id'),
			'fk' => array('site_id' => 'egw_sitemgr_sites'),
			'ix' => array(),
			'uc' => array(array('site_id','language'))
		)
	);
