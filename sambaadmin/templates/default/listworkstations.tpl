<!-- BEGIN main -->
<center>
<table width="100%" border="0" cellspacing="1" cellpading="1">
<tr>
	<td colspan="3" align="right">
		<form method="POST" action="{search_form_action}">
		{lang_search} <input type="text" id="search_string" name="search_string" value="{search_string}" onFocus="this.select();">
		</form>
	</td>
<tr>
<tr>
	<td width="33%">
		&nbsp;
	</td>
	<td width="33%" align="center">
<b>{lang_workstation_list}</b>
	</td>
	<td width="33%" align="right">
		<a href="{add_link}">{lang_add_workstation}</a>
	</td>
</tr>
</table>
<br>
{next_match_table}
<table width="100%" border="0" cellspacing="1" cellpading="1">
<tr>
<td align="right">
	<input type="submit" name="delete" value="{lang_delete}" onClick="return confirm('{lang_do_you_really_want_to_delete}')">
</td>
</tr>
</table>
</form>
<br>
</center>

<!-- END main -->

