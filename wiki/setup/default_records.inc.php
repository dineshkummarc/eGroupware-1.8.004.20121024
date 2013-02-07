<?php
	/**************************************************************************\
	* eGroupWare - Setup                                                       *
	* http://www.eGroupWare.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: default_records.inc.php 34048 2011-03-07 19:06:30Z ralfbecker $ */

	$time = time();
	$oProc->query("DELETE FROM egw_wiki_pages");
	foreach(array(
		'RecentChanges' => '[[! *]]',
		'eGroupWare' => "Welcome to '''Wiki''' - the eGroupWare Version of '''WikkiTikkiTavi'''. Wikis are a revolutionary new form of collaboration and online community.

'''eGroupWare''' is the groupware suite you are useing right now. For further information see http://www.eGroupWare.org",
		'WikkiTikkiTavi' => "= WikkiTikkiTavi =

WikkiTikkiTavi is the original version of this documentation system. Their [http://tavi.sourceforge.net documentation] is usable for the eGroupWare '''Wiki''' too.

'''Learn about Wiki formatting:'''
----
SmashWordsTogether to create a page link.  Click on the ? to edit the new page.

You can also create ((free links)) that aren't WordsSmashedTogether.  Type them like this: {{```((free links))```}}.
----
{{```''italic text''```}} becomes ''italic text''
----
{{```'''bold text'''```}} becomes '''bold text'''
----
{{```{{monospace text}}```}} becomes {{monospace text}}
----
{{```----```}} becomes a horizontal rule:
----
Create a remote link simply by typing its URL: http://www.egroupware.org

If you like, enclose it in brackets to create a numbered reference and avoid cluttering the page; {{```[http://www.php.net]```}} becomes [http://www.php.net].

Or you can have a description instead of a numbered reference; {{```[http://www.php.net/manual/en/ PHP Manual]```}} becomes [http://www.php.net/manual/en/ PHP Manual]
----
You can put a picture in a page by typing the URL to the picture (it must end in gif, jpg, or png).  For example, {{```http://www.egroupware.org/egroupware/phpgwapi/templates/default/images/logo.png```}} becomes
http://www.egroupware.org/egroupware/phpgwapi/templates/default/images/logo.png
----
There are 2 possibilities for '''code formatting''':
{{'''{{\$is_admin = \$GLOBALS['egw_info']['user']['apps']['admin'];}}'''}}
or
{{<code>}}
if (\$_POST['add'])
{
   do_something();
}
{{</code>}}
becomes

{{\$GLOBALS['egw_info']['user']['apps']['admin']}}
or
<code>
if (\$_POST['add'])
{
   do_something();
}
</code>
----
You can indent by starting a paragraph with one or more colons.

{{```:Indent me!```}}
{{```::Me too!```}}
becomes

:Indent me
::Me too!
----
You can create bullet lists by starting a paragraph with one or more asterisks.

{{```*Bullet one```}}
{{```**Sub-bullet```}}
becomes

*Bullet one
**Sub-bullet
----
You can create a description list by starting a paragraph with the following syntax

{{```*;Item 1: Something```}}
{{```*;Item 2: Something else```}}

which gives

*;Item 1: Something
*;Item 2: Something else
----
Similarly, you can create numbered lists by starting a paragraph with one or more hashes.

{{```#Numero uno```}}
{{```#Number two```}}
{{```##Sub-item```}}
becomes

#Numero uno
#Number two
##Sub-item
----
You can mix and match list types:

<code>
#Number one
#*Bullet
#Number two
</code>
#Number one
#*Bullet
#Number two
----
You can make various levels of heading by putting = signs before and after the text =
= Level 1 heading =
== Level 2 heading ==
=== Level 3 heading ===
==== Level 4 heading ====
===== Level 5 heading =====
====== Level 6 heading ======
<code>
= Level 1 heading =
== Level 2 heading ==
=== Level 3 heading ===
==== Level 4 heading ====
===== Level 5 heading =====
====== Level 6 heading ======
</code>
----
You can create tables using pairs of vertical bars:

||cell one || cell two ||
|||| big ol' line ||
|| cell four || cell five ||
|| cell six || here's a very long cell ||

<code>
||cell one || cell two ||
|||| big ol' line ||
|| cell four || cell five ||
|| cell six || here's a very long cell ||
</code>
----
You can create anchors with the Macro:
<code>
[[Anchor ANCHORNAME]]
</code>
You can jump to that Anchor with:
<code>
((PageName|Jump to Anchor #ANCHORNAME))
</code>
----
=== Curly Options ===
only supported with tables right now
<code>
||{s=background:red}  CurlyOptions ||{s=color:blue;font-variant:small-caps;font-size:large} some text||
</code>
||{s=background:red}  CurlyOptions ||{s=color:blue;font-variant:small-caps;font-size:large} some text||
<code>
||{Tb=0,s=background:red;color:blue;font-variant:small-caps;font-size:large} some blue text on red ground||
</code>
||{Tb=0,s=background:red;color:blue;font-variant:small-caps;font-size:large} some blue text on red ground||
<code>
||{Tb=0,s=color:red;font-variant:small-caps;font-size:large} some red text||
</code>
||{Tb=0,s=color:red;font-variant:small-caps;font-size:large} some red text||
----
=== HTML Formatting ===
<code>
<html>
<b> more </b> <font color=\"red\">to</font> <b>come</b>
</html>
</code>
displays as:
<html>
<b> more </b> <font color=\"red\">to</font> <b>come</b>
</html>
----
=== Macros ===
<code>
[[PageSize]]
</code>
Displays the size of each page in bytes.
[[PageSize]]
<code>
[[LinkTable]]
</code>
Displays all of the pages that each page links to
[[LinkTable]]
<code>
[[PageLinks]]
</code>
Indicates how many links to other pages each page contains. Multiple links to the same page count multiple times
[[PageLinks]]
<code>
[[PageRefs]]
</code>
Displays how many links there are to each page. Multiple links on a page increase the count. A page that links to itself also counts.
[[PageRefs]]
<code>
[[OrphanedPages]]
</code>
Displays all of the pages that no other page links to
[[OrphanedPages]]
<code>
[[WantedPages]]
</code>
Displays all pages that are linked to but do not yet exist
[[WantedPages]]
<code>
[[Transclude eGroupWare]]
</code>
Include the text of an other wikipage:
[[Transclude eGroupWare]]
",
	) as $name => $body)
	{
		$oProc->insert('egw_wiki_pages',array(
			'wiki_id'        => 0,
			'wiki_name'      => $name,
			'wiki_lang'      => 'en',
			'wiki_version'   => 1,
			'wiki_time'      => $time,
			'wiki_supercede' => $time,
			'wiki_readable'  => 0,
			'wiki_writable'  => 0,
			'wiki_username'  => 'setup',
			'wiki_hostname'  => 'localhost',
			'wiki_title'     => $name,
			'wiki_body'      => $body,
			'wiki_comment'   => 'added by setup',
		),false,__LINE__,__FILE__,'wiki');
	}


