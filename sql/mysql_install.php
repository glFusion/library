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

$_SQL['library.items'] = "CREATE TABLE {$_TABLES['library.items']} (
  `id` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
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
  `uid` int(11) NOT NULL DEFAULT '0',
  `due` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `item_name` (`name`)
)";

$_SQL['library.trans'] = "CREATE TABLE {$_TABLES['library.trans']} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) NOT NULL,
  `dt` int(11) NOT NULL,
  `doneby` tinyint(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL,
  `trans_type` varchar(15) NOT NULL DEFAULT 'checkout',
  PRIMARY KEY (`id`),
  KEY `purchases_productid` (`item_id`),
  KEY `purchases_userid` (`uid`)
)";

$_SQL['library.waitlist'] = "CREATE TABLE {$_TABLES['library.waitlist']} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dt` datetime DEFAULT NULL,
  `item_id` varchar(255) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxItem` (`item_id`,`uid`)
)";

$_SQL['library.images'] = "CREATE TABLE {$_TABLES['library.images']} (
  `img_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`img_id`),
  KEY `idxItem` (`item_id`,`img_id`)
)";

$_SQL['library.categories'] = "CREATE TABLE {$_TABLES['library.categories']} (
  `cat_id` smallint(5) unsigned NOT NULL auto_increment,
  `parent_id` smallint(5) unsigned default '0',
  `cat_name` varchar(255) default '',
  `dscp` varchar(255) default '',
  `enabled` tinyint(1) unsigned default '1',
  `group_id` mediumint(8) unsigned NOT NULL default '1',
  `owner_id` mediumint(8) unsigned NOT NULL default '1',
  `perm_owner` tinyint(1) unsigned NOT NULL default '3',
  `perm_group` tinyint(1) unsigned NOT NULL default '3',
  `perm_members` tinyint(1) unsigned NOT NULL default '2',
  `perm_anon` tinyint(1) unsigned NOT NULL default '2',
  `lft` int(5) unsigned NOT NULL DEFAULT '0',
  `rgt` int(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (`cat_id`),
  KEY `idxName` (`cat_name`,`cat_id`)
)";

$_SQL['library.types'] = "CREATE TABLE {$_TABLES['library.types']} (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
)";

$_DEFDATA['library.types'] = "INSERT INTO {$_TABLES['library.types']} VALUES
    (1,'Book'),(2,'CD'),(3,'DVD')";

$_DEFDATA['library.categories'] = "INSERT INTO {$_TABLES['library.categories']} (
        parent_id, cat_name, dscp,
        group_id, owner_id, perm_owner, perm_group, perm_members, perm_anon
    ) VALUES (
        0, 'Root', 'Root Category',
        13, 2, 3, 3, 2, 2
    )";

?>
