<?php
/**
* sitemgr - search business object
*
* @link http://www.egroupware.org
* @author Jose Luis Gordo Romero <jgordor@gmail.com>
* @package sitemgr
* @copyright Jose Luis Gordo Romero <jgordor@gmail.com>
* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
* @version $Id: class.search_bo.inc.php 30990 2010-06-22 16:48:53Z ralfbecker $
*/

class search_bo
{
	var $so,$pages_bo,$catbo;

	function search_bo()
	{
		//all sitemgr BOs should be instantiated via a globalized Common_BO object,
		$this->so =& CreateObject('sitemgr.search_so', true);
		$this->catbo = &$GLOBALS['Common_BO']->cats;
		$this->pages_bo = &$GLOBALS['Common_BO']->pages;
	}
	function search($query,$lang="all",$mode="any")
	{
		$matches = 0;
		$searched = $this->so->search(htmlspecialchars(stripslashes($query)),$lang,$mode);
		if ($searched)
		{
			foreach ($searched as $item) 
			{			
				if ($GLOBALS['Common_BO']->acl->can_read_category($item['cat_id'])) //$has_perm
				{
					// Content in a category (not page) has page_id=0, then don't search for info
											
					$cat = $this->catbo->getCategory($item['cat_id'],$GLOBALS['sitemgr_info']['userlang']);
					
					if ($item['page_id'] != 0)
					{
						$page = $this->pages_bo->getPage($item['page_id'],$GLOBALS['sitemgr_info']['userlang']);
						$link_page = '<a href="'.sitemgr_link('page_name='.$page->name).'">'.$page->title.'</a>';								
					}
					else
					{
						$link_page = null;
					}

					$link_cat = '<a href="'.sitemgr_link('category_id='.$item['cat_id']).'">'.$cat->name.'</a>';

					$res[] = array('link_cat' => $link_cat, 'link_page' => $link_page, 'content' => $this->extract_content($item['content'],$query,$mode));
					$matches = $matches + 1;
				}
			} 
			return array('search' => $query, 'matches' => $matches, 'result' => $res);
		}
		else
		{
			return array('search' => $query, 'matches' => $matches, 'result' => "No records found");
		}
	}
	
	function extract_content($content,$query,$mode)
	{
		$content = unserialize($content);
		$content = strip_tags($content['htmlcontent']);
		
		
		if ($mode == "exact") {
			$searchwords = array($query);
			$needle = $query;
		} else {
			$searchwords = explode(' ', $query);
			$needle = $searchwords[0];
		}

		$content = $this->extract_word($content,$needle);
		
		// highlight_string
	  	foreach ($searchwords as $hlword) {
			$content = preg_replace( '/' . preg_quote( $hlword, '/' ) . '/i', '<span class="highlight">\0</span>', $content ); 
		}

		return $content;		
	}
	
	function extract_word($content,$word,$length=200)
	{
		// strips tags won't remove the actual jscript
		$content = preg_replace( "'<script[^>]*>.*?</script>'si", "", $content );	
		$content = preg_replace( '/{.+?}/', '', $content);			
		
		// replace line breaking tags with whitespace
		$content = preg_replace( "'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $content );
		
		$content = $this->SmartSubstr(strip_tags($content),$length,$word); 
	
		return $content;
	}

	function SmartSubstr($text, $length=200, $searchword) {
		$wordpos = strpos(strtolower($text), strtolower($searchword));
		$halfside = intval($wordpos - $length/2 - strlen($searchword));
		if ($wordpos && $halfside > 0) {
			return '... ' . substr($text, $halfside, $length) . ' ...';
		} else {
			return substr( $text, 0, $length);
		}
	}
}
