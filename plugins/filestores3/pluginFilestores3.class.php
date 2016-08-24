<?php

class pluginFilestores3 extends Plugin
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
        return parent::install();
    }
    
    public function deleteS3Cache($fileId)
    {
        // get cache path
        $cacheFilePath = $this->cachePath . (int)$fileId . '/';

        // queue cache for delete
		$file     = file::loadById($fileId);		
		$serverId = file::getDefaultLocalServerId(); 
        if ($serverId)
        {
            // get all file listing
            $files = coreFunctions::getDirectoryListing($cacheFilePath);
            if (COUNT($files))
            {
                foreach ($files AS $file)
                {
                    fileAction::queueDeleteFile($serverId, $file, $fileId);
                }
            }

            // add folder aswell
            fileAction::queueDeleteFile($serverId, $cacheFilePath, $fileId);
        }
    }

}