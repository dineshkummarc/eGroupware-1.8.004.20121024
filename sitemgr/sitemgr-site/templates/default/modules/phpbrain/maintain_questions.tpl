{message}
{links_nav}
<div class=divSideboxHeader style='position:relative; text-align:left'>{lang_outstanding_q}<span style='width:100%; position:absolute; right:0; text-align:right'>{num_regs}</span></div>
<table width=100% cellspacing=0>
	<tr class=divSideboxHeader>
		{left}
		<td width=100% align=center>
			<form method="POST" action="{form_filters_action}">
				<table>
					<tr>
						<td>
							Category&nbsp;&nbsp;
							<select name="cat" onchange="this.form.submit();">
								<option value="0">All</option>
								{select_categories}
							</select>
						</td>
						<td align=center>
							<input type=text name="query" value="{value_query}">&nbsp;&nbsp;&nbsp;<input type=submit name="search" value="{lang_search}">
						</td>
					</tr>
				</table>
			</form>
		</td>
		{right}
	</tr>
</table>
<table width=100%>
		<tr class=divSideboxHeader>
			<th>{head_summary}</th><th>{head_details}</th>
		</tr>
		<!-- BEGIN table_row_block -->
		<tr bgcolor="{tr_color}">
			<td>{summary}</td><td>{details}</td>
		</tr>
		<!-- END table_row_block -->
</table>
