CREATE TABLE IF NOT EXISTS `plugin_torrentdownload_torrent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `torrent_hash` varchar(100) COLLATE utf8_bin NOT NULL,
  `date_added` datetime NOT NULL,
  `status` varchar(50) COLLATE utf8_bin NOT NULL,
  `torrent_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `torrent_size` bigint(15) NOT NULL,
  `download_percent` int(5) NOT NULL,
  `downloaded` bigint(15) NOT NULL,
  `uploaded` bigint(15) NOT NULL,
  `download_speed` int(11) NOT NULL,
  `upload_speed` int(11) NOT NULL,
  `time_remaining` bigint(15) NOT NULL,
  `save_path` varchar(255) COLLATE utf8_bin NOT NULL,
  `peers_connected` int(11) NOT NULL,
  `peers_in_swarm` int(11) NOT NULL,
  `seeds_connected` int(11) NOT NULL,
  `seeds_in_swarm` int(11) NOT NULL,
  `save_status` enum('downloading','pending','processing','complete','cancelled') COLLATE utf8_bin NOT NULL DEFAULT 'downloading',
  `date_completed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `torrent_hash` (`torrent_hash`),
  KEY `user_id` (`user_id`),
  KEY `save_status` (`save_status`),
  KEY `torrent_name` (`torrent_name`),
  KEY `save_status_2` (`save_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `plugin_torrentdownload_torrent_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `torrent_id` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8_bin NOT NULL,
  `filesize` bigint(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `torrent_id` (`torrent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE  `plugin_torrentdownload_torrent` ADD  `status_notes` VARCHAR( 255 ) NULL AFTER  `save_status`;

