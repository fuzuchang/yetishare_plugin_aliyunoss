CREATE TABLE IF NOT EXISTS `plugin_reward_ppd_complete_download` (`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `download_token` VARCHAR(64) NOT NULL, `date_added` DATETIME NOT NULL, `download_ip` VARCHAR(45) NOT NULL, `bytes_sent` BIGINT(15) NOT NULL, INDEX (`download_token`)) ENGINE = MyISAM;
ALTER TABLE `plugin_reward_ppd_complete_download` ADD INDEX ( `date_added` );
ALTER TABLE `plugin_reward_ppd_complete_download` ADD `pay_ppd` INT( 1 ) NOT NULL DEFAULT '0';
ALTER TABLE `plugin_reward_ppd_complete_download` ADD INDEX ( `pay_ppd` );

CREATE TABLE IF NOT EXISTS `plugin_reward_ppd_group_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `payout_rate` float(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `range_id` (`range_id`),
  KEY `group_id` (`group_id`),
  KEY `payout_rate` (`payout_rate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=29;

INSERT INTO `plugin_reward_ppd_group_rate` (`id`, `group_id`, `range_id`, `payout_rate`) VALUES
(1, 1, 1, 0.00),
(2, 1, 2, 0.00),
(3, 1, 3, 0.00),
(4, 1, 4, 0.00),
(5, 2, 1, 0.00),
(6, 2, 2, 0.00),
(7, 2, 3, 0.00),
(8, 2, 4, 0.00),
(9, 3, 1, 0.00),
(10, 3, 2, 0.00),
(11, 3, 3, 0.00),
(12, 3, 4, 0.00),
(13, 4, 1, 0.00),
(14, 4, 2, 0.00),
(15, 4, 3, 0.00),
(16, 4, 4, 0.00),
(17, 5, 1, 0.00),
(18, 5, 2, 0.00),
(19, 5, 3, 0.00),
(20, 5, 4, 0.00),
(21, 6, 1, 0.00),
(22, 6, 2, 0.00),
(23, 6, 3, 0.00),
(24, 6, 4, 0.00),
(25, 7, 1, 0.00),
(26, 7, 2, 0.00),
(27, 7, 3, 0.00),
(28, 7, 4, 0.00);

CREATE TABLE IF NOT EXISTS `plugin_reward_ppd_range` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_filesize` bigint(15) NOT NULL,
  `to_filesize` bigint(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `from_filesize` (`from_filesize`),
  KEY `to_filesize` (`to_filesize`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=5 ;

INSERT INTO `plugin_reward_ppd_range` (`id`, `from_filesize`, `to_filesize`) VALUES
(1, 0, 262144000),
(2, 262144000, 524288000),
(3, 524288000, 1048576000),
(4, 1048576000, NULL);