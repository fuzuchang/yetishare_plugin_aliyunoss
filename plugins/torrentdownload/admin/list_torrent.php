<?php

// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Torrents');
define('ADMIN_SELECTED_PAGE', 'torrents');

// includes and security
include_once ('../../../core/includes/master.inc.php');
include_once (DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// get instance
$pluginObj = pluginHelper::getInstance('torrentdownload');
$torrentPluginSettings = $pluginObj->settings;

// prepare filters
$statusDetails = array(
    'downloading',
    'pending',
    'processing',
    'cancelled',
    'complete');
$filterByStatus = 'downloading';

// prepare full utorrent host url
$uTorrentUrl = '';
if (isset($torrentPluginSettings['utorrent_host']))
{
    $uTorrentUrl = $torrentPluginSettings['utorrent_host'];
    if ((isset($torrentPluginSettings['utorrent_port'])) && strlen($torrentPluginSettings['utorrent_port']))
    {
        $uTorrentUrl .= ':' . $torrentPluginSettings['utorrent_port'];
    }
}

$torrentGuiUrl = $uTorrentUrl.'/gui/';
$torrent_host = $torrentPluginSettings['utorrent_host'];
if($torrentPluginSettings['torrent_server'] == 'transmission')
{
    $torrentGuiUrl = $torrentPluginSettings['transmission_host'].":".$torrentPluginSettings['transmission_port'];
    $torrent_host = $torrentPluginSettings['transmission_host'];
}                    

// page header
include_once (ADMIN_ROOT . '/_header.inc.php');

?>

<!-- Load jQuery build -->
<script type="text/javascript">
    oTable = null;
    gRemoveTorrentId = null;
    gTableLoaded = false;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/list_torrent.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 4, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center" },
                { sName: 'torrent_name' },
                { sName: 'size', sWidth: '8%', sClass: "center" },
                { sName: 'status', sWidth: '10%', sClass: "center" },
                { sName: 'progress', sWidth: '10%', sClass: "center" },
                { sName: 'download_speed', sWidth: '13%', sClass: "center" },
                { sName: 'time_remaining', sWidth: '10%', sClass: "center" },
                { sName: 'user', sWidth: '10%', sClass: "center" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],                                
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/list_torrent.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
                gTableLoaded = true;
            },
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

echo t('plugin_torrentdownload_datatable_no_matching_records_found',
    'No torrents found in current search filter.');

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

echo t('plugin_torrentdownload_datatable_no_matching_records_found',
    'No torrents found in current search filter.');

?>"
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: 800,
            buttons: {
                "Confirm Removal": function() {
                    removeTorrent();
                    $("#confirmDelete").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            }
        });
        
        $( "#viewTorrentDetails" ).dialog({
            modal: true,
            autoOpen: false,
            width: 800,
            height: 540,
            buttons: {
                "Close": function() {
                    $("#viewTorrentDetails").dialog("close");
                }
            }
        });
        
        // refresh every 10 seconds
        window.setInterval(function(){
            if(gTableLoaded == false)
            {
                return true;
            }
            gTableLoaded = false;
            reloadTable();
        }, 20000);
    });

    function reloadTable()
    {
        oTable.fnDraw();
    }
    
    function viewTorrentDetails(torrentId)
    {
        $('#viewTorrentDetails').html('Loading...');
        $('#viewTorrentDetails').dialog('open');
        $('#viewTorrentDetails').load('ajax/list_torrent_detail.ajax.php', {torrentId: torrentId});
    }
    
    function confirmRemoveTorrent(torrentId)
    {
        $('#confirmDelete').dialog('open');
        gRemoveTorrentId = torrentId;
    }
    
    function removeTorrent()
    {
        $.ajax({
            type: "POST",
            url: "ajax/list_torrent_remove.ajax.php",
            data: { gRemoveTorrentId: gRemoveTorrentId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function resyncUTorrent()
    {
        $.ajax({
            type: "POST",
            url: "../site/track_torrents.cron.php",
            dataType: 'json',
            success: function() {
                showSuccess('Data resynced with uTorrent.');
                reloadTable();
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
    <div class="widget clearfix">
        <h2>Torrents</h2>
        <div class="widget_inside">
            <?php

echo adminFunctions::compileNotifications();

?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_torrent_name",
    "torrent name"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_size", "size"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_status", "status"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_progress", "progress"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_download_speed",
    "speed down/up"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_remaining", "remaining"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("plugin_torrentdownload_user", "user"));

?></th>
                            <th class="align-left"><?php

echo UCWords(adminFunctions::t("action", "action"));

?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>
            
            <div style="float: right;">
                <a href="#" class="button blue" onClick="window.open('http://<?php echo adminFunctions::makeSafe($torrent_host); ?>/plugins/torrentdownload/site/track_torrents.cron.php');">Trigger Torrent Cron</a>
            </div>
            
            <div style="float: left;">
                <a href="http://<?php echo adminFunctions::makeSafe($torrentGuiUrl); ?>" class="button blue" target="_blank">Torrent Engine GUI</a>
            </div>

            <div class="clear"></div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label style="padding-left: 6px;">
        By Status:
        <select name="filterByStatus" id="filterByStatus" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php

if (COUNT($statusDetails))
{
    foreach ($statusDetails as $statusDetail)
    {
        echo '<option value="' . $statusDetail . '"';
        if (($filterByStatus) && ($filterByStatus == $statusDetail))
        {
            echo ' SELECTED';
        }
        echo '>' . UCWords(str_replace("_", " ", $statusDetail)) . '</option>';
    }
}

?>
        </select>
    </label>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to remove this torrent? Any downloaded files will be deleted.</p>
</div>

<div id="viewTorrentDetails" title="Torrent Details">
    <p>Loading...</p>
</div>

<?php

include_once (ADMIN_ROOT . '/_footer.inc.php');

?>