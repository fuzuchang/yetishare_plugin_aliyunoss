CREATE TABLE IF NOT EXISTS `plugin_newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `subject` text COLLATE utf8_bin NOT NULL,
  `html_content` text COLLATE utf8_bin NOT NULL,
  `user_group` enum('all registered','free only','premium only','admin only') COLLATE utf8_bin NOT NULL,
  `status` enum('draft','sending','sent') COLLATE utf8_bin NOT NULL,
  `date_created` datetime NOT NULL,
  `date_sent` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `plugin_newsletter_sent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email_address` varchar(255) COLLATE utf8_bin NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `subject` text COLLATE utf8_bin NOT NULL,
  `html_content` text COLLATE utf8_bin NOT NULL,
  `date_created` datetime NOT NULL,
  `date_sent` datetime NOT NULL,
  `newsletter_id` int(11) NOT NULL,
  `status` enum('sent','failed') COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `to_user_id` (`to_user_id`),
  KEY `newsletter_id` (`newsletter_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `plugin_newsletter_unsubscribe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_unsubscribed` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `user_id_2` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE  `plugin_newsletter` CHANGE  `user_group`  `user_group` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
