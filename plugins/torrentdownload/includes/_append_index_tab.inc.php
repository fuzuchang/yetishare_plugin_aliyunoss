<?php

// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

$nonPaid = true;
if (($pluginSettings['show_torrent_tab_paid'] == 1) && ($Auth->level_id <= 2))
{
    $nonPaid = false;
}

$showTab = false;
if (($pluginSettings['show_torrent_tab'] == 1) || ($Auth->level_id >= 2))
{
    $showTab = true;
}

?>

<?php

if ($showTab == true):

?>
<li>
    <a href="#torrentDownload" data-toggle="tab">
        <?php

    echo UCWords(t('torrent', 'torrent'));

?>
    </a>
</li>
<?php

endif;

?>