<!-- BEGIN form -->
<form action="{form_action}" method="POST">
 <h1 align="center">{lang_lost_user_id}</h1>
 <table border="0" style="width:600px;" align="center">
 <tr>

 <td align="center" colspan="2">
 <div style="text-align:center;color:red" class="regerror">{errors}</div>
 <!--<font color=#FF0000 size="+2">{errors}</font>-->
 </td>
 </tr>
 <tr>
 	<td align="justify" colspan="2">{lang_explain}</td>
 </tr>
 <tr><td>&nbsp;</td></tr>
 <tr>
   <td align="right">{lang_email}</td>
   <td><input name="r_reg[email]" value="{value_email}"></td>
  </tr>
  <tr>
  <td>&nbsp</td>
   <td colspan="1"><input type="submit" name="submit" value="{lang_submit}"></td>
  </tr>
 </table>
 <input name="r_reg[firsttime]" type="hidden" value="1">
</form>
<!-- END form -->
