CREATE TABLE IF NOT EXISTS `ymind_ip_log` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(16) NOT NULL,
  `unixtime` int(11) default NULL,
  PRIMARY KEY  (`id`)
);


CREATE TABLE IF NOT EXISTS `ymind_block_list` (
`id` INT NOT NULL AUTO_INCREMENT ,
`ip` VARCHAR( 20 ) NOT NULL ,
PRIMARY KEY (  `id` )
);