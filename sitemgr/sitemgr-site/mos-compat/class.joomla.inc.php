<?php
	/**
 * eGroupWare: sitemgr: Joomla Template handler
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sitemgr
 * @author Stefan Becker <StefanBecker-AT-outdoor-training.de>
 */

class joomla {

	function getmenu()
	{
		$cat_id = 0;
		$depth = 0;
		$tree = array();	// stack/path mit cat_id's
		foreach($GLOBALS['objbo']->getIndex(false,false,true) as $page)
		{
			//_debug_array($page);
			$id = 2 * $page['cat_id'];			// cat's have even id's
			if ($id != $cat_id)		// new cat
			{
				$cat_id = $id;
				if($depth == $page['catdepth'])	// same level -> remove last element
				{
					array_pop($tree);
				}
				elseif($depth > $page['catdepth'])	// we are going back (maybe multiple levels)
				{
					$tree = array_slice($tree,0,$page['catdepth']-1);
				}
				$tree[] = $cat_id;
				$depth = $page['catdepth'];
				$cat_path = implode('/',$tree);
				$parent = (int)$tree[$depth-2];
				$name = $page['catname'];
				$title = $page['catdescrip']?$page['catdescrip']:$page['catname'];
				$url = $page['cat_url'];
				$rows[] = (object)$this->set_menu($page,$parent,$depth,$tree,$name,$id,$url,$title);
				//echo "<p>new cat $page[cat_id]=$cat_id ($depth: /$cat_path, parent=$parent): $page[catname]:</p>\n";
			}
			if ($page['page_id'])
			{
				$id = 2 * $page['page_id'] + 1;	// pages have odd id's
				$page_path = $cat_path.'/'.$id;
				$name=$page['pagetitle']?$page['pagetitle']:$page['pagename'];
				$parent = (int)$tree[$depth-1];
				$url= $page['page_url'];
				$rows[] = (object)$this->set_menu($page,$parent,$depth,$tree,$name,$id,$url);
				//echo "- page: $page[page_id]=$id (".($depth+1).": /$page_path, parent=$cat_id): $page[pagename]<br/>\n";
			}
		}
		//_debug_array($rows);
		return $rows;
	}

	function set_menu($page,$parent,$sublevel,$tree,$name,$id,$url,$title=null)
	{
		$arr = array(
		'id' => $id,
		'menutype' => 'mainmenu',
		'name' => $name,
 		'alias' => $name,
 		'title' => $title?$title:$name,
  		'type' => 'component_item_link',
		'published' => 1,
		'parent' => $parent,
		'componentid' => 20,
		'sublevel' => $sublevel,
		'ordering' => 1,
		'checked_out' => 62,
		'checked_out_time' => '2008-06-30 16:52:29',
		'pollid' => 0,
		'browserNav' => 0,
		'access' => 0,
		'utaccess' => 3,
		'params' => 'num_leading_articles=1
		num_intro_articles=4
		num_columns=2
		num_links=4
		orderby_pri=
		orderby_sec=front
		show_pagination=2
		show_pagination_results=1
		show_feed_link=1
		show_noauth=
		show_title=
		link_titles=
		show_intro=
		show_section=
		link_section=
		show_category=
		link_category=
		show_author=
		show_create_date=
		show_modify_date=
		show_item_navigation=
		show_readmore=
		show_vote=
		show_icons=
		show_pdf_icon=
		show_print_icon=
		show_email_icon=
		show_hits=
		feed_summary=
		page_title=
		show_page_title=1
		pageclass_sfx=
		menu_image=-1
		secure=0'

		,
		'lft' => 0,
		'rgt' => 0,
		'home' => 1,
		'component' => 'com_content',
		'tree' => $tree,
	  		'route' => 'home',
	  		'query' => Array('option' => 'com_content','view' => 'frontpage'),
		'url'  => $url,
		'_idx' => 0,
		);
		return $arr;
	}

}