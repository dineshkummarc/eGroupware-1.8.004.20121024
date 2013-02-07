<!-- BEGIN header -->
<p style="text-align: center; color: {th_err};">{error}</p>
<form name=frm method="POST" action="{action_url}">
{hidden_vars}
<table border="0" align="left">
   <tr class="th">
    <td colspan="2">&nbsp;<b>{title}</b></td>
   </tr>
<!-- END header -->
<!-- BEGIN body -->
<tr class="row_on">
<td>{lang_name_wiki_home_link}:</td>
<td><input name="newsettings[wikihome]" size="30" value="{value_wikihome}"></td>
</tr>

<tr class="row_off">
<td>{lang_allow_anonymous_access}:</td>
<td>
<select name="newsettings[allow_anonymous]">
<option value=""{selected_allow_anonymous_False}>{lang_No}</option>
<option value="True"{selected_allow_anonymous_True}>{lang_Yes}</option>
<option value="Navbar"{selected_allow_anonymous_Navbar}>{lang_Yes_with_navbar}</option>
</select>
</td>
</tr>

<tr class="row_on">
<td>{lang_Anonymous_Session_Type}:</td>
<td>
<select name="newsettings[Anonymous_Session_Type]">
<option value="readonly"{selected_Anonymous_Session_Type_readonly}>{lang_readonly}</option>
<option value="editable"{selected_Anonymous_Session_Type_editable}>{lang_editable}</option>
</select>
</td>
</tr>

<tr class="row_off">
<td>{lang_anonymous_username}:</td>
<td><input name="newsettings[anonymous_username]" size="30" value="{value_anonymous_username}"></td>
</tr>

<tr class="row_on">
<td>{lang_Anonymous_password}:</td>
<td><input name="newsettings[anonymous_password]" size="30" value="{value_anonymous_password}"></td>
</tr>

<tr class="row_off">
<td>{lang_Emailaddress_Administrator}:</td>
<td><input name="newsettings[emailadmin]" size="30" value="{value_emailadmin}"></td>
</tr>

<tr class="row_on">
<td>{lang_InterWikiPrefix}:</td>
<td><input name="newsettings[InterWikiPrefix]" size="30" value="{value_InterWikiPrefix}"></td>
</tr>


<tr class="row_off">
<td>{lang_Enable_Free_Links}:</td>
<td>
<select name="newsettings[Enable_Free_Links]">
<option value="True"{selected_Enable_Free_Links_True}>{lang_Yes}</option>
<option value="False"{selected_Enable_Free_Links_False}>{lang_No}</option>
</select>
</td>
</tr>

<tr class="row_on">
<td>{lang_Enable_Wiki_Links}:</td>
<td>
<select name="newsettings[Enable_Wiki_Links]">
<option value="True"{selected_Enable_Wiki_Links_True}>{lang_Yes}</option>
<option value="False"{selected_Enable_Wiki_Links_False}>{lang_No}</option>
</select>
</td>
</tr>

<tr class="row_off">
<td>{lang_Automatically_convert_pages_with_wiki-syntax_to_richtext_(if_edited)?}:</td>
<td>
<select name="newsettings[AutoconvertPages]">
<option value="auto"{selected_AutoconvertPages_auto}>{lang_Only_if_browser_supports_a_richtext-editor}</option>
<option value="onrequest"{selected_AutoconvertPages_onrequest}>{lang_No_only_on_request}</option>
<option value="never"{selected_AutoconvertPages_never}>{lang_No_never} {lang_(don't_offer_the_possibility)}</option>
<option value="always"{selected_AutoconvertPages_always}>{lang_Yes_always}</option>
</select>
</td>
</tr>

<tr class="row_on">
 <td>{lang_After_how_many_days_should_old_versions_of_a_page_be_removed_(0_for_never)}:</td>
 <td><input name="newsettings[ExpireLen]" size="5" value="{value_ExpireLen}"></td>
</tr>

<tr class="row_off">
 <td>
  {lang_Image_directory_relative_to_document_root_(use_/_!),_example:} /images<br />
  {lang_An_existing_AND_by_the_webserver_readable_directory_enables_the_image_browser_and_upload.}<br />
  {lang_Upload_requires_the_directory_to_be_writable_by_the_webserver!}
 </td>
 <td><input name="newsettings[upload_dir]" size="40" value="{value_upload_dir}"></td>
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
