<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- $Id: main.tpl 38595 2012-03-24 14:35:23Z ralfbecker $ -->
<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
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
		<link rel="icon" href="templates/idots/images/favicon.ico" type="image/x-ico" />
		<link rel="shortcut icon" href="templates/idots/images/favicon.ico" />
		{editmode_styles}
		<link href="templates/idots/style/style.css" type="text/css" rel="StyleSheet" />
		<!-- This solves the Internet Explorer PNG-transparency bug, but only for IE 5.5 and 6.0 -->
		<!--[if lt IE 7.0]>
		<script src="templates/idots/js/pngfix.js" type=text/javascript>
		</script>
		<![endif]-->
		{java_script}
		{default_css}
	</head>



<body bgcolor="#ffffff" text="#000000" link="#363636" vlink="#363636" alink="#d5ae83">
<div id="divLogo">
<script type="text/javascript">
var Aussage = "{sitedesc}";
var Ergebnis = Aussage.search(/##.+/);
if (Ergebnis != -1)
  document.write("<a href=\"index.php\"><img src=\""+Aussage.slice(Ergebnis+2,Aussage.lastIndexOf("##"))+"\" border=\"0\" title=\"{sitename}\" /></a>");
if (Ergebnis == -1)
  document.write("<a href=\"index.php\"><img src=\"templates/idots/images/logo.png\" border=\"0\" title=\"{sitename}\" /></a>");
</script>
</div>
<div id="divMain">
	<div id="divAppIconBar">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td width="180" valign="top" align="left"><img src="templates/idots/images/grey-pixel.png" width="1" height="68" /></td>
				<td>
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td width="100%" height="68" align="center">
{contentarea:header}
							</td>
						</tr>
						<tr>
							<td width="100%">&nbsp;</td>
						</tr>
					</table>
				</td>
				<td width="1" valign="top" align="right"><img src="templates/idots/images/grey-pixel.png" width="1" height="68" /></td>
			</tr>
		</table>
	</div>

	<div id="divSubContainer">
		<table width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td id="tdSideboxLeft">
{contentarea:left}
				</td>
				<td id="tdAppbox">
					<div id="divAppboxHeader">{title} {editicons}</div>
					<div id="divAppbox">
						<h3>{subtitle}</h3>
{contentarea:center}
					</div>
				</td>
				<td id="tdSideboxRight">
{contentarea:right}
				</td>
			</tr>
		</table>
	</div>
</div>
<div id="divFooter">
{contentarea:footer}
</div>

{need_footer}
</body>
</html>
