<script>{focus_reload_close}</script>
<div id="divMain">
<form method="POST" action="{action_url}">
<input type="hidden" name="inputcatid" value="{cat_id}">
<input type="hidden" name="inputparentold" value="{old_parent}">
<table align="center" border ="0" width="80%" cellpadding="5" cellspacing="0">
	<tr>
		<td align="center" colspan="2"><u><b>{add_edit}</b></u></td>
	</tr>
	<tr>
		<td align="center" colspan="2"><font size="2" color="#FF0000"><b>&nbsp;{message}</b></font></td>
	</tr>
	<tr>
		<td colspan="2"><b><u>{lang_basic}:</u></b></td>
	</tr>
	<tr>
		<td>{lang_catname}:<font size='2' color='#ff0000'><b>*</b></font></td>
		<td><input type="text" name="inputcatname" value="{catname}"></td>
	</tr>
	<tr>
		<td>{lang_catsort}:</td>
		<td><input type="text" name="inputsortorder" value="{sort_order}"></td>
	</tr>
	<tr>
		<td>{lang_catparent}:</td>
		<td>{parent_dropdown}</td>
	</tr>
	<tr>
		<td>{lang_catdesc}:<font size='2' color='#ff0000'><b>*<br>&nbsp;<br>*</b> {lang_required}</font></td>
		<td><textarea ROWS="3" COLS="50" name="inputcatdesc">{catdesc}</textarea></td>
	</tr>
	<tr>
		<td>{lang_indexpage}:</td>
		<td><select name="inputindexpage">{indexpageselect}</select></td>
	</tr>
	<tr>
		<td>{lang_state}:</td>
		<td><select name="inputstate">{stateselect}</select></td>
	</tr>
	<tr>
		<td colspan="2"><input name="inputgetparentpermissions" type="checkbox">{lang_getparentpermissions}</td>
	</tr>
	<tr>
		<td colspan="2"><input name="inputapplypermissionstosubs" type="checkbox">{lang_applypermissionstosubs}</td>
	</tr>
	<tr>
		<td colspan="2"><b><u>{lang_groupaccess} <font size='2' color='#ff0000'><b>*</b></font></u></b></td>
	</tr>
	<tr>
		<td colspan="2">
			<table align="center" border="0" width="80%" cellpadding="2" cellspacing="2">
				<tr>
					<td align="center" width="33%"><u>{lang_groupname}</u></td>
					<td align="center" width="33%"><u>{lang_readperm}</u></td>
					<td align="center" width="33%"><u>{lang_writeperm}</u><br>({lang_implies})</td>
				</tr>
				<!-- BEGIN GroupBlock -->
				<tr>
					<td align="center" bgcolor="dddddd" width="33%">{groupname}</td>
 					<td align="center" bgcolor="dddddd" width="33%"><input type="checkbox" {checkedgroupread} name="inputgroupaccessread[i{group_id}][read]" value="checked"></td>
					<td align="center" bgcolor="dddddd" width="33%"><input type="checkbox" {checkedgroupwrite} name="inputgroupaccesswrite[i{group_id}][write]" value="checked"></td>
				</tr>
				<!-- END GroupBlock -->
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2"><b><u>{lang_useraccess}</u></b></td>
	</tr>
		<td colspan="2">
			<table align="center" border="0" width="80%" cellpadding="2" cellspacing="2">
				<tr>
					<td align="center" width="33%"><u>{lang_username}</u></td>
					<td align="center" width="33%"><u>{lang_readperm}</u></td>
					<td align="center" width="33%"><u>{lang_writeperm}</u><br>({lang_implies})</td>
				</tr>
				<!-- BEGIN UserBlock -->
				<tr>
					<td bgcolor="dddddd" align="center">{username}</td>
					<td bgcolor="dddddd" align="center"><input type="checkbox" {checkeduserread} name="inputindividualaccessread[i{user_id}][read]" value="checked"></td>
					<td bgcolor="dddddd" align="center"><input type="checkbox" {checkeduserwrite} name="inputindividualaccesswrite[i{user_id}][write]" value="checked"></td>
				</tr>
				<!-- END UserBlock -->
			</table>
		</td>
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
</div>
