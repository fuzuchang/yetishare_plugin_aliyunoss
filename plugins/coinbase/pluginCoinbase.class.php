<?php

class pluginCoinbase extends Plugin
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

}