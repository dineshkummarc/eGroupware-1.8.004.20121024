<!-- BEGIN form -->
 <table width="500" border="0" align="center">
  <tr>
   <td colspan="3">
    <table border="0" width="100%">
     <tr>
        {match_left}
        <td align="center">{lang_showing}</td>
        {match_right}
     </tr>
    </table>
   </td>
  </tr>
  <tr class="th">
   <td width="50%">{sort_title}</td>
   <td width="50%">{sort_answer}</td>
   <td width="50">{lang_actions}</td>
  </tr>
  
  {rows}

 </table>
 
 <form method="POST" action="{add_action}">
  <center><input type="submit" name="add" value="{lang_add}"></center>
 </form>
<!-- END form -->

<!-- BEGIN row -->
  <tr class="{tr_class}">
   <td>{row_title}</td>
   <td>{row_answer}</td>
   <td align="center">{row_actions}</td>
  </tr>
<!-- END row -->
