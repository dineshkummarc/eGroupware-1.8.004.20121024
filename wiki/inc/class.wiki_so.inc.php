<?php
/**
 * eGroupware Wiki - DB-Layer
 *
 * originaly based on WikkiTikkiTavi tavi.sf.net and www.axisgroupware.org:
 * former files lib/pagestore.php + lib/page.php
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.wiki_so.inc.php 38777 2012-04-04 08:55:18Z leithoff $
 */

define ('WIKI_ACL_ALL',0);		// everyone incl. anonymous
define ('WIKI_ACL_USER',1);		// everyone BUT anonymous
define ('WIKI_ACL_ADMIN',2);	// only admins (access to the admin app !)

/**
 * Class representing a wiki-page, usualy gets instanciated via sowiki::page()
 */
class soWikiPage
{
	var $name = '';                       // Name of page.
	var $title = '';                      // Title of page.
	var $text = '';                       // Page's text in wiki markup form.
	var $time = '';                       // Page's modification time.
	var $hostname = '';                   // Hostname of last editor.
	var $username = '';                   // Username of last editor.
	var $comment  = '';                   // Description of last edit.
	var $version = -1;                    // Version number of page.
	var $mutable = 1;                     // Whether page may be edited (depricated !)
	var $readable = WIKI_ACL_ALL;         // who can read the page
	var $writable = WIKI_ACL_ALL;         // who can write the page
	var $exists = 0;                      // Whether page already exists.
	var $db; /* @var $db db */            // Database object.
	var $PgTbl;
	var $colNames = array(                // column-name - class-var-name pairs
		'wiki_id'   	 => 'wiki_id',
		'wiki_name'      => 'name',
		'wiki_lang'      => 'lang',
		'wiki_version'   => 'version',
		'wiki_time'      => 'time',
		'wiki_supercede' => 'supercede',
		'wiki_readable'  => 'readable',
		'wiki_writable'  => 'writable',
		'wiki_hostname'  => 'hostname',
		'wiki_username'  => 'username',
		'wiki_comment'   => 'comment',
		'wiki_title'     => 'title',
		'wiki_body'      => 'text',
	);
	var $debug = 0;	// overwritten by constructor, set it in the sowiki class, not here

	/**
	 * Constructor of the soWikiPage class, gets instanciated via the soWiki::page function
	 *
	 *  @param object $db db-object
	 *  @param string $PgTbl name of pages-table
	 *  @param string $name name of the wiki-page
	 *  @param string/boolean $lang requested language or False
	 *  @param int $wiki_id which wiki to use
	 *  @param int $debug debug-value
	 */
	function soWikiPage($db,$PgTbl,$name = '',$lang=False,$wiki_id=0,$debug=0)
	{
		$this->db = $db;		// to have an independent result-pointer
		$this->db->set_app('wiki');
		$this->PgTbl = $PgTbl;

		$this->name = $name;
		$this->lang = $lang;
		$this->wiki_id = (int) $wiki_id;
		$this->memberships = $GLOBALS['egw']->accounts->membership();
		foreach($this->memberships as $n => $data)
		{
			$this->memberships[$n] = (int) $data['account_id'];
		}
		$this->user_lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
		$this->use_langs = array($this->user_lang,'');
		// english as fallback, should be configurable or a pref
		if ($this->user_lang != 'en') $this->use_langs[] = 'en';
		$this->lang_priority_sql  = "CASE WHEN wiki_body IS NULL THEN ".(count($this->use_langs)+1).' ELSE (CASE wiki_lang';

		foreach($this->use_langs as $order => $lang)
		{
			$this->lang_priority_sql .= ' WHEN '.$this->db->quote($lang)." THEN $order";
		}
		$this->lang_priority_sql  .= ' ELSE '.count($this->use_langs).' END) END AS lang_priority';

		// $GLOBALS['config'] is set by lib/init
		if (!is_array($GLOBALS['config']))
		{
			$c =& CreateObject('phpgwapi.config','wiki');
			$c->read_repository();
			$GLOBALS['config'] = $c->config_data;
			unset($c);
		}
		$this->config = &$GLOBALS['config'];
	}

	/**
	 * filter to and into query to get only readable / writeable page of current user
	 *
	 * @param boolean $readable generate SQL for readable or writable filter, default True == readable
	 * @param boolean $add_wiki_id add code to filter only the actual wiki
	 * @param string $table='' table to prefix the column, default none
	 * @return string SQL to AND into the query
	 */
	function acl_filter($readable = True,$add_wiki_id=True,$table='')
	{
		static $filters = array();

		$filter_id = "$readable-$add_wiki_id";
		if (isset($filters[$filter_id]))
		{
			return $filters[$filter_id];
		}
		$user = $GLOBALS['egw_info']['user']['account_id'];

		$filter = array(WIKI_ACL_ALL);

		if ($GLOBALS['egw_info']['user']['account_lid'] !=  $GLOBALS['config']['AnonymousUser'])
		{
			$filter[] = WIKI_ACL_USER;
		}
		if (@$GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$filter[] = WIKI_ACL_ADMIN;
		}
		$filter = array_merge($filter,$this->memberships);

		$sql = '('.($add_wiki_id ? " wiki_id=$this->wiki_id AND " : '').
			($table ? $table.'.' : '');
		if($readable)
		{
			$sql .= 'wiki_readable IN ('.implode(',',$filter).')';
		}
		// Writable implies readable
		$sql .= ($readable ? ' OR ' : ' ') . 'wiki_writable IN ('.implode(',',$filter).'))';
		if ($this->debug) echo "<p>sowiki::acl_filter($readable,$add_wiki_id) = '$sql'</p>\n";

		return $filters[$filter_id] = $sql;
	}

	/**
	 * check if page is readable or writeable by the current user
	 *
	 * If we have an anonymous session and the anonymous session-type is NOT editable,
	 * all pages are readonly (even if their own setting is editable by all) !!!
	 *
	 * @param boolean $readable=false check if page is readable or writable, default False == writeable
	 * @return boolean true if check was successful, false otherwise
	 */
	function acl_check($readable = False)
	{
		if (!$this->time) $this->read(true);	// read the page (ignoring acl), if we have not done so

		//echo "soWikiPage::acl_check(".($readable?'readable':'writeable').") $this->name ($this->time/$this->version): readable=$this->readable, writable=$this->writable</p>\n";
		if (!$readable && $this->config['Anonymous_Session_Type'] != 'editable' &&
			$GLOBALS['egw_info']['user']['account_lid'] == $this->config['anonymous_username'])
		{
			return False;	// Global config overrides page-specific setting
		}

		$writable = False;
		switch($this->writable) {
			case WIKI_ACL_ALL:
				$writable = True;
				break;
			case WIKI_ACL_USER:
				$writable = $GLOBALS['egw_info']['user']['account_lid'] !=  $this->config['anonymous_username'];
				break;
			case WIKI_ACL_ADMIN:
				$writable = isset($GLOBALS['egw_info']['user']['apps']['admin']);
				break;
			default:
				$writable =  in_array($this->writable, $this->memberships);
		}
		if(!$readable) return $writable;

		// Writable implies readable
		switch ($this->readable)
		{
			case WIKI_ACL_ALL:
				return True;

			case WIKI_ACL_USER:
				return $writable || ($GLOBALS['egw_info']['user']['account_lid'] !=  $this->config['anonymous_username']);

			case WIKI_ACL_ADMIN:
				return $writable || isset($GLOBALS['egw_info']['user']['apps']['admin']);

			default:
				return $writable || in_array($this->readable,$this->memberships);
		}
		return False;
	}

	/**
	 * Returns the class-vars belonging direct to the wiki-page as an array
	 *
	 * @return array
	 */
	function as_array()
	{
		$arr = array();
		foreach($this->colNames as $name)
		{
			$arr[$name] = $this->$name;
		}
		return $arr;
	}

	/**
	 * Check if the page, which name, lang was set in the constructor, exists.
	 *
	 * @return boolean true if page exists in database, false otherwise
	 */
	function exists()
	{
		$this->db->select($this->PgTbl,'wiki_lang',$where=array(
				'wiki_id' => $this->wiki_id,
				'wiki_name'	=> $this->name,
				'wiki_lang' => $this->use_langs,
				$this->acl_filter(),
				'wiki_time=wiki_supercede',	// only check the current version!
			),__LINE__,__FILE__);
		$ret = $this->db->next_record() ? ($this->db->f(0) ? $this->db->f(0) : 'default')  : False;
		//echo "<p>exists() where=".print_r($where,true)."=$ret</p>\n";
		return $ret;
	}

	/**
	 * Read in a page contents, name and lang was set in the constructor
	 *
	 * @param boolean $ignore_acl=false should the page read, even if we have no access-rights, default no
	 * @return array/boolean contents of the page or False.
	 */
	function read($ignore_acl=false)
	{
		$where = array(
			'wiki_id' => $this->wiki_id,
			'wiki_name'	=> $this->name,
			'wiki_lang' => !empty($this->lang) ? $this->lang : $this->use_langs,
		);
		if (!$ignore_acl) $where[] = $this->acl_filter();

		if($this->version != -1)
		{
			$where['wiki_version'] = $this->version;
		}
		else
		{
			$where[] = 'wiki_supercede=wiki_time';	// gives the up-to-date version only
		}
		$this->db->select($this->PgTbl,"*,$this->lang_priority_sql",$where,__LINE__,__FILE__,false,'ORDER BY lang_priority, wiki_version DESC');

		if (!$this->db->next_record())
		{
			return False;
		}
		foreach($this->colNames as $dbname => $name)
		{
			$this->$name     = $this->db->f($dbname);
		}
		$this->exists   = 1;
		$this->mutable  = $this->acl_check();

		return $this->text;
	}

	/**
	 * Write the a page's contents to the db and sets the supercede-time of the prev. version
	 *
	 * The caller is responsible for performing locking.
	 */
	function write()
	{
		$this->time = $this->supercede = time();
		foreach($this->colNames as $dbname => $name)
		{
			$arr[$dbname] = $this->$name;
		}
		if (is_null($arr['wiki_comment'])) $arr['wiki_comment'] = '';	// can not be null

		if (empty($this->text))	unset($arr['wiki_body']);	// deleted / empty pages are written as SQL NULL

		$this->db->insert($this->PgTbl,$arr,false,__LINE__,__FILE__);

		if($this->version > 1)	// set supercede-time of prev. version
		{
			$this->db->update($this->PgTbl,array(
					'wiki_supercede' => $this->supercede
				),array(
					'wiki_id' => $this->wiki_id,
					'wiki_name' => $this->name,
					'wiki_lang' => $this->lang,
					'wiki_version' => $this->version-1
				),__LINE__,__FILE__);
		}
	}

	/**
	 * Renames a page to a new name and/or lang
	 *
	 * The caller is responsible for performing locking.
	 * @param string/boolean $new_name to rename to or false if only a new language, default false
	 * @param string/boolean $new_lang to rename to or false if only a new name, default false
	 * @return int affected rows, 1=success, 0=not found
	 */
	function rename($new_name=False,$new_lang=False)
	{
		if ($new_name === False && $new_lang === False || !$this->acl_check())
		{
			if ($this->debug) echo "soWikiPage::rename('$new_name','$new_lang') returning False this=<pre>".print_r($this->as_array(),True)."</pre>";
			return False;	// nothing to do or no permission
		}
		$new = array(
			'wiki_id' => $this->wiki_id,
			'wiki_name'    => $new_name === False ? $this->name : $new_name,
			'wiki_lang'    => $new_lang === False ? $this->lang : $new_lang,
		);
		// delete (evtl.) existing target
		$this->db->delete($this->PgTbl,$new,__LINE__,__FILE__);

		$this->db->update($this->PgTbl,$new,array(
				'wiki_id' => $this->wiki_id,
				'wiki_name' => $this->name,
				'wiki_lang' => $this->lang,
			),__LINE__,__FILE__);

		if ($this->debug) echo "<p>soWikiPage::rename('$new_name','$new_lang') old='$this->name:$this->lang', sql='$sql', sql2='$sql2'</p>";

		if ($new_name !== False) $this->name = $new_name;
		if ($new_lang !== False) $this->lang = $new_lang;

		return $this->db->affected_rows();
	}

	/**
	 * Returns colNames array, which translates column-names to internal names
	 *
	 * @return array with column-names as keys
	 */
	function column2names()
	{
		return $this->colNames;
	}
}

/**
 * Wiki's storage-object was former called pageStore in WikiTikiTavi
 * @class sowiki
 * @author RalfBecker-AT-outdoor-training.de
 * @license GPL
 */
class wiki_so	// DB-Layer
{
	/**
	 * private instance of the db class
	 *
	 * @var egw_db
	 */
	var $db;
	var $LkTbl = 'egw_wiki_links';
	var $PgTbl = 'egw_wiki_pages';
	var $RtTbl = 'egw_wiki_rate';
	var $IwTbl = 'egw_wiki_interwiki';
	var $SwTbl = 'egw_wiki_sisterwiki';
	var $RemTbl= 'egw_wiki_remote_pages';
	var $ExpireLen,$Admin;
	var $RatePeriod,$RateView,$RateSearch,$RateEdit;
	var $wiki_id = 0;
	var $colNames=false;	// array converting column-names to internal names, set on the first call to sowiki::page
	var $debug = 0;

	/**
	 * Constructor of the PageStrore class sowiki
	 *
	 * @param int $wikid_id which wiki to use, default 0
	 */
	function __construct($wiki_id=0)
	{
		$this->wiki_id = (int) $wiki_id;
		$this->user_lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];

		$this->db = clone($GLOBALS['egw']->db);
		$this->db->set_app('wiki');

		global $ExpireLen,$Admin;		// this should come from the app-config later
		global $RatePeriod, $RateView, $RateSearch, $RateEdit;
		$this->ExpireLen  = $ExpireLen;
		$this->Admin      = $Admin;
		$this->RatePeriod = $RatePeriod;
		$this->RateView   = $RateView;
		$this->RateSearch = $RateSearch;
		$this->RateEdit   = $RateEdit;
	}

	function sowiki($wiki_id=0)
	{
		self::__construct($wiki_id);
	}

	/**
	 * Create a page object / instanciate the soWikiPage class.
	 * @param string $name name of the page
	 * @param string/boolean $lang language or false for the users default language-order
	 * @return object soWikiPage class of the page
	*/
	function &page($name = '',$lang=False)
	{
		if ($this->debug) echo "<p>sowiki::page(".print_r($name,True).",'$lang')</p>";

		if (is_array($name))
		{
			$lang = $lang ? $lang : @$name['lang'];
			$name = @$name['name'] ? $name['name'] : @$name['title'];
		}
		$page = new soWikiPage($this->db,$this->PgTbl,$name,$lang,$this->wiki_id,$this->debug);

		if (!$this->colNames) $this->colNames = $page->column2names();

		return $page;
	}


	/**
	 * Returns the SQL to retrive the length of the body-column
	 *
	 * MaxDB cant calculate the length of the content of a LONG column, we set it to 1,
	 * we could retrive the complete column and use strlen on it, I dont do it as the length is only for sorting
	 * via a macro and that macro retrives all pages(!) - never used that macro ;-)
	 *
	 * @param string $table table-name of join alias incl. '.', or '' (default)
	 * @return string the SQL
	 */
	function length_sql($table='')
	{
		if ($this->db->Type == 'maxdb' || $this->db->Type == 'sapdb')
		{
			return '1';
		}
		return 'LENGTH('.$table.'wiki_body)';
	}

	/**
	 * Find $text in the database, searches title and body.
	 *
	 * @param string $text pattern to search
	 * @param string/boolean $search_in comma-separated string with columns to search (name,title,body) or false to search all three for "%text%" (!)
	 * @return array of wiki-pages (array with column-name / value pairs)
	 */
	function find($text,$search_in=False)
	{
		$sowikipage = new soWikiPage($this->db,$this->PgTbl);
		$sql="SELECT t1.wiki_name,t1.wiki_lang,t1.wiki_version,MAX(t2.wiki_version) as wiki_max,t1.wiki_title,t1.wiki_body".
			" FROM $this->PgTbl AS t1, (select wiki_id,wiki_lang,wiki_name, max(wiki_version) as wiki_version  from $this->PgTbl GROUP BY wiki_id, wiki_lang, wiki_name) AS t2".
			" WHERE t1.wiki_name=t2.wiki_name AND t1.wiki_lang=t2.wiki_lang AND t1.wiki_id=$this->wiki_id AND t2.wiki_id=$this->wiki_id".
			"  AND ".$sowikipage->acl_filter(true,false,'t1').	// only include pages we are allowed to read!
			" GROUP BY t1.wiki_name,t1.wiki_lang,t1.wiki_version,t1.wiki_title,t1.wiki_body".
			" HAVING t1.wiki_version=MAX(t2.wiki_version) AND (";

		// fix for case-insensitiv search on pgsql for lowercase searchwords
		$op_text = !preg_match('/[A-Z]/',$text) ? $this->db->capabilities['case_insensitive_like'] : 'LIKE';
		$op_text .= ' '.$this->db->quote($search_in ? $text : "%$text%");

		$search_in = $search_in ? explode(',',$search_in) : array('wiki_name','wiki_title','wiki_body');

		if (!$this->db->capabilities['like_on_text'])
		{
			$search_in = array_intersect($search_in,array('wiki_name','wiki_title'));
		}
		foreach($search_in as $n => $name)
		{
			$sql .= ($n ? ' OR ' : '') . "t1.$name $op_text";
		}
		$sql .= ')';

		$this->db->query($sql,__LINE__,__FILE__);

		return $this->_return_pages("find('$text','".implode(',',$search_in)."'");
	}

	/**
	 * Retrieve a page's edit history.
	 *
	 * @param string/array $page name of the page or array with values for keys 'name' and 'lang'
	 * @param string/boolean $lang language to use or false if given via array in $name, default false
	 * @return an array of the different versions
	 */
	function history($page,$lang=False)
	{
		$name = $this->db->db_addslashes(is_array($page) ? $page['name'] : $page);
		$lang = $this->db->db_addslashes(is_array($page) && !$lang ? $page['lang'] : $lang);

		$this->db->select($this->PgTbl,'wiki_time,wiki_hostname,wiki_version,wiki_username,wiki_comment',array(
				'wiki_name'	=> is_array($page) ? $page['name'] : $page,
				'wiki_lang'	=> is_array($page) && !$lang ? $page['lang'] : $lang,
				'wiki_id'	=> $this->wiki_id,
			),__LINE__,__FILE__,False,'ORDER BY wiki_version DESC');

		return $this->_return_pages('history('.print_r($page,True).",'$lang')");
	}

	/**
	 * Look up an interwiki prefix
	 *
	 * @param string $name name-prefix of an interwiki
	 * @return string/boolean the url of False
	 */
	function interwiki($name)
	{
		$this->db->select($this->IwTbl,'interwiki_url',array(
				'wiki_id' => $this->wiki_id,
				'interwiki_prefix'  => $name,
			),__LINE__,__FILE__);

		return $this->db->next_record() ? $this->db->f('url') : False;
	}

	/**
	 * Clear all the links cached for a particular page.
	 *
	 * @param string/array $page page-name or array with values for wiki_id, name and lang keys
	 */
	function clear_link($page)
	{
		if ($this->debug) echo "<p>sowiki::clear_link(".print_r($page,true)."</p>\n";

		$this->db->delete($this->LkTbl,array(
			'wiki_id' => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
			'wiki_name'    => trim(is_array($page) ? $page['name'] : $page),
			'wiki_lang'    => $page['lang'],
		),__LINE__,__FILE__);
	}

	/**
	 * Clear all the interwiki definitions for a particular page.
	 *
	 * @param string/array $page page-name or array with values for wiki_id, name and lang keys
	 */
	function clear_interwiki($page)
	{
		if ($this->debug) echo "<p>sowiki::clear_interwiki(".print_r($page,true)."</p>\n";

		$this->db->delete($this->IwTbl,array(
			'wiki_id' => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
			'wiki_name'    => is_array($page) ? $page['name'] : $page,
			'wiki_lang'    => $page['lang'],
		),__LINE__,__FILE__);
	}

	/**
	 * Clear all the sisterwiki definitions for a particular page.
	 *
	 * @param string/array $page page-name or array with values for wiki_id, name and lang keys
	 */
	function clear_sisterwiki($page)
	{
		if ($this->debug) echo "<p>sowiki::clear_sisterwiki(".print_r($page,true)."</p>\n";

		$this->db->delete($this->SwTbl,array(
			'wiki_id' => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
			'wiki_name'    => is_array($page) ? $page['name'] : $page,
			'wiki_lang'    => $page['lang'],
		),__LINE__,__FILE__);
	}

	/**
	 * Add a link for a given page to the link table.
	 *
	 * @param string/array $page page-name or array with values for wiki_id, name and lang keys
	 * @param string $link the link to add
	 */
	function new_link($page, $link)
	{
		static $links = array();
		if (stripos($link,'webdav.php') !== false) return false; // webdav links are no wiki links, and the link table is for wiki link lookup only
		if (is_array($page))
		{
			$page['name']=trim($page['name']);
		} else {
			$page = trim($page);
		}
		$where = array(
			'wiki_id' => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
			'wiki_name'    => is_array($page) ? $page['name'] : $page,
			'wiki_lang'    => $page['lang'],
			'wiki_link'    => trim($link),
		);
		// $links need to be 2-dimensional as rename, can cause new_link to be called for different pages
		$page_uid = strtolower($where['wiki_id'].':'.$where['wiki_name'].':'.$where['wiki_lang']);
		$link = strtolower(trim($link));

		$data = array('wiki_count' => ++$links[$page_uid][$link]);
		//error_log(__METHOD__.__LINE__.' link 2 insert:'.trim($link));
		if ($this->debug) echo "<p>sowiki::new_link('$where[wiki_id]:$where[wiki_name]:$where[wiki_lang]','$link') = $data[wiki_count]</p>";
		if ($data['wiki_count'] == 1)
		{
			$this->db->insert($this->LkTbl,array_merge($data,$where),False,__LINE__,__FILE__);
		}
		else
		{
			$this->db->update($this->LkTbl,$data,$where,__LINE__,__FILE__);
		}
	}

	/**
	 * Retrives all links on all pages and all languages
	 *
	 * @param string $link if none-empty, only these links are retrived
	 * @return array 2-dim. array with linking pages and languages, eg. $arr[$page][$lang] = $link
	 */
	function get_links($link='')
	{
		$where = array('wiki_id' => $this->wiki_id);
		if ($link)
		{
			$where['wiki_link'] = $link;
		}
		$this->db->select($this->LkTbl,'wiki_name,wiki_lang,wiki_link',$where,__LINE__,__FILE__,false,
			'ORDER BY wiki_name,wiki_lang');

		$result = array();
		while ($row = $this->db->row(True))
		{
			$result[$row['wiki_name']][$row['wiki_lang']][] = $row['wiki_link'];
		}
		return $result;
	}

	/**
	 * Add an interwiki definition for a particular page.
	 *
	 * @param string/array $page page-name or array with values for name, lang and evtl. wiki_id (this->wiki_id is used if not)
	 * @param string $prefix Prefix of the new interwiki
	 * @param string $url URL of the new interwiki
	 */
	function new_interwiki($page, $prefix, $url)
	{
		$this->db->insert($this->IwTbl,array(
				'wiki_name'        => is_array($page) ? $page['name'] : $page,
				'wiki_lang'        => $page['lang'],
				'interwiki_url'    => str_replace('&amp;','&',$url),
			),array(
				'wiki_id'          => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
				'interwiki_prefix' => $prefix,
			),__LINE__,__FILE__);
	}

	/**
	 * Add an sisterwiki definition for a particular page.
	 *
	 * @param string/array $page page-name or array with values for name, lang and evtl. wiki_id (this->wiki_id is used if not)
	 * @param string $prefix Prefix of the new interwiki
	 * @param string $url URL of the new interwiki
	 */
	function new_sisterwiki($page, $prefix, $url)
	{
		$this->db->insert($this->SwTbl,array(
				'wiki_name'        => is_array($page) ? $page['name'] : $page,
				'wiki_lang'        => $page['lang'],
				'interwiki_url'    => str_replace('&amp;','&',$url),
			),array(
				'wiki_id'          => is_array($page) && isset($page['wiki_id']) ? $page['wiki_id'] : $this->wiki_id,
				'interwiki_prefix' => $prefix,
			),__LINE__,__FILE__);
	}

	/**
	 * Find all twins of a page at sisterwiki sites.
	 *
	 * @param string/array $page page-name or array with values for name
	 * @return array list of array(site,page)
	 */
	function twinpages($page)
	{
		$this->db->query("SELECT wiki_remote_site, wiki_remote_page FROM $this->RemTbl WHERE wiki_remote_page=".
			$this->db->quote(is_array($page) ? $page['name'] : $page),__LINE__,__FILE__);

		$list = array();
		while($this->db->next_record())
		{
			$list[] = array(
				'site' => $this->db->f('wiki_remote_site'),
				'page' => $this->db->f('wiki_remote_page'),
			);
		}
		return $list;
	}

	/*
	 * Lock all wiki database tables.
	 */
	function lock()
	{
		$this->db->lock(array($this->PgTbl,$this->IwTbl,$this->SwTbl,$this->LkTbl),'write');
	}

	/*
	 * Unlock all database tables.
	 */
	function unlock()
	{
		$this->db->unlock();
	}

	/*
	 * Retrieve a list of all of the pages in the wiki.
	 *
	 * @return array of all pages
	 */
	function allpages()
	{
		$qid = $this->db->query("SELECT t1.wiki_time,t1.wiki_name,t1.wiki_lang,t1.wiki_hostname,t1.wiki_username,t1.wiki_title,".$this->length_sql('t1.').
														" AS wiki_length,t1.wiki_comment,t1.wiki_version,MAX(t2.wiki_version)" .
														" FROM $this->PgTbl AS t1, (select wiki_id,wiki_lang,wiki_name, max(wiki_version) as wiki_version  from $this->PgTbl GROUP BY wiki_id, wiki_lang, wiki_name) AS t2" .
														" WHERE t1.wiki_name = t2.wiki_name AND t1.wiki_lang=t2.wiki_lang AND t1.wiki_id=t2.wiki_id AND t1.wiki_id=".(int)$this->wiki_id.
														" GROUP BY t1.wiki_name,t1.wiki_lang,t1.wiki_version,t1.wiki_time,t1.wiki_hostname,t1.wiki_username,t1.wiki_body,t1.wiki_comment,t1.wiki_title" .
														" HAVING t1.wiki_version = MAX(t2.wiki_version)",__LINE__,__FILE__);

		return $this->_return_pages('allpages()');
	}

	/**
	 * Create array of page-arrays from the returned rows of a query
	 *
	 * @internal
	 * @param string $func calling function incl. parameters for debug-message
	 */
	function _return_pages($func)
	{
		if (!$this->colNames)
		{
			$page = new soWikiPage($this->db,$this->PgTbl);
			$this->colNames = $page->column2names();
			unset($page);
		}
		$list = array();
		while($this->db->next_record())
		{
			$page = array();
			foreach($this->db->Record as $col => $val)
			{
				$name = isset($this->colNames[$col]) ? $this->colNames[$col] : ($name == 'wiki_length' ? 'length' : $col);
				$page[$name] = $val;
			}
			$list[] = $page;
		}
		if ($this->debug) echo "<p>sowiki::$func<pre>".print_r($list,true)."</pre>\n";

		return $list;
	}

	/*
	 * Retrieve a list of the new pages in the wiki.
	 *
	 * @return array of pages
	 */
	function newpages()
	{
		$this->db->select($this->PgTbl,'wiki_time,wiki_name,wiki_lang,wiki_hostname,wiki_username,'.$this->length_sql().' AS wiki_length,wiki_comment,wiki_title',
			array(
				'wiki_id' => $this->wiki_id,
				'wiki_version=1',
			),__LINE__,__FILE__);

		return $this->_return_pages('newpages()');
	}

	/*
	 * Retrieve a list of all empty (deleted) pages in the wiki.
	 *
	 * @return array of pages
	 */
	function emptypages()
	{
		$this->db->query("SELECT t1.wiki_time,t1.wiki_name,t1.wiki_lang,t1.wiki_hostname,t1.wiki_username,0,t1.wiki_comment,t1.wiki_version,MAX(t2.wiki_version),t1.wiki_title " .
										 " FROM $this->PgTbl AS t1, (select wiki_id,wiki_lang,wiki_name, max(wiki_version) as wiki_version  from $this->PgTbl GROUP BY wiki_id, wiki_lang, wiki_name) AS t2" .
										 " WHERE t1.wiki_name=t2.wiki_name AND t1.wiki_lang=t2.wiki_lang AND t1.wiki_id=t2.wiki_id AND t1.wiki_id=".(int)$this->wiki_id.
										 "  AND t1.wiki_body IS NULL ".
										 " GROUP BY t1.wiki_name,t1.wiki_lang,t1.wiki_version,t1.wiki_time,t1.wiki_hostname,t1.wiki_username,t1.wiki_comment".
										 " HAVING t1.wiki_version = MAX(t2.wiki_version) ",__LINE__,__FILE__);

		return $this->_return_pages('emptypages()');
	}

	/*
	 * Retrieve a list of information about a particular set of pages
	 *
	 * @param array $names array of page-names
	 * @return array of pages
	 */
	function givenpages($names)
	{
		$list = array();
		foreach($names as $page)
		{
			$this->db->select($this->PgTbl,'wiki_time,wiki_name,wiki_hostname,wiki_username,'.$this->length_sql().' AS wiki_length,wiki_comment,wiki_title',array(
				'wiki_name' => $page,
				'wiki_id'	=> $this->wiki_id,
				),__LINE__,__FILE__,False,'ORDER BY wiki_version DESC');

			$list = array_merge($list,$this->_return_pages('givenpages('.@print_r($names,true).')'));
		}
		return $list;
	}

	/**
	 * Expire old versions of pages.
	 */
	function maintain()
	{
		$this->db->delete($this->PgTbl,"(wiki_time!=wiki_supercede OR wiki_body IS NULL) AND ".
			"wiki_supercede<".(time()-86400*$this->ExpireLen),__LINE__,__FILE__);

		if($this->RatePeriod)
		{
			$this->db->delete($this->RtTbl,"wiki_rate_ip NOT LIKE '%.*' AND " .
				intval(time()/86400)." > wiki_rate_time/86400",__LINE__,__FILE__);
		}
	}

	/**
	 * Perform a lookup on an IP addresses edit-rate.
	 *
	 * @param string $type 'view',' search' or 'edit'
	 * @param string $remote_addr eg. $_SERVER['REMOTE_ADDR']
	 */
	function rateCheck($type,$remote_addr)
	{
		//_debug_array(array('type'=>$type,'remoteaddr'=>$remote_addr,'rateperiod'=>$this->RatePeriod,'view'=>$this->RateView,'search'=>$this->RateSearch,'edit'=>$this->RateEdit));
		if(!$this->RatePeriod)
		{
			return;
		}

		$this->db->lock($this->RtTbl,'WRITE');

		// Make sure this IP address hasn't been excluded.

		$fields = explode(".", $remote_addr);
		$this->db->select($this->RtTbl,'*',"wiki_rate_ip='$fields[0].*'".
			" OR wiki_rate_ip='$fields[0].$fields[1].*'".
			" OR wiki_rate_ip='$fields[0].$fields[1].$fields[2].*'",__LINE__,__FILE__);

		if ($this->db->next_record())
		{
			global $ErrorDeniedAccess;
			die($ErrorDeniedAccess);
		}

		// Now check how many more actions we can perform.

		$cols = explode(',','wiki_rate_time,wiki_rate_viewLimit,wiki_rate_searchLimit,wiki_rate_editLimit');
		foreach($cols as &$col)
		{
			$col = $this->db->name_quote("`$col`");	// PostgreSQL requires mixed case names quoted!
		}
		$this->db->select($this->RtTbl,$cols,array(
				'wiki_rate_ip' => $remote_addr
			),__LINE__,__FILE__);

		if(!$this->db->next_record())
		{
			$result = array(-1, $this->RateView, $this->RateSearch, $this->RateEdit);
		}
		else
		{
			$result = $this->db->Record;

			$result[0] = time()-$result[0];
			if ($result[0]  < 0)
			{
				$result[0] = $this->RatePeriod;
			}
			$result[1] = (int)min($result[1] + $result[0] * $this->RateView / $this->RatePeriod,$this->RateView);
			$result[2] = (int)min($result[2] + $result[0] * $this->RateSearch / $this->RatePeriod,$this->RateSearch);
			$result[3] = (int)min($result[3] + $result[0] * $this->RateEdit / $this->RatePeriod,$this->RateEdit);
		}

		switch($type)
		{
			case 'view':	$result[1]--; break;
			case 'search':	$result[2]--; break;
			case 'edit':	$result[3]--; break;
		}

		if($result[1] < 0 || $result[2] < 0 || $result[3] < 0)
		{
			global $ErrorRateExceeded;
			die($ErrorRateExceeded);
		}

		// Record this action.
		$this->db->insert($this->RtTbl, array(
				'wiki_rate_viewLimit'	=> $result[1],	// PostgreSQL requires mixed case names quoted! Mysql does not
				'wiki_rate_searchLimit'	=> $result[2],  // update those columns, as column_data_implode does not find
				'wiki_rate_editLimit'	=> $result[3],  // the quoted column names -> thus the resulting query does not
				'wiki_rate_time'		=> time(),      // have the quoted columns to update
			),array(
				'wiki_rate_ip' => $remote_addr
			),__LINE__,__FILE__);

		$this->db->unlock();
	}

	/**
	 * Return a list of blocked address ranges.
	 *
	 * @return array of blocked address-ranges
	 */
	function rateBlockList()
	{
		$list = array();

		if(!$this->RatePeriod)
		{
			return $list;
		}
		$this->db->select($this->RtTbl,'wiki_rate_ip',false,__LINE__,__FILE__);

		while($this->db->next_record())
		{
			if(preg_match('/^\\d+\\.(\\d+\\.(\\d+\\.)?)?\\*$/',$this->db->f('wiki_rate_ip')))
			{
				$list[] = $this->db->f('wiki_rate_ip');
			}
		}
		return $list;
	}

	/**
	 * Block an address range.
	 *
	 * @param string $address ip-addr. or addr-range
	 */
	function rateBlockAdd($address)
	{
		if(preg_match('/^\\d+\\.(\\d+\\.(\\d+\\.)?)?\\*$/', $address))
		{
			$this->db->select($this->RtTbl,'*',array(
					'wiki_rate_ip' => $address
				),__LINE__,__FILE__);

			if(!$this->db->next_record())
			{
				$this->db->insert($this->RtTbl,array(
						'wiki_rate_ip'	=> $address,
						'wiki_rate_time'=> time(),
					),__LINE__,__FILE__);
			}
		}
	}

	/**
	 * Remove an address-range block.
	 *
	 * @param string $address ip-addr. or addr-range
	 */
	function rateBlockRemove($address)
	{
		$this->db->delete($this->RtTbl,array('wiki_rate_ip' => $address),__LINE__,__FILE__);
	}
}
?>
