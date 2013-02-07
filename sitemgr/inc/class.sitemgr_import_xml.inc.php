<?php
/**
 * eGroupWare
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sitemgr
 * @subpackage importexport
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @copyright Nathan Gray
 * @version $Id: class.sitemgr_import_xml.inc.php 34125 2011-03-14 14:52:22Z ralfbecker $
 */

// define empty interface, if it does not exists (eg. 1.8 or importexport not installed)
if (!interface_exists('importexport_iface_import_plugin'))
{
	interface importexport_iface_import_plugin { }
}

/**
 * class import_xml
 *
 * Import a site.
 */
class sitemgr_import_xml implements importexport_iface_import_plugin
{
	/**
	 * List of errors encountered
	 */
	protected $errors = array();

	/**
	 * Import results
	 */
	protected $results = array();

	/**
	 * @var Common_BO
	 */
	public $common = null;

	/**
	 * XMLReader to use
	 *
	 * @var XMLReader
	 */
	protected $reader;

	/**
	* Key queues to keep track of where we are
	*/
	protected $cat_id = array();
	protected $block_id = array();
	protected $page_id = array();

	private static $debug = 0;

	// Due to limitations in creating Sites_UI, make the import directly accessable
	public $public_functions = array(
		'ui_import' => true
	);

	/**
	 * Constructor
	 *
	 * @param XMLReader $reader XMLReader opened for xml to import
	 */
	public function __construct(XMLReader $reader=null)
	{
		if (!is_null($reader))
		{
			$this->reader = $reader;
		}
		else
		{
			$this->reader = new XMLReader();
		}
	}

	/**
	* Provide a base UI (not part of importexport)
	* so sites can be imported directly as part of sitemgr
	*
	* The usual eTemplate stuff is not available because of specific exceptions in eTemplate,
	* so we use the normal $_FILES array.
	*/
	public function ui_import() {
		if (!$GLOBALS['egw']->acl->check('run',1,'admin')) {
			common::egw_header();
			echo parse_navbar();
			return;
		}
		$data = array();
		if($_FILES['exec']) {
			$this->reader->open($_FILES['exec']['tmp_name']['file']);
			$this->import_record(null,null,false,(boolean)$_POST['exec']['import_permissions']);
			$data['message'] = lang('Imported') . "\n".implode("\n", $this->errors);
			if(!self::$debug) {
				$GLOBALS['egw']->redirect_link('/index.php', array(
					'menuaction' => 'sitemgr.Sites_UI.edit',
					'site_id' => CURRENT_SITE_ID
				));
			}
		}
		common::egw_header();
		echo parse_navbar();
		$template = new etemplate('sitemgr.import_ui');
		echo $template->exec('sitemgr.sitemgr_import_xml.ui_import', $data, array(), array());
	}

	/**
	 * imports a site from the given stream
	 *
	 * @param stream $_stream
	 * @param definition $_definition
	 * @return int number of successful imports
	 */
	public function import( $_stream, importexport_definition $_definition ) {
	}

	/**
	 * returns translated name of plugin
	 *
	 * @return string name
	 */
	public static function get_name() {
		return lang('Import site');
	}

	/**
	 * returns translated (user) description of plugin
	 *
	 * @return string descriprion
	 */
	public static function get_description() {
		return lang('Import a SiteMgr website');
	}

	/**
	 * retruns file suffix(s) plugin can handle (e.g. csv)
	 *
	 * @return string suffix (comma seperated)
	 */
	public static function get_filesuffix() {
		return 'xml';
	}

	/**
	 * return etemplate components for options.
	 * @abstract We can't deal with etemplate objects here, as an uietemplate
	 * objects itself are scipt orientated and not "dialog objects"
	 *
	 * @return array (
	 * 		name 		=> string,
	 * 		content		=> array,
	 * 		sel_options => array,
	 * 		preserv		=> array,
	 * )
	 */
	public function get_options_etpl() {
		return array();
	}

	/**
	 * returns etemplate name for slectors of this plugin
	 *
	 * @return string etemplate name
	 */
	public function get_selectors_etpl() {
		return array();
	}

	/**
	* Returns errors that were encountered during importing
	* Maximum of one error message per record, but you can concatenate them if you need to
	*
	* @return Array (
	*	record_# => error message
	*	)
	*/
	public function get_errors() {
		return $this->errors;
	}

	/**
	* Returns a list of actions taken, and the number of records for that action.
	* Actions are things like 'insert', 'update', 'delete', and may be different for each plugin.
	*
	* @return Array (
	*	action => record count
	* )
	*/
	public function get_results() {
		return $this->results;
	}

	/**
	 * Read the XML stream and import the site(s)
	 *
	 * @param array $admins=null which users to make site-admins, default current user
	 * @param array $cat_acl=null array of user => EGW_ACL_READ|EGW_ACL_ADD, default current user
	 * @param boolean $ignore_acl=false ignore acl of current user
	 * @param boolean $import_permissions=false import permissions too, requires identical user and group names to exist!
	 * @return integer site_id, so call can use it to eg. update some settings
	 */
	public function import_record(array $admins=null,array $cat_acl=null,$ignore_acl=false,$import_permissions=false)
	{
		$this->common = $GLOBALS['Common_BO'] = CreateObject('sitemgr.Common_BO');
		if(self::$debug) {
			echo '<style type = "text/css">
				.site, .category, .block, .page, .node {
					border: 1px solid black;
					margin: 1ex;
					padding: 1ex;
				}
				.node {
					display: none;
				}
			</style>';
		}
		if (is_null($admins)) $admins = array($GLOBALS['egw_info']['user']['account_id']);
		while ($this->reader->read()) {
			if($this->reader->nodeType == XMLReader::ELEMENT) {
				switch($this->reader->name) {
					case 'site':
						$this->import_site($admins,$cat_acl,$ignore_acl,$import_permissions);
						break;
					case 'category':
						$this->import_category($cat_acl,$import_permissions);
						break;
					default:
						echo 'Do not know how to deal with ' . $this->reader->name . ' at the top level.<br />';
				}
			}
		}
		return $this->site_id;
	}

	/**
	 * Import the site
	 *
	 * @param array $admins=null which users to make site-admins, default current user
	 * @param array $cat_acl=null array of user => EGW_ACL_READ|EGW_ACL_ADD, default current user
	 * @param boolean $ignore_acl=false ignore acl of current user
	 * @param boolean $import_permissions=false import permissions too, requires identical user and group names to exist!
	 */
	protected function import_site(array $admins=null,array $cat_acl=null,$ignore_acl=false,$import_permissions=false)
	{
		if(self::$debug >= 1) {
			echo '<div class="site">Import site ---<br />' ;
		}
		$site = array();
		$lang_array = array();
		$current_tag = null;
		$dest = null;
		$this->read_node(array('content_areas', 'categories', 'blocks', 'pages'), $site, $lang_array);

		reset($lang_array);

		$site['site_name'] = $site['name'];
		$site['dir'] = $site['directory'];
		$site['anonuser'] = $site['anonymous_user'];
		$site['anonpasswd'] = $site['anonymous_password'];
		$site['site_languages'] = implode(',', array_keys($lang_array));
		$this->lang = key($lang_array);
		$site['home_page_id'] = 0;

		// Done with the site header for now, save it
		$this->site_id = $this->common->sites->add($site);
		$site['site_id'] = $this->site_id;
		$this->common->sites->so->update_logo_css_params($this->site_id, $site);
		$this->common->sites->saveprefs($site, $this->site_id);
		$this->common->sites->set_currentsite($this->site_id, 'Administration');
		$this->common->cats->setcurrentcats();
		$this->cat_id[] = $this->site_id;

		// allow all modules for the whole page
		$this->common->modules->savemodulepermissions('__PAGE__',$this->site_id,array_keys($this->common->modules->getallmodules()));

		// Set current user as an admin and refresh so the rest of the import works
		$this->common->acl->set_adminlist($this->site_id, $admins);
		$this->common->acl->acl->read_repository();

		// Skip content areas for now
		if($this->reader->name == 'content_areas' && $this->reader->nodeType == XMLReader::ELEMENT) {
			while($this->reader->read()) {
				if($this->reader->name == 'content_areas' && $this->reader->nodeType == XMLReader::END_ELEMENT) break;
			}
			$this->reader->read(); // On to next element
		}
		// tell instance of ACL_BO to always return true for is_admin
		if ($ignore_acl)
		{
			$this->common->acl->__construct(true);
		}
		// Site is a special category
		$this->import_children('site',$cat_acl,$import_permissions);

		// Set home page, after all pages are stored
		if ($site['home_page'])
		{
			$this->common->sites->saveHomePage($this->site_id,$site['home_page']);
		}

		if(self::$debug >= 1) {
			echo '</div>' ;
		}
	}

	/**
	 * Import a category
	 *
	 * @param array $cat_acl=null array of user => EGW_ACL_READ|EGW_ACL_ADD, default current user
	 * @param boolean $import_permissions=false import permissions too, requires identical user and group names to exist!
	 */
	protected function import_category(array $cat_acl=null,$import_permissions=false)
	{
		if (is_null($cat_acl)) $cat_acl = array($GLOBALS['egw_info']['user']['account_id'] => EGW_ACL_READ|EGW_ACL_ADD);

		$category = array();
		$lang_array = array();
		$current_tag = null;
		$dest = null;

		$this->read_node(array('categories', 'blocks', 'pages', 'permissions'), $category, $lang_array);

		if(self::$debug >= 1) {
			$name = $category['name'] ? $category['name'] : $lang_array[$this->lang]['name'];
			echo '<div class="category">Import category --- ' . $name . '<br />';
		}
		// Save category
		if(count($category) > 0) {
			$parent_id = count($this->cat_id) > 0 ? end($this->cat_id) : False;
			$name = $category['name'] ? $category['name'] : $lang_array[$this->lang]['name'];
			$description = $category['description'] ? $category['description'] : $lang_array[$this->lang]['description'];
			$result = $this->common->cats->addCategory($name, $description, $parent_id);
			if($result) {
				$this->cat_id[] = $result;
				if(self::$debug) echo "cat_id: $result<br />";
			} else {
				die('Bad cat');
			}

			// read permissions
			$permissions = $this->read_permissions();
			if ($import_permissions) $cat_acl = $permissions;

			// Set current user as an admin, so the rest of the import works
			foreach($cat_acl as $user => $rights)
			{
				$this->common->acl->grant_permissions($user, end($this->cat_id),
					$rights & EGW_ACL_READ, $rights & EGW_ACL_ADD);
			}

			foreach($lang_array as $lang => $data) {
				$this->common->cats->saveCategoryInfo(
					end($this->cat_id),
					$data['name'],
					$data['description'],
					$lang,
					$category['sort_order'],
					$category['state'],
					$parent_id
				);
			}
		}
		$this->common->cats->setcurrentcats();

		if(!($this->reader->name == 'category' && $this->reader->nodeType == XMLReader::END_ELEMENT)) {
			$this->import_children('category',$cat_acl);
		}

		// set index page, after all pages are stored
		if ($category['index_page'])
		{
			$this->common->cats->saveCategoryIndex(end($this->cat_id),$category['index_page']);
		}

		if(self::$debug >= 1) {
			echo 'Done with category --- ' . $name . '<br /></div>';
		}
		array_pop($this->cat_id);
	}

	/**
	 * Many of the elements can have the same children, so abstract the search and handling
	 *
	 * @param string $tag tag-name
	 * @param array $cat_acl=null array of user => EGW_ACL_READ|EGW_ACL_ADD, default current user
	 * @param boolean $import_permissions=false import permissions too, requires identical user and group names to exist!
	 */
	protected function import_children($tag,array $cat_acl=null,$import_permissions=false)
	{
		if(!$tag) {
			$tag = $this->reader->name;
		}
		if(self::$debug >= 1) {
			echo '<div class="children">Import children of ' . $tag . ' ---<br />';
		}

		$current_depth = $this->reader->depth;
		$extra_elements = array();

		// Children
		while($this->reader->read()) {
			if($this->reader->name == $tag && $this->reader->nodeType == XMLReader::END_ELEMENT) {
				break;
			}
			if($this->reader->nodeType == XMLReader::ELEMENT) {
				switch($this->reader->name) {
					case 'category':
						$this->import_category($cat_acl,$import_permissions);
						break;
					case 'block':
						$this->import_block();
						break;
					case 'page':
						$this->import_page();
						break;
					default:
						// categories, blocks, pages
						if(self::$debug > 1) echo $this->reader->name . '-moving on...<br />';
						break;
				}
			} else if($this->reader->nodeType == XMLReader::END_ELEMENT) {
				//echo 'Done with ' . $this->reader->name . '<br />';
				// Blocks are the last one, so just end
				//if($this->reader->name == 'blocks') break;
			} else if(self::$debug) {
				// Unexpected node
				echo 'Unexpected node: ' . $this->reader->name . ' = ' . $this->reader->value . '<br />';
			}
		}

		if(self::$debug >= 1) {
			echo 'Done with children of ' . $tag . ' ---<br /></div>';
		}
	}

	protected function import_block() {
		if(self::$debug >= 1) {
			echo '<div class="block">Import block ---<br />';
		}
		$block = array();
		$lang_array = array();
		$current_tag = null;
		$dest = null;

		$this->read_node(array('contents'), $block, $lang_array);

		// Save block
		if(count($block) > 0) {
			$block_obj = CreateObject('sitemgr.Block_SO', $block);
			$block_obj->cat_id = end($this->cat_id);
			$block_obj->page_id = count($this->page_id > 0) ? end($this->page_id) : 0;
			$block_obj->module_name = $block['module'];
			$block_obj->module_id = $this->common->modules->getmoduleid($block['module']);
			$block_obj->state = 0;
			if($block['arguments']) {
				$block_obj->arguments = unserialize($block['arguments']);
			}

			$result = $this->common->content->addblock($block_obj);
			if($result) {
				$this->block_id[] = $result;
				$block_obj->id = $result;
				$this->common->content->createversion(end($this->block_id));
				$versions = $this->common->content->getallversionsforblock($block_obj->id,$lang);
				$block_obj->version = end(array_keys($versions));
				$this->common->content->savepublicdata($block_obj);
			} else {
				$this->errors[] = lang('Could not add block %1', $block['title']);
				return;
			}
			foreach($lang_array as $lang => $data) {
				foreach($data as $key => $value) {
					if(property_exists($block_obj, $key)) $block_obj->$key = $value;
				}
				$this->common->content->saveblockdata($block_obj, array(), array(), $lang);
				$result = $this->common->content->saveblockdatalang($block_obj, array(), $lang);
			}
		}

		// Content areas
		while(!(in_array($this->reader->name, array('block', 'blocks', 'contents')) && $this->reader->nodeType == XMLReader::END_ELEMENT) && $this->reader->read()) {
			if($this->reader->name == 'content') {
				$this->import_content($this->reader->getAttribute('lang'));
			}
		}

		if(self::$debug >= 1) {
			echo '</div>';
		}
		array_pop($this->block_id);
	}

	protected function import_content($lang) {
		if(self::$debug >= 1) {
			echo '<div class="content">Import content ---<br />';
		}
		$content = array();
		$lang_array = array();
		$current_tag = null;
		$dest = null;

		$this->read_node(array(), $content, $lang_array);
		if(count($content) > 0) {
			$versions = $this->common->content->getallversionsforblock(end($this->block_id), $lang);
			$version_id = key($versions);
			$block_obj = $this->common->content->getblock(end($this->block_id), $lang);
			foreach($content as $key => &$value) {
				if($value == serialize(false)) {
					$value = false;
					continue;
				} else {
					$unserialized = unserialize(trim($value));
					if($unserialized !== false) {
						$value = $unserialized;
					}
					// import unserialized html blocks (as we do in the database), to allow eg. manual editing
					elseif($block_obj->module_name == 'html' && $key == 'arguments')
					{
						$value = array('htmlcontent' => $value);
				}
			}
			}
			$versions[$version_id] = $content['arguments'];//array_merge($versions[$version_id], $content);
			unset($versions[$version_id]['id']);
			unset($versions[$version_id]['state']);
			$state = array($version_id => $content['state']);
			if($versions[$version_id]) {
				$result = $this->common->content->saveversiondatalang($block_obj->id, $version_id, $versions[$version_id], $lang);
			}
			$this->common->content->so->saveversionstate($block_obj->id, $version_id, $content['state']);
		}
		if(self::$debug >= 1) {
			echo '</div>';
		}
	}

	protected function import_page() {
		if(self::$debug >= 1) {
			echo '<div class="page">Import page ';
		}
		$page = array();
		$lang_array = array();
		$current_tag = null;
		$dest = null;
		$this->read_node(array('page', 'blocks', 'categories'), $page, $lang_array);

		if(self::$debug >= 1) {
			echo $page['name'] . ' ---<br />';
		}

		if(count($page) > 0) {
			$result = $this->common->pages->addPage(end($this->cat_id));
			if(!$result) {
				$this->errors[] = "Unable to add page '{$page['name']}'!";
				return;
			} else {
				$this->page_id[] = $result;
			}
			$page_obj = $this->common->pages->getPage($result, False, true);
			foreach($page as $key => $value) {
				if(property_exists($page_obj, $key)) {
					$page_obj->$key = $value;
				}
			}
			foreach($lang_array as $lang => $data) {
				foreach($data as $key => &$value) {
					if($value == serialize(false)) {
						$value = false;
						continue;
					} else {
						$unserialized = unserialize($value);
						if($unserialized !== false) {
							$value = $unserialized;
						}
					}
					if(property_exists($page_obj, $key)) {
						$page_obj->$key = $value;
					}
				}
				$this->common->pages->savePageInfo($page_obj, $lang);
			}
		}
		if(!($this->reader->name == 'page' && $this->reader->nodeType == XMLReader::END_ELEMENT)) {
			$this->import_children('page');
		}
		if(self::$debug >= 1) {
			echo '</div>';
		}
		array_pop($this->page_id);
	}

	protected function read_node($stop_tags, &$data, &$lang_array) {
		if(self::$debug >= 1) {
			echo '<div class="node">Read node (' . $this->reader->name . ') ---<br />' ;
		}
		$start_tag = $this->reader->name;
		$current_tag = null;
		$dest = null;

		while($this->reader->read()) {
			if(self::$debug >= 2) echo $this->reader->name . '=' . $this->reader->value . '<br />';
			if(in_array($this->reader->name, $stop_tags) ||
					$this->reader->name == $start_tag && $this->reader->nodeType == XMLReader::END_ELEMENT) {
				break;
			}
			if($this->reader->nodeType == XMLReader::ELEMENT) {
				$current_tag = $this->reader->name;
				$dest =& $data[$current_tag];
			} else if($this->reader->nodeType == XMLReader::END_ELEMENT) {
				$current_tag = null;
				continue;
			}
			if($current_tag != null) {
				if($this->reader->getAttribute('lang')) {
					$dest =& $lang_array[$this->reader->getAttribute('lang')][$current_tag];
				}
				$dest .= $this->reader->value;
			}
		}
		if(self::$debug >= 1) {
			if(self::$debug >= 2) _debug_array($data);
			echo '</div>';
		}
	}

	/**
	 * Read content of permissions tag incl. user and group children
	 *
	 * Unknows user are silently ignored, as are rights == 0 or if $this->reader is NOT on a permissions start-tag.
	 *
	 * @return array with account_id => rights pairs
	 */
	protected function read_permissions()
	{
		$permissions = array();
		if ($this->reader->name == 'permissions' && $this->reader->nodeType == XMLReader::ELEMENT)
		{
			while($this->reader->read() &&
				!($this->reader->name == 'permissions' && $this->reader->nodeType == XMLReader::END_ELEMENT))
			{
				if ($this->reader->nodeType == XMLReader::ELEMENT)
				{
					$current_tag = $this->reader->name;
					$rights = (int)$this->reader->getAttribute('rights');
					continue;
				}
				if ($this->reader->nodeType == XMLReader::END_ELEMENT)
				{
					unset($current_tag);
					continue;
				}
				switch($current_tag)
				{
					case 'user':
					case 'group':
						$account_id = $GLOBALS['egw']->accounts->name2id($this->reader->value,
							'account_lid',$current_tag == 'group' ? 'g' : 'u');

						if ($account_id && $rights)
						{
							$permissions[$account_id] = $rights;
						}
						break;
				}
			}
		}
		return $permissions;
	}
}
