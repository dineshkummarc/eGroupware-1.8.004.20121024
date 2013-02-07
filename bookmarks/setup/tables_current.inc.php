<?php
  /**************************************************************************\
  * eGroupWare - Setup                                                       *
  * http://www.egroupware.org                                                *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /**************************************************************************\
  * This file should be generated for you. It should never be edited by hand *
  \**************************************************************************/

  /* $Id: tables_current.inc.php 19809 2005-11-14 10:11:00Z ralfbecker $ */

	$phpgw_baseline = array(
		'egw_bookmarks' => array(
			'fd' => array(
				'bm_id' => array('type' => 'auto','nullable' => False),
				'bm_owner' => array('type' => 'int', 'precision' => 4,'nullable' => True),
				'bm_access' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
				'bm_url' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
				'bm_name' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
				'bm_desc' => array('type' => 'text', 'nullable' => True),
				'bm_keywords' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
				'bm_category' => array('type' => 'int', 'precision' => 4,'nullable' => True),
				'bm_rating' => array('type' => 'int', 'precision' => 4,'nullable' => True),
				'bm_info' => array('type' => 'varchar', 'precision' => 255,'nullable' => True),
				'bm_visits' => array('type' => 'int', 'precision' => 4,'nullable' => True)
			),
			'pk' => array('bm_id'),
			'fk' => array(),
			'ix' => array(),
			'uc' => array()
		)
	);
?>
