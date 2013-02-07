<div style="margin-bottom:1cm;font-weight:bold;text-align:center;text-decoration:underline">{commit_manager} </div>
<form method="POST" action="{action_url}">
<h2 style="text-align:center">{lang_categories}</h2>
<table align="center" width="60%">
<!-- BEGIN Category -->
	<tr>
		<td width="10%"><input type="checkbox" name="cat[{catid}]" /></td>
		<td width="70%"><a href="{edit}" target="editwindow" onclick="if (this != '') { window.open(this,this.target,'width=800,height=600,scrollbars=yes,resizable=yes'); return false; } else { return true; }">{category}</a></td>
		<td>{addedorremoved}</td>
	</tr>
<!-- END Category -->
</table>
<h2 style="text-align:center">{lang_pages}</h2>
<table align="center" width="60%">
<!-- BEGIN Page -->
	<tr>
		<td width="10%"><input type="checkbox" name="page[{pageid}]" /></td>
		<td width="70%"><a href="{edit}" target="editwindow" onclick="if (this != '') { window.open(this,this.target,'width=800,height=600,scrollbars=yes,resizable=yes'); return false; } else { return true; }">{page}</a></td>
		<td>{addedorremoved}</td>
	</tr>
<!-- END Page -->
</table>
<h2 style="text-align:center">{lang_blocks}</h2>
<table align="center" width="60%">
<!-- BEGIN Block -->
	<tr>
		<td width="10%"><input type="checkbox" name="block[{blockid}]" /></td>
		<td width="35%"><a href="{edit}" target="editwindow" onclick="if (this != '') { window.open(this,this.target,'width=800,height=600,scrollbars=yes,resizable=yes'); return false; } else { return true; }">{block}</a></td>
		<td width="35%">{scope}</td>
		<td>{addedorremovedorreplaced}</td>
	</tr>
<!-- END Block -->
</table>
<center><input type="submit" name="btnCommit" value="{lang_commit}" /></center>
</form>
