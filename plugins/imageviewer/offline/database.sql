CREATE TABLE IF NOT EXISTS `plugin_imageviewer_embed_token` (
  `token` varchar(32) COLLATE utf8_bin NOT NULL,
  `date_added` datetime NOT NULL,
  `file_id` int(11) NOT NULL,
  UNIQUE KEY `token` (`token`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `plugin_imageviewer_watermark` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `file_name` VARCHAR(255) NOT NULL, `image_content` BLOB NOT NULL) ENGINE = MyISAM;

CREATE TABLE IF NOT EXISTS `plugin_imageviewer_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `width` int(8) NOT NULL,
  `height` int(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

ALTER TABLE `plugin_imageviewer_embed_token` ADD `ip_address` VARCHAR( 15 ) NULL;
ALTER TABLE `plugin_imageviewer_meta` ADD `raw_data` TEXT NULL;
