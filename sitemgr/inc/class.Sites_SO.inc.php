<?php
/**
 * EGroupware SiteMgr CMS - Site storage object
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @copyright Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.Sites_SO.inc.php 33888 2011-02-23 08:37:11Z ralfbecker $
 */

/**
 * Site storage object
 */
class Sites_SO
{
	/**
	 * Own instance of DB object
	 *
	 * @var egw_db
	 */
	private $db;
	/**
	 * Table name, only reference to db-prefix
	 *
	 * @var string
	 */
	private $sites_table = 'egw_sitemgr_sites';
	/**
	 * Cache last read site on a per request base
	 *
	 * @var array
	 */
	private static $site_cache;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->db = clone($GLOBALS['egw']->db);
		$this->db->set_app('sitemgr');
	}

	/**
	 * Get site_id of all available sites
	 *
	 * @return array
	 */
	public function list_siteids()
	{
		$result = array();
		foreach($this->db->select($this->sites_table,'site_id',False,__LINE__,__FILE__) as $row)
		{
			$result[] = $row['site_id'];
		}
		return $result;
	}

	/**
	 * Get available sites
	 *
	 * @param int $limit
	 * @param int $start
	 * @param string $sort
	 * @param string $order
	 * @param string $query
	 * @param int &$total
	 * @return array site_id => array pairs
	 */
	public function getWebsites($limit,$start,$sort,$order,$query,&$total)
	{
		if ($limit)
		{
			if ($query)
			{
				$query = $this->db->quote('%'.$query.'%');
				$whereclause = "site_name LIKE $query OR site_url LIKE $query";
			}
			if (preg_match('/^[a-z_0-9]+$/i',$order) && preg_match('/^(asc|desc)*$/i',$sort))
			{
				$orderclause = "ORDER BY $order " . ($sort ? $sort : 'DESC');
			}
			else
			{
				$orderclause = 'ORDER BY site_name ASC';
			}
			$total = $this->db->select($this->sites_table,'COUNT(*)',$whereclause,__LINE__,__FILE__)->fetchColumn();

			$rs = $this->db->select($this->sites_table,'site_id,site_name,site_url',$whereclause,__LINE__,__FILE__,$start,$orderclause);
		}
		else
		{
			$rs = $this->db->select($this->sites_table,'site_id,site_name,site_url',False,__LINE__,__FILE__);
		}
		foreach($rs as $site)
		{
			$result[$site['site_id']] = $site;
		}
		return $result;
	}

	/**
	 * Get number of defined sites
	 *
	 * @return int
	 */
	public function getnumberofsites()
	{
		return $this->db->select($this->sites_table,'COUNT(*)',False,__LINE__,__FILE__)->fetchColumn();
	}

	/**
	 * Get site_id from url(s)
	 *
	 * @param string|array $url
	 * @return int
	 */
	public function urltoid($url)
	{
		return $this->db->select($this->sites_table,'site_id',array(
				'site_url' => $url,
			),__LINE__,__FILE__)->fetchColumn();
	}

	/**
	 * Read site (or just url and dir) by site_id
	 *
	 * @param int $site_id
	 * @param boolean $only_url_dir=false
	 * @return array
	 */
	public function read($site_id,$only_url_dir=false)
	{
		if (!is_array(self::$site_cache) || self::$site_cache['site_id'] != $site_id)
		{
			if ((self::$site_cache = $this->db->select($this->sites_table,'*',array(
					'site_id' => $site_id,
				),__LINE__,__FILE__)->fetch()))
			{
				// if we run inside sitemgr, use the script dir as site-dir
				// fixes problems if sitemgr-site directory got moved
				if (isset($GLOBALS['site_id']) && file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/config.inc.php'))
				{
					self::$site_cache['site_dir'] = dirname($_SERVER['SCRIPT_FILENAME']);
				}
				elseif(self::$site_cache['site_dir'] == 'sitemgr'.SEP.'sitemgr-site')
				{
					self::$site_cache['site_dir'] = EGW_SERVER_ROOT.SEP.self::$site_cache['site_dir'];
				}
				// for database schema version < 1.9.002 read logo, css & params from configuration
				if (version_compare($GLOBALS['egw_info']['apps']['sitemgr']['version'], '1.9.002', '<') ||
					is_null(self::$site_cache['logo_url']) && is_null(self::$site_cache['custom_css']) &&
						is_null(self::$site_cache['params_ini']))
				{
					$config = config::read('sitemgr');
					self::$site_cache['logo_url'] = $config['logo_url_'.$site_id];
					self::$site_cache['custom_css'] = $config['custom_css_'.$site_id];
					self::$site_cache['params_ini'] = $config['params_ini_'.$site_id];
				}
			}
		}
		return !$only_url_dir || !self::$site_cache ? self::$site_cache : array(
			'site_url' => self::$site_cache['site_url'],
			'site_dir' => self::$site_cache['site_dir'],
		);
	}

	/**
	 * Read only site_url for given site_id
	 *
	 * @deprecated use read($site_id,true)
	 * @param int $site_id
	 * @return array 'site_url' => $value
	 */
	public function read2($site_id)
	{
		return $this->read($site_id,true);
	}

	/**
	 * Add new site via define websites: name, url, dir, anon user & password
	 *
	 * @param array $site
	 * @return int site_id
	 */
	public function add(array $site)
	{
		$cats = new categories(categories::GLOBAL_ACCOUNT,'sitemgr');
		$site_id =  $cats->add(array(
			'name'		=> $site['name'],
			'descr'		=> '',
			'access'	=> 'public',
			'parent'	=> 0,
			'old_parent' => 0
		));
		$this->db->insert($this->sites_table,array(
				'site_id'   => $site_id,
				'site_name' => $site['name'],
				'site_url'  => $site['url'],
				'site_dir'  => $site['dir'],
				'anonymous_user' => $site['anonuser'],
				'anonymous_passwd' => $site['anonpasswd'],
			),False,__LINE__,__FILE__);

		return $site_id;
	}

	/**
	 * Update data from define websites: name, url, dir, anon user & password
	 *
	 * @param int $site_id
	 * @param array $site
	 * @return int affected rows
	 */
	public function update($site_id,array $site)
	{
		if ($site_id == self::$site_cache['site_id']) self::$site_cache = null;

		return $this->db->update($this->sites_table,array(
				'site_name' => $site['name'],
				'site_url'  => $site['url'],
				'site_dir'  => $site['dir'],
				'anonymous_user' => $site['anonuser'],
				'anonymous_passwd' => $site['anonpasswd'],
			),array(
				'site_id' => $site_id
			),__LINE__,__FILE__);
	}

	/**
	 * Update logo-url, custom css and template parameters for given site_id
	 *
	 * If SiteMgr version is >= 1.9.002 data is stored in sites-table, otherwise
	 * in SiteMgr configuration.
	 *
	 * @param int $site_id
	 * @param array $data
	 * @return int number or updated rows
	 */
	public function update_logo_css_params($site_id,array $data)
	{
		if ($site_id == self::$site_cache['site_id']) self::$site_cache = null;

		// for database schema version < 1.9.002 store as configuration
		if (version_compare($GLOBALS['egw_info']['apps']['sitemgr']['version'], '1.9.002', '<'))
		{
			// store information in sitemgr config
			config::save_value('logo_url_'.$site_id,$data['logo_url'],'sitemgr');
			config::save_value('custom_css_'.$site_id,$data['custom_css'],'sitemgr');
			config::save_value('params_ini_'.$site_id,$data['params_ini'],'sitemgr');

			return 1;
		}
		return $this->db->update($this->sites_table,array(
				'logo_url' => $data['logo_url'],
				'custom_css' => $data['custom_css'],
				'params_ini'  => $data['params_ini'],
			),array(
				'site_id' => $site_id
			),__LINE__,__FILE__);
	}

	/**
	 * Delete a site(s) from sites table
	 *
	 * @param int|array $site_id
	 * @return int number of affected rows
	 */
	public function delete($site_id)
	{
		if ($site_id == self::$site_cache['site_id']) self::$site_cache = null;

		return $this->db->delete($this->sites_table,array(
				'site_id' => $site_id
			),__LINE__,__FILE__);
	}

	/**
	 * Update site preferences of given site_id
	 *
	 * @param array $prefs
	 * @param int|array $site_id=CURRENT_SITE_ID
	 * @return int affected rows
	 */
	public function saveprefs(array $prefs,$site_id=CURRENT_SITE_ID)
	{
		if ($site_id == self::$site_cache['site_id']) self::$site_cache = null;

		return $this->db->update($this->sites_table,array(
				'themesel' => $prefs['themesel'],
				'site_languages' => $prefs['site_languages'],
				'home_page_id' => $prefs['home_page_id'],
				'upload_dir'  => $prefs['upload_dir'],
			),array(
				'site_id' => $site_id
			),__LINE__,__FILE__);
	}

	/**
	 * Save named page as home page
	 *
	 * @param int $site_id
	 * @param int $page
	 */
	function saveHomePage($site_id,$page)
	{
		if ($site_id == self::$site_cache['site_id']) self::$site_cache = null;

		$this->db->update($this->sites_table,array(
				'home_page_id' => $page,
			),array('site_id' => $site_id),__LINE__,__FILE__);
	}
}
