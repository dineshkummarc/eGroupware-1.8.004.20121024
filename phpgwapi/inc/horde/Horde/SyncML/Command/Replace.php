<?php
/**
 * eGroupWare - SyncML based on Horde 3
 *
 *
 * Using the PEAR Log class (which need to be installed!)
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package api
 * @subpackage horde
 * @author Anthony Mills <amills@pyramid6.com>
 * @copyright (c) The Horde Project (http://www.horde.org/)
 * @version $Id: Replace.php 31121 2010-06-26 16:28:37Z jlehrke $
 */
include_once 'Horde/SyncML/Command.php';

class Horde_SyncML_Command_Replace extends Horde_SyncML_Command {

    /**
     * Name of the command.
     *
     * @var string
     */
    var $_cmdName = 'Replace';

    function output($currentCmdID, &$output)
    {
        return $currentCmdID;
    }

}
