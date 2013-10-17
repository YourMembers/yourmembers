<?php

/*
* $Id: sql_update_9.php 2085 2012-04-11 09:44:10Z bcarlyon $
* $Revision: 2085 $
* $Date: 2012-04-11 10:44:10 +0100 (Wed, 11 Apr 2012) $
*/

$queries = array();

global $ym_res;

//Fixing small issue where old users didn't get Password text
if(empty($ym_res->registration_flow_invalid_password)){
	$ym_res->registration_flow_invalid_password = __('The password is invalid, or does not match', 'ym');
}
$ym_res->save();
