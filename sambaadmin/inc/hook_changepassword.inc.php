<?php

$boSambaAdmin =& CreateObject('sambaadmin.bosambaadmin');

$boSambaAdmin->changePassword($GLOBALS['hook_values']['account_id'],$GLOBALS['hook_values']['new_passwd']);

?>