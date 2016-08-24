CREATE TABLE IF NOT EXISTS `plugin_ftp_proftpd_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(32) NOT NULL DEFAULT '',
  `passwd` varchar(32) NOT NULL DEFAULT '',
  `uid` smallint(6) NOT NULL DEFAULT '5500',
  `gid` smallint(6) NOT NULL DEFAULT '5500',
  `home_dir` varchar(255) NOT NULL DEFAULT '',
  `shell` varchar(16) NOT NULL DEFAULT '/sbin/nologin',
  `count` int(11) NOT NULL DEFAULT '0',
  `accessed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `plugin_ftp_proftpd_group` (
  `group_name` varchar(16) NOT NULL,
  `gid` smallint(6) NOT NULL DEFAULT '5500',
  `members` varchar(16) NOT NULL,
  KEY `groupname` (`group_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
