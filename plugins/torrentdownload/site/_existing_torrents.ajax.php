<?php

// setup includes
require_once ('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginInstance = pluginHelper::getInstance('torrentdownload');

// get existing torrent downloads and any recent completed
$torrents = $db->getRows('SELECT * FROM plugin_torrentdownload_torrent WHERE (save_status = \'downloading\' AND user_id=' .
    (int)$Auth->id . ') OR (date_completed IS NOT NULL AND date_completed >= DATE_SUB(NOW(), INTERVAL 2 day) AND user_id=' .
    (int)$Auth->id . ') ORDER BY download_percent ASC');

?>

<?php if (COUNT($torrents)): ?>

<div class="urlUploadMainInternal contentPageWrapper" style="width: auto; margin-bottom: 20px;">
    <div>
        <div class="initialUploadText">
            <div class="uploadText">
                <h2><?php

    echo t('plugin_torrentdownload_pending_transfers', 'Torrent Transfers');

?>:</h2>
            </div>
            <div class="clearLeft"><!-- --></div>

            <div class="dataTables_wrapper">
                <table cellspacing="0" cellpadding="0" width="100%" id="existingTorrentTable" class="table table-striped">
                    <thead>
                        <th style="width: 16px;"></th>
                        <th><?php

    echo UCWords(t('plugin_torrentdownload_torrent_name', 'torrent name'));

?></th>
                        <th style="width: 100px; text-align:center;"><?php

    echo UCWords(t('plugin_torrentdownload_progress', 'progress'));

?></th>
                        <th style="width: 120px; text-align:center;"><?php

    echo UCWords(t('plugin_torrentdownload_status', 'status'));

?></th>
                    </thead>
                    <tbody>
                    <?php

                    foreach ($torrents as $i => $torrent)
                    {
                        echo '<tr ' . ($i % 2 == 0 ? 'class="odd"' : '') . '>';
                        $icon = 'blue_arrow_down.png';
                        if ($torrent['save_status'] == 'complete')
                        {
                            $icon = 'accept.png';
                        }
                        elseif ($torrent['save_status'] == 'cancelled')
                        {
                            $icon = 'block.png';
                        }
                        echo '<td><img src="' . PLUGIN_WEB_ROOT . '/torrentdownload/assets/img/' . $icon .
                            '" width="16" height="16" alt="' . validation::safeOutputToScreen(UCWords($torrent['save_status'])) .
                            '" title="' . validation::safeOutputToScreen(UCWords($torrent['save_status'])) .
                            '"/></td>';
                        echo '<td>';
                        if($torrent['save_status'] == 'cancelled')
                        {
                            echo '<strong class="text-danger">'.validation::safeOutputToScreen($torrent['status_notes']).'</strong><br/>';
                        }
                        echo validation::safeOutputToScreen($torrent['torrent_name']).' ('.validation::safeOutputToScreen(coreFunctions::formatSize($torrent['torrent_size'])).')';
                        echo '</td>';
                        echo '<td>';
                        if($torrent['save_status'] == 'cancelled')
                        {
                            echo '-';
                        }
                        else
                        {
                            // % progress
                            echo validation::safeOutputToScreen(number_format($torrent['download_percent'] / 10, 2)) . ' %';
                            
                            if($torrent['save_status'] == 'downloading')
                            {
                                // peers
                                if((int)$torrent['peers_in_swarm'] > 0)
                                {
                                    echo '<br/>'.(int)$torrent['peers_connected'].' / '.(int)$torrent['peers_in_swarm'].' '.UCWords(t('plugin_torrentdownload_peers', 'peers'));
                                }
                            }
                        }
                        echo '</td>';
                        echo '<td>';
                        echo validation::safeOutputToScreen(UCWords($torrent['save_status']));
                        if($torrent['save_status'] == 'downloading')
                        {
                            echo '<br/><a href="#" onClick="confirmRemoveTorrent('.(int)$torrent['id'].');">('.t('plugin_torrentdownload_cancel', 'cancel').')</a>';
                        }
                        elseif($torrent['save_status'] == 'cancelled')
                        {
                            echo '<br/><a href="#" onClick="removeTorrent('.(int)$torrent['id'].');">('.t('plugin_torrentdownload_remove', 'remove').')</a>';
                        }
                        elseif($torrent['save_status'] == 'complete')
                        {
                            echo '<br/><a href="#" onClick="removeTorrent('.(int)$torrent['id'].');">('.t('plugin_torrentdownload_clear', 'clear').')</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                
                ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="clear"><!-- --></div>
    </div>

    <div class="clear"><!-- --></div>
</div>

<?php endif; ?>