<!-- BEGIN form -->
<script language="JavaScript" type="text/javascript">
	var tos;

	function opentoswindow()
	{
		if (tos)
		{
			if (tos.closed)
			{
				tosWindow.stop();
				tosWindow.close();
			}
		}
		tosWindow = window.open("{tos_link}","tos","width=700,height=600,location=no,menubar=no,directories=no,toolbar=no,scrollbars=yes,resizable=yes,status=no");
		if (tosWindow.opener == null)
		{
			tosWindow.opener = window;
		}
	}
</script>

<div style="text-align:center;color:red" class="regerror">{errors}</div>
<form action="{form_action}" method="POST">
<input type="hidden" name="r_reg[lang_code]" value="{lang_code}">
<table border="0" width="95%" align="center">
<!-- BEGIN password -->
<tr>
       <td width="1%">&nbsp;</td>
       <td>&nbsp;</td>
       <td><a href="{change_login_lid}">{lang_change_login_lid}</a></td>
</tr>
  <tr>
  <td width="1%"></td>
  <td><b>{lang_username}</b></td>
  <td>{value_username}</td>
 </tr>
 <tr>
   <td width="1%">{missing_passwd}</td>
   <td><b>{lang_password}</b></td>
   <td><input type="password" name="r_reg[passwd]" value="{value_passwd}"></td>
  </tr>

  <tr>
   <td width="1%">{missing_passwd_confirm}</td>
   <td><b>{lang_reenter_password}</b></td>
   <td><input type="password" name="r_reg[passwd_confirm]" value="{value_passwd_confirm}"></td>
  </tr>
<!-- END password -->

<!-- BEGIN other_fields_proto -->
  <tr>
   <td width="1%">{missing_indicator}</td>
   <td>{bold_start}{lang_displayed_text}{bold_end}</td>
   <td>{input_field}</td>
  </tr>
<!-- END other_fields_proto -->

<!-- BEGIN tos -->
  <tr>
   <td width="1%">{missing_tos_agree}</td>
   <td colspan="2"><b><font size="2"><a href="javascript:opentoswindow()">{lang_tos_agree}</a></font></b><input type="checkbox" name="r_reg[tos_agree]" {value_tos_agree}></td>
  </tr>
<!-- END tos -->

  <tr>
   <td colspan="3"><input type="submit" name="submit" value="{lang_submit}"></td>
  </tr>
 </table>
</form>
<!-- END form -->

