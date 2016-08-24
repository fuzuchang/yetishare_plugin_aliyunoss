<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Detailed Downloads');
define('ADMIN_SELECTED_PAGE', 'rewards');
define('ADMIN_SELECTED_SUB_PAGE', 'rewards_detailed_download');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// get instance
$rewardObj = pluginHelper::getInstance('rewards');
$rewardObj->clearPendingRewards();
$rewardObj->aggregateRewards();
$rewardObj->pruneData();

// page header
include_once(ADMIN_ROOT.'/_header.inc.php');

// load all users
$sQL         = "SELECT id, username AS selectValue FROM users ORDER BY username";
$userDetails = $db->getRows($sQL);

// load all referral status
$statusDetails = array('pending', 'cancelled', 'charged_back', 'refunded', 'cleared');

$filterByStatus = '';
if (isset($_REQUEST['filterByStatus']))
{
    $filterByStatus = (int) $_REQUEST['filterByStatus'];
}

$filterByUser = null;
if (isset($_REQUEST['filterByUser']))
{
    $filterByUser = (int) $_REQUEST['filterByUser'];
}
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
            "sAjaxSource": 'ajax/detailed_download.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'download_date', sWidth: '15%' , sClass: "dataTableFix" },
                { sName: 'user', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'file',  sClass: "center adminResponsiveHide" },
                { sName: 'reward_group', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'amount', sWidth: '10%', sClass: "center" },
                { sName: 'status', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                aoData.push( { "name": "filterByGroupData", "value": $('#filterByGroupData').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/detailed_download.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Confirm": function() {
                    removeDownloadReward();
                    $("#confirmDelete").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            }
        });
    });

    function reloadTable()
    {
        oTable.fnDraw();
    }
    
    function confirmRemoveReward(rewardId)
    {
        $('#confirmDelete').dialog('open');
        gRewardId = rewardId;
    }

    function removeDownloadReward()
    {
        $.ajax({
            type: "POST",
            url: "ajax/detailed_download_cancel.ajax.php",
            data: { gRewardId: gRewardId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    $('#removal_type').val('cancelled');
                    oTable.fnDraw(); 
                }
                
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
        <h2>Downloads</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('download_date', 'Download Date'); ?></th>
                            <th><?php echo adminFunctions::t('reward_user', 'Reward User'); ?></th>
                            <th><?php echo adminFunctions::t('file', 'File'); ?></th>
                            <th><?php echo adminFunctions::t('reward_group', 'Reward Group'); ?></th>
                            <th><?php echo adminFunctions::t('amount', 'Amount'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
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
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By User:
        <select name="filterByUser" id="filterByUser" onChange="reloadTable(); return false;" style="width: 160px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($userDetails))
            {
                foreach ($userDetails AS $userDetail)
                {
                    echo '<option value="' . $userDetail['id'] . '"';
                    if (($filterByUser) && ($filterByUser == $userDetail['id']))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . $userDetail['selectValue'] . '</option>';
                }
            }
            ?>
        </select>
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
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        Group Results:
        <select name="filterByGroupData" id="filterByGroupData" onChange="reloadTable(); return false;" style="width: 120px;">
            <?php
            $options = array('no', 'yes');
            foreach ($options AS $option)
            {
                echo '<option value="' . $option . '"';
                echo '>' . UCWords($option) . '</option>';
            }
            ?>
        </select>
    </label>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to cancel this download reward?</p>
</div>

<?php
include_once(ADMIN_ROOT.'/_footer.inc.php');
?>