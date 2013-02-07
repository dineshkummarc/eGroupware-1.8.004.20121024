<?php

/**
* 
* Tests default plugins
*
* @version $Id: 8_extend.php 27222 2009-06-08 16:21:14Z ralfbecker $
* 
*/

error_reporting(E_ALL);

require_once 'Savant2.php';

$conf = array(
	'template_path' => 'templates',
	'resource_path' => 'resources'
);

$savant = new Savant2($conf);

$savant->display('extend.tpl.php');

?>