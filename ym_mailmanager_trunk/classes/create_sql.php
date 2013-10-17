<?php

global $wpdb;

$mm_email = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'mm_email (
 id INT NOT NULL AUTO_INCREMENT ,
 name VARCHAR(255) NOT NULL ,
 subject VARCHAR(255) NOT NULL ,
 body TEXT NOT NULL ,
 active INT NULL DEFAULT 1 ,
 PRIMARY KEY (id) )
ENGINE = MyISAM;
';
$wpdb->query($mm_email);

$mm_email_series = 'CREATE TABLE ' . $wpdb->prefix . 'mm_email_in_series (
id INT(11) NOT NULL AUTO_INCREMENT,
 series_id INT(11) NOT NULL,
 email_id INT(11) NOT NULL,
 delay_days INT(11) NOT NULL,
PRIMARY KEY ( id )
) ENGINE = MYISAM';
$wpdb->query($mm_email_series);

$mm_email_sent = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'mm_email_sent (
 id INT NOT NULL AUTO_INCREMENT ,
 user_id INT NULL ,
 email_id INT NULL ,
 sent_date INT NULL ,
 PRIMARY KEY (id) )
ENGINE = MyISAM;
';
$wpdb->query($mm_email_sent);

$mm_list_unsubcribe = 'CREATE TABLE ' . $wpdb->prefix . 'mm_list_unsubscribe (
id INT(11) NOT NULL AUTO_INCREMENT,
 list_name VARCHAR(255) NOT NULL,
 user_id INT(11) NOT NULL,
PRIMARY KEY ( id ),
UNIQUE KEY list_name (list_name,user_id)
) ENGINE = MYISAM';
$wpdb->query($mm_list_unsubcribe);

$mm_series = 'CREATE TABLE ' . $wpdb->prefix . 'mm_series (
id INT( 11 ) NOT NULL AUTO_INCREMENT ,
 name VARCHAR( 255 ) NOT NULL ,
 description TEXT,
 recipient_list VARCHAR(255) NOT NULL,
 enabled INT(1) NOT NULL DEFAULT 0,
PRIMARY KEY ( id )
) ENGINE = MYISAM';
$wpdb->query($mm_series);

/*
$mm_user = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'mm_user (
 id INT NOT NULL AUTO_INCREMENT ,
 email VARCHAR(255) NULL ,
 name VARCHAR(255) NULL ,
 wp_user_id INT NULL ,
 PRIMARY KEY (id) )
ENGINE = MyISAM;
';
$wpdb->query($mm_user);
*/


$mm_user_series_assoc = 'CREATE TABLE ' . $wpdb->prefix . 'mm_user_series_assoc (
id INT( 11 ) NOT NULL AUTO_INCREMENT ,
 user_id INT( 11 ) DEFAULT NULL ,
 series_id INT( 11 ) DEFAULT NULL ,
 start_date INT( 11 ) DEFAULT NULL ,
PRIMARY KEY ( id ),
UNIQUE KEY user_id (user_id, series_id)
) ENGINE = MYISAM';
$wpdb->query($mm_user_series_assoc);

get_currentuserinfo();
global $current_user;

$settings = new StdClass();
$settings->from_email = $current_user->user_email;
$settings->from_name = get_bloginfo();
$settings->series_hour = '23';
$settings->series_min = '59';
$settings->mail_gateway = FALSE;
$settings->first_run_done = FALSE;

update_option('ym_other_mm_settings', $settings);

$settings = new StdClass();
$settings->enable = 0;
update_option('ym_other_mm_welcome', $settings);
