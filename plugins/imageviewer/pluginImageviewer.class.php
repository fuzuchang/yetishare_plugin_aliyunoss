<?php

class PluginImageviewer extends Plugin
{

    public $config    = null;
    public $data      = null;
    public $settings  = null;
    public $cachePath = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include(dirname(__FILE__).'/_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->data   = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->quote($this->config['folder_name']) . ' LIMIT 1');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
        $this->cachePath = CACHE_DIRECTORY_ROOT . '/plugins/imageviewer/';
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
        $sQL = 'DROP TABLE plugin_imageviewer_embed_token';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_imageviewer_watermark';
        $db->query($sQL);

        return parent::uninstall();
    }

    public function deleteImageCache($fileId)
    {
        // get cache path
        $cacheFilePath = $this->cachePath . (int)$fileId . '/';

        // queue cache for delete
		$file = file::loadById($fileId);
		$serverId = $file->serverId;
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

    public function isAnimatedGif($imageFileContents)
    {
        $str_loc = 0;
        $count   = 0;
        while ($count < 2) # There is no point in continuing after we find a 2nd frame
        {
            $where1 = strpos($imageFileContents, "\x00\x21\xF9\x04", $str_loc);
            if ($where1 === false)
            {
                break;
            }
            else
            {
                $str_loc = $where1 + 1;
                $where2  = strpos($imageFileContents, "\x00\x2C", $str_loc);
                if ($where2 === false)
                {
                    break;
                }
                else
                {
                    if ($where1 + 8 == $where2)
                    {
                        $count++;
                    }
                    $str_loc = $where2 + 1;
                }
            }
        }

        if ($count > 1)
        {
            return true;
        }

        return false;
    }

}
