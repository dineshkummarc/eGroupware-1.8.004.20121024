<?php
// $Id: macro.php 19415 2005-10-14 14:08:53Z ralfbecker $

require('parse/macros.php');
require('parse/html.php');

// Execute a macro directly from the URL.
function action_macro()
{
	global $ViewMacroEngine, $macro, $parms;

	if(!empty($ViewMacroEngine[$macro]))
	{
		print $ViewMacroEngine[$macro]($parms);
	}
}
?>
