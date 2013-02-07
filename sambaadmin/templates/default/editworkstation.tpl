<!-- BEGIN main -->
 <form method="POST" action="{form_action}">
  <center>
	<table border="0" width="100%">
		<tr>
			<td valign="top">
					{rows}
			</td>
			<td>
				<table border=0 width=100%>
					<tr bgcolor="{th_bg}">
						<td colspan="4">
							<b>{lang_workstation_config}</b>
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_workstation_name}</td>
						<td colspan="2">
							<input name="workstationname" value="{workstationname}" size="35">
						</td>
					</tr>
					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_description}</td>
						<td colspan="2">
							<input name="description" value="{description}" size="35">
						</td>
					</tr>
<!--					<tr bgcolor="{tr_color1}">
						<td width="200">{lang_account_active}</td>
						<td colspan="2">
							<input type="checkbox" name="accountactive" {checked_accountactive}>
						</td>
					</tr>
-->
				</table>
				<table border=0 width=100%>
					<tr bgcolor="{tr_color1}">
						<td align="left">
							<a href="{back_link}">{lang_back}</a>
						</td>
						<td align="right">
							<input type="submit" name="save" value="{lang_save}">
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
  </center>
  <input type="hidden" name="workstationID" value="{workstationid}">
 </form>
<!-- END main -->

<!-- BEGIN link_row -->
					<tr bgcolor="{tr_color}">
						<td colspan="2">&nbsp;&nbsp;<a href="{row_link}">{row_text}</a></td>
					</tr>
<!-- END link_row -->
