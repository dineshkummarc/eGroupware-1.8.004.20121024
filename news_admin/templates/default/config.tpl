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
