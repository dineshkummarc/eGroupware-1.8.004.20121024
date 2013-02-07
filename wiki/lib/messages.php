<?php
// $Id: messages.php 14872 2004-04-12 13:02:09Z ralfbecker $

// Error messages.
$ErrorSuffix          = '<br /><br />'.lang('Please contact the %1Administrator%2 for assistance.',
	$Admin ? '<a href="mailto:' . $Admin . '">' : '',$Admin ? '</a>' : '');
/* not used in eGW
$ErrorDatabaseConnect = 'Error connecting to database.' . $ErrorSuffix;
$ErrorDatabaseSelect  = 'Error selecting database.' . $ErrorSuffix;
$ErrorDatabaseQuery   = 'Error executing database query.' . $ErrorSuffix;
*/
$ErrorCreatingTemp    = lang('Error creating temporary file.') . $ErrorSuffix;
$ErrorWritingTemp     = lang('Error writing to temporary file.') . $ErrorSuffix;
$ErrorDeniedAccess    = lang('You have been denied access to this site.') . $ErrorSuffix;
$ErrorRateExceeded    = lang('You have exeeded the number of pages you are allowed to visit in a given period of time. Please return later.') . $ErrorSuffix;
$ErrorNameMatch       = lang('You have entered an invalid user name.') . $ErrorSuffix;
$ErrorInvalidPage     = lang('Invalid page name.') . $ErrorSuffix;
$ErrorAdminDisabled   = lang('Administration features are disabled for this wiki.') . $ErrorSuffix;
$ErrorPageLocked      = lang('This page can not be edited.') . $ErrorSuffix;
?>
