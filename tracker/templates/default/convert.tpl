<!-- BEGIN form -->
<br>
{messages}

<form method="POST" action="{form_action}">

<table border="0" width="80%" cellspacing="0" align="center">

	<tr>
 		<td colspan="3" align="center" height="40" class="redItalic">
 			<font color="red"><em>{confirmation}</em></font>
 		</td>
	</tr>
	
	<tr class="th">
		<td align="right">&nbsp;</td>
		<td align="left">{lang_total}</td>
		<td align="left">{lang_converted}</b></td>
	</tr>

	<tr class="row_off">
		<td align="right">{lang_open_cnt}:</td>
		<td align="left"><b>{open_cnt}</b></td>
		<td align="left"><b>{open_cnv_cnt}</b></td>
	</tr>

	<tr class="row_on">
		<td align="right">{lang_close_cnt}:</td>
		<td align="left"><b>{close_cnt}</b></td>
		<td align="left"><b>{close_cnv_cnt}</b></td>
	</tr>

	<tr class="row_off">
		<td align="left" colspan="3">&nbsp;</td>
	</tr>

	<tr class="row_on">
		<td align="right"><input type="checkbox" name="conv_open" value="1" {convopen_disa}></td>
		<td align="left" colspan="2">{lang_conv_open}</td>
	</tr>

	<tr class="row_off">
		<td align="right"><input type="checkbox" name="conv_closed" value="1" {convclose_disa}></td>
		<td align="left" colspan="2">{lang_conv_closed}</td>
	</tr>

	<tr class="row_off">
		<td colspan="3">&nbsp;</td>
	</tr>

	<tr class="row_on">
		<td colspan="3">{lang_catconv}</td>
	</tr>

	{convert_categories}

	<tr>
 		<td colspan="3" align="center" height="40">
 			{btn_or_msg}
 		</td>
	</tr>

</table>

</form>
<!-- END form -->
