<div>{lang_help_module_manager}</div>
<br/>
<table>
<!-- BEGIN Contentarea -->
	<tr>
		<td colspan="4">
			<h4 style="display: inline;">{title}</h4>
			<span style="color: red;">{error}</span>
		</td>
	</tr>
	<tr valign="bottom">
		<form method="POST" action="{action_url}">
			<td>{selectmodules}</td>
			<td>
				<input type="hidden" name="inputarea" value={contentarea} />
				<input style="vertical-align:middle" type="submit" name="btnselect" value="{lang_select_allowed_modules}" />
				&nbsp;
			</td>
		</form>
		<form action="{configureurl}" method="POST">
			<td>{configuremodules}</td>
			<td>
				<input type="hidden" name="inputarea" value={contentarea} />
				<input style="vertical-align:middle" type="submit" value="{lang_configure_module_properties}" />
			</td>
		</form>
	</tr>
	<tr><td colspan="4">&nbsp;</td></tr>
<!-- END Contentarea -->
</table>
<div align="center">{managelink}</div>
<div><a href={findmodules}>{lang_findmodules}</a></div>
