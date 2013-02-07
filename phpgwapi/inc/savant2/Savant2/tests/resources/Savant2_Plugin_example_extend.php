<?php

/**
* 
* Example plugin for unit testing.
*
* @version $Id: Savant2_Plugin_example_extend.php 18360 2005-05-26 19:38:09Z mipmip $
*
*/

$this->loadPlugin('example');

class Savant2_Plugin_example_extend extends Savant2_Plugin_example {
	
	var $msg = "Extended Example! ";
	
}
?>