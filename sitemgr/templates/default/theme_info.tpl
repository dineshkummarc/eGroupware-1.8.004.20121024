<html>
<head>
	<title>{name}</title>
</head>
<body style="bgcolor: #ffffff; margin: 0px; padding: 0px;">
	<table class="tempalteInfo">
<!-- BEGIN info_block -->
	<tr>
		<td>{thumbnail}</td>
		<td valign="top">
			<p><b>{name}</b>: <span {version_style}>Version {version} ({creationDate})</span><br />
			{title}</p>
			<p {author_style}><b>{lang_author}</b>: {author}, <a href="{authorUrl2}" target="_blank">{authorUrl}</a></p>
			<p {copyright_style}><b>{lang_copyright}</b>: {copyright}</p>
			<p {license_style}><b>{lang_license}</b>: {license}</p>
		</td>
	</tr>
<!-- END info_block -->
	</table>
</body>
</html>
