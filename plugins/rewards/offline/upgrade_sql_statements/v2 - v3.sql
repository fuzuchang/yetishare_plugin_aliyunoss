ALTER TABLE `plugin_reward_ppd_detail` CHANGE `reward_amount` `reward_amount` FLOAT( 5, 5 ) NOT NULL;
ALTER TABLE `plugin_reward_aggregated` CHANGE `amount` `amount` FLOAT( 7, 6 ) NOT NULL;

INSERT INTO `site_config` (`id`, `config_key`, `config_value`, `config_description`, `availableValues`, `config_type`, `config_group`) VALUES (NULL, 'next_check_for_ppd_aggregation', '0', 'System value. The next time collate the PPD data.', '', 'integer', 'System');
