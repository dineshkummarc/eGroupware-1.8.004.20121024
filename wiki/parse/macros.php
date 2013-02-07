<?php
// $Id: macros.php 40389 2012-10-02 18:53:17Z nathangray $

// Prepare a category list.
function view_macro_category($args)
{
	global $pagestore, $MinEntries, $DayLimit, $page, $Entity;
	global $FlgChr;

	$text = '';
	if(strpos($args, '*') !== false)                // Category containing all pages.
	{
		$list = $pagestore->allpages();
	}
	elseif(strpos($args, '?') !== false)           // New pages.
	{
		$list = $pagestore->newpages();
	}
	elseif(strpos($args, '~') !== false)           // Zero-length (deleted) pages.
	{
		$list = $pagestore->emptypages();
	}
	else                                  // Ordinary list of pages.
	{
		$parsed = parseText($args, array('parse_wikiname', 'parse_freelink'), '');
		$pagenames = array();
		preg_replace('/' . $FlgChr . '(\\d+)' . $FlgChr . '/e', '$pagenames[]=$Entity[\\1][1]', $parsed);
		$list = $pagestore->givenpages($pagenames);
	}

	if(count($list) == 0)
	{
		return '';
	}
	usort($list, 'catSort');

	$now = time();

	//for($i = 0; $i < count($list); $i++)
	foreach($list as $i => $lpage)
	{
		$editTime = $lpage['time'];
		if($DayLimit && $i >= $MinEntries && !$_GET['full'] && ($now - $editTime) > $DayLimit * 24 * 60 * 60)
		{
			break;
		}
		$text .= html_category($lpage['time'], $lpage,$lpage['author'], $lpage['username'],$lpage['comment']);
		$text .= html_newline();
	}

	if($i < count($list)-1)
	{
		$pname = $_GET['wikipage'] ? $_GET['wikipage'] : $_GET['page'];
		$text .= html_fulllist(preg_match('/^[a-z]+$/i',$pname) ? $pname : $page, count($list));
	}
	return $text;
}

function catSort($p1, $p2)
	{ return strcmp($p2['time'], $p1['time']); }

function sizeSort($p1, $p2)
	{ return $p2['length'] - $p1['length']; }

function nameSort($p1, $p2)
{
	$titlecmp = strcmp($p1['title'], $p2['title']);
	return $titlecmp ? $titlecmp : strcmp($p1['lang'],$p2['lang']);
}

// Prepare a list of pages sorted by size.
function view_macro_pagesize()
{
	global $pagestore;

	$first = 1;
	$list = $pagestore->allpages();

	usort($list, 'sizeSort');

	$text = '';

	foreach($list as $page)
	{
		if(!$first)                         // Don't prepend newline to first one.
			{ $text = $text . "\n"; }
		else
			{ $first = 0; }

		$text = $text .
						$page[4] . ' ' . html_ref($page[1], $page[1]);
	}

	return html_code($text);
}

// Prepare a list of pages and those pages they link to.
function view_macro_linktab()
{
	global $pagestore;

	$text = '';
	foreach($pagestore->get_links() as $page => $data)
	{
		foreach($data as $lang => $links)
		{
			$text .= ($text ? "\n" : '') . html_ref(array('page' => $page,'lang' => $lang), "$page:$lang") . ' |';

			foreach($links as $link)
			{
				$text .= ' ' . html_ref($link, $link);
			}
		}
	}
	return html_code($text);
}

// Prepare a list of pages with no incoming links.
function view_macro_orphans()
{
	global $pagestore, $LkTbl;

	$text = '';
	$first = 1;

	$pages = $pagestore->allpages();
	usort($pages, 'nameSort');

	foreach($pages as $page)
	{
		if (!$pagestore->db->query("SELECT wiki_name FROM ".$pagestore->LkTbl." " .
			"WHERE wiki_id=".(int)$pagestore->wiki_id." AND wiki_link=".$pagestore->db->quote($page[1])." AND wiki_name!=".$pagestore->db->quote($page[1]),__LINE__,__FILE__)->fetchColumn(0))
		{
			if(!$first)                       // Don't prepend newline to first one.
				{ $text = $text . "\n"; }
			else
				{ $first = 0; }

			$text = $text . html_ref($page[1], $page[1]);
		}
	}

	return html_code($text);
}

// Prepare a list of page names
function view_macro_names($args)
{
	global $pagestore;

	$text = '<ol>';
	$counter = 0;
	$pages = $pagestore->find($args,'wiki_name');
	//usort($pages, 'nameSort');
	while($counter < count($pages))
	{
		$text = $text . "<li>".html_ref($pages[$counter]['name'], $pages[$counter]['name']). ' ('.$pages[$counter]['lang'].'): ' .
						html_ref(findURL($pages[$counter]['name']), $pages[$counter]['title']).
						' </li>';
		$counter++;
	}
	$text .= '</ol>';
	return html_code($text);
}

// Prepare a list of page Titles
function view_macro_title($args)
{
	global $pagestore;

	$text = '<ol>';
	$counter = 0;
	$pages = $pagestore->find($args,'wiki_title');
	//usort($pages, 'nameSort');
	while($counter < count($pages))
	{
		$text = $text . "<li>".html_ref($pages[$counter]['name'], $pages[$counter]['name']). ' ('.$pages[$counter]['lang'].'): ' .
						html_ref(findURL($pages[$counter]['name']), $pages[$counter]['title']).
						' </li>';
		$counter++;
	}
	$text .= '</ol>';
	return html_code($text);
}

// Prepare a list of pages linked to that do not exist.
function view_macro_wanted()
{
	global $pagestore, $LkTbl, $PgTbl;

	$text = '';
	$first = 1;

	foreach($pagestore->db->query("SELECT l.wiki_link, SUM(l.wiki_count) AS ct, p.wiki_title " .
		"FROM ".$pagestore->LkTbl." AS l LEFT JOIN ".$pagestore->PgTbl." AS p " .
		"ON l.wiki_link = p.wiki_name " .
		"WHERE l.wiki_id=".(int)$pagestore->wiki_id." ".
		"GROUP BY l.wiki_link, p.wiki_title " .
		"HAVING p.wiki_title IS NULL " .
		"ORDER BY ct DESC, l.wiki_link",__LINE__,__FILE__) as $result)
	{
		if(!$first)                         // Don't prepend newline to first one.
			{ $text = $text . "\n"; }
		else
			{ $first = 0; }

		$text = $text . '(' .
						html_url(findURL($result[0]), $result[1]) .
						') ' . html_ref($result[0], $result[0]);
	}

	return html_code($text);
}

// Prepare a list of pages sorted by how many links they contain.
function view_macro_outlinks()
{
	global $pagestore, $LkTbl;

	$text = '';
	$first = 1;

	foreach($pagestore->db->query("SELECT wiki_name, SUM(wiki_count) AS ct FROM ".$pagestore->LkTbl." " .
		"WHERE wiki_id=".(int)$pagestore->wiki_id." ".
		"GROUP BY wiki_name ORDER BY ct DESC, wiki_name",__LINE__,__FILE__) as $result)
	{
		if(!$first)                         // Don't prepend newline to first one.
			{ $text = $text . "\n"; }
		else
			{ $first = 0; }

		$text = $text .
						'(' . $result[1] . ') ' . html_ref($result[0], $result[0]);
	}

	return html_code($text);
}

// Prepare a list of pages sorted by how many links to them exist.
function view_macro_refs()
{
	global $pagestore, $LkTbl, $PgTbl;

	$text = '';
	$first = 1;

// It's not quite as straightforward as one would imagine to turn the
// following code into a JOIN, since we want to avoid multiplying the
// number of links to a page by the number of versions of that page that
// exist.  If anyone has some efficient suggestions, I'd be welcome to
// entertain them.  -- ScottMoonen

	foreach($pagestore->db->query("SELECT wiki_link, SUM(wiki_count) AS ct FROM ".$pagestore->LkTbl." " .
		"WHERE wiki_id=".(int)$pagestore->wiki_id." ".
		"GROUP BY wiki_link ORDER BY ct DESC, wiki_link",__LINE__,__FILE__) as $result)
	{
		if ($pagestore->db->query("SELECT MAX(wiki_version) FROM ".$pagestore->PgTbl." " .
			"WHERE wiki_id=".(int)$pagestore->wiki_id." ".
			"AND wiki_name=".$pagestore->db->quote($result[0]),__LINE__,__FILE__)->fetchColumn(0))
		{
			if(!$first)                       // Don't prepend newline to first one.
				{ $text = $text . "\n"; }
			else
				{ $first = 0; }

			$text = $text . '(' .
							html_url(findURL($result[0]), $result[1]) . ') ' .
							html_ref($result[0], $result[0]);
		}
	}

	return html_code($text);
}

// This macro inserts an HTML anchor into the text.
function view_macro_anchor($args)
{
	preg_match('/^([A-Za-z][-A-Za-z0-9_:.]*)$/', $args, $result);

	if($result[1] != '')
		{ return html_anchor($result[1]); }
	else
		{ return ''; }
}

// This macro transcludes another page into a wiki page.
function view_macro_transclude($args)
{
  global $pagestore, $ParseEngine, $ParseObject, $HeadingOffset;
  static $visited_array = array();
  static $visited_count = 0;

  $previousHeadingOffset = $HeadingOffset;  // Backup previous version

  // Check for CurlyOptions, and split them
  preg_match("/^(?:\s*{([^]]*)})?\s*(.*)$/", $args, $arg);
  $options = $arg[1];
  $page = $arg[2];

  if(!validate_page($page))
    { return '[[Transclude ' . $args . ']]'; }

  $visited_array[$visited_count++] = $ParseObject;
  for($i = 0; $i < $visited_count; $i++)
  {
    if($visited_array[$i] == $page)
    {
      $visited_count--;
      return '[[Transclude ' . $args . ']]';
    }
  }

  $pg = $pagestore->page($page);
  $pg->read();
  if(!$pg->exists)
  {
    $visited_count--;
    return '[[Transclude ' . $args . ']]';
  }

  // Check for CurlyOptions affecting transclusion
  // Parse options
  foreach (split_curly_options($options) as $name=>$value) {
    $name=strtolower($name);
    if ($name[0]=='o') { // Offset - Adds to header levels in transcluded docs
      $HeadingOffset = $previousHeadingOffset + (($value=='') ? 1 : $value);
    }
  }

  // Rich text pages have HTML wrapper, which is used to tell which editor to use,
  // but breaks page when transcluded.
  $result = parseText(preg_replace('@</?html>@i','',$pg->text), $ParseEngine, $page);
  $visited_count--;
  $HeadingOffset = $previousHeadingOffset; // Restore offset
  return $result;
}

?>
