<?php
	/**
	* sitemgr - user interface
	*
	* @link http://www.egroupware.org
	* @author Jose Luis Gordo Romero <jgordor@gmail.com>
	* @package sitemgr
	* @copyright Jose Luis Gordo Romero <jgordor@gmail.com>
	* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	* @version $Id: class.search_ui.inc.php 24736 2007-12-01 04:34:05Z jgordor $
	*/
	
	class search_ui
	{
		var $search_bo;

		function search_ui()
		{
			$this->search_bo =& CreateObject('sitemgr.search_bo', true);
		}
		
		function search($query,$lang="all",$mode="any",$options="advgoogle")
		{					
			$searched = $this->search_bo->search($query,$lang,$mode);
			
			$res = '<br><form name="research" method="post" action="'.sitemgr_link2('/index.php').'">' . "\n".
				   lang('search keyword').': &nbsp;'.
				   '<input type="text" height="20" size="19" name="searchword" value="'.$query.'">'.
			       "\n".'<input type="hidden" name="search_lang" value="'.$lang.'">'."\n".
			       '&nbsp;<input type="submit" value="'.lang('search').'"> <br>';
			       if ($options == "simple")
				   {
						$res .= '<input type="hidden" name="search_mode" value="'.$mode.'">';	
				   }
				   else
				   {
				   	    $res .= $this->show_options($mode,$options,$query);
				   }	   
				   
				   
			$res .= "</form><br><br>";
			// Construct the result
			if ($searched['matches'] == 0)
			{
				$res .= lang('Sorry, no content found for you search criteria');
				return array('search' => $query, 'matches' => 0, 'result' => $res);
			}
			$count = 1;
			$res .= lang('Number of results').': '.$searched['matches'].'<br><br>';
			foreach ($searched['result'] as $item)
			{
				$res .= '<div class="search_results">'."\n".$count.". &nbsp;";
				if ($item['link_page'])
				{
					$res .= lang('page').': &nbsp;'.$item['link_page']."<br>".
						    '<small>'.lang('Category').': &nbsp;'.$item['link_cat'].'</small>';
					if ($item['content'])
					{
						$res .= "<br><br>".$item['content'];
					}
				}
				else
				{
					$res .= 'Content in '.lang('category').': &nbsp;'.$item['link_cat'];
				}
				$res .= '</div><br>';
				$count = $count + 1;
			}
			return array('search' => $query, 'matches' => $searched['matches'], 'result' => $res);
		}
		
		function show_options($mode,$options,$query)
		{
			$res .= '<input type="radio" name="search_mode" value="any" ';
					if ($mode == "any") $res .= 'checked="checked"';
					$res .= ' ><label>'.lang('Any Words').'</label>&nbsp;&nbsp;'.
					'<input type="radio" name="search_mode" value="all" ';
					if ($mode == "all") $res .= 'checked="checked"';
					$res .= ' ><label>'.lang('All Words').'</label>&nbsp;&nbsp;'.					
					'<input type="radio" name="search_mode" value="exact" ';
					if ($mode == "exact") $res .= 'checked="checked"';
					$res .= ' ><label>'.lang('Exact Phrase').'</label>';					
					if ($options == "advgoogle")
					{
						$res .= '<br>&nbsp;&nbsp;'.lang('Search with').' <a href="http://www.google.com/search?q='.$query." site:".$_SERVER['HTTP_HOST'].'" target="_blank">'. 
								'<img alt="google" src="images/google_little.png" align="middle" border="0" name="Google"></a>';
					}
			return $res;	
		}			
	}
?>
