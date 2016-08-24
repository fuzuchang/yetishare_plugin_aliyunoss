<?php

// includes and security
include_once ('../../../../core/includes/master.inc.php');
include_once (DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$torrentId = (int)$_REQUEST['torrentId'];

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginInstance = pluginHelper::getInstance('torrentdownload');

// load torrent details
$torrentData = $db->getRow('SELECT plugin_torrentdownload_torrent.*, users.username AS username FROM plugin_torrentdownload_torrent LEFT JOIN users ON plugin_torrentdownload_torrent.user_id=users.id WHERE plugin_torrentdownload_torrent.id = ' .
    $torrentId . ' LIMIT 1');

// get torrent contents
$torrentFiles = $db->getRows('SELECT * FROM plugin_torrentdownload_torrent_file WHERE torrent_id=' .
    (int)$torrentData['id']);

?>
<span id="popupMessageContainer"></span>
<form class="form">
    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_torrent_name",
    "torrent name"));

?>:</label>
        <div class="input" style="padding-top: 6px; width: 550px;">
            <?php

echo adminFunctions::makeSafe($torrentData['torrent_name'] . ' (' .
    coreFunctions::formatSize($torrentData['torrent_size']) . ')');

?>
        </div>
    </div>

    <div class="clearfix">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_status", "status"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe(UCWords($torrentData['save_status']));

?>
        </div>
    </div>
    
    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_progress", "progress"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe(coreFunctions::formatSize($torrentData['downloaded']));

?> / <?php

echo adminFunctions::makeSafe(coreFunctions::formatSize($torrentData['torrent_size']));

?> (<?php

echo adminFunctions::makeSafe(number_format($torrentData['download_percent'] /
    10, 2) . ' %');

?>)
        </div>
    </div>

    <div class="clearfix">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_date_added", "date added"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe(coreFunctions::formatDate($torrentData['date_added']));

?>
        </div>
    </div>
    
    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_owner", "owner"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <a href="<?php

echo ADMIN_WEB_ROOT;

?>/user_edit.php?id=<?php

echo $torrentData['user_id'];

?>"><?php

echo adminFunctions::makeSafe($torrentData['username']);

?></a>
        </div>
    </div>

    <div class="clearfix">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_speed", "speed"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe(coreFunctions::formatSize($torrentData['download_speed']));

?>s (down) / <?php

echo adminFunctions::makeSafe(coreFunctions::formatSize($torrentData['upload_speed']));

?>s (up)
        </div>
    </div>

    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_peers", "peers"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe($torrentData['peers_connected']);

?> / <?php

echo adminFunctions::makeSafe($torrentData['peers_in_swarm']);

?>
        </div>
    </div>

    <div class="clearfix">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_seeds", "seeds"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe($torrentData['seeds_connected']);

?> / <?php

echo adminFunctions::makeSafe($torrentData['seeds_in_swarm']);

?>
        </div>
    </div>
    
    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_torrent_hash",
    "torrent hash"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe($torrentData['torrent_hash']);

?>
        </div>
    </div>
    
    <div class="clearfix">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_save_path", "save path"));

?>:</label>
        <div class="input" style="padding-top: 6px;">
            <?php

echo adminFunctions::makeSafe($torrentData['save_path']);

?>
        </div>
    </div>

    <div class="clearfix alt-highlight">
        <label><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_torrent_file",
    "torrent contents"));

?>:</label>
        <div class="input" style="padding-top: 6px; width: 550px;">
            <?php

if (COUNT($torrentFiles) == 0)
{
    if ($torrentData['save_status'] == 'downloading')
    {
        echo 'Try again later.';
    }
    else
    {
        echo 'Unavailable.';
    }
}
else
{
    foreach ($torrentFiles as $torrentFile)
    {
        echo '- ' . adminFunctions::makeSafe($torrentFile['file_name']) . ' (' .
            adminFunctions::makeSafe(coreFunctions::formatSize($torrentFile['filesize'])) .
            ')<br/>';
    }
}

?>
        </div>
    </div>
</form>