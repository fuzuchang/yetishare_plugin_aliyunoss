<?php

// setup includes
require_once ('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

$gRemoveTorrentId = (int)$_REQUEST['gRemoveTorrentId'];

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginInstance = pluginHelper::getInstance('torrentdownload');

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';

// load torrent details
$torrentData = $db->getRow('SELECT * FROM plugin_torrentdownload_torrent WHERE id=' .
    (int)$gRemoveTorrentId . ' AND user_id = '.(int)$Auth->id.' LIMIT 1');
if (!$torrentData)
{
    $result['error'] = true;
    $result['msg'] = adminFunctions::t("plugin_torrentdownload_could_not_find_torrent",
        "Could not find torrent.");
}
else
{
    // utorrent
    if($pluginSettings['torrent_server'] == 'utorrent')
    {
        // remove torrent from uTorrent
        $uTorrent = $pluginInstance->connectUTorrent();
        $uTorrent->ExecAction('removedata', $torrentData['torrent_hash']);
    }
    // transmission
    elseif($pluginSettings['torrent_server'] == 'transmission')
    {
        // remove torrent from Transmission
        $rpc = $pluginInstance->connectTransmission();
        $rpc->remove($torrentData['torrent_hash'], true);
    }

    // delete local record
    $db->query('DELETE FROM plugin_torrentdownload_torrent_file WHERE torrent_id = :id',
        array('id' => $torrentData['id']));
    $db->query('DELETE FROM plugin_torrentdownload_torrent WHERE id = :id', array('id' =>
            $torrentData['id']));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg'] = 'Torrent removed.';
    }
    else
    {
        $result['error'] = true;
        $result['msg'] = 'Could not remove torrent, please try again later.';
    }
}

echo json_encode($result);
exit;
