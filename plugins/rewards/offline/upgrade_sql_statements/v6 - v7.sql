CREATE TABLE IF NOT EXISTS `plugin_reward_outpayment_method` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_key` varchar(50) COLLATE utf8_bin NOT NULL,
  `label` varchar(150) COLLATE utf8_bin NOT NULL,
  `fields_json` text COLLATE utf8_bin NOT NULL,
  `admin_payment_link` text COLLATE utf8_bin NOT NULL,
  `is_enabled` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_key` (`name_key`),
  KEY `is_enabled` (`is_enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=4 ;

INSERT INTO `plugin_reward_outpayment_method` (`id`, `name_key`, `label`, `fields_json`, `admin_payment_link`, `is_enabled`) VALUES
(NULL, 'paypal', 'PayPal', 0x7b2270617970616c5f656d61696c223a5b2274657874225d7d, 0x68747470733a2f2f7777772e70617970616c2e636f6d2f6367692d62696e2f7765627363723f636d643d5f78636c69636b2672657475726e3d5b5b5b52455455524e5f504147455d5d5d26627573696e6573733d5b5b5b50415950414c5f454d41494c5d5d5d266974656d5f6e616d653d5b5b5b4954454d5f4e414d455d5d5d266974656d5f6e756d6265723d3126616d6f756e743d5b5b5b414d4f554e545d5d5d266e6f5f7368697070696e673d32266e6f5f6e6f74653d312663757272656e63795f636f64653d5b5b5b43555252454e43595d5d5d266c633d474226626e3d50502532644275794e6f77424626636861727365743d55544625326438, 1),
(NULL, 'cheque', 'Cheque', 0x7b22796f75725f706f7374616c5f61646472657373223a5b227465787461726561225d7d, '', 0),
(NULL, 'banktransfer', 'Bank Transfer', 0x7b22796f75725f6163636f756e745f6e616d65223a5b2274657874225d2c22696e7465726e6174696f6e616c5f6962616e5f6e756d626572223a5b2274657874225d2c2273776966745f6e756d626572223a5b2274657874225d2c22796f75725f706f7374616c5f61646472657373223a5b227465787461726561225d7d, '', 1);

ALTER TABLE  `plugin_reward_affiliate_id` ADD  `outpayment_method` VARCHAR( 50 ) NOT NULL DEFAULT  'paypal';
ALTER TABLE  `plugin_reward_affiliate_id` ADD  `method_data_json` TEXT NULL DEFAULT NULL;
UPDATE plugin_reward_affiliate_id SET method_data_json = CONCAT('{"paypal_email":["', paypal_email, '"]}');
ALTER TABLE  `plugin_reward_affiliate_id` DROP  `paypal_email`;

ALTER TABLE  `plugin_reward_ppd_detail` CHANGE  `status`  `status` ENUM(  'pending',  'cleared',  'cancelled',  'aggregated',  'ip_limit_reached',  'file_limit_reached',  'user_limit_reached' ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT  'pending';
ALTER TABLE  `plugin_reward_ppd_detail` ADD INDEX (  `download_date` );
ALTER TABLE  `plugin_reward_ppd_detail` ADD INDEX (  `file_id` );
