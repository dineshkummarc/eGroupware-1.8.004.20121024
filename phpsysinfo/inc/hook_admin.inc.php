<?php
  /**************************************************************************\
  * eGroupWare - PHPSysInfo                                                *
  * http://www.egroupware.org                                              *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id: hook_admin.inc.php 21209 2006-04-06 13:38:25Z ralfbecker $ */

{ 
// Only Modify the $file and $title variables.....
	$title = $appname;
	$file = Array(
		'view system information' => $GLOBALS['phpgw']->link('/phpsysinfo/index.php')
	);
//Do not modify below this line
	display_section($appname,$title,$file);
}
?>
