<?php
/**
 * EGroupware SiteMgr CMS - Site template infos
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @copyright Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: theme_info.php 33188 2010-11-28 20:27:05Z ralfbecker $
 */

$GLOBALS['egw_info'] = array(
	'flags' => array(
		'currentapp' => 'sitemgr',
		'nonavbar'   => True,
		'noheader'   => True,
		'noapi'      => False,
	)
);
include('../header.inc.php');

$GLOBALS['Common_BO'] = CreateObject('sitemgr.Common_BO');
$GLOBALS['Common_BO']->sites->set_currentsite(False,'Administration');

$GLOBALS['egw']->template->set_file('theme_info','theme_info.tpl');
if ($_GET['theme'] && ($info = $GLOBALS['Common_BO']->theme->getThemeInfos($_GET['theme'])))
{
	if ($info['thumbnail']) $info['thumbnail'] = '<img src="'.$info['thumbnail'].'" />';
	$GLOBALS['egw']->template->set_var($info);
	$GLOBALS['egw']->template->set_var(array(
		'lang_author' => lang('Author'),
		'lang_copyright' => lang('Copyright'),
		'lang_license' => lang('License'),
	));
}
$GLOBALS['egw']->template->pfp('out','theme_info');

common::egw_exit();
