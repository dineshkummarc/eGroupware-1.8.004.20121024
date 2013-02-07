<!-- BEGIN header -->
<p style="text-align: center; color: {th_err};">{error}</p>
<form name=frm method="POST" action="{action_url}">
{hidden_vars}
<table border="0" align="center">
<tr class="th">
 <td colspan="2">&nbsp;<b>{title}:</b></td>
</tr>
<!-- END header -->
<!-- BEGIN body -->
<tr class="row_on">
 <td>{lang_Remote_eGroupWare_URL_for_the_manual_to_use,_empty_for_a_local_manual,_default}:<br /><i>http://manual.egroupware.org/egroupware</i></td>
 <td><input name="newsettings[manual_remote_egw_url]" size="50" value="{value_manual_remote_egw_url}"></td>
</tr>

<tr class="row_off">
 <td>{lang_Update_URL_for_the_local_manual,_default}:<br /><i>http://manual.egroupware.org/egroupware/wiki/index.php?page=Manual&amp;action=xml</i></td>
 <td><input name="newsettings[manual_update_url]" size="50" value="{value_manual_update_url}"></td>
</tr>

<tr class="row_on">
 <td>{lang_Wiki_Id_for_the_local_manual,_default_1,_0_to_use_the_wiki_to_edit_the_manual_pages}:</td>
 <td><input name="newsettings[manual_wiki_id]" size="3" value="{value_manual_wiki_id}"></td>
</tr>

<tr class="row_off">
 <td>{lang_Allow_anonymous_access_to_the_manual,_only_necessary_to_act_as_remote_manual}:</td>
 <td>
  <select name="newsettings[manual_allow_anonymous]">
   <option value=""{selected_manual_allow_anonymous_False}>{lang_No}</option>
   <option value="True"{selected_manual_allow_anonymous_True}>{lang_Yes}</option>
  </select>
 </td>
</tr>

<tr class="row_off">
 <td>{lang_Anonymous_username}:</td>
 <td><input name="newsettings[manual_anonymous_user]" size="20" value="{value_manual_anonymous_user}"></td>
</tr>

<tr class="row_on">
 <td>{lang_Anonymous_password}:</td>
 <td><input type="password" name="newsettings[manual_anonymous_password]" size="20" value="{value_manual_anonymous_password}"></td>
</tr>
<!-- END body -->

<!-- BEGIN footer -->
  <tr class="th">
    <td colspan="2">
&nbsp;
    </td>
  </tr>
  <tr>
    <td colspan="2" align="center">
      <input type="submit" name="submit" value="{lang_submit}">
      <input type="submit" name="cancel" value="{lang_cancel}">
    </td>
  </tr>
</table>
</form>
<!-- END footer -->
