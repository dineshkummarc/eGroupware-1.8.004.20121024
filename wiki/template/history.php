<?php
// $Id: history.php 19415 2005-10-14 14:08:53Z ralfbecker $

require_once(TemplateDir . '/common.php');

// The history template is passed an associative array with the following
// elements:
//
//   page      => A string containing the name of the wiki page.
//   history   => A string containing the XHTML markup for the history form.
//   diff      => A string containing the XHTML markup for the changes made.

function template_history($args)
{
	global $DiffScript;

	//echo "<p>template_history(".print_r($args,True).")</p>";
	template_common_prologue(array('norobots' => 1,
																 'title'    => lang('History of').' ' . get_title($args['page']),
																 'heading'  => lang('History of').' ',
																 'headlink' => $args['page'],
																 'headsufx' => '',
																 'toolbar'  => 1));
?>
<div id="body">
	<form method="get" action="<?php print $DiffScript; ?>">
	<div class="form">
		<input type="hidden" name="action" value="diff" />
		<input type="hidden" name="page" value="<?php print $args['page']['name']; ?>" />
		<input type="hidden" name="lang" value="<?php print $args['page']['lang']; ?>" />
<table border="0">
	<tr><td><strong><?php echo lang('Older'); ?></strong></td>
			<td><strong><?php echo lang('Newer'); ?></strong></td><td></td></tr>
<?php
	print $args['history'];

?>
	<tr><td colspan="3">
		<input type="submit" value="<?php echo lang('Compute Difference'); ?>" /></td></tr>
</table>
	</div>
	</form>
<hr /><br />

<strong><?php echo lang('Changes by last author'); ?>:</strong><br /><br />

<?php print $args['diff']; ?>
</div>
<?php
	template_common_epilogue(array('twin'      => $args['page'],
																 'edit'      => '',
																 'editver'   => 0,
																 'history'   => '',
																 'timestamp' => '',
																 'nosearch'  => 0));
}
?>
