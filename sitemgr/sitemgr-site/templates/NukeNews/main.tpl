<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- $Id: main.tpl 19104 2005-09-08 09:46:36Z nelius_weiss $ -->
<html>
	<head>
		<title>{sitename}: {title}</title>
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset={charset}">
		<META HTTP-EQUIV="EXPIRES" CONTENT="0">
		<META NAME="RESOURCE-TYPE" CONTENT="DOCUMENT">
		<META NAME="DISTRIBUTION" CONTENT="GLOBAL">
		<META NAME="AUTHOR" CONTENT="{sitename}">
		<META NAME="COPYRIGHT" CONTENT="Copyright (c) {year} by {sitename}">
		<META NAME="DESCRIPTION" CONTENT="{slogan}">
		<META NAME="ROBOTS" CONTENT="INDEX, FOLLOW">
		<META NAME="REVISIT-AFTER" CONTENT="1 DAYS">
		<META NAME="RATING" CONTENT="GENERAL">
		<META NAME="GENERATOR" CONTENT="eGroupWare Web Site Manager">
		{editmode_styles}
		<LINK REL="StyleSheet" HREF="templates/NukeNews/style/style.css" TYPE="text/css">
		{java_script}
		{default_css}
	</head>
<body bgcolor="#505050" text="#000000" link="#363636" vlink="#363636" alink="#d5ae83">
<br>
<table cellpadding="0" cellspacing="0" width="100%" border="0" align="center" bgcolor="#ffffff">
<tr>
<td bgcolor="#cfcfbb" valign="top"><img height="16" alt="" hspace="0" src="templates/NukeNews/images/corner-top-left.gif" width="17" align="left">
<br><a href="index.php" valign="center"><img src="templates/NukeNews/images/logo.gif" align="left" alt="" border="0" hspace="10"></a></td>
<td bgcolor="#999999"><IMG src="templates/NukeNews/images/pixel.gif" width="1" height="1" alt="" border="0" hspace="0"></td>
<td bgcolor="#cfcfbb" align="center">
<center>
	{contentarea:header}
</center></td>
<td bgcolor="#cfcfbb" align="center">
<center>
	&nbsp;
</center></td>
<td bgcolor="#cfcfbb" valign="top"><img height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-top-right.gif" width="17" align="right"></td>
</tr></table>
<table cellpadding="0" cellspacing="0" width="100%" border="0" align="center" bgcolor="#fefefe">
<tr>
<td bgcolor="#000000" colspan="4"><IMG src="templates/NukeNews/images/pixel.gif" width="1" height=1 alt="" border="0" hspace="0"></td>
</tr>
<tr>
<td bgcolor="#000000" colspan="4"><IMG src="templates/NukeNews/images/pixel.gif" width="1" height="1" alt="" border="0" hspace="0"></td>
</tr>
</table>
<!-- FIN DEL TITULO -->
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" align="center"><tr valign="top">
<td bgcolor="#ffffff"><img src="templates/NukeNews/images/pixel.gif" width="1" height="20" border="0" alt=""></td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" align="center"><tr valign="top">
<td bgcolor="#ffffff"><img src="templates/NukeNews/images/pixel.gif" width="10" height="1" border="0" alt=""></td>
<td bgcolor="#ffffff" width="150" valign="top">
{contentarea:left}
</td><td><img src="templates/NukeNews/images/pixel.gif" width="15" height="1" border="0" alt=""></td><td valign="top" width="100%">
<h1>{title} {editicons}</h1>
<h3>{subtitle}</h3>
{contentarea:center}
</td><td><img src="templates/NukeNews/images/pixel.gif" width="15" height="1" border="0" alt=""></td><td valign="top" width="150">
{contentarea:right}
</td><td bgcolor="#ffffff"><img src="templates/NukeNews/images/pixel.gif" width=10 height=1 border=0 alt="">
</td></tr></table>
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" align="center"><tr valign="top">
<td align="center" height="17">
<IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-bottom-left.gif" width="17" align="left">
<IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-bottom-right.gif" width="17" align="right">
</td></tr></table>
<br>
<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" align="center"><tr valign="top">
<td><IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-top-left.gif" width="17" align="left"></td>
<td width="100%">&nbsp;</td>
<td><IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-top-right.gif" width="17" align="right"></td>
</tr><tr align="center">
<td width="100%" colspan="3">
{contentarea:footer}
</td>
</tr><tr>
<td><IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-bottom-left.gif" width="17" align="left"></td>
<td width="100%"></td>
<td><IMG height="17" alt="" hspace="0" src="templates/NukeNews/images/corner-bottom-right.gif" width="17" align="right"></td>
</tr></table>
{need_footer}
</body>
</html>