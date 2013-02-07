<?php
/**
 * EGroupware SiteMgr CMS
 *
 * @link http://www.egroupware.org
 * @package sitemgr
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.Common_BO.inc.php 33211 2010-11-30 11:48:48Z ralfbecker $
 */

/**
 * Common BO object, contains links to diverse sub-objects
 */
class Common_BO
{
	/**
	 * Instance of Sites_BO object
	 *
	 * @var Sites_BO
	 */
	public $sites;
	/**
	 * Instance of ACL_BO object
	 *
	 * @var ACL_BO
	 */
	public $acl;
	/**
	 * Instance of Theme_BO object
	 *
	 * @var Theme_BO
	 */
	public $theme;
	/**
	 * Instance of Pages_BO object
	 *
	 * @var Pages_BO
	 */
	public $pages;
	/**
	 * Instance of Categories_BO object
	 *
	 * @var Categories_BO
	 */
	public $cats;
	/**
	 * Instance of Content_BO object
	 *
	 * @var Content_BO
	 */
	public $content;
	/**
	 * Instance of Modules_BO object
	 *
	 * @var Modules_BO
	 */
	public $modules;

	/**
	 * Labels for states: draft, (pre)published, preunpublished, archived
	 *
	 * @var array
	 */
	public $states;
	/**
	 * Labels for viewable by states: everybody, users, admin, anonymous
	 *
	 * @var array
	 */
	public $visiblestates;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->sites   = CreateObject('sitemgr.Sites_BO',True);
		$this->acl     = CreateObject('sitemgr.ACL_BO');
		$this->theme   = CreateObject('sitemgr.Theme_BO');
		$this->pages   = CreateObject('sitemgr.Pages_BO');
		$this->cats    = CreateObject('sitemgr.Categories_BO');
		$this->content = CreateObject('sitemgr.Content_BO');
		$this->modules = CreateObject('sitemgr.Modules_BO');
		$this->state = array(
			SITEMGR_STATE_DRAFT => lang('draft'),
			SITEMGR_STATE_PREPUBLISH => lang('prepublished'),
			SITEMGR_STATE_PUBLISH => lang('published'),
			SITEMGR_STATE_PREUNPUBLISH => lang('preunpublished'),
			SITEMGR_STATE_ARCHIVE => lang('archived'),
		);
		$this->viewable = array(
			SITEMGR_VIEWABLE_EVERBODY => lang('everybody'),
			SITEMGR_VIEWABLE_USER => lang('egw users'),
			SITEMGR_VIEWABLE_ADMIN => lang('administrators'),
			SITEMGR_VIEWABLE_ANONYMOUS => lang('anonymous')
		);
	}

	/**
	 * Default for custom CSS
	 *
	 * Can be overwritten by user (stored in DB) or per template in a 'custom_css_default.css' file
	 *
	 * @var string
	 */
	const CUSTOM_CSS_DEFAULT =
'/**
 * Custom CSS to set logo for joomlart templates
 */
h1.logo a, div#logo, #logo, div#ja-header h1, div#header h1 {
	background: url("$$logo_url$$") no-repeat left center;
}
div#ja-header h1 a img, div#header h1 a img, div#header h1 img {
	visibility: hidden;
}
';

	/**
	 * Get custom css for current site and template
	 *
	 * @param boolean $return_parsed=true return custom css with already inserted logo_url or return css with placeholder
	 * @param string &$logo_url=null on return url of logo
	 * @param int $site_id=null default current site ($this->sites->current_site['site_id'])
	 * @param string $template=null default current site ($this->sites->current_site['themesel'])
	 * @return string custom css
	 */
	function get_custom_css($return_parsed=true,&$logo_url=null,$site_id=null,$template=null)
	{
		if (is_null($site_id))
		{
			$site = $this->sites->current_site;
		}
		else
		{
			$site = $this->sites->read($site_id);
		}
		if (is_null($template)) $template = $site['themesel'];

		$logo_url = $site['logo_url'];
		$custom_css = $site['custom_css'];
		if (empty($custom_css))
		{
			$template_info = $this->theme->getThemeInfos($template);
			if (file_exists($custom_css_file=$template_info['template_dir'].'/custom_css_default.css'))
			{
				$custom_css = file_get_contents($custom_css_file);
			}
		}
		if (empty($custom_css))
		{
			$custom_css = self::CUSTOM_CSS_DEFAULT;
		}
		if ($return_parsed)
		{
			if (empty($logo_url))	// if no logo specified use EGroupware logo
			{
				if (substr($GLOBALS['egw_info']['server']['login_logo_file'],0,4) == 'http' ||
					$GLOBALS['egw_info']['server']['login_logo_file'][0] == '/')
				{
					$logo_url = $GLOBALS['egw_info']['server']['login_logo_file'];
				}
				else
				{
					$logo_url = common::image('phpgwapi',$GLOBALS['egw_info']['server']['login_logo_file'] ?
						$GLOBALS['egw_info']['server']['login_logo_file'] : 'logo');
				}
			}
			$custom_css = str_replace('$$logo_url$$',$logo_url,$custom_css);
		}
		return $custom_css;
	}

	function setvisiblestates($mode)
	{
		$this->visiblestates = $this->getstates($mode);
	}

	function getstates($mode)
	{
		switch ($mode)
		{
			case 'Administration' :
				return array(SITEMGR_STATE_DRAFT,SITEMGR_STATE_PREPUBLISH,SITEMGR_STATE_PUBLISH,SITEMGR_STATE_PREUNPUBLISH);
			case 'Draft' :
				return array(SITEMGR_STATE_PREPUBLISH,SITEMGR_STATE_PUBLISH);
			case 'Edit' :
				return array(SITEMGR_STATE_DRAFT,SITEMGR_STATE_PREPUBLISH,SITEMGR_STATE_PUBLISH,SITEMGR_STATE_PREUNPUBLISH);
			case 'Commit' :
				return array(SITEMGR_STATE_PREPUBLISH,SITEMGR_STATE_PREUNPUBLISH);
			case 'Archive' :
				return array(SITEMGR_STATE_ARCHIVE);
			case 'Production' :
			default:
				return array(SITEMGR_STATE_PUBLISH,SITEMGR_STATE_PREUNPUBLISH);
		}
	}

	function globalize($varname)
	{
		if (is_array($varname))
		{
			foreach($varname as $var)
			{
				$GLOBALS[$var] = $_POST[$var];
			}
		}
		else
		{
			$GLOBALS[$varname] = $_POST[$varname];
		}
	}

	function getlangname($lang)
	{
		return $GLOBALS['egw']->translation->lang2language($lang);
	}

	function inputstateselect($default)
	{
		$returnValue = '';
		foreach($this->state as $value => $display)
		{
			$selected = ($default == $value) ? $selected = 'selected="selected" ' : '';
			$returnValue.='<option '.$selected.'value="'.$value.'">'.
				$display.'</option>'."\n";
		}
		return $returnValue;
	}

	function get_sitemenu()
	{
		if ($GLOBALS['Common_BO']->acl->is_admin())
		{
			$file['Configure Website'] = egw::link('/index.php','menuaction=sitemgr.Common_UI.DisplayPrefs');
			$link_data['cat_id'] = CURRENT_SITE_ID;
			$link_data['menuaction'] = "sitemgr.Modules_UI.manage";
			$file['Manage site-wide module properties'] = egw::link('/index.php',$link_data);
/* not longer show, as it can be done via Edit-mode now
			$link_data['page_id'] = 0;
			$link_data['menuaction'] = "sitemgr.Content_UI.manage";
			$file['Manage site-wide content'] = egw::link('/index.php',$link_data);
*/
			$file['Manage Notifications'] = egw::link('/index.php', 'menuaction=sitemgr.uinotifications.index&site_id='.CURRENT_SITE_ID);
		}
//		$file['Manage Categories and pages'] = egw::link('/index.php', 'menuaction=sitemgr.Outline_UI.manage');
		$file['Manage Translations'] = egw::link('/index.php', 'menuaction=sitemgr.Translations_UI.manage');
		$file['Commit Changes'] = egw::link('/index.php', 'menuaction=sitemgr.Content_UI.commit');
		$file['Manage archived content'] = egw::link('/index.php', 'menuaction=sitemgr.Content_UI.archive');

		$file['Manage Notification Messages'] = egw::link('/index.php', 'menuaction=sitemgr.uintfmess.index&site_id='.CURRENT_SITE_ID);
		if (($site = $this->sites->read(CURRENT_SITE_ID)) && $site['site_url'])
		{
			$file[] = '_NewLine_';
			$file[] = array(
				'text' => 'View generated Site',
				'link' => $site['site_url'].'?mode=Production'.
					'&sessionid='.@$GLOBALS['egw_info']['user']['sessionid'] .
					'&kp3=' . @$GLOBALS['egw_info']['user']['kp3'] .
					'&domain=' . @$GLOBALS['egw_info']['user']['domain'],
				'target' => '_blank',
			);
			$file['Edit Site'] = egw::link('/sitemgr/index.php');
		}
		return $file;
	}

	function get_othermenu()
	{
		$numberofsites = $this->sites->getnumberofsites();
		$isadmin = $GLOBALS['egw']->acl->check('run',1,'admin');
		if ($numberofsites < 2 && !$isadmin)
		{
			return false;
		}
		$menu_title = lang('Other websites');
		if ($numberofsites > 1)
		{
			$link_data['menuaction'] = 'sitemgr.Common_UI.DisplayIFrame';
			$sites = $GLOBALS['Common_BO']->sites->list_sites(False);
			while(list($site_id,$site) = @each($sites))
			{
				if ($site_id != CURRENT_SITE_ID)
				{
					$link_data['siteswitch'] = $site_id;
					$file[] = array(
						'text' => $site['site_name'],
						'no_lang' => True,
						'link' => egw::link('/index.php',$link_data)
					);
				}
			}
		}
		if ($numberofsites > 1 && $isadmin)
		{
			$file['_NewLine_'] ='';
		}
		if ($isadmin)
		{
			$file['Define websites'] = egw::link('/index.php','menuaction=sitemgr.Sites_UI.list_sites');
		}
		return $file;
	}
}
