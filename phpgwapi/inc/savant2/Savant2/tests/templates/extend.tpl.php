<?php
/**
* 
* Template for testing token assignment.
* 
* @version $Id: extend.tpl.php 18360 2005-05-26 19:38:09Z mipmip $
*
*/
?>
<p><?php $result = $this->plugin('example'); var_dump($result); ?></p>
<p><?php $result = $this->plugin('example_extend'); var_dump($result); ?></p>
