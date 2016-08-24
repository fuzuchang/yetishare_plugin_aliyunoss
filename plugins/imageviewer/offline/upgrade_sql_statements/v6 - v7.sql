CREATE TABLE IF NOT EXISTS `plugin_imageviewer_meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `width` int(8) NOT NULL,
  `height` int(8) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

ALTER TABLE `plugin_imageviewer_meta` ADD `raw_data` TEXT NULL;
