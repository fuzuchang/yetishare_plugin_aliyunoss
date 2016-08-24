<?php

class PluginMediaplayer extends Plugin
{

    public $config   = null;
    public $data     = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include_once('_plugin_config.inc.php');

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
        $sQL = 'DROP TABLE plugin_mediaplayer_embed_token';
        $db->query($sQL);
        
        return parent::uninstall();
    }
    
    public function getSubtitlesForJWPlayer(file $file)
    {
        // setup database
        $db = Database::getDatabase();
        
        // lookup for subtitle files
        $subTitleValidExt = array('vtt', 'srt', 'xml', 'dfxp');
        $subtitleArr = array();
        $subtitles = $db->getRows('SELECT * FROM file WHERE statusId = 1 AND folderId '.((int)$file->folderId?('= '.(int)$file->folderId):'IS NULL').' AND originalFilename LIKE "'.$db->escape(str_replace('.'.$file->extension, '', $file->originalFilename)).'%" AND extension IN ("'.implode('","', $subTitleValidExt).'")');
        if($subtitles)
        {
            foreach($subtitles AS $subtitle)
            {
                $subtitleFile = file::hydrate($subtitle);
                $originalBasePath = str_replace('.'.$file->extension, '', $file->originalFilename);
                $subtitleLabel = str_replace($originalBasePath, '', $subtitleFile->originalFilename);
                $subtitleLabel = trim(str_replace($subTitleValidExt, '', $subtitleLabel));
                
                // tidy the label
                $reps = array('.', '-', '_');
                foreach($reps AS $rep)
                {
                    if(substr($subtitleLabel, strlen($subtitleLabel)-1, 1) == $rep)
                    {
                        $subtitleLabel = substr($subtitleLabel, 0, strlen($subtitleLabel)-1);
                    }
                    if(substr($subtitleLabel, 0, 1) == $rep)
                    {
                        $subtitleLabel = substr($subtitleLabel, 1, strlen($subtitleLabel)-1);
                    }
                }
                if(strlen($subtitleLabel) == 0)
                {
                    $subtitleLabel = 'Subtitles';
                }
                
                // format for javascript
                $subtitleArr[] = json_encode(array('file'=>$subtitleFile->generateDirectDownloadUrlForMedia(), 'label'=>$subtitleLabel, 'kind'=>'captions'));
            }
        }
        
        return $subtitleArr;
    }
}