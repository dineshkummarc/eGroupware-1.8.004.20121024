<!-- BEGIN form -->
 <form method="POST" action="{form_action}">
  <center>
	<table border="0" width="95%">
		<tr>
			<td valign="top" width="120px">
					{rows}
			</td>
			<td>
				<table border="0" width="100%">
					<tr class="th">
						<td colspan="3">
							<b>{lang_samba_config}</b>
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_displayname}</td>
						<td colspan="2">
							<input name="displayname" value="{displayname}" style="width:99%;">
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_homepath}</td>
						<td colspan="2">
							<input name="sambahomepath" value="{sambahomepath}" style="width:99%;">
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_homedrive}</td>
						<td colspan="2">
							<input name="sambahomedrive" value="{sambahomedrive}" style="width:99%;">
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_logonscript}</td>
						<td colspan="2">
							<input name="sambalogonscript" value="{sambalogonscript}" style="width:99%;">
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_profilepath}</td>
						<td colspan="2">
							<input name="sambaprofilepath" value="{sambaprofilepath}" style="width:99%;">
						</td>
					</tr>
				</table>
				<table border=0 width=100%>
					<tr bgcolor="{tr_color1}">
						<td align="right" colspan="2">
							<input type="submit" name="save" value="{lang_button}">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
  </center>
 </form>
<!-- END form -->

<!-- BEGIN link_row -->
					<tr bgcolor="{tr_color}">
						<td colspan="2">&nbsp;&nbsp;<a href="{row_link}">{row_text}</a></td>
					</tr>
<!-- END link_row -->
