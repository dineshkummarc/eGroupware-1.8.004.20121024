<?php

	{
		$file = Array
		(
			'Site Configuration'	=> $GLOBALS['egw']->link('/index.php','menuaction=admin.uiconfig.index&appname=' . $appname),
			'check ldap setup (experimental!!!)'	=> $GLOBALS['egw']->link('/index.php','menuaction=sambaadmin.uisambaadmin.checkLDAPSetup'),
		);
		display_section($appname,$appname,$file);
	}
?>
