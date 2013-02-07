<?php

/**
* 
* Example plugin for unit testing.
*
* @version $Id: Savant2_Plugin_example.php 18360 2005-05-26 19:38:09Z mipmip $
*
*/

require_once 'Savant2/Plugin.php';

class Savant2_Plugin_example extends Savant2_Plugin {
	
	var $msg = "Example: ";
	
	function plugin()
	{
		echo $this->msg . "this is an example!";
	}
}
?>