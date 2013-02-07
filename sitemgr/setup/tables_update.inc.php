<?php
/**
 * eGroupWare - SiteMgr Web content Management
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sitemgr
 * @subpackage setup
 * @version $Id: tables_update.inc.php 32107 2010-09-15 17:51:10Z ralfbecker $
 */

function sitemgr_upgrade0_9_13_001()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.001';

	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_pages',
		'sort_order',array('type'=>int, 'precision'=>4));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_categories',
		'sort_order',array('type'=>int, 'precision'=>4));

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_001()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.002';

	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_pages',
		'hide_page',array('type'=>int, 'precision'=>4));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_categories',
		'parent',array('type'=>int, 'precision'=>4));

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_002()
{
	/******************************************************\
	* Purpose of this upgrade is to switch to phpgw        *
	* categories from the db categories.  So the           *
	* sql data will be moved to the cat stuff and the sql  *
	* categories table will be deleted.                    *
	*                                                      *
	* It would be nice if we could just run an UPDATE sql  *
	* query, but then you run the risk of this scenario:   *
	* old_cat_id = 5, new_cat_id = 2 --> update all pages  *
	* old_cat_id = 2, new_cat_id = 3 --> update all pages  *
	*  now all old_cat_id 5 pages are cat_id 3....         *
	\******************************************************/
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.003';

	//$cat_db_so =& CreateObject('sitemgr.Categories_db_SO');

	//$cat_db_so->convert_to_phpgwapi();

	// Finally, delete the categories table
	//$GLOBALS['egw_setup']->oProc->DropTable('phpgw_sitemgr_categories');

	// Turns out that convert_to_phpgwapi() must be run under
	// the normal phpgw environment and not the setup env.
	// This upgrade routine has been moved to the main body
	// of code.

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_003()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.004';

	if (file_exists(EGW_SERVER_ROOT .'/sitemgr/setup/sitemgr_sitelang'))
	{
		$langfile = file(EGW_SERVER_ROOT . '/sitemgr/setup/sitemgr_sitelang');
		$lang = rtrim($langfile[0]);
		if (strlen($lang) != 2)
		{
			$lang = "en";
		}
		}
	else
		{
			$lang = "en";
		}

	//echo 'Updating sitemgr to a multilingual architecture with existing site language ' . $lang;

	$db2 = $GLOBALS['egw_setup']->db;

	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_pages_lang',
		array(
			'fd' => array(
				'page_id' => array('type' => 'auto', 'nullable' => false),
				'lang' => array('type' => 'varchar', 'precision' => 2,
					'nullable' => false),
				'title' => array('type' => 'varchar', 'precision' => 256),
				'subtitle' => array('type' => 'varchar',
					'precision' => 256),
				'content' => array('type' => 'text')
			),
			'pk' => array('page_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		)
	);
	$GLOBALS['egw_setup']->oProc->CreateTable(
		'phpgw_sitemgr_categories_lang',
		array(
			'fd' => array(
				'cat_id' => array('type' => 'auto', 'nullable' => false),
				'lang' => array('type' => 'varchar', 'precision' => 2,
					'nullable' => false),
				'name' => array('type' => 'varchar', 'precision' => 100),
				'description' => array('type' => 'varchar',
					'precision' => 256)
			),
			'pk' => array('cat_id','lang'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		)
	);
	$GLOBALS['egw_setup']->oProc->query("select * from {$GLOBALS['egw_setup']->cats_table} where cat_appname='sitemgr'");
	while($GLOBALS['egw_setup']->oProc->next_record())
	{
		$cat_id = $GLOBALS['egw_setup']->oProc->f('cat_id');
		$name = $GLOBALS['egw_setup']->oProc->f('cat_name');
		$description = $GLOBALS['egw_setup']->oProc->f('cat_description');
		$db2->query("INSERT INTO phpgw_sitemgr_categories_lang (cat_id, lang, name, description) VALUES ($cat_id, '$lang', '$name', '$description')");
	}

	$GLOBALS['egw_setup']->oProc->query("select * from phpgw_sitemgr_pages");
	while($GLOBALS['egw_setup']->oProc->next_record())
	{
		$page_id = $GLOBALS['egw_setup']->oProc->f('page_id');
		$title = $GLOBALS['egw_setup']->oProc->f('title');
		$subtitle = $GLOBALS['egw_setup']->oProc->f('subtitle');
		$content =  $GLOBALS['egw_setup']->oProc->f('content');

		$db2->query("INSERT INTO phpgw_sitemgr_pages_lang (page_id, lang, title, subtitle, content) VALUES ($page_id, '$lang', '$title', '$subtitle', '$content')");
	}

	$newtbldef = array(
		'fd' => array(
			'page_id' => array('type' => 'auto', 'nullable' => false),
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'hide_page' => array('type' => 'int', 'precision' => 4),
			'name' => array('type' => 'varchar', 'precision' => 100),
			'subtitle' => array('type' => 'varchar', 'precision' => 256),
			'content' => array('type' => 'text')
		),
		'pk' => array('page_id'),
		'fk' => array(),
		'ix' => array('cat_id'),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_pages',
		$newtbldef,'title');
	$newtbldef = array(
		'fd' => array(
			'page_id' => array('type' => 'auto', 'nullable' => false),
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'hide_page' => array('type' => 'int', 'precision' => 4),
			'name' => array('type' => 'varchar', 'precision' => 100),
			'content' => array('type' => 'text')
		),
		'pk' => array('page_id'),
		'fk' => array(),
		'ix' => array('cat_id'),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_pages',
		$newtbldef,'subtitle');
	$newtbldef = array(
		'fd' => array(
			'page_id' => array('type' => 'auto', 'nullable' => false),
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'hide_page' => array('type' => 'int', 'precision' => 4),
			'name' => array('type' => 'varchar', 'precision' => 100)
		),
		'pk' => array('page_id'),
		'fk' => array(),
		'ix' => array('cat_id'),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_pages',
		$newtbldef,'content');

	// try to set the sitelanguages preference.
	// if it already exists do nothing
	$db2->query("SELECT pref_id FROM phpgw_sitemgr_preferences WHERE name='sitelanguages'");
	if ($db2->next_record())
	{
	}
	else
	{
		$db2->query("INSERT INTO phpgw_sitemgr_preferences (name, value) VALUES ('sitelanguages', '$lang')");
	}

	//internationalize the names for site-name, header and footer
	//preferences
	$prefstochange = array('sitemgr-site-name','siteheader','sitefooter');

	foreach ($prefstochange as $oldprefname)
	{
		$newprefname = $oldprefname . '-' . $lang;
		//echo "DEBUG: Changing $oldprefname to $newprefname. ";
		$db2->query("UPDATE phpgw_sitemgr_preferences SET name='$newprefname' where name='$oldprefname'");
	}

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_004()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.005';

	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_sitemgr_blocks', 'position', 'pos');

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_005()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.14.006';

	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_blocks',
		'description', array('type' => 'varchar', 'precision' => 256));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_blocks',
		'view', array('type' => 'int', 'precision' => 4));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_blocks',
		'actif', array('type' => 'int', 'precision' => 2));
	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_14_006()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.15.001';

	$GLOBALS['egw_setup']->oProc->DropTable('phpgw_sitemgr_blocks');
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_modules',array(
		'fd' => array(
			'module_id' => array('type' => 'auto', 'precision' => 4, 'nullable' => false),
			'app_name' => array('type' => 'varchar', 'precision' => 25),
			'module_name' => array('type' => 'varchar', 'precision' => 25),
			'description' => array('type' => 'varchar', 'precision' => 255)
		),
		'pk' => array('module_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_content',array(
		'fd' => array(
			'block_id' => array('type' => 'auto', 'nullable' => false),
			'area' => array('type' => 'varchar', 'precision' => 50),
			//if page_id != NULL scope=page, elseif cat_id != NULL scope=category, else scope=site
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'page_id' => array('type' => 'int', 'precision' => 4),
			'module_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'arguments' => array('type' => 'text'),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'view' => array('type' => 'int', 'precision' => 4),
			'actif' => array('type' => 'int', 'precision' => 2)
		),
		'pk' => array('block_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_content_lang',array(
		'fd' => array(
			'block_id' => array('type' => 'auto', 'nullable' => false),
			'lang' => array('type' => 'varchar', 'precision' => 2, 'nullable' => false),
			'arguments_lang' => array('type' => 'text'),
			'title' => array('type' => 'varchar', 'precision' => 255),
		),
		'pk' => array('block_id','lang'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_active_modules',array(
		'fd' => array(
			// area __PAGE__ stands for master list
			'area' => array('type' => 'varchar', 'precision' => 50, 'nullable' => false),
			// cat_id 0 stands for site wide
			'cat_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'module_id' => array('type' => 'auto', 'precision' => 4, 'nullable' => false)
		),
		'pk' => array('area','cat_id','module_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_properties',array(
		'fd' => array(
			// area __PAGE__ stands for all areas
			'area' => array('type' => 'varchar', 'precision' => 50, 'nullable' => false),
			// cat_id 0 stands for site wide
			'cat_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'module_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'properties' => array('type' => 'text')
		),
		'pk' => array('area','cat_id','module_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	//we register some standard modules so that the default site template works
	// but only if we running a real update
	if (!$GLOBALS['egw_setup']->oProc->m_bDeltaOnly)
	{
	$db2 = $GLOBALS['egw_setup']->db;
		foreach (array('index','toc','html') as $module)
		{
			$db2->query("INSERT INTO phpgw_sitemgr_modules (app_name,module_name) VALUES ('sitemgr','$module')",__LINE__,__FILE__);
			$module_id = $db2->get_last_insert_id('phpgw_sitemgr_modules','module_id');
			$db2->query("INSERT INTO phpgw_sitemgr_active_modules (area,cat_id,module_id) VALUES ('__PAGE__',0,$module_id)",__LINE__,__FILE__);
		}
	}
	//now to the difficult part: we try to put the old content field of phpgw_sitemgr_pages into the new phpgw_sitemgr_content table
	$db3 = $GLOBALS['egw_setup']->db;
	$GLOBALS['egw_setup']->oProc->query("select * from phpgw_sitemgr_pages",__LINE__,__FILE__);
	$emptyarray = serialize(array());
	while($GLOBALS['egw_setup']->oProc->next_record())
	{
		$page_id = $GLOBALS['egw_setup']->oProc->f('page_id');
		$cat_id = $GLOBALS['egw_setup']->oProc->f('cat_id');
		//module_id is still the id of html module since it is the last inserted above
		$db2->query("INSERT INTO phpgw_sitemgr_content (area,cat_id,page_id,module_id,arguments,sort_order,view,actif) VALUES ('CENTER',$cat_id,$page_id,$module_id,'$emptyarray',0,0,1)",__LINE__,__FILE__);
		$block_id = $db2->get_last_insert_id('phpgw_sitemgr_content','block_id');
		$db2->query("select * from phpgw_sitemgr_pages_lang WHERE page_id = $page_id",__LINE__,__FILE__);
		while($db2->next_record())
		{
			$lang = $db2->f('lang');
			$content = $db2->db_addslashes(serialize(array('htmlcontent' => stripslashes($db2->f('content')))));
			$db3->query("INSERT INTO phpgw_sitemgr_content_lang (block_id,lang,arguments_lang,title) VALUES ($block_id,'$lang','$content','HTML')",__LINE__,__FILE__);
		}
	}
	//finally drop the content field
	$newtbldef = array(
		'fd' => array(
			'page_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'lang' => array('type' => 'varchar', 'precision' => 2, 'nullable' => false),
			'title' => array('type' => 'varchar', 'precision' => 255),
			'subtitle' => array('type' => 'varchar', 'precision' => 255)
		),
		'pk' => array('page_id','lang'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_pages_lang',$newtbldef,'content');
	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_001()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.15.002';

	$GLOBALS['egw_setup']->oProc->RenameColumn('phpgw_sitemgr_content', 'view', 'viewable');

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_002()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.15.003';

	$newtbldef = array(
		'fd' => array(
			'module_id' => array('type' => 'auto', 'precision' => 4, 'nullable' => false),
			'module_name' => array('type' => 'varchar', 'precision' => 25),
			'description' => array('type' => 'varchar', 'precision' => 255)
		),
		'pk' => array('module_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	);

	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_modules',$newtbldef,'app_name');

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_003()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.15.004';

	$GLOBALS['egw_setup']->oProc->createtable('phpgw_sitemgr_sites',array(
		'fd' => array(
			'site_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'site_name' => array('type' => 'varchar', 'precision' => 255),
			'site_url' => array('type' => 'varchar', 'precision' => 255),
			'site_dir' => array('type' => 'varchar', 'precision' => 255),
			'themesel' => array('type' => 'varchar', 'precision' => 50),
			'site_languages' => array('type' => 'varchar', 'precision' => 50),
			'home_page_id' => array('type' => 'int', 'precision' => 4),
			'anonymous_user' => array('type' => 'varchar', 'precision' => 50),
			'anonymous_passwd' => array('type' => 'varchar', 'precision' => 50),
		),
		'pk' => array('site_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	$db2 = $GLOBALS['egw_setup']->db;

	//Create default site and hang all existing categories into it
	$GLOBALS['egw_setup']->oProc->query("INSERT INTO {$GLOBALS['egw_setup']->cats_table} (cat_parent,cat_owner,cat_access,cat_appname,cat_name,cat_description,last_mod) VALUES (0,-1,'public','sitemgr','Default Website','This website has been added by setup',0)");

	$GLOBALS['egw_setup']->oProc->query("SELECT cat_id FROM {$GLOBALS['egw_setup']->cats_table} WHERE cat_name='Default Website' AND cat_appname='sitemgr'");
	if ($GLOBALS['egw_setup']->oProc->next_record())
	{
		$site_id = $GLOBALS['egw_setup']->oProc->f('cat_id');
		$db2->query("UPDATE {$GLOBALS['egw_setup']->cats_table} SET cat_main = $site_id WHERE cat_appname = 'sitemgr'",__LINE__,__FILE__);
		$db2->query("UPDATE {$GLOBALS['egw_setup']->cats_table} SET cat_parent = $site_id WHERE cat_appname = 'sitemgr' AND cat_parent = 0 AND cat_id != $site_id",__LINE__,__FILE__);
		$db2->query("UPDATE {$GLOBALS['egw_setup']->cats_table} SET cat_level = cat_level +1 WHERE cat_appname = 'sitemgr' AND cat_id != $site_id",__LINE__,__FILE__);
		$db2->query("INSERT INTO phpgw_sitemgr_sites (site_id,site_name)  VALUES ($site_id,'Default Website')");
	}

	//insert values from old preferences table into new sites table
	$oldtonew = array(
		'sitemgr-site-url' => 'site_url',
		'sitemgr-site-dir' => 'site_dir',
		'themesel' => 'themesel',
		'sitelanguages' => 'site_languages',
		'home-page-id' => 'home_page_id',
		'anonymous-user' => 'anonymous_user',
		'anonymous-passwd' => 'anonymous_passwd'
	);
	foreach ($oldtonew as $old => $new)
	{
		$GLOBALS['egw_setup']->oProc->query("SELECT value from phpgw_sitemgr_preferences WHERE name = '$old'");
		if ($GLOBALS['egw_setup']->oProc->next_record())
		{
			$value = $GLOBALS['egw_setup']->oProc->f('value');
			$db2->query("UPDATE phpgw_sitemgr_sites SET $new = '$value' WHERE site_id = $site_id");
		}
	}

	//site names and headers
	$GLOBALS['egw_setup']->oProc->query("SELECT site_languages from phpgw_sitemgr_sites");
	if ($GLOBALS['egw_setup']->oProc->next_record())
	{
		$sitelanguages = $db2->f('site_languages');
		$sitelanguages = explode(',',$sitelanguages);
		$db2->query("SELECT module_id from phpgw_sitemgr_modules WHERE module_name='html'");
		$db2->next_record();
		$html_module = $db2->f('module_id');
		$emptyarray = serialize(array());
		$db2->query("INSERT INTO phpgw_sitemgr_content (area,cat_id,page_id,module_id,arguments,sort_order,viewable,actif) VALUES ('HEADER',$site_id,0,$html_module,'$emptyarray',0,0,1)",__LINE__,__FILE__);
		$headerblock = $db2->get_last_insert_id('phpgw_sitemgr_content','block_id');
		$db2->query("INSERT INTO phpgw_sitemgr_content (area,cat_id,page_id,module_id,arguments,sort_order,viewable,actif) VALUES ('FOOTER',$site_id,0,$html_module,'$emptyarray',0,0,1)",__LINE__,__FILE__);
		$footerblock = $db2->get_last_insert_id('phpgw_sitemgr_content','block_id');

		foreach ($sitelanguages as $lang)
		{
			$db2->query("SELECT value from phpgw_sitemgr_preferences WHERE name = 'sitemgr-site-name-$lang'");
			if ($db2->next_record())
			{
				$name_lang = $db2->f('value');
				$db2->query("INSERT INTO phpgw_sitemgr_categories_lang (cat_id,lang,name) VALUES ($site_id,'$lang','$name_lang')");
			}
			$db2->query("SELECT value from phpgw_sitemgr_preferences WHERE name = 'siteheader-$lang'");
			if ($db2->next_record())
			{
				$header_lang = $db2->f('value');
				$content = $db2->db_addslashes(serialize(array('htmlcontent' => stripslashes($header_lang))));

				$db2->query("INSERT INTO phpgw_sitemgr_content_lang (block_id,lang,arguments_lang,title) VALUES ($headerblock,'$lang','$content','Site header')",__LINE__,__FILE__);
			}
			$db2->query("SELECT value from phpgw_sitemgr_preferences WHERE name = 'sitefooter-$lang'");
			if ($db2->next_record())
			{
				$footer_lang = $db2->f('value');
				$content = $db2->db_addslashes(serialize(array('htmlcontent' => stripslashes($footer_lang))));

				$db2->query("INSERT INTO phpgw_sitemgr_content_lang (block_id,lang,arguments_lang,title) VALUES ($footerblock,'$lang','$content','Site footer')",__LINE__,__FILE__);
			}
		}
	}
	$GLOBALS['egw_setup']->oProc->DropTable('phpgw_sitemgr_preferences');

	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_004()
{
	global $setup_info;
	$setup_info['sitemgr']['currentver'] = '0.9.15.005';
	$db2 = $GLOBALS['egw_setup']->db;
	$db3 = $GLOBALS['egw_setup']->db;

	//Create the field state for pages and categories and give all existing pages and categories published state (2)
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_pages',
		'state',array('type'=>int, 'precision'=>2));

	$GLOBALS['egw_setup']->oProc->query("UPDATE phpgw_sitemgr_pages SET state = 2");

	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_categories_state',array(
		'fd' => array(
			'cat_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'state' => array('type' => 'int', 'precision' => 2)
		),
		'pk' => array('cat_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	$GLOBALS['egw_setup']->oProc->query("select cat_id from {$GLOBALS['egw_setup']->cats_table} where cat_appname='sitemgr' AND cat_level > 0");
	while($GLOBALS['egw_setup']->oProc->next_record())
	{
		$cat_id = $GLOBALS['egw_setup']->oProc->f('cat_id');
		$db2->query("INSERT INTO phpgw_sitemgr_categories_state (cat_id,state) VALUES ($cat_id,2)");
	}

	//rename table content blocks and table content_lang blocks_lang
	//and add the new tables content and content_lang
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_sitemgr_content','phpgw_sitemgr_blocks');
	$GLOBALS['egw_setup']->oProc->RenameTable('phpgw_sitemgr_content_lang','phpgw_sitemgr_blocks_lang');
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_content',array(
		'fd' => array(
			'version_id' => array('type' => 'auto', 'nullable' => false),
			'block_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'arguments' => array('type' => 'text'),
			'state' => array('type' => 'int', 'precision' => 2)
		),
		'pk' => array('version_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_content_lang',array(
		'fd' => array(
			'version_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'lang' => array('type' => 'varchar', 'precision' => 2, 'nullable' => false),
			'arguments_lang' => array('type' => 'text'),
		),
		'pk' => array('version_id','lang'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	));

	//create rows in the new content tables from old content tables (where state=0(Draft) when inactive, state=2(Published) when active)
	$GLOBALS['egw_setup']->oProc->query("SELECT block_id,arguments,actif FROM phpgw_sitemgr_blocks");
	while ($GLOBALS['egw_setup']->oProc->next_record())
	{
		$block_id = $GLOBALS['egw_setup']->oProc->f('block_id');
		$arguments = $GLOBALS['egw_setup']->oProc->f('arguments');
		$state = $GLOBALS['egw_setup']->oProc->f('actif') ? 0 : 2;
		$db2->query("INSERT INTO phpgw_sitemgr_content (block_id,arguments,state) VALUES ($block_id,'$arguments',$state)");
		$version_id = $db2->get_last_insert_id('phpgw_sitemgr_content','version_id');
		$db2->query("SELECT lang,arguments_lang  FROM phpgw_sitemgr_blocks_lang WHERE block_id = $block_id");
		while ($db2->next_record())
		{
			$lang = $db2->f('lang');
			$arguments_lang = $db2->f('arguments_lang');
			$title = $db2->f('title');
			$db3->query("INSERT INTO phpgw_sitemgr_content_lang (version_id,lang,arguments_lang) VALUES ($version_id,'$lang','$arguments_lang')");
		}
	}

	//drop columns in tables blocks and blocks_lang
	$newtbldef = array(
		'fd' => array(
			'block_id' => array('type' => 'auto', 'nullable' => false),
			'area' => array('type' => 'varchar', 'precision' => 50),
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'page_id' => array('type' => 'int', 'precision' => 4),
			'module_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'viewable' => array('type' => 'int', 'precision' => 4),
			'actif' => array('type' => 'int', 'precision' => 2)
		),
		'pk' => array('block_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_blocks',$newtbldef,'arguments');
	$newtbldef = array(
		'fd' => array(
			'block_id' => array('type' => 'auto', 'nullable' => false),
			'area' => array('type' => 'varchar', 'precision' => 50),
			'cat_id' => array('type' => 'int', 'precision' => 4),
			'page_id' => array('type' => 'int', 'precision' => 4),
			'module_id' => array('type' => 'int', 'precision' => 4, 'nullable' => false),
			'sort_order' => array('type' => 'int', 'precision' => 4),
			'viewable' => array('type' => 'int', 'precision' => 4),
		),
		'pk' => array('block_id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_blocks',$newtbldef,'actif');
	$newtbldef = array(
		'fd' => array(
			'block_id' => array('type' => 'auto', 'nullable' => false),
			'lang' => array('type' => 'varchar', 'precision' => 2, 'nullable' => false),
			'title' => array('type' => 'varchar', 'precision' => 255),
		),
		'pk' => array('block_id','lang'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
	);
	$GLOBALS['egw_setup']->oProc->DropColumn('phpgw_sitemgr_blocks_lang',$newtbldef,'arguments_lang');
	return $setup_info['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_005()
{
	// setting all lang-columns to varchar(5)
	foreach(array(
		'phpgw_sitemgr_pages_lang',
		'phpgw_sitemgr_categories_lang',
		'phpgw_sitemgr_blocks_lang',
		'phpgw_sitemgr_content_lang',
	) as $table)
	{
		$GLOBALS['egw_setup']->oProc->AlterColumn($table,'lang',array(
			'type' => 'varchar',
			'precision' => '5',
			'nullable' => False
		));
	}
	$GLOBALS['setup_info']['sitemgr']['currentver'] = '0.9.15.006';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_006()
{
	// add column for index-pages
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_categories_state','index_page_id',array(
		'type' => 'int',
		'precision' => '4',
		'default' => '0'
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '0.9.15.007';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


// the following series of updates add some indices, to speedup the selects
function sitemgr_upgrade0_9_15_007()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_sitemgr_pages',array(
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
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '0.9.15.008';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_008()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_sitemgr_categories_state',array(
		'fd' => array(
			'cat_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'state' => array('type' => 'int','precision' => '2'),
			'index_page_id' => array('type' => 'int','precision' => '4','default' => '0')
		),
		'pk' => array('cat_id'),
		'fk' => array(),
		'ix' => array(array('cat_id','state')),
		'uc' => array()
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '0.9.15.009';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_009()
{
	$GLOBALS['egw_setup']->oProc->RefreshTable('phpgw_sitemgr_sites',array(
		'fd' => array(
			'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'site_name' => array('type' => 'varchar','precision' => '255'),
			'site_url' => array('type' => 'varchar','precision' => '255'),
			'site_dir' => array('type' => 'varchar','precision' => '255'),
			'themesel' => array('type' => 'varchar','precision' => '50'),
			'site_languages' => array('type' => 'varchar','precision' => '50'),
			'home_page_id' => array('type' => 'int','precision' => '4'),
			'anonymous_user' => array('type' => 'varchar','precision' => '50'),
			'anonymous_passwd' => array('type' => 'varchar','precision' => '50')
		),
		'pk' => array('site_id'),
		'fk' => array(),
		'ix' => array('site_url'),
		'uc' => array()
	));

	// we dont need to do update 0.9.15.010, as UpdateSequenze is called now by RefreshTable
	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.0';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade0_9_15_010()
{
	$GLOBALS['egw_setup']->oProc->UpdateSequence('phpgw_sitemgr_pages','page_id');

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.0';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_0_0()
{
	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_notifications',array(
		'fd' => array(
			'notification_id' => array('type' => 'auto','nullable' => False),
			'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'site_language' => array('type' => 'varchar','precision' => '3','nullable' => False,'default' => 'all'),
			'cat_id' => array('type' => 'int','precision' => '4','nullable' => False,'default' => '0'),
			'email' => array('type' => 'varchar','precision' => '255','nullable' => False)
		),
		'pk' => array('notification_id'),
		'fk' => array('site_id' => 'phpgw_sitemgr_sites'),
		'ix' => array('email'),
		'uc' => array()
	));

	$GLOBALS['egw_setup']->oProc->CreateTable('phpgw_sitemgr_notify_messages',array(
		'fd' => array(
			'message_id' => array('type' => 'auto','nullable' => False),
			'site_id' => array('type' => 'int','precision' => '4','nullable' => False),
			'language' => array('type' => 'varchar','precision' => '3'),
			'message' => array('type' => 'text','nullable' => False),
			'subject' => array('type' => 'text','nullable' => False)
		),
		'pk' => array('message_id'),
		'fk' => array('site_id' => 'phpgw_sitemgr_sites'),
		'ix' => array(),
		'uc' => array(array('site_id','language'))
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.0.001';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_0_0_001()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_sites','upload_dir',array(
		'type' => 'varchar',
		'precision' => '50'
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.0.002';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_0_0_002()
{
	$GLOBALS['egw_setup']->oProc->AlterColumn('phpgw_sitemgr_sites','upload_dir',array(
		'type' => 'varchar',
		'precision' => '255'
	));
	$GLOBALS['egw_setup']->oProc->AddColumn('phpgw_sitemgr_sites','upload_url',array(
		'type' => 'varchar',
		'precision' => '255'
	));

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.0.003';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_0_0_003()
{
	foreach(array('phpgw_sitemgr_pages','phpgw_sitemgr_pages_lang','phpgw_sitemgr_categories_state','phpgw_sitemgr_categories_lang','phpgw_sitemgr_modules','phpgw_sitemgr_blocks','phpgw_sitemgr_blocks_lang','phpgw_sitemgr_content','phpgw_sitemgr_content_lang','phpgw_sitemgr_active_modules','phpgw_sitemgr_properties','phpgw_sitemgr_sites','phpgw_sitemgr_notifications','phpgw_sitemgr_notify_messages') as $table)
	{
		$GLOBALS['egw_setup']->oProc->RenameTable($table,str_replace('phpgw_sitemgr','egw_sitemgr',$table));
	}

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.0.1.001';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_0_1_001()
{
	// this update replaces diverse old navigation modules with conny's new "navigation" module
	$modules2nav_type = array('currentsection' => 1,'index' => 2,'index_block' => 3,'navigation' => 4,'sitetree' => 5,'toc' => 6,'toc_block' => 7);

	$db = clone($GLOBALS['egw_setup']->db);
	$db->set_app('sitemgr');
	$db2 = clone($db);

	// get the module_id of all navigation modules and remove the old modules
	$db->select('egw_sitemgr_modules','module_id,module_name',array('module_name' => array_keys($modules2nav_type)),__LINE__,__FILE__);
	$id2module = $old_modules = array();
	while(($row = $db->row(true)))
	{
		$id2module[$row['module_id']] = $row['module_name'];
		if ($row['module_name'] != 'navigation')
		{
			$old_modules[] = $row['module_id'];
		}
	}
	$db->delete('egw_sitemgr_modules',array('module_id' => $old_modules),__LINE__,__FILE__);

	// check if navigation is already registered, if not register it

	if (!($navigation_id = array_search('navigation',$id2module)))
	{
		if (ereg('\$this->description = lang\(\'([^'."\n".']*)\'\);',implode("\n",file(EGW_SERVER_ROOT.'/sitemgr/modules/class.module_navigation.inc.php')),$parts))
		{
			$description = str_replace("\\'","'",$parts[1]);
		}
		$db->insert('egw_sitemgr_modules',array(
			'module_name' => 'navigation',
			'module_description' => $description,
		),false,__LINE__,__FILE__);
		$navigation_id = $db->get_last_insert_id('egw_sitemgr_modules','module_id');
	}
	// add navigation to all contentareas, which allowed any for the old modules before and remove the old modules
	$db->select('egw_sitemgr_active_modules','DISTINCT cat_id,area',array('module_id' => $old_modules),__LINE__,__FILE__);
	while (($row = $db->row(true)))
	{
		$row['module_id'] = $navigation_id;
		$db2->insert('egw_sitemgr_active_modules',array(),$row,__LINE__,__FILE__);
	}
	$db->delete('egw_sitemgr_active_modules',array('module_id' => $old_modules),__LINE__,__FILE__);

	// replace old modules in the blocks with the navigation module
	$db->select('egw_sitemgr_blocks','block_id,module_id',array('module_id' => array_keys($id2module)),__LINE__,__FILE__);
	$block_id2module_id = array();
	while (($row = $db->row(true)))
	{
		$block_id2module_id[$row['block_id']] = $row['module_id'];
	}
	$db->select('egw_sitemgr_content','version_id,block_id,arguments',array('block_id' => array_keys($block_id2module_id)),__LINE__,__FILE__);
	while (($row = $db->row(true)))
	{
		$arguments = unserialize($row['arguments']);
		if (!isset($arguments['nav_type']))
		{
			$version_id = $row['version_id'];
			unset($row['version_id']);
			$arguments['nav_type'] = $modules2nav_type[$id2module[$block_id2module_id[$row['block_id']]]];
			$row['arguments'] = serialize($arguments);
			$db2->update('egw_sitemgr_content',$row,array('version_id' => $version_id),__LINE__,__FILE__);
		}
	}
	$db->update('egw_sitemgr_blocks',array('module_id' => $navigation_id),array('module_id' => $old_modules),__LINE__,__FILE__);

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.2';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_2()
{
	// replace news module with news_admin module
	$GLOBALS['egw_setup']->db->update('egw_sitemgr_modules',array('module_name' => 'news_admin'),array('module_name' => 'news'),__LINE__,__FILE__);

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.3.001';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_3_001()
{
	$db = clone($GLOBALS['egw_setup']->db);
	$db->set_app('sitemgr');

	// insert the search module into the module table
	$db->insert('egw_sitemgr_modules',array(
		'module_name' => 'search',
		'description' => 'This module search throw the content (Page title/description and html content)',
	),false,__LINE__,__FILE__);
	$search_id = $db->get_last_insert_id('egw_sitemgr_modules','module_id');

	// insert in the active_module table the search module (all areas are permited)
	foreach(array('left','right','header','footer','__PAGE__') as $area)
	{
		$db->insert('egw_sitemgr_active_modules',array(
			'area' => $area,
			'cat_id' => 2,
			'module_id' => $search_id,
		),false,__LINE__,__FILE__);
	}

	$GLOBALS['setup_info']['sitemgr']['currentver'] = '1.3.002';
	return $GLOBALS['setup_info']['sitemgr']['currentver'];
}


function sitemgr_upgrade1_3_002()
{
	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.4';
}


function sitemgr_upgrade1_4()
{
	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_sitemgr_notifications','site_language',array(
		'type' => 'varchar',
		'precision' => '5',
		'nullable' => False,
		'default' => 'all'
	));

	$GLOBALS['egw_setup']->oProc->AlterColumn('egw_sitemgr_notify_messages','language',array(
		'type' => 'varchar',
		'precision' => '5',
	));

	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.5.001';
}


function sitemgr_upgrade1_4_002()
{
	// duno why, but the stable 1.4 branch uses 1.4.002 for what's called 1.5.001 in trunk
	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.5.001';
}


function sitemgr_upgrade1_5_001()
{
	// convert htmlcontent value of serialized array in arguments_lang (used by the html block) into just that
	// (unserialzied) string in that (unserialized) column,
	// to easy the conversation from iso-... --> utf-8 (all serialized content with non-ascii chars get lost!)
	foreach($GLOBALS['egw_setup']->db->select('egw_sitemgr_content_lang','*',false,__LINE__,__FILE__,false,'','sitemgr') as $row)
	{
		if ($row['arguments_lang'] && ($arr = unserialize($row['arguments_lang'])) !== false &&
			is_array($arr) && count($arr) == 1 && isset($arr['htmlcontent']))
		{
			$row['arguments_lang'] = $arr['htmlcontent'];
			$GLOBALS['egw_setup']->db->update('egw_sitemgr_content_lang',array(
				'arguments_lang' => $row['arguments_lang'],
			),array(
				'version_id' => $row['version_id'],
				'lang' => $row['lang'],
			),__LINE__,__FILE__,'sitemgr');
		}
	}
	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.5.002';
}


function sitemgr_upgrade1_5_002()
{
	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.6';
}


function sitemgr_upgrade1_6()
{
	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.8';
}

/**
 * Downgrade from Trunk
 * 
 * @return string
 */
function sitemgr_upgrade1_9_001()
{
	$GLOBALS['egw_setup']->oProc->AddColumn('egw_sitemgr_sites','upload_url',array(
		'type' => 'varchar',
		'precision' => '255',
	));
	$GLOBALS['egw_setup']->oProc->DropColumn('egw_sitemgr_sites',array(
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
	),'htaccess_rewrite');

	return $GLOBALS['setup_info']['sitemgr']['currentver'] = '1.8';
}
