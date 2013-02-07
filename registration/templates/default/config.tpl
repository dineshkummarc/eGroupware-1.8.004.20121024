<!-- BEGIN header -->
<form method="POST" action="{action_url}">
<table border="0" align="center">
   <tr bgcolor="{th_bg}">
    <td colspan="2"><font color="{th_text}">&nbsp;<b>{title}</b></font></td>
   </tr>
<!-- END header -->

<!-- BEGIN body -->
   <tr bgcolor="{row_on}">
    <td colspan="2">&nbsp;</td>
   </tr>

   <tr bgcolor="{row_off}">
    <td colspan="2"><b>{lang_Registration_settings}</b></td>
  </tr>
  <tr bgcolor="{row_on}">
  <td>{lang_Enable_Registration?}</td>
  <td>
   <select name="newsettings[enable_registration]">
	<option value="False"{selected_enable_registration_False}>{lang_No}</option>
	<option value="True"{selected_enable_registration_True}>{lang_Yes}</option>
   </select>
  </td>
</tr>
<tr bgcolor="{row_off}">
  <td>{lang_Register_link_at_login_screen?}</td>
  <td>
   <select name="newsettings[register_link]">
	<option value="False"{selected_register_link_False}>{lang_No}</option>
	<option value="True"{selected_register_link_True}>{lang_Yes}</option>
   </select>
  </td>
</tr>
</tr>
<tr bgcolor="{row_on}">
  <td>{lang_Lost_password_link_at_login_screen?}</td>
  <td>
   <select name="newsettings[lostpassword_link]">
	<option value="False"{selected_lostpassword_link_False}>{lang_No}</option>
	<option value="True"{selected_lostpassword_link_True}>{lang_Yes}</option>
   </select>
  </td>
</tr>
<tr bgcolor="{row_off}">
  <td>{lang_Lost_user_id_link_at_login_screen?}</td>
   <td>
   <select name="newsettings[lostid_link]">
	<option value="False"{selected_lostid_link_False}>{lang_No}</option>
	<option value="True"{selected_lostid_link_True}>{lang_Yes}</option>
   </select>
  </td>
</tr>
<tr bgcolor="{row_on}">
    <td>{lang_Use_trial_accounts?}</td>
    <td>
     <select name="newsettings[trial_accounts]">
      <option value="False"{selected_trial_accounts_False}>{lang_No}</option>
      <option value="True"{selected_trial_accounts_True}>{lang_Yes}</option>
     </select>
    </td>
  </tr>
  <tr bgcolor="{row_off}">
    <td>{lang_Days_until_trial_accounts_expire}:</td>
   <td><input name="newsettings[days_until_trial_account_expires]" value="{value_days_until_trial_account_expires}"></td>
  </tr>

   <tr>
   <tr bgcolor="{row_off}">
    <td>{lang_Activate_account}:</td>
    <td>
     <select name="newsettings[activate_account]">
      <option value="email"{selected_activate_account_email}>{lang_Send_Email}</option>
      <option value="immediately"{selected_activate_account_immediately}>{lang_Immediately}</option>
     </select>
    </td>
   </tr>
   <tr bgcolor="{row_on}">
    <td>{lang_Username_is}:</td>
    <td>
     <select name="newsettings[username_is]">
      <option value="choice"{selected_username_is_choice}>{lang_Users_Choice}</option>
      <option value="http"{selected_username_is_http}>{lang_HTTP_Username}</option>
     </select>
    </td>
   </tr>
   <tr bgcolor="{row_off}">
    <td>{lang_Password_is}:</td>
    <td>
     <select name="newsettings[password_is]">
      <option value="choice"{selected_password_is_choice}>{lang_Users_Choice}</option>
      <option value="http"{selected_password_is_http}>{lang_HTTP_Password}</option>
     </select>
    </td>
   </tr>
  <tr bgcolor="{row_on}">
   <td>{lang_Anonymous_user}:</td>
   <td><input name="newsettings[anonymous_user]" value="{value_anonymous_user}"></td>
  </tr>
  <tr bgcolor="{row_off}">
   <td>{lang_Anonymous_password}:</td>
   <td><input type="password" name="newsettings[anonymous_pass]" value="{value_anonymous_pass}"></td>
  </tr>
  <tr bgcolor="{row_on}">
  <td>{lang_Name_Sender_to_send_notices_from}:</td>
  <td><input name="newsettings[name_nobody]" value="{value_name_nobody}"></td>
  </tr>
  <tr bgcolor="{row_off}">
    <td>{lang_Email_address_to_send_notices_from}:</td>
    <td><input name="newsettings[mail_nobody]" value="{value_mail_nobody}"></td>
  </tr>
  <tr bgcolor="{row_on}">
    <td>{lang_Email_address_to_display_for_support}:</td>
    <td><input name="newsettings[support_email]" value="{value_support_email}"></td>
  </tr>
  <tr bgcolor="{row_off}">
  <td>{lang_Subject_for_confirmation_email}:</td>
  <td><input name="newsettings[subject_confirm]" value="{value_subject_confirm}"></td>
 </tr>
 <tr bgcolor="{row_on}">
   <td>{lang_Convert_user_login_ID_to_7_bit_string}:</td>
   <td>
   <select name="newsettings[conv7bit]">
	<option value=""{selected_conv7bit_False}>{lang_No}</option>
	<option value="True"{selected_conv7bit_True}>{lang_Yes}</option>
   </select>
  </tr>
   <tr bgcolor="{row_off}">
  <td>{lang_Display_Terms_of_Service?}</td>
  <td>
   <select name="newsettings[display_tos]">
	<option value=""{selected_display_tos_False}>{lang_No}</option>
	<option value="True"{selected_display_tos_True}>{lang_Yes}</option>
   </select>
  </td>
 </tr>
 <tr bgcolor="{row_on}">
 <td>{lang_Terms_of_Service_text} ({lang_use HTML})</td>
 <td>
	<textarea name="newsettings[tos_text]" cols="40" rows="20">{value_tos_text}</textarea>
</td>
<!--<tr bgcolor="{row_off}">-->

<!-- END body -->

<!-- BEGIN footer -->
  <tr bgcolor="{th_bg}">
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
