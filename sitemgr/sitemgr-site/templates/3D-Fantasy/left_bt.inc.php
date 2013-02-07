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
<table border="0" cellspacing="0" cellpadding="0" width="180"><tr>
<td width="15" height="15"><img src="templates/3D-Fantasy/images/up-left2.gif" alt="" border="0"></td>
<td><img src="templates/3D-Fantasy/images/up2.gif" width="100%" height="15"></td>
<td><img src="templates/3D-Fantasy/images/up-right2.gif" width="15" height="15" alt="" border="0"></td></tr>
<tr>
<td background="templates/3D-Fantasy/images/left2.gif" width="15">&nbsp;</td>
<td bgcolor="ffffff" width="100%">
<b>' .$title . '</b><br><br>' .
$content .'</td>
<td background="templates/3D-Fantasy/images/right2.gif">&nbsp;</td></tr>
<tr>
<td width="15" height="15"><img src="templates/3D-Fantasy/images/down-left2.gif" alt="" border="0"></td>
<td><img src="templates/3D-Fantasy/images/down2.gif" width="100%" height="15"></td>
<td><img src="templates/3D-Fantasy/images/down-right2.gif" width="15" height="15" alt="" border="0"></td></tr></table>
<br>';
	}
}
