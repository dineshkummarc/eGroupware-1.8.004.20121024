<?php

class left_bt
{
	function apply_transform($title,$content)
	{
	/* in my case the title on left_bt doesn't make sense
	if you want it back remove the "display:none" style */
	return "
	<img src=\"templates/realss/images/left_bt_top.png\" 
	     style=\"display:block\"/>
	<div class=\"left_bt\">
	<h3 style=\"display:none\">$title</h3>
	$content
	</div>
	<img src=\"templates/realss/images/left_bt_bottom.png\" 
	     style=\"display:block; margin-bottom: 10px\"/>
	";
	}
}
