<?php
/**
*   Database creation and update statements for the Library plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_TABLES;
$_SQL = array();
$_SQL['library.items'] = "CREATE TABLE `{$_TABLES['library.items']}` (
  `id` varchar(40) NOT NULL,
  `name` varchar(128) NOT NULL,
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0',
  `short_dscp` varchar(255) NOT NULL DEFAULT '',
  `dscp` text,
  `keywords` varchar(255) DEFAULT '',
  `author` varchar(255) DEFAULT '',
  `publisher` varchar(255) DEFAULT '',
  `pub_date` varchar(20) DEFAULT '',
  `type` tinyint(2) DEFAULT '0',
  `qoh` int(4) DEFAULT '1',
  `daysonhold` int(4) DEFAULT '0',
  `maxcheckout` int(4) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) DEFAULT '1',
  `dt_add` int(11) unsigned DEFAULT NULL,
  `views` int(4) unsigned DEFAULT '0',
  `comments` int(5) unsigned DEFAULT '0',
  `comments_enabled` tinyint(1) unsigned DEFAULT '0',
  `rating` double(6,4) NOT NULL DEFAULT '0.0000',
  `votes` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `item_name` (`name`)
) ENGINE=MyISAM";

$_SQL['library.instances'] = "CREATE TABLE `{$_TABLES['library.instances']}` (
  `instance_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(80) NOT NULL DEFAULT '',
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `checkout` int(11) unsigned NOT NULL DEFAULT '0',
  `due` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`instance_id`),
  KEY `item_id` (`item_id`,`instance_id`)
) ENGINE=MyISAM";

$_SQL['library.log'] = "CREATE TABLE `{$_TABLES['library.log']}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` varchar(40) NOT NULL,
  `dt` int(11) NOT NULL,
  `doneby` tinyint(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `trans_type` varchar(15) NOT NULL DEFAULT 'checkout',
  PRIMARY KEY (`id`),
  KEY `purchases_productid` (`item_id`),
  KEY `purchases_userid` (`uid`)
) ENGINE=MyISAM";

$_SQL['library.waitlist'] = "CREATE TABLE `{$_TABLES['library.waitlist']}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt` int(11) unsigned NOT NULL DEFAULT '0',
  `expire` int(11) unsigned NOT NULL DEFAULT '0',
  `item_id` varchar(40) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxItem` (`item_id`,`uid`)
) ENGINE=MyISAM";

$_SQL['library.images'] = "CREATE TABLE `{$_TABLES['library.images']}` (
  `img_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(40) NOT NULL DEFAULT '',
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`img_id`),
  KEY `idxItem` (`item_id`,`img_id`)
) ENGINE=MyISAM";

$_SQL['library.categories'] = "CREATE TABLE `{$_TABLES['library.categories']}` (
  `cat_id` smallint(5) unsigned NOT NULL auto_increment,
  `cat_name` varchar(40) default '',
  `dscp` varchar(255) default '',
  `enabled` tinyint(1) unsigned default '1',
  `group_id` mediumint(8) unsigned NOT NULL default '1',
  PRIMARY KEY  (`cat_id`),
  KEY `idxName` (`cat_name`,`cat_id`)
) ENGINE=MyISAM";

$_SQL['library.types'] = "CREATE TABLE `{$_TABLES['library.types']}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM";

$_DEFDATA['library.types'] = "INSERT INTO {$_TABLES['library.types']} VALUES
    (1,'Book'),(2,'CD'),(3,'DVD')";

$_DEFDATA['library.categories'] = "INSERT INTO {$_TABLES['library.categories']} (
        cat_id, cat_name, dscp,
        group_id, owner_id, perm_owner, perm_group, perm_members, perm_anon
    ) VALUES (
        1, 'Miscellaneous', 'Miscellaneous Items',
        1, 2, 3, 3, 2, 2
    )";

?>
