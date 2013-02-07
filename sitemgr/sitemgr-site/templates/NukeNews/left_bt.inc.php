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

	/* $Id: left_bt.inc.php 13729 2004-02-10 14:56:34Z ralfbecker $ */

class left_bt
{
	function apply_transform($title,$content)
	{
		return '
<table border="0" cellpadding="1" cellspacing="0" bgcolor="#000000" width="150"><tr><td>
<table border="0" cellpadding="3" cellspacing="0" bgcolor="#dedebb" width="100%"><tr><td align="left">
<font class="content" color="#363636"><b>' . $title . '</b></font>
</td></tr></table></td></tr></table>
<table border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" width="150">
<tr valign="top"><td bgcolor="#ffffff">' .
$content . '
</td></tr></table>
<br>';
	}
}
