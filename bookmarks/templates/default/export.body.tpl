<form method="post" action="{FORM_ACTION}">
 <table border="0" bgcolor="#EEEEEE" align="center" width=60%>
 <tr>
  <td colspan="2">{lang_categories}<br>&nbsp;</td>
 </tr>
 <tr>
   <td>
  {input_categories}
   </td>
 </tr>
 <tr>
  <td colspan="2">{lang_format}<br>&nbsp;

  </td>
 </tr>
 <tr>
   <td>
  	<select name="exporttype"><option>Netscape/Mozilla</option><option>XBEL</option></select>
   </td>
 </tr>
 <tr>
  <td colspan="2" align="right">
   <input type="submit" name="export" value="{lang_export_bookmarks}">
  </td>
 </tr>
</table>
</form>
