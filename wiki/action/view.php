<?php
// $Id: view.php 19415 2005-10-14 14:08:53Z ralfbecker $

require('parse/main.php');
require('parse/macros.php');
require('parse/html.php');
require(TemplateDir . '/view.php');
require('lib/headers.php');

// Parse and display a page.
function action_view()
{
	global $page, $pagestore, $ParseEngine, $version;

	$pg = $pagestore->page($page);
	if($version != '')
		{ $pg->version = $version; }
	$pg->read();

	gen_headers($pg->time);

	template_view(array('page'      => $pg->as_array(),
											'title'     => $pg->title,
											'html'      => parseText($pg->text, $ParseEngine, $page),
											'editable'  => $pg->acl_check(),
											'timestamp' => $pg->time,
											'archive'   => $version != '',
											'version'   => $pg->version));
}
?>
