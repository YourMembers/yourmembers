<?php

/*
* $Id: yss_admin_functions.include.php 1754 2012-01-03 16:45:50Z BarryCarlyon $
* $Revision: 1754 $
* $Date: 2012-01-03 16:45:50 +0000 (Tue, 03 Jan 2012) $
*/

function yss_activate() {
	// check to see if ym is installed
	if (!function_exists('ym_loaded')) {
		echo '<strong>Your Members - Your Secure Stream</strong>';
		echo '<p>YourMembers does not appear to be installed. <a href="http://yourmembers.co.uk/">YourMembers</a> is required to use Your Members - Your Secure Stream, visit <a href="http://yourmembers.co.uk/">YourMembers</a> to purchase</p>';
		die();
	} else {
		// do tables
		yss_create_tables();
		update_option('yss_custom_player', 'No Player Selected for this Video File');
	}
}

function yss_deactivate() {
	// list of options to kill
	$keys_to_kill = array(
		'yss_user_key',
		'yss_secret_key',
		'yss_playerofchoice',
		'yss_license_key',
		'yss_licensing_activation_date',
		'yss_custom_player',
		'yss_cloudfront_id',
		'yss_cloudfront_public',
		'yss_cloudfront_private',
	);
	
	foreach ($keys_to_kill as $key) {
		delete_option($key);
	}
	
	global $wpdb;
	$query = 'DROP TABLE ' . $wpdb->prefix . 'yss_%';
	$wpdb->query($sql);
}

// mysql
function yss_create_tables() {
	global $wpdb;
	
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'yss_videos` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `bucket` varchar(200) NOT NULL,
			  `resource_path` longtext NOT NULL,
			  `postDate` datetime NOT NULL,
			  `members` int(12) NOT NULL,
			  `account_types` varchar(255) NOT NULL,
			  `user` varchar(200) NOT NULL,
			  `distribution` text NOT NULL,
			  PRIMARY KEY (`id`)
			)ENGINE=MyISAM';
	$wpdb->query($sql);
	
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'yss_post_assoc` (
	  `s3_id` int(11) NOT NULL,
	  `post_id` int(11) NOT NULL,
	  PRIMARY KEY (`s3_id`,`post_id`)
	) ENGINE=MyISAM';
	$wpdb->query($sql);
}

if (!function_exists('xml2array')) {
function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
	if (!function_exists('xml_parser_create'))
	{
		return array ();
	}
	$parser = xml_parser_create('');

	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents), $xml_values);
	xml_parser_free($parser);

	if (!$xml_values)
		return;//Hmm...

	$xml_array = array ();
	$parents = array ();
	$opened_tags = array ();
	$arr = array ();
	$current = & $xml_array;
	$repeated_tag_index = array ();

	foreach ($xml_values as $data) {
		unset ($attributes, $value);
		extract($data);
		$result = array ();
		$attributes_data = array ();
		if (isset ($value))
		{
			if ($priority == 'tag')
				$result = $value;
			else
				$result['value'] = $value;
		}
		if (isset ($attributes) and $get_attributes)
		{
			foreach ($attributes as $attr => $val)
			{
				if ($priority == 'tag')
					$attributes_data[$attr] = $val;
				else
					$result['attr'][$attr] = $val;//Set all the attributes in a array called 'attr'
			}
		}
		if ($type == "open")
		{ 
			$parent[$level -1] = & $current;
			if (!is_array($current) or (!in_array($tag, array_keys($current))))
			{
				$current[$tag] = $result;
				if ($attributes_data)
					$current[$tag . '_attr'] = $attributes_data;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				$current = & $current[$tag];
			}
			else
			{
				if (isset ($current[$tag][0]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{ 
					$current[$tag] = array (
						$current[$tag],
						$result
					);
					$repeated_tag_index[$tag . '_' . $level] = 2;
					if (isset ($current[$tag . '_attr']))
					{
						$current[$tag]['0_attr'] = $current[$tag . '_attr'];
						unset ($current[$tag . '_attr']);
					}
				}
				$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
				$current = & $current[$tag][$last_item_index];
			}
		}
		elseif ($type == "complete")
		{
			if (!isset ($current[$tag]))
			{
				$current[$tag] = $result;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				if ($priority == 'tag' and $attributes_data)
					$current[$tag . '_attr'] = $attributes_data;
			}
			else
			{
				if (isset ($current[$tag][0]) and is_array($current[$tag]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					if ($priority == 'tag' and $get_attributes and $attributes_data)
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array (
						$current[$tag],
						$result
					);
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $get_attributes)
					{
						if (isset ($current[$tag . '_attr']))
						{ 
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
						if ($attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag . '_' . $level]++;//0 and 1 index is already taken
				}
			}
		}
		elseif ($type == 'close')
		{
			$current = & $parent[$level -1];
		}
	}
	return ($xml_array);
}
}
