<?php
/**
 * Export a site manager site to an XML file
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package sitemgr
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @copyright Nathan Gray
 * @version $Id: class.sitemgr_export_xml.inc.php 34125 2011-03-14 14:52:22Z ralfbecker $
 */

// define empty interface, if it does not exists (eg. 1.8 or importexport not installed)
if (!interface_exists('importexport_iface_export_plugin'))
{
	interface importexport_iface_export_plugin { }
}

/**
 * class export_xml
 * This exports an entire web site
 * An record is e.g. a single address or or single event.
 * No matter where the records come from, at the end export_entry
 * stores it into the stream
 */
class sitemgr_export_xml implements importexport_iface_export_plugin
{
	/**
	 * Reference to common sitemgr object.  We should be able to get at everything from here.
	 *
	 * @var Common_BO
	 */
	protected $common;

	/**
	 * Destination xml writer
	 */
	protected $writer;

	public function __construct(/*resource*/ $xml_writer = null, array $options = array()) {
		if($xml_writer) {
			$this->writer = $xml_writer;
			xmlwriter_start_document($this->writer);
		}

		$this->common = CreateObject('sitemgr.Common_BO');
	}

	// Functions for supporting importexport
	public function export($_stream, importexport_definition $_definition) {

		// Not the best way to do it, but I don't know how to get it to write to the stream
		$this->writer = xmlwriter_open_memory();

		xmlwriter_start_document($this->writer);
		$site_id = $_definition->plugin_options['selection']['site_id'];
		foreach((array)$site_id as $id) {
			$this->export_record($site_id);
		}
		xmlwriter_end_document($this->writer);

		// Not the best, but it needs to get into that stream
		fwrite($_stream, xmlwriter_flush($this->writer));

	}

	public static function get_name() {
		return lang('Site export');
	}

	public static function get_description() {
		return lang('Export a SiteMgr site');
	}

	public static function get_filesuffix() {
		return 'xml';
	}

	/**
	 * returns mime type for exported file
	 *
	 * @return string mimetype
	 */
	public static function get_mimetype() {
		return 'application/xml';
	}

	/**
	 * return etemplate components for options.
	 * @abstract We can't deal with etemplate objects here, as an uietemplate
	 * objects itself are scipt orientated and not "dialog objects"
	 *
	 * @return array (
	 *              name            => string,
	 *              content         => array,
	 *              sel_options     => array,
	 *              readonlys       => array,
	 *              preserv         => array,
	 * )
	 */
	public function get_options_etpl() {
		return array();
	}

	/**
	 * returns etemplate name for slectors of this plugin
	 *
	 * @return array (
	 *              name            => string,
	 *              content         => array,
	 *              sel_options     => array,
	 *              readonlys       => array,
	 *              preserv         => array,
	 * )
	 */
	public function get_selectors_etpl() {
		$sites = ExecMethod('sitemgr.Sites_BO.list_sites');
		$site_list = array();
		foreach($sites as $site) {
			$site_list[$site['site_id']] = $site['site_name'];
		}
		return array(
			'name'	=>	'sitemgr.export.selection',
			'sel_options'	=> array(
				'site_id' => $site_list
			)
		);
	}

	// Actual building of XML
	public function export_record($site_id) {
		$site = $this->common->sites->read($site_id);
		$this->common->sites->set_currentsite($site['site_url'], 'Administration');

		xmlwriter_start_element($this->writer, 'site');

		$comment = "Export of eGroupWare SiteMgr website '{$site['site_name']}' on " . date('Y-m-d');
		xmlwriter_write_comment($this->writer, $comment);
		xmlwriter_write_element($this->writer, 'name', $site['site_name']);
		xmlwriter_write_element($this->writer, 'url', $site['site_url']);
		xmlwriter_write_element($this->writer, 'directory', $site['site_dir']);
		xmlwriter_write_element($this->writer, 'themesel', $site['themesel']);
		xmlwriter_write_element($this->writer, 'default_theme', $site['default_theme']);
		xmlwriter_write_element($this->writer, 'anonymous_user', $site['anonymous_user']);
		xmlwriter_write_element($this->writer, 'anonymous_password', $site['anonymous_passwd']);
		xmlwriter_write_element($this->writer, 'upload_directory', $site['upload_directory']);
		xmlwriter_write_element($this->writer, 'htaccess_rewrite', $site['htaccess_rewrite']);
		xmlwriter_write_element($this->writer, 'logo_url', $site['logo_url']);
		xmlwriter_start_element($this->writer, 'custom_css');
		xmlwriter_write_cdata($this->writer, $site['custom_css']);
		xmlwriter_end_element($this->writer);
		xmlwriter_write_element($this->writer, 'params_ini', $site['params_ini']);

		$langs = $site['sitelanguages'];
		foreach($langs as $lang) {
			xmlwriter_start_element($this->writer, 'name');
			xmlwriter_write_attribute($this->writer, 'lang', $lang);
			xmlwriter_text($this->writer, $site["site_name_$lang"]);
			xmlwriter_end_element($this->writer);
			xmlwriter_start_element($this->writer, 'description');
			xmlwriter_write_attribute($this->writer, 'lang', $lang);
			xmlwriter_text($this->writer, $site["site_desc_$lang"]);
			xmlwriter_end_element($this->writer);
		}

		// Export home page name
		if($site['home_page_id'] && ($page = $this->common->pages->getPage($site['home_page_id'])))
		{
			xmlwriter_write_element($this->writer, 'home_page', $page->name);
		}

		$this->common->cats->setcurrentcats();

		// A site is a special category
		$this->export_category($site_id, False);

		xmlwriter_end_element($this->writer); // End site
	}

	protected function export_category($cat_id, $include_cat = True) {

		// Category info
		$cat = $this->common->cats->getCategory($cat_id, False, True);
		if($cat && $include_cat) {
			$lang_array = $this->common->cats->getlangarrayforcategory($cat_id);

			xmlwriter_start_element($this->writer, 'category');
			xmlwriter_write_element($this->writer, 'sort_order', $cat->sort_order);
			xmlwriter_write_element($this->writer, 'state', $cat->state);

			foreach($lang_array as $lang) {
				$lang_cat = $this->common->cats->getCategory($cat_id, $lang, True);
				xmlwriter_start_element($this->writer, 'name');
				xmlwriter_write_attribute($this->writer, 'lang', $lang);
				xmlwriter_text($this->writer, $lang_cat->name);
				xmlwriter_end_element($this->writer);
				xmlwriter_start_element($this->writer, 'description');
				xmlwriter_write_attribute($this->writer, 'lang', $lang);
				xmlwriter_text($this->writer, $lang_cat->description);
				xmlwriter_end_element($this->writer);
			}
			// eporting indexpage name
			if ($cat->index_page_id && ($page = $this->common->pages->getPage($cat->index_page_id)))
			{
				xmlwriter_write_element($this->writer, 'index_page', $page->name);
			}

			// exporting ACL
			xmlwriter_start_element($this->writer, 'permissions');
			if (is_null($acl_bo)) $acl_bo = new ACL_BO; //CreateObject('sitemgr.ACL_BO');
			foreach($acl_bo->get_permission_list($cat_id) as $account_id => $rights)
			{
				if ($rights && ($account_lid = $GLOBALS['egw']->accounts->id2name($account_id)))
				{
					xmlwriter_start_element($this->writer, $account_id < 0 ? 'group' : 'user');
					xmlwriter_write_attribute($this->writer, 'rights', $rights);
					xmlwriter_text($this->writer, $account_lid);
					xmlwriter_end_element($this->writer);
				}
			}
			xmlwriter_full_end_element($this->writer); // End permissions
		}

		// Sub-categories
		$categories = $this->common->cats->getpermittedcats($cat_id, 'read', false);
		if(count($categories) > 0) {
			xmlwriter_start_element($this->writer, 'categories');
			foreach($categories as $cat) {
				$this->export_category($cat);
			}
			xmlwriter_full_end_element($this->writer); // End categories
		}

		// Category wide blocks
		$blocks = $this->common->content->getblocksforscope($cat_id, 0);
		if(count($blocks) > 0) {
			xmlwriter_start_element($this->writer, 'blocks');
			foreach($blocks as $block) {
				$this->export_block($block);
			}
			xmlwriter_full_end_element($this->writer); // End blocks
		}

		// Pages
		$pages = $this->common->pages->getPageIDList($cat_id);
		if(count($pages) > 0) {
			xmlwriter_start_element($this->writer, 'pages');

			foreach($pages as $page_id) {
				$this->export_page($page_id);
			}
			xmlwriter_end_element($this->writer); // End pages
		}
		// Close tag
		if($include_cat) {
			xmlwriter_full_end_element($this->writer); // End category
		}
	}

	protected function export_page($page_id) {
		xmlwriter_start_element($this->writer, 'page');
		$page = $this->common->pages->getPage($page_id);

		xmlwriter_write_element($this->writer, 'name', $page->name);
		xmlwriter_write_element($this->writer, 'sort_order', $page->sort_order);
		xmlwriter_write_element($this->writer, 'hidden', $page->hidden);
		xmlwriter_write_element($this->writer, 'state', $page->state);

		$langs = $this->common->pages->getlangarrayforpage($page_id);
		foreach($langs as $lang) {
			$page = $this->common->pages->getPage($page_id, $lang);
			xmlwriter_start_element($this->writer, 'title');
			xmlwriter_write_attribute($this->writer, 'lang', $lang);
			xmlwriter_text($this->writer, $page->title);
			xmlwriter_end_element($this->writer);
			xmlwriter_start_element($this->writer, 'subtitle');
			xmlwriter_write_attribute($this->writer, 'lang', $lang);
			xmlwriter_text($this->writer, $page->subtitle);
			xmlwriter_end_element($this->writer);
		}

		$blocks = $this->common->content->getblocksforscope($page->cat_id, $page->id);
		if(count($blocks) > 0) {
			xmlwriter_start_element($this->writer, 'blocks');
			foreach($blocks as $block) {
				$this->export_block($block);
			}
			xmlwriter_full_end_element($this->writer); // End blocks
		}

		xmlwriter_end_element($this->writer); // End page
	}

	protected function export_block(Block_SO $block) {
		$langs = $this->common->content->getlangarrayforblocktitle($block->id);
		$block = $this->common->content->getblock($block->id, True);
		$versions = $this->common->content->getallversionsforblock($block->id, false);
		$block->arguments = $this->common->content->getversion(end(array_keys($versions)), false);
		xmlwriter_start_element($this->writer, 'block');
		xmlwriter_write_element($this->writer, 'area', $block->area);
		xmlwriter_write_element($this->writer, 'module', $block->module_name);
		if($block->arguments) {
			xmlwriter_start_element($this->writer, 'arguments');
			xmlwriter_write_cdata($this->writer, serialize($block->arguments));
			xmlwriter_end_element($this->writer);
		}
		xmlwriter_write_element($this->writer, 'sort_order', $block->sort_order);
		xmlwriter_write_element($this->writer, 'view', $block->view);

		// Titles
		foreach($langs as $lang) {
			$block = $this->common->content->getblock($block->id, $lang);
			$title = $this->common->content->getlangblocktitle($block->id, $lang);
			xmlwriter_start_element($this->writer, 'title');
			xmlwriter_write_attribute($this->writer, 'lang', $lang);
			xmlwriter_text($this->writer, $title);
			xmlwriter_end_element($this->writer);
		}

		// Contents
		xmlwriter_start_element($this->writer, 'contents');
		foreach($langs as $lang) {
			$contents = $this->common->content->getallversionsforblock($block->id, $lang);
			foreach($contents as $content) {
				$this->export_content($block->id, $content, $lang);
			}
		}
		xmlwriter_end_element($this->writer); // End content
		xmlwriter_end_element($this->writer); // End block
	}

	protected function export_content($block_id, $content, $lang) {
		xmlwriter_start_element($this->writer, 'content');
		xmlwriter_write_attribute($this->writer, 'lang', $lang);
		xmlwriter_write_element($this->writer, 'state', $content['state']);

		// A little mangling to avoid problems in importing - separate the block arguments from the lang arguments
		$block = $this->common->content->getblock($block_id, $lang);
		$versions = $this->common->content->getallversionsforblock($block->id, false);
		$block->arguments = $this->common->content->getversion(end(array_keys($versions)), false);
		$arguments = $this->common->content->getversion($content['id'], $lang);
		if($block->arguments) {
			$arguments = array_diff($arguments, $block->arguments);
		}
		if(array_key_exists(0, $arguments) && !$arguments[0]) {
			// Not sure where this comes from, but it's not in the DB
			unset($arguments[0]);
		}

		if($arguments) {
			xmlwriter_start_element($this->writer, 'arguments');
			// export html blocks without serializing (as we do in the database), to allow eg. manual editing
			if (count($arguments) == 1 && isset($arguments['htmlcontent']))
			{
				xmlwriter_write_cdata($this->writer, $arguments['htmlcontent']);
			}
			else
			{
			xmlwriter_write_cdata($this->writer, serialize($arguments));
			}
			xmlwriter_end_element($this->writer);
		}
		xmlwriter_end_element($this->writer);
	}

        /**
         * destructor
         *
         * @return
         */
        public function __destruct( ) {
		if($this->writer) {
			xmlwriter_end_document($this->writer);
		}
	}
}
