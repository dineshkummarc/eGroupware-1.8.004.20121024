<?php
/**
 * EGroupware SiteMgr - HTML block
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @subpackage modules
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_html.inc.php 38587 2012-03-24 12:58:13Z ralfbecker $
 */

/**
 * HTML block
 */
class module_html extends Module
{
	function __construct()
	{
		$this->i18n = true;
		$this->arguments = array(
			'htmlcontent' => array(
				'type' => 'htmlarea',
				'label' => lang('Enter the block content here'),
				'large' => True,	// show label above content
				'i18n' => True,
			)
		);
		$this->properties = array('striphtml' => array('type' => 'checkbox', 'label' => lang('Strip HTML from block content?')));
		$this->title = lang('HTML module');
		$this->description = lang('This module is a simple HTML editor');
	}

	/**
	 * Return module content
	 */
	function get_content(&$arguments,$properties)
	{
		$content = $arguments['htmlcontent'];

		// global module property to remove html
		if ($properties['striphtml'])
		{
			$content = $GLOBALS['egw']->strip_html($content);
		}
		// spamsaver emailaddress and activating the links
		$content = html::activate_links($content);

		// remove CKeditor SCAYT (spell-check-as-you-type) tags, which dont validate
		if (strpos($content, 'data-scayt_word="') !== false)
		{
			$content = preg_replace('/data-scayt(_word|id)="[^"]*"/','',$content);
		}

		return $content;
	}
}
