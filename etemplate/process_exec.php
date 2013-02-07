<?php
/**
 * eGroupWare - eTemplates - Editor
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package etemplate
 * @copyright (c) 2002-8 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: process_exec.php 26074 2008-10-07 12:25:28Z ralfbecker $
 */

list($app) = explode('.',$_GET['menuaction']);

$GLOBALS['egw_info'] = array(
	'flags' => array(
		'currentapp'	=> $app,
		'noheader'		=> True,
		'nonavbar'		=> True,
	),
);
include('../header.inc.php');

ExecMethod('etemplate.etemplate.process_exec');
