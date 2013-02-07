<?php
/**
 * eGroupWare - Online User manual
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package manual
 * @copyright (c) 2004-9 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: index.php 27533 2009-07-23 19:06:46Z ralfbecker $
 */

/**
 * Check if we allow anon access and with which creditials
 *
 * @param array &$anon_account anon account_info with keys 'login', 'passwd' and optional 'passwd_type'
 * @return boolean true if we allow anon access, false otherwise
 */
function manual_check_anon_access(&$anon_account)
{
	$config = config::read('manual');

	if ($config['manual_allow_anonymous'] && $config['manual_anonymous_user'])
	{
		$anon_account = array(
			'login'  => $config['manual_anonymous_user'],
			'passwd' => $config['manual_anonymous_password'],
			'passwd_type' => 'text',
		);
		return true;
	}
	return false;
}

// uncomment the next line if manual should use a eGW domain different from the first one defined in your header.inc.php
// and of cause change the name accordingly ;-)
// $GLOBALS['egw_info']['user']['domain'] = $GLOBALS['egw_info']['server']['default_domain'] = 'developers';

$GLOBALS['egw_info'] = array(
	'flags' => array(
		'currentapp' => 'manual',
		'autocreate_session_callback' => 'manual_check_anon_access',
		'disable_Template_class' => True,
		'noheader'  => True,
		'nonavbar'   => True,
	),
);
include('../header.inc.php');

ExecMethod('manual.uimanual.view');

$GLOBALS['egw']->common->egw_footer();
