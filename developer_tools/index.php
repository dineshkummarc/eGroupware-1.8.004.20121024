<?php
/**
 * eGroupWare - Translation tools
 *
 * @link http://www.outdoor-training.de
 * @package developer_tools
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: index.php 26694 2009-03-30 08:48:59Z ralfbecker $
 */

header('Location: ../index.php?menuaction=developer_tools.uilangfile.index'.
	(isset($_GET['sessionid']) ? '&sessionid='.$_GET['sessionid'].'&kp3='.$_GET['kp3'] : ''));
