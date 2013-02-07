<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh" >
<head>
	<title>{sitename}: {title}</title>
	<meta http-equiv="content-type" content="text/html; charset={charset}" />
	<meta http-equiv="expires" content="0" />
	<meta name="RESOURCE-TYPE" content="DOCUMENT" />
	<meta name="DISTRIBUTION" content="GLOBAL" />
	<meta name="AUTHOR" content="{sitename}" />
	<meta name="COPYRIGHT" content="Copyright (c) {year} by {sitename}" />
	<meta name="DESCRIPTION" content="{description}" />
	<meta name="ROBOTS" content="INDEX, FOLLOW" />
	<meta name="REVISIT-AFTER" content="1 DAYS" />
	<meta name="RATING" content="GENERAL" />
	<meta name="GENERATOR" content="EGroupware SiteManager version {version}" />
	<meta name="keywords" content="{keywords}" />
	<meta name="language" content="{lang}" />
		{editmode_styles}
	<link rel="stylesheet" type="text/css" 
	      href="templates/realss/realss.css"/>
	{java_script}
</head>
<body>
<!-------   for template structure information read the stylesheets ------>
<!-------------- title bar -------------->
<table align="center" id="contentarea_top" style="width: 784px">
<tr><td>
	<a href="index.php"><img src="templates/realss/images/logo.png" 
	                         alt="Welcome to {sitename}" ></a>
</td><td>
	{contentarea:header} 
</td></tr>
</table>
<!-------------- content bar -------------->
<table align="center" id="middle_contentarea_container">
<tr>
	<td valign="top" id="contentarea_left">
		{contentarea:left}
	</td>
	<td valign="top" id="contentarea_center">
		<img src="templates/realss/images/center_bt_top.png"
		     style="display:block"/>
		<div id="middle_bt">
		<!-- the block title does not necessarily make sense 
		in central content area. 
		If you want it change display:block to enable it. -->
		<h1 style="display: none;">{title} {editicons}</h1>
		<h3 style="display: none;">{subtitle}</h3>
		{contentarea:center}
		</div>
		<img src="templates/realss/images/center_bt_bottom.png"
		     style="display:block"/>
	</td>
	<td valign="top">
		{contentarea:right}
	</td>
</tr>
</table>
<!------
<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td width="15" height="15"><img src="templates/realss/images/up-left2.gif" alt="" border="0"></td>
		<td><img src="templates/realss/images/up2.gif" width="100%" height="15"></td>
		<td><img src="templates/realss/images/up-right2.gif" width="15" height="15" alt="" border="0"></td>
	</tr><tr>
		<td background="templates/realss/images/left2.gif" width="15">&nbsp;</td>
		<td bgcolor="ffffff" width="100%" align="center"><font class="tiny">{contentarea:footer}</font></td>
		<td background="templates/realss/images/right2.gif">&nbsp;</td>
	</tr><tr>
		<td width="15" height="15"><img src="templates/realss/images/down-left2.gif" alt="" border="0"></td>
		<td height="15" background="templates/realss/images/down2.gif"></td>
		<td width="15" height="15"><img src="templates/realss/images/down-right2.gif" alt="" border="0"></td>
	</tr>
</table>
-->
{need_footer}
</body>
</html>
