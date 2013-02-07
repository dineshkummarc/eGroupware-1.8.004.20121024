<?php
	/**
	* sitemgr - notification Messages storage object
	*
	* @link http://www.egroupware.org
	* @author Jose Luis Gordo Romero <jgordor@gmail.com>
	* @package sitemgr
	* @copyright Jose Luis Gordo Romero <jgordor@gmail.com>
	* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	* @version $Id$
	*/

require_once(EGW_INCLUDE_ROOT.'/etemplate/inc/class.so_sql.inc.php');

class sontfmess extends so_sql
{
	/**
	 * Table-name for notifications messajes
	 *
	 * @var string
	 */
	var $messages_table = 'egw_sitemgr_notify_messages';
	
	function sontfmess()
	{
		$this->so_sql('sitemgr',$this->messages_table);
	}
}
	
?>