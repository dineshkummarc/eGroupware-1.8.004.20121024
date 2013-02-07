<?php
// $Id: find.php 14872 2004-04-12 13:02:09Z ralfbecker $

require('parse/html.php');
require(TemplateDir . '/find.php');

// Find a string in the database.
function action_find()
{
	global $pagestore;

	$find = $_POST['find'] ? $_POST['find'] : $_GET['find'];

	$list = $pagestore->find($find);

	$text = '';
	foreach($list as $page)
	{
		$text .= html_ref($page, $page) . html_newline();
	}
	template_find(array(
		'find'  => $find,
		'pages' => $text)
	);
}
?>
