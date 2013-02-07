<div style="text-align:center; color:#FF0000; font-weight:bold;"><b>{error_msg}</b></div>
<form name="translatecategory" method="POST" action="{action_url}">
<input type="hidden" name="category_id" value="{catid}">

<table style="border-width:2px;border-style:solid;" align="center" border="1" rules="all" width="100%" cellpadding="5">
	<tr>
		<td width="10%">{lang_refresh}</td><td width="40%">{showlang}</td><td width="50%">{savelang}</td>
	</tr>
	<tr>
		<td>{lang_catname}:</td>
		<td>{showcatname}</td>
		<td><input type="text" name="savecatname" value="{savecatname}"></td>
	</tr>
	<tr>
		<td>{lang_catdesc}:</td>
		<td style="vertical-align:top">{showcatdesc}</td>
		<td><textarea rows="3" cols="50" name="savecatdesc">{savecatdesc}</textarea></td>
	</tr>
	<tr>
		<td colspan="3" align="center"><input type="reset" name="reset" value="{lang_reset}"><input type="submit" name="btnSaveCategory" value="{lang_save}"></td>
	</tr>
</table>
</form>
<h4 style="text-align:center">{lang_contentblocks}</h4>
{blocks}
