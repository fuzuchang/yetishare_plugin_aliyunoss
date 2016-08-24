<?php

class pluginFileleech extends Plugin
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include(DOC_ROOT.'/plugins/fileleech/_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
    }

    public function getPluginDetails()
    {
        return $this->config;
    }
    
    public function install()
    {
        return parent::install();
    }

}