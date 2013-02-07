<script>{focus_reload_close}</script>
<div id="divMain">
<!-- BEGIN form -->
<form method="POST" action="{action_url}">
<input type="hidden" name="inputpageid" value="{page_id}">
<table align="center" border="0" width="80%" cellpadding="5" cellspacing="0">
	<tr>
		<td><b>{lang_name}: <font size="2" color="#ff0000">*</font></b></td>
		<td><input size="40" type="text" name="inputname" id="name" value="{name}"></td>
	</tr>
	<tr>
		<td><font size="2" color="#ff0000"><b>*</b> {lang_required}</font></td>
		<td><i><font size="2" color="#ff0000">{lang_nameinfo}</font></i></td>
	<tr>
		<td><b>{lang_title}: <font size="2" color="#ff0000">*</font></b></td>
		<td><input size="40" type="text" name="inputtitle" value="{title}"></td>
	</tr>
	<tr>
		<td><b>{lang_subtitle}: </b></td>
		<td><input size="40" type="text" name="inputsubtitle" value="{subtitle}"></td>
	</tr>
	<tr>
		<td><b>{lang_sort}: </b></td>
		<td><input size="10" type="text" name="inputsort" value="{sort_order}"></td>
	</tr>
	<tr>
		<td><b>{lang_category}: </b></td>
		<td>{catselect}</td>
	</tr>
	<tr>
		<td><b>{lang_state}: </b></td>
		<td><select name="inputstate">{stateselect}</select></td>
	</tr>
	<tr>
		<td align="right"><input type="checkbox" {hidden} name="inputhidden" /></td>
		<td>{lang_hide}</td>
	</tr>
	<tr>
		<td align="left" style="white-space: nowrap;">
			<input type="submit" name="btnSave" value="{lang_save}" />
			<input type="submit" name="btnApply" value="{lang_apply}" />
			<input type="reset" value="{lang_cancel}" onClick="self.close();" />
		</td>
		<td align="center">{savelang}</td>
		<td align="right">
			<input type="submit" name="btnDelete" value="{lang_delete}" onClick="return confirm('{lang_confirm}');" /> &nbsp;
		</td>
	</tr>
</table>
</form>
<!-- END form -->
</div>

