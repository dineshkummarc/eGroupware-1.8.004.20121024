<?php
/**
 * news_admin - business object
 *
 * @link http://www.egroupware.org
 * @author Cornelius Weiss <egw@von-und-zu-weiss.de>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package news_admin
 * @copyright (c) 2005-7 by Cornelius Weiss <egw@von-und-zu-weiss.de> and Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_news_admin.inc.php 33254 2010-12-01 13:29:13Z ralfbecker $
 */

class module_news_admin extends Module
{
	/**
	 * Instance of the business object of news_admin
	 *
	 * @var bonews
	 */
	var $bonews;

	function module_news_admin()
	{
		$this->bonews =& CreateObject('news_admin.bonews');
		$GLOBALS['egw']->translation->add_app('news_admin');

		$this->arguments = array(
			'category' => array(
				'type' => 'select',
				'label' => lang('Choose a category'),
				'options' => array(),	// specification of options is postponed into the get_user_interface function
				'multiple' => 5,
			),
			'show' => array(
				'type' => 'select',
				'label' => lang('Which information do you want to show (CSS class)'),
				'multiple' => 9,
				'options' => array(
					'date'        => lang('Date').' (news_date)',
					'datetime'    => lang('Date and time').' (news_date)',
					'title'       => lang('Headline with link').' (news_title)',
					'title_list'  => lang('Headline with link').' (news_title)'.' (with List Stylesheets)',
					'headline'    => lang('Headline').' (news_headline)',
					'submitted'   => lang('Submitted by and date').' (news_submitted)',
					'teaser'      => lang('Teaser').' (news_teaser)',
					'teaser_more' => lang('Teaser with read more link').' (news_teaser_more)',
					'content'     => lang('Content').' (news_content)',
					'more'        => lang('More news link').' (news_more_news)',
				),
			),
			'limit' => array(
				'type' => 'textfield',
				'label' => lang('Number of news items to be displayed on page'),
				'params' => array('size' => 3)
			),
			'rsslink' => array(
				'type' => 'checkbox',
				'label' => lang('Do you want to publish a RSS feed for this news category'),
			),
			'linkpage' => array(
				'type' => 'textfield',
				'label' => lang('Page-name the item should be displayed (empty = current page)'),
				'params' => array('size' => 50)
			),
			'items' => array(
				'type' => 'textfield',
				'label' => lang('Comma separated item IDs which shall be shown (empty = all)'),
				'params' => array('size' => 50)
			),
		);
		$this->get = array('item','start');
		$this->session = array('item','start');
		$this->properties = array();
		$this->title = lang('%1 module',lang('news_admin'));
		$this->description = lang('This module publishes news from the news_admin application on your website. Be aware of news_admin\'s ACL restrictions.');
	}

	function get_user_interface()
	{
		//we could put this into the module's constructor, but by putting it here, we make it execute only when the block is edited,
		//and not when it is generated for the web site, thus speeding the latter up slightly
		$this->arguments['category']['options'] = $this->bonews->rights2cats(EGW_ACL_READ);

		if (isset($this->block->arguments['layout']) && !isset($this->block->arguments['show']))
		{
			$this->block->arguments['show'] = $this->block->arguments['layout'] == 'header' ?
				array('date','title') : array('headline','submitted','teaser','content');
		}
		if ($this->block->arguments['category'] && !is_array($this->block->arguments['category']))
		{
			$this->block->arguments['category'] = array($this->block->arguments['category']);
		}
		return parent::get_user_interface();
	}

	/**
	 * Returns a news_admin block
	 *
	 * @param array &$arguments
	 * @param array $properties
	 * @return string
	 */
	function get_content(&$arguments,$properties)
	{
		if (!$arguments['show'])
		{
			$arguments['show'] = $arguments['layout'] == 'header' ?
				array('date','title') : array('headline','submitted','teaser','content');
		}
		$limit = (int)$arguments['limit'] ? $arguments['limit'] : 5;
		$show = $arguments['show'];

		// If not set, assume default values.
		if (count($show) == 0)
		{
			$show = array('headline','teaser','content');
		}
		// for the center area you can use a direct link to call a certain item
		if ($show[0] == 'title_list') $listview = true;  //for Joomla Style news
		if (!($item = (int)$arguments['item']) && $this->block->area == 'center')
		{
			$item = (int)$_GET['item'];
			// if no page-target is given (display on same page), we have to show everything
			// otherwise a list showing just headlines and optional teaser is not able to show a full news
			if (!$arguments['linkpage'])
			{
				$show = array('headline','submitted','teaser','content');
			}
		}

		if (!$listview)
		{
			$html = '<div class="news_items news_items_'.implode('_',$arguments['show']).'">'."\n";
		}
		else
		{
			if ($listview) $html = '<ul class="latestnews">'."\n";
		}


		if ($_GET['module'] == 'news_admin' && isset($_GET['cat_id']))
		{
			$itemsyntax = (preg_match('/^[-a-z_0-9]+$/i',$_GET['linkpage']) ? '?page_name='.$_GET['linkpage'].'&amp;' : '?').'item=';
			ob_end_clean();		// for mos templates, stop the output buffering
			include(EGW_SERVER_ROOT.'/news_admin/website/export.php');
			// No more stuff in the generated xml
			$GLOBALS['egw']->common->egw_exit();
		}
		elseif ((int)$item)
		{
			if (($news = $this->bonews->read($item)))
			{
				$html .= $this->render($news,$show);
			}
			else
			{
				$html = "\t<div>".lang('No matching news item')." ($item)!</div>\n";
			}
			$html .= "\t<div class=\"news_more_news\"><a href=\"" . $this->link(array('item'=>0)) . '">' . lang('More news') . "</a></div>\n";
		}
		else
		{
			$filter = array();
			$result = array();

			if ($GLOBALS['sitemgr_info']['userlang'])
			{
				$filter['news_lang'] = $GLOBALS['sitemgr_info']['userlang'];
			}

			if ($arguments['category'])
			{
				$filter = array('cat_id' => $arguments['category']);
			}

			if ($arguments['items'])
			{
				$count = 0;
				$items_array = array();
				// Normalize the sting user input for later uniqueness
				foreach (explode(',', $arguments['items']) as $news_id)
				{
					$items_array[] = (int) trim($news_id);
				}
				// Let the order of the entries matter.
				// Loop here instead of putting the news IDs into the DB search.
				foreach (array_unique($items_array) as $news_id)
				{
					$criteria = array('news_id' => (int) trim($news_id));
					$result_int = $this->bonews->search($criteria,false,'news_date DESC','','',false,'OR',false,$filter);
					if (is_array($result_int))
					{
						$result = array_merge($result, $result_int);
						$count++;
					}
					if (($limit) && ($count >= $limit))
					{
						break;
					}
				}
			}
			else
			{
				$result = $this->bonews->search('',false,'news_date DESC','','',false,'AND',array((int)$arguments['start'],$limit),$filter);
			}

			// Catch the error case of the last search (not an array).
			// If none is found, $result is an empty array with a quick loop exit.
			if (is_array($result))
			{
				foreach($result as $news)
				{
					$html .= $this->render($news,$arguments['show'],$arguments['linkpage']);
				}
			}

			if (in_array('more',$arguments['show']))
			{
				if ($arguments['start'])
				{
					$link_data['start'] = $arguments['start'] - $limit;
					if ($link_data['start']<0) $link_data['start']=0;
					$more = '<a href="' . $this->link($link_data) . '">&lt;&lt;&lt; '.lang('Back').'</a> ';
				}
				if ($this->bonews->total > $arguments['start'] + $limit)
				{
					$link_data['start'] = $arguments['start'] + $limit;
					if ($link_data['start']<0) $link_data['start']=0;
					$more .= '<a href="' . $this->link($link_data) . '">' . lang('More news') . '</a>';
				}
				if ($more) $html .= "\t<div class=\"news_more_news\">$more</div>\n";
			}
			if ($arguments['rsslink'])
			{
				// Use new "holder" to prevent using old news_admin/website/export.php URL when site manager is installed
				$link = $GLOBALS['sitemgr_info']['site_url'] . 'index.php?module=news_admin&cat_id=' . implode(',',(array)$arguments['category']).
					($arguments['linkpage'] ? '&linkpage='.$arguments['linkpage'] : '');

				// Only add the domain to the url if using the egw common tree instead of a sitemgr-site custom copy
				if ($GLOBALS['sitemgr_info']['site_url'] == $GLOBALS['egw_info']['server']['webserver_url'].'/sitemgr/sitemgr-site/')
				{
					$link .= '&domain=' . $GLOBALS['egw_info']['user']['domain'];
				}
				$link = '<a href="'.$link.'" target="_blank"><img src="images/M_images/rss.png" alt="RSS" border="0"/></a>';
				$html .= "\t<div class=\"news_rss\">$link</div>\n";
			}
		}
		if (!$listview) $html .= "</div>\n";
		if ($listview) $html .= "</ul>\n";

		return $html;
	}

	/**
	 * Return one formatted news item
	 *
	 * @param array $news
	 * @param array $show values: date, title, headline, submitted, teaser, teaser_more, content
	 * @param string $page page-name the items should link to
	 * @return string
	 */
	function render($news,$show,$page='')
	{
		if (!$listview) $html = "\t".'<div class="news_item news_item_'.implode('_',$show).'">'."\n";

		foreach($show as $name)
		{
			$news_link = $this->link(false,array(
						'page_name' => $page,
						'category_id' => $news['cat_id'],
						'item' => $news['news_id'],
					));
			switch($name)
			{
				case 'content':
					// without the table an <img align="left"> displays the image outside the div, strange ...
					if (preg_match('/<img[^>]* align="(left|right)"/i',$news['news_content']))
					{
						$value = '<table><tr><td>'.$news['news_content'].'</td></tr></table>';
						break;
					}
					// fall-through
				case 'headline':
				case 'teaser':
					$value = $news['news_'.$name];
					break;

				case 'title':
				case 'teaser_more':
					$link = $news['link'] ? $news['link'] : $news_link;
					$link=$news_link;
					$value = $name == 'title' ? '' : $news['news_teaser'].' ';
					$value .= '<a href="'.$link.'" title="'.htmlspecialchars(lang('read more')).
						($news['link'] ? '" target="_blank' : '').'">'.
						($name == 'title' ? $news['news_headline'] : lang('read more')).'</a>';
					break;
				case 'title_list':
					$link = $news['link'] ? $news['link'] : $news_link;
					$value = $name == 'title_list' ? '' : $news['news_teaser'].' ';
					$value .= '<a href="'.$link.'" title="'.htmlspecialchars(lang('read more')).
						($news['link'] ? '" target="_blank' : '').'">'.
						($name == 'title_list' ? $news['news_headline'] : lang('read more')).'</a>';
					break;

				case 'submitted':
					$value = lang('Submitted by %1 on %2',$GLOBALS['egw']->common->grab_owner_name($news['news_submittedby']),
						$GLOBALS['egw']->common->show_date($news['news_date'],'',false));
					break;

				case 'date':
				case 'datetime':
					$format = $GLOBALS['egw_info']['user']['preferences']['common']['dateformat'];
					if ($name == 'datetime')
					{
						$format .= ' '.($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] == 12 ? 'h:i a' : 'H:i');
					}
					$value = $GLOBALS['egw']->common->show_date($news['news_date'],$format,false);
					break;

				case 'more':
					$value = '';	// not displayed per item
					break;
			}
			if ($value)
			{
				if ($name != 'title_list') $html .= "\t\t<div class=\"news_$name\">$value</div>\n";
				if ($name == 'title_list') $html .= "\t\t<li class=\"latestnews\">$value</li>\n";
			}
		}
		if (!$listview) $html .= "\t</div>\n";

		return $html;
	}
}
