<?php
	/**************************************************************************\
	* eGroupWare - Webpage news admin                                          *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	* --------------------------------------------                             *
	* This program was sponsered by Golden Glair productions                   *
	* http://www.goldenglair.com                                               *
	\**************************************************************************/

	/* $Id: hook_home.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $ */
	
	$showevents = (int)$GLOBALS['egw_info']['user']['preferences']['news_admin']['homeShowLatest'];
	if($showevents > 0)
	{
		$d1 = strtolower(substr(EGW_APP_INC,0,3));
    		if($d1 == 'htt' || $d1 == 'ftp' ) {
        	    echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
        	    $GLOBALS['egw']->common->egw_exit();
    		}
        	unset($d1);

		$GLOBALS['egw']->translation->add_app('news_admin');
		$title = lang('News');
		$portalbox =& CreateObject('phpgwapi.listbox',array(
			'title'     => $title,
			'primary'   => $GLOBALS['egw_info']['theme']['navbar_bg'],
			'secondary' => $GLOBALS['egw_info']['theme']['navbar_bg'],
			'tertiary'  => $GLOBALS['egw_info']['theme']['navbar_bg'],
			'width'     => '100%',
			'outerborderwidth' => '0',
			'header_background_image' => $GLOBALS['egw']->common->image('phpgwapi/templates/default','bg_filler')
		));

		$latestcount = (int)$GLOBALS['egw_info']['user']['preferences']['news_admin']['homeShowLatestCount'];
		if($latestcount<=0) 
		{
			$latestcount = 10;
		}
		print_debug("showing $latestcount news items");
		$app_id = $GLOBALS['egw']->applications->name2id('news_admin');
		$GLOBALS['portal_order'][] = $app_id;

		require_once(EGW_INCLUDE_ROOT.'/news_admin/inc/class.bonews.inc.php');
		$news = new bonews();
		$criteria = array();
		if ($GLOBALS['egw_info']['user']['preferences']['news_admin']['homeShowCats'])
		{
			$criteria['cat_id'] = explode(',',$GLOBALS['egw_info']['user']['preferences']['news_admin']['homeShowCats']);
		}
		$newslist = $news->search($criteria,false,'news_date DESC','','',false,'AND',array(0,$latestcount));
		$image_path = $GLOBALS['egw']->common->get_image_path('news_admin');

		$text = "<ul>";
		if(is_array($newslist))
		{
			foreach($newslist as $newsitem)
			{
				$text .= "<li>";
				if ($newsitem['news_is_html'] < 0)	// external feed
				{
					$text .= '<a href="'.htmlspecialchars($newsitem['link']).'" target="_blank">';
				}
				else
				{
					$text .= '<a href="'.$GLOBALS['egw']->link('/index.php',array(
						'menuaction' => 'news_admin.uinews.edit',
						'news_id' => $newsitem['news_id'],
					)).'" onclick="window.open(this.href,\'_blank\',\'dependent=yes,width=700,height=580,scrollbars=yes,status=yes\'); return false;">';
				}
				$text .= $newsitem['news_headline'].'</a>';
				
				if($showevents == 1)
				{
					$text .= ' - ' . ($newsitem['news_submittedby'] ? lang('Submitted by').' '.$GLOBALS['egw']->common->grab_owner_name($newsitem['news_submittedby']).' '.lang('on').' ' : '').
						$GLOBALS['egw']->common->show_date($newsitem['news_date']);
					if ($newsitem['news_teaser']) $text .= "\n<br />" . $newsitem['news_teaser'];
					$text .= "</li>\n";
				}
				else
				{
    				$text .= "</li>\n";
				}
			}
		}
		else
		{
			$text .= lang('no news');
		}
		
		$text .= "</ul>";

		$GLOBALS['portal_order'][] = $app_id;
		$var = Array(
				'up'    => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'down'  => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'close' => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'question'      => Array('url'  => '/set_box.php', 'app'        => $app_id),
				'edit'  => Array('url'  => '/set_box.php', 'app'        => $app_id)
		);

		while(list($key,$value) = each($var))
		{
			$portalbox->set_controls($key,$value);
		}

		$tmp = "\r\n"
			. '<!-- start News Admin -->' . "\r\n"
			. $portalbox->draw($text)
			. '<!-- end News Admin -->'. "\r\n";
		print $tmp;
	}
?>
