<?php
// $Id: conflict.php 19415 2005-10-14 14:08:53Z ralfbecker $

require(TemplateDir . '/conflict.php');

// Conflict editor.  Someone accidentally almost overwrote something someone
//   else just saved.
function action_conflict()
{
	global $pagestore, $page, $document, $ParseEngine;

	$pg = $pagestore->page($page);
	$pg->read();

	template_conflict(array('page'      => $page,
													'text'      => $pg->text,
													'html'      => parseText($pg->text,
																									 $ParseEngine, $page),
													'usertext'  => $document,
													'timestamp' => $pg->time,
													'nextver'   => $pg->version + 1));
}
?>
