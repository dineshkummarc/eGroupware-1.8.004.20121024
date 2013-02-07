<?php
/**
 * news_admin - import RSS and Atom feeds
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package news_admin
 * @copyright (c) 2007-11 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.news_admin_import.inc.php 35370 2011-06-17 15:36:40Z ralfbecker $
 */

require_once(EGW_INCLUDE_ROOT.'/news_admin/inc/class.bonews.inc.php');

/**
 * Import RSS and Atom feeds via PEAR's XML_Feed_Parser class
 */
class news_admin_import
{
	/**
	 * Reference to the news_admins's bo
	 *
	 * @var bonews
	 */
	var $bonews;

	/**
	 * Constructor
	 *
	 * @return news_admin_import
	 */
	function news_admin_import($bonews=null)
	{
		if (is_null($bonews))
		{
			$this->bonews = new bonews();
		}
		else
		{
			$this->bonews =& $bonews;
		}
	}

	/**
	 * Read the feed of the given URL
	 *
	 * @param string $url
	 * @return XML_Feed_Parser|boolean false on error
	 */
	function read($url)
	{
		$parts = parse_url($url);
		if (!in_array($parts['scheme'],array('http','https','ftp'))) return false;	// security!

		if (!($feed_xml = file_get_contents($url,false)) || !@include_once('XML/Feed/Parser.php'))
		{
			return false;
		}

		// if the xml-file specifes an encoding, convert it to our own encoding
		if (preg_match('/\<\?xml.*encoding="([^"]+)"/i',$feed_xml,$matches) && $matches[1])
		{
			$feed_xml = preg_replace('/(\<\?xml.*encoding=")([^"]+)"/i','$1'.$GLOBALS['egw']->translation->charset().'"',$feed_xml);
			$feed_xml = $GLOBALS['egw']->translation->convert($feed_xml,$matches[1]);
		}
		// stop "unsupported encoding" warnings
		error_reporting(($level = error_reporting()) & !E_WARNING);
		try {
		    $parser = new XML_Feed_Parser($feed_xml);
		} catch (XML_Feed_Parser_Exception $e) {
		    $parser = false;
		}
		error_reporting($level);

		return $parser;
	}

	/**
	 * Import the feed of one category
	 *
	 * @param int $cat_id
	 * @param string $url
	 * @return array/boolean array(total imported,newly imported) or false on error
	 */
	function import($cat_id,$url)
	{
		$parser = $this->read($url);

		if (!is_object($parser)) return false;

		$imported = $newly = 0;
		foreach ($parser as $entry)
		{
			$content_is_html = $entry->content && strip_tags($entry->content) != $entry->content;

			/* Update comma to the %ASCII encoding in the link to cope with the etemplate display.
			 * This cannot be done inside the eTemplate as this does not know the content type
			 * (URL / HTML / Plain Text) when converting variables to values in etemplate:expand_name()
			 */
			$entry->link = str_replace(',', '%2C', $entry->link);

			$check = array('cat_id' => $cat_id);
			if ($content_is_html)
			{
				$check['news_teaser'] = $entry->link;
			}
			else
			{
				$check['news_content'] = $entry->link;
			}
			if (!$this->bonews->read($check))
			{
				$this->bonews->init();
				++$newly;
			}
			if ((($date = $entry->updated) || ($date = $entry->pubDate)) && !is_numeric($date))
			{
				$date = strtotime($date);
			}
			if (!($err = $this->bonews->save($item=array(
				'cat_id' => $cat_id,
				'news_date' => $date,
				'news_headline' => $entry->title,
				'news_content' => $content_is_html ? $entry->content : $entry->link,
				'news_is_html' => $content_is_html ? -2 : -1,		// -1 = only link
				'news_teaser' =>  $content_is_html ? $entry->link : ($entry->summary != $entry->title ? $entry->summary : NULL),
				'news_submittedby' => 0,
			),true)))
			{
				++$imported;
			}
			//var_dump($err); print "<li><a href=\"$entry->link\" target=\"_blank\">$entry->title</a></li>\n"; //_debug_array($this->bonews->data);
		}
		return array($imported,$newly);
	}

	/**
	 * Import all categories, called via the async timed service
	 *
	 */
	function async_import()
	{
		if (!$this->bonews->get_cats(array(
			'num_rows' => 999,
			'start' => 0,
		),$cats,$nul,true)) return;

		foreach($cats as $cat)
		{
			if ($cat['import_url'] && !((int)date('H') % $cat['import_frequency']))
			{
				$this->import($cat['cat_id'],$cat['import_url']);
			}
		}
	}
}
