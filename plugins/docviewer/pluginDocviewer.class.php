<?php

class PluginDocviewer extends Plugin
{

    public $config   = null;
    public $data     = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include('_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->data = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->quote($this->config['folder_name']) . ' LIMIT 1');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
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
        $sQL = 'DROP TABLE plugin_docviewer_embed_token';
        $db->query($sQL);
        
        return parent::uninstall();
    }
	
	public function deleteDocCache($fileId)
    {
        // queue for delete
		$serverId = file::getDefaultLocalServerId();
        if ($serverId)
        {
            // queue cache for delete on local server
            $docRoot = fileServer::getDocRoot($serverId);
            $cacheFilePath = $docRoot . '/core/cache/plugins/docviewer/' . $fileId . '/pdf/original_thumb.jpg';
            fileAction::queueDeleteFile($serverId, $cacheFilePath, $fileId);
        }
    }

}