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

	/* $Id: class.module_navigation.inc.php 33634 2011-01-20 11:49:06Z leithoff $ */

	/**
	 * Navigation framework module
	 * The idea behind this module is, just to have ONE highly configurable module FOR ALL navigation elements
	 * If it's possible, we don't use extra functions for different views to reduce code
	 * There are some predefined views wich are quite commen or are needed for backward compability
	 *
	 * The views are customizeable by css. See default.css in folder sitemgr-site/templates/default/style/
	 *
	 *  There is a horde of options to control the generation engine:
	 * - category_id
	 * - current_section_only
	 * - expand
	 * - highlight_current_page
	 * - main_cats_to_include
	 * - max_cat_depth -> could be ablolute ('number')  or relative (+number)
	 * - max_pages_depth -> could be ablolute ('number')  or relative (+number)
	 * - nav_title
	 * - no_full_index
	 * - page_link_text {0 = title; 1 = subtitle }
	 * - path_only
	 * - show_cat_description
	 * - show_edit_icons
	 * - show_page_description
	 * - show_subcats_above
	 * - showhidden
	 * - sub_cats
	 * - suppress_current_cat
	 * - suppress_main_cats
	 * - suppress_cat_link
	 * - suppress_current_page
	 * - suppress_parent
	 * - suppress_show_all
	 *
	 * @author Cornelius Weiss<egw@von-und-zu-weiss.de>
	 * @package sitemgr
	 *
	 */
	class module_navigation extends Module
	{
		var $lastcatdepth = 0;
		var $lastpagedepth = 0;

		function module_navigation()
		{
			$this->arguments = array(
				'nav_type' => array(
					'type' => 'select',
					'label' => lang('Select type of Navigation'),
					'options' => array(
						0 => lang('Select one'),
						1 => 'currentsection',
						2 => 'index',
						3 => 'index_block',
						4 => 'navigation',
						5 => 'sitetree',
						6 => 'toc',
						7 => 'toc_block',
						8 => 'path',
						9 => lang('custom'),
					   10 => 'tabs',
					   11 => 'XML Sitemap',
					)
				)
			);
			$this->nav_args = array(
				1 => array( // Currentsection
					'description' => lang('This block displays the current section\'s table of contents'),
					'suppress_current_page' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress the current page')
					),
					'suppress_parent' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress link to parent category')
					),
					'suppress_show_all' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress link to index (show all)')
					)),
				2 => array( // Index
					'description' => lang('This module provides the site index, it is automatically used by the index GET parameter')
					),
				3 => array( // Index_block
					'description' => lang('This module displays the root categories, its pages and evtl. subcategories. It is meant for side areas'),
					'sub_cats' => array(
						'type' => 'checkbox',
						'label' => lang('Show subcategories')
					),
					'no_full_index' => array(
						'type' => 'checkbox',
						'label' => lang('No link to full index')
					),
					'expand' => array(
						'type' => 'checkbox',
						'label' => lang('Expand current category')
					),
					'page_link_text' => array(
						'type' => 'select',
						'label' => lang('Text of page links'),
						'options' => array(
							0 => lang('Title'),
							1 => lang('Subtitle'))
					),
					'main_cats_to_include' => array(
						'type' => 'textfield',
						'label' => lang('Which main categories should be included (comma separated list, empty for all)'),
					)),
				4 => array( // Navigation
					'description' => lang("This module displays the root categories in one block each, with pages and subcategories (incl. their pages if activated).")
					),
				5 => array( // Sitetree
					'description' => lang('This block displays a javascript based tree menu')
					),
				6 => array( // Toc
					'description' => lang('This module provides a complete table of contents, it is automatically used by the toc and category_id GET parameters'),
					'category_id' =>array(
						'type' => 'textfield',
						'label' => lang('The category to display, 0 for complete table of contents')
					)),
				7 => array( // Toc_block
					'description' => lang('This module provides a condensed table of contents, meant for side areas')
					),
				8 => array( // Path
					'description' => lang('This module provides the path to the element currently shown'),
					'no_show_sep' => array(
						'type' => 'checkbox',
						'label'=> lang('Don\'t use egroupware css ">" separator (for templates that uses images/symbols for lists)')),
					'suppress_hide_pages' => array(
						'type' => 'checkbox',
						'label'=> lang('Don\'t show hiden pages in the path way')),

						),
				9 => array( //Custom
					'description' => lang('This module is a customisable navigation element'),
					'alignment' => array(
						'type' => 'select',
						'label' => lang('Allignment of navigation elements'),
						'options' => array(
							'vertical' => lang('Vertical'),
							'horizontal' => lang('Horizontal'))
					),
					'textalign' => array(
						'type' => 'select',
						'label' => lang('Text allignment'),
						'options' => array(
							'left' => lang('Left'),
							'center' => lang('Center'),
							'right' => lang('Right'))),
					'max_cat_depth' => array(
						'type' => 'textfield',
						'label' => lang('Maximal category depth to be shown'),
					),
					'max_pages_depth' => array(
						'type' => 'textfield',
						'label' => lang('Maximal page depth to be shown'),
					),
					'sub_cats' => array(
						'type' => 'checkbox',
						'label' => lang('Show subcategories')
					),
					'expand' => array(
						'type' => 'checkbox',
						'label' => lang('Expand current category'),
					),
					'current_section_only' => array(
						'type' => 'checkbox',
						'label' => lang('Show current section only')
					),
					'suppress_parent' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress link to parent category')
					),
					'suppress_current_cat' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress the current category')
					),
					'suppress_main_cats' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress main categories')
					),
					'suppress_show_all' => array(
						'type' => 'checkbox',
						'label' => lang('Suppress link to index (show all)')
					),
					'no_full_index' => array(
						'type' => 'checkbox',
						'label' => lang('No link to full index')
					),
					'highlight_current_page' => array(
						'type' => 'checkbox',
						'label' => lang('Highlight current page')
					),
				),
			   10 => array( // tabs
					'description' => lang('This module provides tabs'),
					'tab_names' => array(
						'type' => 'textfield',
						'label' => lang('Name of the tabs (comma seperated)'),
					),
					'tab_links' => array(
						'type' => 'textfield',
						'label' => lang('Links for the tabs (comma seperated)'),
					),
					'tab_active' => array(
						'type' => 'textfield',
						'label' => lang('When is a tab activated?').
							lang('Seperate Cats / Pages of one tab by :').
							lang('cats are numeric, pages strings'),
					),
				),
				11 => array(	// xml sitemap
					'description' => lang('This module provides an XML sitemap (see www.sitemap.org)'),
				),
			);
			$this->title = 'Navigation element';
			$this->description = lang("This module displays any kind of navigation element.");
		}

		function get_user_interface()
		{
			$GLOBALS['egw']->js->validate_file('tabs','tabs');

			// I know, this is ugly. If you find a better solution for this, please help!
			$interface[] = array(
				'label' => "
				<style type=\"text/css\">
					div.activetab{ display:inline; position: relative; left: -0px; text-align:left;}
					div.inactivetab{ display:none; }
				</style>
				<script type=\"text/javascript\">
					var tab = new Tabs(".(string)(count($this->arguments['nav_type']['options']) -1).",
					'activetab','inactivetab','tab','tabcontent','','','tabpage');
					tab.init();
				</script>",
			);
			$this->arguments['nav_type']['params'] = array(
				'onchange' => 'javascript:tab.display(this.value)'
			);

			$elementname = 'element[' . $this->block->version . '][nav_type]';
			$interface[] = array(
				'label' => '<b>'.$this->arguments['nav_type']['label'].'</b>'.
					parent::build_input_element($this->arguments['nav_type'],$this->block->arguments['nav_type'],$elementname)
			);

			// build the tab elements
			$tabs = '';
			for($id = 1; $id < count($this->arguments['nav_type']['options']); $id++)
			{
				$description = $this->nav_args[$id]['description'];
				unset($this->nav_args[$id]['description']);

				$tmpargs = $this->arguments;
				$this->arguments = $this->nav_args[$id];
				$tabs .= '<div id="tabcontent'. $id. '" class="inactivetab"><table>';
				$tabs .= '<tr><td colspan="2"><i>'. $description. '</i></td></tr>';
				if(count($this->nav_args[$id]) >= 1)
				{
					// only add content for the active nav_type, as otherwise we can NOT longer uncheck checkboxes,
					// if the same name is used for multiple nav_type's
					if ($id != $this->block->arguments['nav_type'])
					{
						$save_args =& $this->block->arguments;
						unset($this->block->arguments);
					}
					foreach (parent::get_user_interface() as $param)
					{
						$tabs .= '<tr><td>'.$param['label'].'</td><td>'.$param['form'].'</td></tr>';
					}
					// restore arguments
					if ($save_args)
					{
						$this->block->arguments =& $save_args;
						unset($save_args);
					}
				}
				else
				{
					$tabs .= '<td>'. lang('No additional arguments required'). '</td><td></td>';
				}
				$tabs .= '</table></div>';
				$this->arguments = $tmpargs;
			}
			$interface[] = array('label' => $tabs);

			// show current tab
			$interface[] = array(
				'label' => "
				<script type=\"text/javascript\">
					tab.display(". $this->block->arguments['nav_type']. ");
				</script>",
			);

			return $interface;
		}

		// strip options from other nav_types
		function validate(&$data)
		{
			$val_data = array('nav_type' => $data['nav_type']);
			foreach($data as $key => $val)
			{
				if($this->nav_args[$data['nav_type']][$key]) $val_data[$key] = $val;
			}
			$data = $val_data;
			return true;
		}

		function get_content(&$arguments,$properties)
		{
			$out =  "<!-- navigation-context begins here -->\n".
				"<div id=\"navigation-context".$arguments['nav_type']."\">\n".
				"  <div id=\"navigation-";
			switch ($arguments['nav_type'])
			{
				case 1 : // Currentsection
					$out .= "currentsection\">\n";
					$arguments = array_merge($arguments, array(
						'nav_title' => lang('Pages:'),
						'current_section_only' => true,
						'suppress_current_cat' => true,
						'highlight_current_page' => true,
						'max_cat_depth' => '+0',
						'max_pages_depth' => '+0',
						'showhidden' => false,
						'no_full_index' => true,
						'show_subcats_above' => true,
						'no-nav-cat-block-divs' => true,
					));
					break;
				case 2 : // Index
					$out .= "index\">\n";
					$arguments = array_merge($arguments, array(
						'max_cat_depth' => '999',	// *full* index is expected to be infinit
						'max_pages_depth' => '999',
						'showhidden' => true,
						'suppress_parent' => true,
						'suppress_show_all' => true,
						'suppress_cat_link' => true,
						'show_edit_icons' => true,
						'show_cat_description' => true,
						'show_page_description' => true,
						'no_full_index' => true,
					));
					break;
				case 3 : // Index_Block
					$out .= "index_block\">\n";
					$arguments = array_merge($arguments, array(
						'max_cat_depth' => $arguments['sub_cats'] ? '2' : '1',
						'max_pages_depth' => '1',
						'showhidden' => false,
						'suppress_parent' => true,
						'suppress_show_all' => true,
					));
					break;
				case 5 : // Sitetree
					$out .= "sitetree\">\n";
					$out .= $this->type_sitetree($arguments,$properties);
					$out .= "  </div>\n<!-- navigation context ends here -->\n</div>\n";
					return $out;
				case 6 : // Toc
					$out .= "toc\">\n";
					$arguments = array_merge($arguments, array(
						'suppress_show_all' => true,
						'show_edit_icons' => true,
						'show_cat_description' => true,
						'suppress_parent' => true,
						'no-nav-cat-block-divs' => true,
					));
					// Topic overview
					if((int)$arguments['category_id'] == 0)
					{
						$arguments = array_merge($arguments, array(
							'nav_title' => lang('Choose a category'),
							'max_cat_depth' => '10',
							'max_pages_depth' => '0',
							'no_full_index' => true,

						));
					}
					// like currentsection of a certain cat
					else
					{
						$arguments = array_merge($arguments, array(
							'nav_title' => lang('Pages:'),
							'suppress_current_cat' => true,
							'max_cat_depth' => '+0',
							'max_pages_depth' => '+0',
							'show_page_description' => true,
							'show_subcats_above' => true,
						));
					}
					break;
				case 7 : // Toc_block
					$out .= "toc_block\">\n";
					$arguments = array_merge($arguments, array(
						'suppress_show_all' => true,
						'no_full_index' => true,
						'suppress_parent' => true,
						'max_cat_depth' => '10',
						'max_pages_depth' => '0',
					));
					break;
				case 8 : // Path
					if(!$arguments['no_show_sep'])
					{
						$out .= "path\">\n";
					}
					else
					{
						$out .= "path-nosep\">\n";
					}
					$arguments = array_merge($arguments, array(
						'suppress_parent' => true,
						'suppress_show_all' => true,
						'path_only' => true,
						'no_full_index' => true,
						'no-nav-cat-block-divs' => true,
					));
					break;

				case 9 : // Custom
					$out .= "custom\" ";
					$out .= "class=\"alignment-". $arguments['alignment'].";";
					$out .= "textalign-". $arguments['textalign']."\"";
					$out .= ">\n";
					$arguments = array_merge($arguments, array(
						'no-nav-cat-block-divs' => true,
					));
					break;
				case 10 : // tabs
					$out .= "tabs\">\n";
					$out .= $this->type_tabs($arguments,$properties);
					$out .= "  </div>\n<!-- navigation context ends here -->\n</div>\n";
					return $out;
				case 11: // xml sitemap
					return $this->type_xml_sitemap($arguments,$properties);
				case 4 : // Navigation
				default:
					$out .= "navigation\">\n";
					$out .= $this->type_navigation($arguments,$properties);
					$out .= "  </div>\n<!-- navigation context ends here -->\n</div>\n";
					return $out;
			}

			$this->objbo =& $GLOBALS['objbo'];
			$this->page =& $GLOBALS['page'];
			$this->category =& $this->objbo->getcatwrapper($this->page->cat_id);
			//error_log(__METHOD__."(".array2string($arguments).") page=".array2string($this->page).", category=".array2string($this->category));

			if (!$arguments['suppress_parent'])
			{
				$parent = $this->category->parent;
				if ($parent && $parent != CURRENT_SITE_ID) // do we have a parent?
				{
					$p = $this->objbo->getcatwrapper($parent);
					$entry['link'] = '<a href="'.sitemgr_link2('/index.php','category_id='.$parent).'" title="'.$p->description.'">'.$p->name.'</a>';
					$out .= "\n<div class=\"nav-header-parent\">".lang('Parent Section:')."</div>\n";
					$out .= $this->encapsulate($arguments,array($parent => $entry),'cat',$parent);
					$out .= "\n<br />\n";
				}
			}

			if($arguments['show_subcats_above'])
			{
				$catlinks = $arguments['category_id'] ?
					$this->objbo->getCatLinks((int)$arguments['category_id'],False,True) :
					$this->objbo->getCatLinks((int)$this->page->cat_id,False,True);
				if(count($catlinks))
				{
					$out .= "\n<div class=\"nav-header-subsection\">".lang('Subsections:')."</div>\n";
					$out .= $arguments['category_id'] ?
						$this->encapsulate($arguments,$catlinks,'cat',(int)$arguments['category_id']) :
						$this->encapsulate($arguments,$catlinks,'cat',(int)$this->page->cat_id);
					$out .= "\n<br />\n";
				}
			}

			if($arguments['nav_title'])
			{
				$out .= "\n<span class=\"nav-title\">".$arguments['nav_title']."</span>\n";
			}

			if (!$arguments['suppress_show_all'])
			{
				$out .= ' (<a href="'.sitemgr_link2('/index.php','category_id='.$this->page->cat_id).
					'"><i>'.lang('show all').'</i></a>)'."\n";
			}

			// relative cat or pages depth ?
			if (strpos($arguments['max_cat_depth'],'+') === 0) (int)$arguments['max_cat_depth'] += $this->category->depth;
			if (strpos($arguments['max_pages_depth'],'+') === 0) (int)$arguments['max_pages_depth'] += $this->category->depth;

			$cat_tree = $cat_tree_data = array('root');

			$this->lastcatdepth = 0; // indicate start of first cat block!
			foreach(($this->objbo->getCatLinks(0,true,true) + array( 0 => array('depth' => 0))) as $cat_id => $cat)
			{
				if(array_key_exists($cat['depth'],$cat_tree))
				{
					$pop_depth = count($cat_tree);
					for($depth=$cat['depth']; $depth < $pop_depth; $depth++)
					{
						array_pop($cat_tree); array_pop($cat_tree_data);
					}
				}
				array_push($cat_tree,$cat_id); array_push($cat_tree_data,$cat);

				if($arguments['expand'] && $cat_id == $this->page->cat_id && $cat['depth'] >= $arguments['max_cat_depth'])
				{
					$cat_tree2 = $cat_tree;	$cat_tree_data2 = $cat_tree_data;
					//strip allready displayed contets of cat_tree
					unset($cat_tree2[0]); unset($cat_tree_data2[0]);
					foreach($cat_tree_data2 as $num => $category)
					{
						if($category['depth'] < $arguments['max_cat_depth'])
						{
							unset($cat_tree2[$num]); unset($cat_tree_data2[$num]);
						}
						// we need only pages of this cat, but not cat itseve!
						if($category['depth'] ==  $arguments['max_cat_depth'] && $this->page->cat_id != $cat_tree2[$num])
						{
							$cat_tree_data2[$num]['pages_only'] = true;
						}
					}

					//expand rest
					$cat_tree2 = array_reverse($cat_tree2); $cat_tree_data2 = array_reverse($cat_tree_data2);
					$outstack = array($cat_tree2[count($cat_tree2) -1]); $outstack_data = array($cat_tree_data2[count($cat_tree2) -1]);
					$popcat = array_pop($outstack); $popcat_data = array_pop($outstack_data);
					while($popcat)
					{
						if(!$popcat_data['pages_only'] && !($arguments['suppress_main_cats'] && $popcat_data['depth'] == 1))
						{
							// if current page is the index page for $popcat, call encapsulate with page-id to highlight the page
							if ($popcat == $this->category->id && $this->category->index_page_id == $this->page->id)
							{
								$out .= $this->encapsulate($arguments,array($this->page->id => $popcat_data),'page',$popcat,$cat['depth'],__LINE__);
							}
							else
							{
								$out .= $this->encapsulate($arguments,array($popcat => $popcat_data),'cat',$popcat,$popcat_data['depth'],__LINE__);
							}
						}
						if(array_search($popcat,$cat_tree2) !== false)
						{
							$pages = $this->objbo->getPageLinks($popcat,$arguments['showhidden'],true);
							$out .= $this->encapsulate($arguments,$pages,'page',$popcat,$popcat_data['depth'] +1,__LINE__);
						}
						$subcats = array_reverse($this->objbo->getCatLinks($popcat,false,true),true);
						foreach($subcats as $subcat_id => $subcat)
						{
							array_push($outstack,$subcat_id); array_push($outstack_data,$subcat);
						}
						$popcat = array_pop($outstack); $popcat_data = array_pop($outstack_data);
					}
					continue;
				}

				if($arguments['path_only'])
				{
					if($cat_id != $this->page->cat_id) continue;
					unset($cat_tree_data[0]);
					$suppress_hide_pages=!$arguments['suppress_hide_pages']?true:false;
					$pages = $this->objbo->getPageLinks($cat_id,$suppress_hide_pages,true);
					if($this->page->id) $cat_tree_data[] = $pages[$this->page->id];
					$out .= $this->encapsulate($arguments,$cat_tree_data,'cat',$cat_id,1,__LINE__);
					break;
				}

 				if($arguments['current_section_only'] && array_search($this->page->cat_id,$cat_tree) === false) continue;
				if((int)$arguments['category_id'] > 0 && (int)$arguments['category_id'] != $cat_id) continue;
				if(! empty($arguments['main_cats_to_include'])) {
					$main_cats_to_include = explode(',',$arguments['main_cats_to_include']);
					$test = array_intersect($main_cats_to_include,$cat_tree);
					if (empty($test)) continue;
				}

//	  			_debug_array($cat_tree);
				if($cat['depth'] <= $arguments['max_cat_depth'])
				{
					if(!($arguments['suppress_current_cat'] && $this->page->cat_id == $cat_id) &&
						!($arguments['suppress_main_cats'] && $cat['depth'] == 1))
					{
						if($arguments['suppress_cat_link'])
						{
							$cat['link'] = $cat['name'];
						}
						//_debug_array(array('id'=>$cat_id,'data'=>$cat));
						$out .= $this->encapsulate($arguments,array($cat_id => $cat),'cat',$cat_id,$cat['depth'],__LINE__);
					}
					// only show pages (of a category), if the category itself is shown
					if($cat['depth'] <= $arguments['max_pages_depth'] && $cat['depth'] != 0)
					{
						$pages = $this->objbo->getPageLinks($cat_id,$arguments['showhidden'],true);
						if($arguments['suppress_current_page']) unset($pages[$this->page->id]);
						if ($pages) $out .= $this->encapsulate($arguments,$pages,'page',$cat_id,$cat['depth'] +1,__LINE__);
					}
				}
			}
			if (!$arguments['no_full_index'])
			{
				$out .= "    <div class=\"nav-full-index\">\n";
				$out .= "      <a href=\"".sitemgr_link2('/index.php','index=1')."\">". lang('View full index') . "</a>\n";
				$out .= "    </div>\n";
			}

			$out .= "  </div>\n<!-- navigation context ends here -->\n</div>\n";

			/* uncomment, if you want to debug/check opening and closing div-tags
			$div_opened = count(explode('<div',$out))-1;
			$div_closed = count(explode('</div>',$out))-1;
			if ($div_opened != $div_closed)
			{
				error_log(__METHOD__."(".array2string($arguments).") unbalanced div tags: $div_opened opened != $div_closed closed");
				return "<div style='position: absolute; background-color: white; color: black; z-index: 9999;'><p style='color: red; font-weight: bold'>unbalanced div tags: $div_opened opened != $div_closed closed<br />arguments = ".array2string($arguments)."</p>\n<pre style='border: 3px dashed red;'>".htmlspecialchars($out)."</pre></div>\n";
			}
			*/
			return $out;
		}

		/**
		 * encapsulates navigation elements
		 *
		 * @param $arguments of module.
		 * @param $data
		 * @param $type string 'cat' or 'page'
		 * @param $cat_id of cat itselve or of cat page belongs to.
		 * @param $depth=1 logical deps of cat or page.
		 * @param $line='other' line number of call
		 *
		 */
		function encapsulate($arguments,$data,$type,$cat_id,$depth=1,$line='other')
		{
			//error_log(__METHOD__."(...,".array2string($data).",$type,$cat_id,$depth,called from line $line)");
			$out = '';
			// some navigation types need opening and closing tags per category, others not
			if(!$arguments['no-nav-cat-block-divs'] && empty($arguments['main_cats_to_include']))
			{
				// do we have to start or finish a block?
				if ($type == 'cat')
				{
					// finish old block
					if ($this->lastcatdepth >= $depth)
					{
						while($this->lastcatdepth != $depth - 1 && $this->lastcatdepth != 0)
						{
							$out .= "  </div>\n";
							//$out .= "  <!-- NAV CAT BLOCK OF DEPTH ". $this->lastcatdepth. " ENDS HERE-->\n";
							$this->lastcatdepth--;
						}
					}
					$this->lastcatdepth = $depth;

					// marker to end last block
					if ($depth == 0) return $out;

					//$out .= "  <!-- NAV CAT BLOCK OF DEPTH ". $depth. " STARTS HERE-->\n";
					$out .= "  <div class=\"nav-cat-block blockdepth-".$depth. ($this->page->cat_id == $cat_id ? ' active' : ' inactive'). "\">\n";
				}
			}
			$out .= "    <div class=\"nav-".$type."-entry depth-".$depth."\">\n";
			if ($depth == 1 && $arguments['nav_type'] == 3)	// menu class for Joomla 1.5 (only for index_block)
			{
				$out .= "      <ul class=\"menu\">\n";
			}
			else
			{
				$out .= "      <ul>\n";
			}
			if (is_array($data))
			foreach($data as $id => $entry)
			{
				if (($arguments['page_link_text'] == 1 )&& $type == 'page')
				{
					preg_match('/href="([^"]+)"/i',$entry['link'],$matches);
					$entry['link'] = '<a href="'. $matches[1]. '">'. $entry['subtitle']. '</a>';
				}

				if($arguments['highlight_current_page'] && $id == $this->page->id && $type == 'page')
				{
					$entry['link'] = "<div class=\"nav-highlight_current_page\">".$entry['link'].'</div>';
				}
				if ($id == $this->page->cat_id && $type != 'page')	// active class for current cat in Joomla 1.5
				{
					$out .= "        <li class=\"active\">\n";
				}
				else
				{
					$out .= "        <li>\n";
				}
				$out .= "          ".$entry['link']."\n";

				if($arguments['show_edit_icons'] && $cat_id >0)
				{
					//echo function_backtrace().'<br>';
					$out .= "<span class=\"nav-edit-icons\">";
					$out .= $type == 'cat' ?
						$this->objbo->getEditIconsCat($id) :
						$this->objbo->getEditIconsPage($id,$cat_id);
					$out .= "</span>\n";
				}

				if(($arguments['show_cat_description'] && $type == 'cat') || ($arguments['show_page_description'] && $type == 'page'))
				{
					$out .= "<span class=\"nav-".$type."-description\">";
					$out .= $type =='cat' ? $entry['description'] : $entry['subtitle'];
					$out .= "</span>\n";
				}

				$out .= "        </li>\n";
			}
			$out .= "      </ul>\n";
			$out .= "    </div>\n";
			return $out;
		}


		function type_navigation(&$arguments,$properties)
		{
			global $objbo,$page;
			$index_pages = $objbo->getIndex(False,False,True);

			if (!count($index_pages))
			{
				return lang('You do not have access to any content on this site.');
			}
			$index_pages[] = array(	// this is used to correctly finish the last block
				'cat_id'	=> 0,
				'catdepth'	=> 1,
			);

			$this->template =& CreateObject('phpgwapi.Template',$this->find_template_dir());
			$this->template->set_file('cat_block','navigation.tpl');
			$this->template->set_block('cat_block','block_start');
			$this->template->set_block('cat_block','level1');
			$this->template->set_block('cat_block','level2');
			$this->template->set_block('cat_block','block_end');

			$last_cat_id = 0;
			foreach($index_pages as $ipage)
			{
				preg_match('/href="([^"]+)"/i',$ipage['catlink'],$matches);
				$this->template->set_var(array(
					'item_link' => $matches[1],
					'item_name' => $ipage['catname'],
					'item_desc' => $ipage['catdescrip'],
				));
				if ($ipage['cat_id'] != $last_cat_id)	// new category
				{
					switch ($ipage['catdepth'])
					{
						case 1:	// start of a new level-1 block
							if ($last_cat_id)	// if there was a previous block, finish that one first
							{
								$content .= $this->template->parse('out','block_end');
							}
							// start the new block
							if ($ipage['cat_id'])
							{
								$content .= $this->template->parse('out','block_start');
							}
							break;
						case 2:
							$content .= $this->template->parse('out','level1');
					}
				}
				$last_cat_id = $ipage['cat_id'];

				// show the pages of the active cat or first-level pages
				if ($ipage['page_id'] && ($ipage['cat_id'] == $page->cat_id || $ipage['catdepth'] == 1))
				{
					preg_match('/href="([^"]+)"/i',$ipage['pagelink'],$matches);
					$this->template->set_var(array(
						'item_link'		=> $matches[1],
						'item_name'		=> $ipage['pagesubtitle'],
						'item_desc'		=> $ipage['pagetitle'],
					));
					$content .= $this->template->parse('out',$ipage['catdepth'] == 1 ? 'level1' : 'level2');
				}
			}
			return $content;
		}

		/**
		 * XML sitemap
		 *
		 * Currently we only output pages, not categories.
		 *
		 * @link http://www.sitemaps.org/
		 */
		function type_xml_sitemap(&$arguments,$properties)
		{
			global $objbo,$page;
			$index_pages = $objbo->getIndex(False,False,True);

			if (!count($index_pages))
			{
				return lang('You do not have access to any content on this site.');
			}
			if (!extension_loaded('xmlwriter') && (!function_exists('dl') || !dl(PHP_SHLIB_PREFIX.'xmlwriter.'.PHP_SHLIB_SUFFIX)) ||
				!function_exists('xmlwriter_open_uri'))
			{
				return 'XML sitemap requires xmlwriter PHP extension!';
			}
			// show in edit mode only a link to download the sitemap, so the page can still be edited
			if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' && !isset($_GET['xml_sitemap_test']))
			{
				return '<a href="'.$this->link(array(),array('xml_sitemap_test' => 1)).'" target="_blank">XML Sitemap</a>';
			}
			ob_end_clean();
			header('Content-Type: text/xml');
			$xml = xmlwriter_open_uri('php://output');
			xmlwriter_start_document($xml,'1.0','utf-8');
			xmlwriter_set_indent($xml,4);
			xmlwriter_start_element_ns($xml,null,'urlset','http://www.sitemaps.org/schemas/sitemap/0.9');

			$last_cat_id = 0;
			foreach($index_pages as $ipage)
			{
				xmlwriter_start_element($xml,'url');

				// pages
				$link = $ipage['page_url'];
				if ($link[0] == '/') $link = ($_SERVER['https'] ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$link;
				if (isset($_GET['lang'])) $link .= '&lang='.$_GET['lang'];
				//xmlwriter_write_comment($xml,$ipage['pagetitle']);
				xmlwriter_write_element($xml,'loc',$link);

				xmlwriter_end_element($xml);	// url
			}
			xmlwriter_end_element($xml);	// urlset
			xmlwriter_end_document($xml);
			@exit;	// otherwise we get a warning about some shutdown problem, messing up the xml
			$GLOBALS['egw']->common->egw_exit();
		}

		function type_sitetree(&$arguments,$properties)
		{
			$title = '';
			if ($arguments['menutree'])
			{
				$this->expandedcats = array_keys($arguments['menutree']);
			}
			else
			{
				$this->expandedcats = Array();
			}
			$topcats = $GLOBALS['objbo']->getCatLinks(0,False);

			$content = "<script type='text/javascript'>
				// the whole thing only works in a DOM capable browser or IE 4*/

				function add(catid)
				{
					document.cookie = 'block[" . $this->block->id . "][menutree][' + catid + ']=';
				}

				function remove(catid)
				{
					var now = new Date();
					document.cookie = 'block[" . $this->block->id . "][menutree][' + catid + ']=; expires=' + now.toGMTString();
				}

				function toggle(image, catid)
				{
					if (document.getElementById)
					{ //DOM capable
						styleObj = document.getElementById(catid);
					}
					else //we're helpless
					{
						return;
					}

					if (styleObj.style.display == 'none')
					{
						add(catid);
						image.src = 'images/tree_collapse.gif';
						styleObj.style.display = 'block';
					}
					else
					{
						remove(catid);
						image.src = 'images/tree_expand.gif';
						styleObj.style.display = 'none';
					}
				}
				</script>";

			if (count($topcats)==0)
			{
				$content=lang('You do not have access to any content on this site.');
			}
			else
			{
				$content .= "\n" .
					'<table border="0" cellspacing="0" cellpadding="0" width="100%">' .
					$this->showcat($topcats) .
					'</table>' .
					"\n";
				//$content .= '<br><a href="'.sitemgr_link('toc=1').'"><font size="1">(' . lang('Table of contents') . ')</font></a>';
				$content .= '<br><a href="'.sitemgr_link2('/index.php','index=1').'"><font size="1">(' . lang('Table of contents') . ')</font></a>';
			}
			return $content;
		}

		/**
		 * provides tabs like on egroupware.org
		 *
		 * @param array $_arguments
		 * @param array $_properties
		 */
		function type_tabs($_arguments,$_properties)
		{
			$out = "    <ul>\n";
			$tab_names = explode(',',$_arguments['tab_names']);
			$tab_links = explode(',',$_arguments['tab_links']);
			foreach (explode(',',$_arguments['tab_active']) as $num => $active)
			{

				$current = false;
				foreach (explode(':',$active) as $item)
				{
					if (!is_numeric($item) && $GLOBALS['page']->name == $item) $current = true;
					elseif($GLOBALS['page']->cat_id == $item) $current = true;

					if ($current) break;
				}
				$out .= '      <li'. ($current ? ' id="current"' : ''). '>';
				$out .= '<a href="'. $tab_links[$num]. '">'. $tab_names[$num]. '</a></li>'."\n";
			}
			$out .= "    </ul>\n";
			return $out;
		}

		function showcat($cats)
		{
			foreach($cats as $cat_id => $cat)
			{
				$status = in_array($cat_id,$this->expandedcats);
				$childrenandself = array_keys($GLOBALS['objbo']->getCatLinks($cat_id));
				$childrenandself[] = $cat_id;
				$catcolour = in_array($GLOBALS['page']->cat_id,$childrenandself) ? "red" : "black";
				$tree .= "\n" .
					'<tr><td width="10%">' .
					'<img src="images/tree_' .
					($status ? "collapse" : "expand") .
					'.gif" onclick="toggle(this, \'' .
					$cat_id .
					'\')"></td><td><b title="' .
					$cat['description'] .
					'" style="color:' .
					$catcolour .
					'">'.
					$cat['name'] .
					'</b></td></tr>' .
					"\n";
				$subcats = $GLOBALS['objbo']->getCatLinks($cat_id,False);
				$pages = $GLOBALS['objbo']->getPageLinks($cat_id);
				if ($subcats || $pages)
				{
					$tree .= '<tr><td></td><td><table style="display:' .
						($status ? "block" : "none") .
						'" border="0" cellspacing="0" cellpadding="0" width="100%" id="'.
						$cat_id .
						'">';
					if (is_array($pages))
					foreach($pages as $page_id => $page)
					{
						//we abuse the subtitle in a nonstandard way: we want it to serve as a *short title* that is displayed
						//in the tree menu, so that we can have long titles on the page that would not be nice in the tree menu
						$title = $page['subtitle'] ? $page['subtitle'] : $page['title'];
						$tree .= '<tr><td colspan="2">' .
							(($page_id == $GLOBALS['page']->id) ?
								('<span style="color:red">' . $title . '</span>') :
								('<a href="' . sitemgr_link('page_name='. $page['name']) . '">' . $title . '</a>')
							) .
							'</td></tr>';
					}
					if ($subcats)
					{
						$tree .= $this->showcat($subcats);
					}

					$tree .= '</table></td></tr>';
				}
			}
			return $tree;
		}
	}
?>
