<?php

/*
* $Id: sql_update_6.php 1946 2012-02-27 16:09:10Z bcarlyon $
* $Revision: 1946 $
* $Date: 2012-02-27 16:09:10 +0000 (Mon, 27 Feb 2012) $
*/

$queries = array();

$user_description = array(
	'id' => '',
	'name' => 'user_description',
	'label' => 'Biographical Info',
	'available_values' => '',
	'caption' => 'Share a little biographical information to fill out your profile. This may be shown publicly',
	'type' => 'textarea',
	'required' => false,
	'readonly' => false,
	'profile_only' => false,
	'no_profile' => false,
	'value' => '',
	'builtin' => true
);

$source = get_option('ym_custom_fields');
$next_id = $source->next_id;
$user_description['id'] = $next_id;
$source->entries[] = $user_description;
$source->next_id++;
update_option('ym_custom_fields', $source);
