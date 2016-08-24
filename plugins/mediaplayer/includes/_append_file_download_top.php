<?php

if (isset($_REQUEST['dt']))
{
    include_once(PLUGIN_DIRECTORY_ROOT . 'mediaplayer/includes/_append_file_download_bottom.php');
}
else
{
    // load plugin details, already in session to limited overhead
    $pluginDetails = pluginHelper::pluginSpecificConfiguration('mediaplayer');
    $pluginConfig  = $pluginDetails['config'];
    $filePlayers   = $pluginConfig['players'];

    // try to load the file object
    $file = null;
    if (isset($_REQUEST['_page_url']))
    {
        // only keep the initial part if there's a forward slash
        $shortUrl = current(explode("/", $_REQUEST['_page_url']));
        $file     = file::loadByShortUrl($shortUrl);
    }

    /* load file details */
    if (!$file)
    {
        /* if no file found, redirect to home page */
        coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
    }

    // if we should skip the countdown for all users
    if (in_array(strtolower($file->extension), array_keys($filePlayers)))
    {
        $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
        if ((int) $pluginSettings['ignore_download_timer'] == 1)
        {
            // skip countdown
            $params['skipCountdown'] = true;
        }
    }
}
