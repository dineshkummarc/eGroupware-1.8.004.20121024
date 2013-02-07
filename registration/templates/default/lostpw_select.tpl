<!-- BEGIN form -->
<form action="{form_action}" method="POST">
 <table border="0" style="width:500px;" align="center">
 <tr>
	<td>&nbsp;</td>
	<td align="center" colspan="2"><font size="+2"><b>{lang_lost_password}</b></font></td>
	<td>&nbsp;</td>
 <tr>
 <tr>
	<td>&nbsp;</td>
		<td colspan="2">
		<div style="text-align:center;color:red" class="regerror">{errors}</div>
		</td>
	<td>&nbsp;</td>
 </tr>
 <tr>
	<td>&nbsp;</td>
 	<td align="justify" colspan="2">{lang_explain}</td>
	<td>&nbsp;</td>
 </tr>
 <tr><td>&nbsp;</td></tr>
 <tr>
   <td>&nbsp;</td>
   <td align="right">{lang_username}</td>
   <td><input name="r_reg[loginid]" value="{value_username}"></td>
   <td>&nbsp;</td>
  </tr>
  <tr>
   <td>&nbsp</td>
   <td>&nbsp</td>
   <td colspan="1"><input type="submit" name="submit" value="{lang_submit}"></td>
   <td>&nbsp</td>
  </tr>
 </table>
 <input name="r_reg[firsttime]" type="hidden" value="1">
</form>
<!-- END form -->
