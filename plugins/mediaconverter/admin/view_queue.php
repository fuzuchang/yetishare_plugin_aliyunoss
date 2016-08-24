<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'View Conversion Queue');
define('ADMIN_SELECTED_PAGE', 'view_queue');
define('ADMIN_SELECTED_SUB_PAGE', 'view_queue');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// process submits
if(isset($_REQUEST['cancel']))
{
    $db->query('UPDATE plugin_mediaconverter_queue SET status = \'cancelled\' WHERE id='.(int)$_REQUEST['cancel'].' LIMIT 1');
    adminFunctions::setSuccess('Conversion job cancelled.');
}
elseif(isset($_REQUEST['redo']))
{
    $db->query('UPDATE plugin_mediaconverter_queue SET status = \'pending\' WHERE id='.(int)$_REQUEST['redo'].' LIMIT 1');
    adminFunctions::setSuccess('Conversion job re-scheduled.');
}

// overview stats
$totalPending = (int) $db->getValue("SELECT COUNT(id) AS total FROM plugin_mediaconverter_queue WHERE status = 'pending'");
$totalFailedLast3Days = (int) $db->getValue("SELECT COUNT(id) AS total FROM plugin_mediaconverter_queue WHERE status = 'pending' AND date_started BETWEEN NOW() - INTERVAL 3 DAY AND NOW()");
$totalConversions = (int) $db->getValue("SELECT COUNT(id) AS total FROM plugin_mediaconverter_queue WHERE status = 'completed'");
$totalConversionsLast3Days = (int) $db->getValue("SELECT COUNT(id) AS total FROM plugin_mediaconverter_queue WHERE status = 'completed' AND date_finished BETWEEN NOW() - INTERVAL 3 DAY AND NOW()");

// page header
include_once(ADMIN_ROOT.'/_header.inc.php');

// load all status
$statusDetails = array('pending', 'processing', 'completed', 'failed', 'cancelled');
?>

<script>
    oTable = null;
    gRewardId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/view_queue.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_added', sWidth: '15%', sClass: "center dataTableFix" },
                { bSortable: false, sName: 'file', sClass: "adminResponsiveHide" },
                { sName: 'status', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'date_started', sWidth: '16%', sClass: "center dataTableFix" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/view_queue.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
    });

    function reloadTable()
    {
        oTable.fnDraw();
    }
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2><?php echo adminFunctions::t('quick_overview', 'Quick Overview'); ?></h2>
            <div class="widget_inside">
                <div class="report">
                    <div class="button">
                        <span class="value"><?php echo $totalPending; ?></span>
                        <span class="attr">Pending Conversions</span>
                    </div>
                    <div class="button">
                        <span class="value"><?php echo $totalFailedLast3Days; ?></span>
                        <span class="attr">Failed Last 3 Days</span>
                    </div>
                    <div class="button">
                        <span class="value"><?php echo $totalConversions; ?></span>
                        <span class="attr">Total Completed</span>
                    </div>
                    <div class="button">
                        <span class="value"><?php echo $totalConversionsLast3Days; ?></span>
                        <span class="attr">Completed Last 3 Days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row clearfix">
    <div class="widget clearfix">
        <h2>Converter Queue</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php echo adminFunctions::t('date_added', 'Date Added'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('file', 'File'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th><?php echo adminFunctions::t('date_started', 'Date Started'); ?></th>
                            <th><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        By Filename:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Status:
        <select name="filterByStatus" id="filterByStatus" onChange="reloadTable(); return false;" style="width: 120px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($statusDetails))
            {
                foreach ($statusDetails AS $statusDetail)
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

<?php
include_once(ADMIN_ROOT.'/_footer.inc.php');
?>