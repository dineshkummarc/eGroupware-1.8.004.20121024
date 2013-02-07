<!-- BEGIN header -->
<form method="POST" action="{action_url}">
<table border="0" align="center">
   <tr bgcolor="{th_bg}">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{title}</b></font></td>
   </tr>
<!-- END header -->
<!-- BEGIN body -->
   <tr bgcolor="{row_on}">
    <td>{lang_path_to_mkntpwd}:</td>
    <td><input size="60" name="newsettings[mkntpwd]" value="{value_mkntpwd}"></td>
   </tr>

   <tr bgcolor="{row_off}">
    <td>{lang_Samba_SID}:</td>
    <td><input size="60" name="newsettings[sambasid]" value="{value_sambasid}"></td>
   </tr>

   <tr bgcolor="{row_on}">
    <td>{lang_computer_ou}:</td>
    <td><input size="60" name="newsettings[samba_computerou]" value="{value_samba_computerou}"></td>
   </tr>

   <tr bgcolor="{row_off}">
    <td>{lang_computer_group}:</td>
    <td><input size="60" name="newsettings[samba_computergroup]" value="{value_samba_computergroup}"></td>
   </tr>

   <tr>
    <td colspan="2">&nbsp;</td>
   </tr>

   <tr bgcolor="{th_bg}">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{lang_new_account_defaults}</b></font></td>
   </tr>

   <tr bgcolor="{row_on}">
    <td>{lang_smb_homepath}:</td>
    <td><input size="60" name="newsettings[samba_homepath]" value="{value_samba_homepath}"></td>
   </tr>

   <tr bgcolor="{row_off}">
    <td>{lang_homedrive}:</td>
    <td><input size="60" name="newsettings[samba_homedrive]" value="{value_samba_homedrive}"></td>
   </tr>

   <tr bgcolor="{row_on}">
    <td>{lang_logonscript}:</td>
    <td><input size="60" name="newsettings[samba_logonscript]" value="{value_samba_logonscript}"></td>
   </tr>

   <tr bgcolor="{row_off}">
    <td>{lang_profilepath}:</td>
    <td><input size="60" name="newsettings[samba_profilepath]" value="{value_samba_profilepath}"></td>
   </tr>
<!-- END body -->
<!-- BEGIN footer -->
  <tr>
    <td colspan="2">
&nbsp;
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" name="submit" value="Submit">
      <input type="submit" name="cancel" value="Cancel">
    </td>
  </tr>
</table>
</form>
<!-- END footer -->
