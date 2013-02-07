<?php
	/**
	* eGroupWare: sitemgr Jooma Template handler
	*
 	* @link http://www.egroupware.org
 	* @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
	* @package sitemgr
	* Modified by Stefan Becker <StefanBecker-AT-outdoor-training.de>
 	*/
	/* $Id: mosmenu.inc.php 13831 2004-02-22 01:46:27Z stefanbecker $ */

	/**
	 * @version		$Id:output.php 6961 2007-03-15 16:06:53Z tcp $
	 * @package		Joomla.Framework
	 * @subpackage	Filter
	 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
	 * @license		GNU/GPL, see LICENSE.php
	 * Joomla! is free software. This version may have been modified pursuant to the
	 * GNU General Public License, and as distributed it includes or is derivative
	 * of works licensed under the GNU General Public License or other free or open
	 * source software licenses. See COPYRIGHT.php for copyright notices and
	 * details.
	 */

	/**
 * @version		$Id: functions.php 10094 2008-03-02 04:35:10Z instance $
 * @package		Joomla.Legacy
 * @subpackage	1.5
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */
/**
 * Legacy function, use {@link JArrayHelper::getValue()} instead
 *
 * @deprecated	As of version 1.5
 */
function mosGetParam( &$arr, $name, $def=null, $mask=0 )
{
	// Static input filters for specific settings
	static $noHtmlFilter	= null;
	static $safeHtmlFilter	= null;

	$var = JArrayHelper::getValue( $arr, $name, $def, '' );

	// If the no trim flag is not set, trim the variable
	if (!($mask & 1) && is_string($var)) {
		$var = trim($var);
	}

	// Now we handle input filtering
	if ($mask & 2) {
		// If the allow html flag is set, apply a safe html filter to the variable
		if (is_null($safeHtmlFilter)) {
			$safeHtmlFilter = & JFilterInput::getInstance(null, null, 1, 1);
		}
		$var = $safeHtmlFilter->clean($var, 'none');
	} elseif ($mask & 4) {
		// If the allow raw flag is set, do not modify the variable
		$var = $var;
	} else {
		// Since no allow flags were set, we will apply the most strict filter to the variable
		if (is_null($noHtmlFilter)) {
			$noHtmlFilter = & JFilterInput::getInstance(/* $tags, $attr, $tag_method, $attr_method, $xss_auto */);
		}
		$var = $noHtmlFilter->clean($var, 'none');
	}
	return $var;
}

/**
 * Legacy function to replaces &amp; with & for xhtml compliance
 *
 * @deprecated	As of version 1.5
 */
function ampReplace( $text ) {
	return JFilterOutput::ampReplace($text);
}
