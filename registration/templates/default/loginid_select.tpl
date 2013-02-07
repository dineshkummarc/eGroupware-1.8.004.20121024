<!-- BEGIN form -->
<center><font color=#FF0000 size=+2>{errors}</font></center>
<!-- BEGIN input -->
<form action="{form_action}" method="POST">

<table border="0" align="center">


  <tr>
   <td valign="top" rowspan="3" style="padding-right:10px;"><img src="{illustration}" alt="{title}" border="0" style="width:198px;height:280px;background-color:#ffffff;margin-left:0px; padding:0px; border-right: #cccccc 1px solid;border-top: #9c9c9c 2px solid;border-left: #9c9c9c 2px solid;border-bottom: #cccccc 1px solid" /></td>
   
   <td valign="top" align="right">{lang_choose_language}</td>
   <td valign="top"><input type="hidden" name="langchanged" value="false" >{selectbox_languages}</td>
  </tr>

  <tr>
  <td style="text-align:right">{lang_username}</td>
  <td><input name="r_reg[loginid]" value="{value_username}"></td>
 </tr>
 <tr>
   <td colspan="3" valign="top" style="text-align:center" align="center"><input type="submit" name="xsubmit" value="{lang_submit}"></td>
  </tr>
 </table>
</form>
<!-- END input -->
<!-- END form -->
