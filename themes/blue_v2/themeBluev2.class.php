<?php

class themeBluev2 extends Theme
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_theme_config.inc.php');

        // load config into the object
        $this->config = $themeConfig;
    }

    public function getThemeDetails()
    {
        return $this->config;
    }

}