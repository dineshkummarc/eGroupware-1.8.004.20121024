<?php
/**************************************************************************\
* eGroupWare - PHPSysInfo                                                  *
* http://www.egroupware.org                                                *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id: setup.inc.php 31896 2010-09-05 16:26:30Z ralfbecker $ */

$setup_info['phpsysinfo']['name']      = 'phpsysinfo';
$setup_info['phpsysinfo']['title']     = 'phpSysInfo';
$setup_info['phpsysinfo']['version']   = '1.2_2.5.2rc1';
$setup_info['phpsysinfo']['app_order'] = 99;
$setup_info['phpsysinfo']['enable']    = 2;
$setup_info['phpsysinfo']['tables']    = '';

$setup_info['phpsysinfo']['license']  = 'GPL';
$setup_info['phpsysinfo']['description'] =
	'phpSysInfo displays information about system facts like Uptime, CPU, Memory, PCI devices, SCSI devices, IDE devices, Network adapters, Disk usage, and more.<p>'.
	'phpSysInfo supports now <b>Windows</b>, beside <b>Linux</b> and nearly every other *nix operating system: <b>Darwin, FreeBSD, HP-UX, NetBSD, OpenBSD, SunOS</b>.';
$setup_info['phpsysinfo']['note'] =
	'The version bundled with eGroupWare is equivalent to phpSysInfo\'s version 2.5.2rc1 from 2005-02-27.';

$setup_info['phpsysinfo']['author'] = array(
	'name' => 'phpSysInfo project',
	'url'  => 'http://phpsysinfo.sf.net',
);
$setup_info['phpsysinfo']['maintainer'] = array(
	'name'  => 'Ralf Becker',
	'email' => 'ralfbecker@outdoor-training.de'
);
/* The hooks this app includes, needed for hooks registration */
$setup_info['phpsysinfo']['hooks'][]   = 'admin';

/* Dependencies for this app to work */
$setup_info['phpsysinfo']['depends'][] = array(
	'appname' => 'phpgwapi',
	'versions' => Array('1.7','1.8','1.9')
);
