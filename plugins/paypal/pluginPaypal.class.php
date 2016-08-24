<?php

class pluginPaypal extends Plugin
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_plugin_config.inc.php');

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
        $db = Database::getDatabase();

        // copy over PayPal details from core if they exist
        $oldPayPalEmail = $db->getValue('SELECT config_value FROM site_config WHERE config_key="paypal_payments_email_address" LIMIT 1');
        if($oldPayPalEmail)
        {
            // get plugin details
            $pluginDetails = $this->getPluginDetails();

            // update settings
            $db = Database::getDatabase();
            $db->query('UPDATE plugin SET plugin_settings = :plugin_settings WHERE folder_name = :folder_name', array('plugin_settings'=>'{"paypal_email":"'.$oldPayPalEmail.'"}', 'folder_name' => $pluginDetails['folder_name']));
            
            // delete old config value
            $db->query('DELETE FROM site_config WHERE config_key="paypal_payments_email_address" LIMIT 1');
        }

        return parent::install();
    }

}