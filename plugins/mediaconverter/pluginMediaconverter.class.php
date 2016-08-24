<?php

class pluginMediaconverter extends Plugin
{

    public $config = null;

    public function __construct()
    {
        // get the plugin config
        include(DOC_ROOT.'/plugins/mediaconverter/_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->cachePath = CACHE_DIRECTORY_ROOT . '/plugins/mediaconverter/';
    }

    public function getPluginDetails()
    {
        return $this->config;
    }
    
    public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();

        // remove plugin specific tables
        $sQL = 'DROP TABLE plugin_mediaconverter_queue';
        $db->query($sQL);
        
        $sQL = 'DROP TABLE plugin_mediaconverter_watermark';
        $db->query($sQL);

        return parent::uninstall();
    }
    
    public function deleteMediaCache($fileId)
    {
        // queue for delete
		$serverId = file::getDefaultLocalServerId();
        if ($serverId)
        {
            // queue cache for delete on local server
            $docRoot = fileServer::getDocRoot($serverId);
            $cacheFilePath = $docRoot . '/core/cache/plugins/mediaconverter/' . $fileId . '/original_thumb.jpg';
            fileAction::queueDeleteFile($serverId, $cacheFilePath, $fileId);
        }
    }

    public function duplicateMediaCache($fromFileId, $toFileId)
    {
        // get cache paths
        $fromCacheFilePath = $this->cachePath . $fromFileId . '/';
        $toCacheFilePath = $this->cachePath . $toFileId . '/';

        // @TODO - queue for moving
    }
}