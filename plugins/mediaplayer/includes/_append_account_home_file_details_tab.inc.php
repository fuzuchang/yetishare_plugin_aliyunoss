<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

if ($file->statusId == 1)
{
    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaplayer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // load available extensions for this user
    $extType = 'non_media_types';
    if ($Auth->level_id == 1)
    {
        $extType = 'free_media_types';
    }
    elseif ($Auth->level_id > 1)
    {
        $extType = 'paid_media_types';
    }

    $ext = explode("|", $pluginSettings[$extType]);

    // check this is a video or audio, only 'mp4', 'webm', 'mp3' supported in this tab view
    if ((in_array(strtolower($file->extension), $ext) && (in_array(strtolower($file->extension), array('mp4', 'webm', 'ogg')))))
    {
        ?>
        <li>
            <a href="#mediaplayer-preview" data-toggle="tab"><i class="entypo-doc-text"></i> <?php echo UCWords(t('watch_video', 'watch video')); ?></a>
        </li>
        <?php
    }
    elseif ((in_array(strtolower($file->extension), $ext) && (in_array(strtolower($file->extension), array('mp3')))))
    {
        ?>
        <li>
            <a href="#mediaplayer-preview" data-toggle="tab"><i class="entypo-doc-text"></i> <?php echo UCWords(t('play_audio', 'play audio')); ?></a>
        </li>
        <?php
    }
}
?>