<?php

// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

$nonPaid = true;
if (($pluginSettings['show_torrent_tab_paid'] == 1) && ($Auth->level_id < 2))
{
    $nonPaid = false;
}

$showTab = false;
if (($pluginSettings['show_torrent_tab'] == 1) || ($Auth->level_id >= 2))
{
    $showTab = true;
}

if ($showTab == true)
{
    if (($nonPaid == false) || ($Auth->loggedIn() == false))
    {
?>    
        <!-- TORRENT DOWNLOAD -->
        <div id="torrentDownload" class="torrentdownload tab-pane">
            <div class="urlUploadMain ui-corner-all">
                <div id="torrentdownloadExistingDownloads"><!-- --></div>
                <div class="urlUploadMainInternal contentPageWrapper" style="width: auto;">
                    <div>
                        <div class="initialUploadText">
                            <div class="uploadText">
                                <h2><?php

        echo t('plugin_torrentdownload_torrent_download', 'Download Torrents');

?>:</h2>
                            </div>
                            <div class="clearLeft"><!-- --></div>

                            <div>
                                <?php

        echo t("plugin_torrentdownload_login_to_download_paid",
            "You need a paid account to upload files using torrents. Go to the <a href='[[[WEB_ROOT]]]/register.[[[SITE_CONFIG_PAGE_EXTENSION]]]'>registration page</a> to create an account now.",
            array('WEB_ROOT' => WEB_ROOT, 'SITE_CONFIG_PAGE_EXTENSION' =>
                SITE_CONFIG_PAGE_EXTENSION));

?>
                            </div>
                        </div>
                        <div class="clear"><!-- --></div>
                    </div>

                    <div class="clear"><!-- --></div>
                </div>
            </div>
        </div>
        <?php

    }
    else
    {

?>

    <script>
        var gTableLoaded = false;
        $(document).ready(function() {
            loadExistingTorrentDownloads();

            // refresh every 10 seconds
            window.setInterval(function() {
                if (gTableLoaded == false)
                {
                    return true;
                }
                gTableLoaded = false;
                loadExistingTorrentDownloads();
            }, 10000);

            $(':file').change(function() {
                $('#urlTorrentList').val('');
            });
        });

        function setupDatatable()
        {
            $('#existingTorrentTable').dataTable({
                "sPaginationType": "full_numbers",
                "bAutoWidth": false,
                "bProcessing": false,
                "iDisplayLength": 20,
                "bFilter": false,
                "bSort": true,
                "bDestroy": true,
                "bLengthChange": false,
                "bPaginate": false,
                "bInfo": false,
                "aoColumns": [
                    {sClass: "alignCenter text-center"},
                    {},
                    {sClass: "alignCenter text-center"},
                    {sClass: "alignCenter text-center"}
                ],
                "oLanguage": {
                    "oPaginate": {
                        "sFirst": "<?php

        echo t('datatable_first', 'First');

?>",
                        "sPrevious": "<?php

        echo t('datatable_previous', 'Previous');

?>",
                        "sNext": "<?php

        echo t('datatable_next', 'Next');

?>",
                        "sLast": "<?php

        echo t('datatable_last', 'Last');

?>"
                    },
                    "sEmptyTable": "<?php

        echo t('datatable_no_data_available_in_table', 'No data available in table');

?>",
                    "sInfo": "<?php

        echo t('datatable_showing_x_to_x_of_total_entries',
            'Showing _START_ to _END_ of _TOTAL_ entries');

?>",
                    "sInfoEmpty": "<?php

        echo t('datatable_no_data', 'No data');

?>",
                    "sLengthMenu": "<?php

        echo t('datatable_show_menu_entries', 'Show _MENU_ entries');

?>",
                    "sProcessing": "<?php

        echo t('datatable_loading_please_wait', 'Loading, please wait...');

?>",
                    "sInfoFiltered": "<?php

        echo t('datatable_base_filtered', ' (filtered)');

?>",
                    "sSearch": "<?php

        echo t('datatable_search_text', 'Search:');

?>",
                    "sZeroRecords": "<?php

        echo t('datatable_no_matching_records_found', 'No matching records found');

?>"
                }
            });
        }

        function torrentdownloadUploadFiles()
        {
            // get textarea contents
            formData = new FormData($('#torrentUploadForm')[0]);
            $('#transferFilesButtonTorrent').hide();
            $('#transferFilesButtonProcessing').show();

            // send via ajax
            $.ajax({
                dataType: "json",
                type: 'POST',
                url: "<?php echo PLUGIN_WEB_ROOT; ?>/torrentdownload/site/_add_torrent.ajax.php",
                data: formData,
                xhr: function() {  // Custom XMLHttpRequest
                    var myXhr = $.ajaxSettings.xhr();
                    return myXhr;
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    if (data.error == true)
                    {
                        // error
                        $('#transferFilesButtonProcessing').hide();
                        $('#transferFilesButtonTorrent').show();
                        alert(data.msg);
                    }
                    else
                    {
                        // success
                        // reload existing torrent data
                        loadExistingTorrentDownloads();
                        $('html, body').animate({
                            scrollTop: $("#torrentdownloadExistingDownloads").offset().top
                        }, 2000);

                        // clear existing data
                        $('#urlTorrentList').val('');
                        $('#torrentFile').val('');
                        $('#transferFilesButtonProcessing').hide();
                        $('#transferFilesButtonTorrent').show();
                    }
                }
            });
        }


        function loadExistingTorrentDownloads()
        {
            $('#torrentdownloadExistingDownloads').load("<?php echo PLUGIN_WEB_ROOT; ?>/torrentdownload/site/_existing_torrents.ajax.php", function() {
                setupDatatable();
                gTableLoaded = true;
            });
        }
        function confirmRemoveTorrent(torrentId)
        {
            if(confirm('<?php echo str_replace("'", "", t('plugin_torrentdownload_are_you_sure_you_want_to_remove', 'Are you sure you want to cancel this torrent?')); ?>'))
            {
                return removeTorrent(torrentId);            
            }
            
            return false;
        }
        
        function removeTorrent(torrentId)
        {
            $.ajax({
                type: "POST",
                url: "<?php echo PLUGIN_WEB_ROOT; ?>/torrentdownload/site/_torrent_remove.ajax.php",
                data: { gRemoveTorrentId: torrentId },
                dataType: 'json',
                success: function(json) {
                    if(json.error == true)
                    {
                        alert(json.msg);
                    }
                    else
                    {
                        loadExistingTorrentDownloads();
                    }
                    
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert('Error getting response from server. '+XMLHttpRequest.responseText);
                }
            });
        }
    </script>

    <!-- TORRENT DOWNLOAD -->
    <div id="torrentDownload" class="torrentdownload tab-pane">
        <div class="urlUploadMain ui-corner-all">
            <div id="torrentdownloadExistingDownloads"><!-- --></div>
            <div class="urlUploadMainInternal contentPageWrapper" style="width: auto;">
                <div>
                    <div class="initialUploadText">
                        <div class="uploadText">
                            <h2><?php

        echo t('plugin_torrentdownload_torrent_download', 'Download Torrents');

?>:</h2>
                        </div>
                        <div class="clearLeft"><!-- --></div>

                        <div>
                            <div id="urltorrentdownloadUploader">
                                <form action="#" method="POST" enctype="multipart/form-data" id="torrentUploadForm">
                                    <div class="torrentdownloadTextBox">
                                        <?php

        echo t("plugin_torrentdownload_tab_content_intro",
            "Use your account to download torrent files. Paste your torrent or magnet link below and click 'Transfer Files'. You can leave this page, your torrents will continue to download in the background.");

?>
                                        <br/><br/>
                                    </div>
                                    <div class="initialUploadText">
                                        <div class="inputElement">
                                            <textarea name="urlTorrentList" id="urlTorrentList" class="urlTorrentList urlList form-control" placeholder="magnet:..." onKeyUp="$('#torrentFile').val(''); return false;"></textarea>
                                            <div class="clear"><!-- --></div>
                                        </div>
                                    </div>
                                    <div class="urlUploadFooter">
                                        <div class="upload-button upload-button-v2">
                                            <button id="transferFilesButton" onClick="torrentdownloadUploadFiles(); return false;" class="btn btn-green btn-lg" type="button"><?php

        echo t("set_transfer_files", "Transfer Files");

?> <i class="entypo-upload"></i></button>
                                        </div>
										<div class="inputElement" style="padding-top: 5px; width: 50%;">
                                            or upload torrent file: <input type="file" name="torrentFile" id="torrentFile" class="torrentFile"/>
                                            <div class="clear"><!-- --></div>
                                        </div>
                                        <div class="clear"><!-- --></div>
                                    </div>
                                    <div class="clear"><!-- --></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="clear"><!-- --></div>
                </div>

                <div class="clear"><!-- --></div>
            </div>
        </div>
    </div>
    <?php

    }
}

?>