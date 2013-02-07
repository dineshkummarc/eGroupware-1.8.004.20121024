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

	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('bday','Birthday','birthday','','Y',1)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('email','E-Mail','email','','Y',2)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('n_given','First Name','first_name','','Y',3)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('n_family','Last Name','last_name','','Y',4)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_street','Address','address','','Y',5)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_locality','City','city','','Y',6)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_region','State','state','','Y',7)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_postalcode','ZIP/Postal','zip','','Y',8)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_countryname','Country','country','','Y',9)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('tel_work','Phone','phone','','N',10)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('gender','Gender','gender','','N',11)");
	
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('challenge_question_1','Challenge1','challenge1','','N',100)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('challenge_question_2','Challenge2','challenge2','','N',100)");
	$oProc->query ("INSERT INTO egw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('challenge_question_3','Challenge3','challenge3','','N',100)");

	$oProc->query ("DELETE FROM {$GLOBALS['egw_setup']->config_table} WHERE config_app='registration'");
	$oProc->query ("INSERT INTO {$GLOBALS['egw_setup']->config_table} (config_app, config_name, config_value) VALUES ('registration','display_tos','True')");
	$oProc->query ("INSERT INTO {$GLOBALS['egw_setup']->config_table} (config_app, config_name, config_value) VALUES ('registration','activate_account','email')");
	$oProc->query ("INSERT INTO {$GLOBALS['egw_setup']->config_table} (config_app, config_name, config_value) VALUES ('registration','username_is','choice')");
	$oProc->query ("INSERT INTO {$GLOBALS['egw_setup']->config_table} (config_app, config_name, config_value) VALUES ('registration','password_is','choice')");
	$oProc->query ("INSERT INTO {$GLOBALS['egw_setup']->config_table} (config_app, config_name, config_value) VALUES ('registration','use_challenge','True')");
	$oProc->query ("ALTER TABLE {$GLOBALS['egw_setup']->accounts_table} ADD COLUMN account_challenge varchar(100)") ;
	$oProc->query ("ALTER TABLE {$GLOBALS['egw_setup']->accounts_table} ADD COLUMN account_response varchar(100)") ;
?>
