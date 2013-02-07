<?php
class center_bt
{
	function apply_transform($title,$content)
	{
		return str_replace('&middot;','<img src="templates/idots/images/orange-ball.png" alt="+" />',$content);
	}
}
