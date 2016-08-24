CREATE TABLE IF NOT EXISTS `plugin_mediaplayer_embed_token` (
  `token` varchar(32) COLLATE utf8_bin NOT NULL,
  `date_added` datetime NOT NULL,
  `file_id` int(11) NOT NULL,
  UNIQUE KEY `token` (`token`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `plugin_mediaplayer_embed_token` ADD `ip_address` VARCHAR( 15 ) NULL;
DROP TABLE `plugin_mediaplayer_embed_token`;