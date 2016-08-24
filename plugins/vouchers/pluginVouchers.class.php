<?php

class pluginVouchers extends Plugin
{
    public $config = null;
    public function __construct()
    {
        // get the plugin config
        include_once('_plugin_config.inc.php');
        // load config into the object
        $this->config = $pluginConfig;
    }
    public function getPluginDetails()
    {
        return $this->config;
    }    
    public function install()
    {
        // setup database
        
		$pluginDetails = $this->getPluginDetails();
		$db = Database::getDatabase();
		// Create Voucher DB tables
		$db->query("CREATE TABLE IF NOT EXISTS `plugin_vouchers_logs` (`id` int(11) NOT NULL AUTO_INCREMENT, `code` varchar(255) NOT NULL, `user` varchar(25) NOT NULL, `date` varchar(11) NOT NULL, `fraud` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `id` (`id`), KEY `code` (`code`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

		$db->query("CREATE TABLE IF NOT EXISTS `plugin_vouchers` (`id` int(11) NOT NULL AUTO_INCREMENT, `voucher` varchar(255) NOT NULL, `length` int(3) NOT NULL, `redeemed` int(1) NOT NULL DEFAULT '0', `expiry_date` bigint(12) NOT NULL, `max_uses` int(10) NOT NULL DEFAULT '0', `unlimited` int(1) NOT NULL DEFAULT '0', `times_used` int(10) NOT NULL DEFAULT '0', `date_redeemed` text NOT NULL, `user_redeemed` text NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

		$settingsArr					= array();
		$settingsArr['disable_selling'] = '0';
		$settingsArr['url']				= 'http://www.yetisharemods.com';
		$settings						= json_encode($settingsArr);		
		$db->query('UPDATE plugin SET plugin_settings = :plugin_settings WHERE folder_name = :folder_name', array('plugin_settings' => $settings, 'folder_name' => $pluginDetails['folder_name']));

		return parent::install();
    }
	public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();
		$pluginDetails = $this->getPluginDetails();
		// Delete News Feeder tables
		$db->query("DROP TABLE plugin_vouchers;");
		return parent::uninstall();
    }
}