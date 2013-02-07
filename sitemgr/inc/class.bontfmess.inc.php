<?php
/**
 * sitemgr - Notifications Messages Bussines Object
 *
 * @link http://www.egroupware.org
 * @author Jose Luis Gordo Romero <jgordor-AT-gmail.com>
 * @package sitemgr
 * @copyright (c) 2007 by Jose Luis Gordo Romero <jgordor-AT-gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */
 
require_once(EGW_INCLUDE_ROOT.'/sitemgr/inc/class.sontfmess.inc.php');

/**
 * Business Object of the sitemgr notifications
 */
class bontfmess extends sontfmess
{
	/**
	 * Current user
	 * 
	 * @var int;
	 */
	var $user;
	/**
	 * Bo Site Object
	 */
	var $bosite;
	/**
	 * Constructor
	 *
	 * @return bonotifications
	 */
	function bontfmess()
	{		
		$this->sontfmess();
		$this->bosite = CreateObject("sitemgr.Sites_BO");
		
		$this->user = $GLOBALS['egw_info']['user']['account_id'];
	}
	
	function get_site_langs($site_id)
	{
		$site = $this->bosite->read($site_id);

		$res = array();
		foreach ($site['sitelanguages'] as $lang)
		{
			$res[$lang]=$GLOBALS['Common_BO']->getlangname($lang);
		}

		return $res;
	}
}
?>
