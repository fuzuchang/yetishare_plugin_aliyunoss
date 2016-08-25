<?php

class pluginAliyunoss extends Plugin
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include('_plugin_config.inc.php');

        // load config into the object

        try{
            if (isset($pluginConfig)){
                $this->config = $pluginConfig;
            }
        } catch(Exception $e) {
            var_dump($e->getMessage());exit();
        }
    }

    public function getPluginDetails()
    {
        return $this->config;
    }
    
    public function install()
    {
        return parent::install();
    }
    
    public function deleteOssCache($fileId)
    {
        // get cache path
        $cacheFilePath = $this->cachePath . (int)$fileId . '/';

        // queue cache for delete
		$file     = file::loadById($fileId);		
		$serverId =$this->getServerId();
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

    public function getServerId(){
        $db = Database::getDatabase(true);
        $db->close();
        $db = Database::getDatabase(true);
        $plugin   = $db->getRow("SELECT * FROM file_server WHERE serverType = 'aliyun_oss' LIMIT 1");
        return (int)$plugin['id'];
    }

}