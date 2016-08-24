<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Detailed Referrals');
define('ADMIN_SELECTED_PAGE', 'rewards');
define('ADMIN_SELECTED_SUB_PAGE', 'rewards_detailed_referral');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// get instance
$rewardObj = pluginHelper::getInstance('rewards');
$rewardObj->clearPendingRewards();
$rewardObj->aggregateRewards();

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
            "sAjaxSource": 'ajax/detailed_referral.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'reward_date', sWidth: '15%', sClass: "dataTableFix" },
                { bSortable: false, sName: 'upgrade_source', sClass: "adminResponsiveHide" },
                { sName: 'user', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'reward_amount', sWidth: '12%', sClass: "center dataTableFix" },
                { sName: 'original_order_number', sWidth: '12%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '10%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/detailed_referral.ajax.php",
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
                    removeFile();
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

    function removeFile()
    {
        $.ajax({
            type: "POST",
            url: "ajax/detailed_referral_cancel.ajax.php",
            data: { gRewardId: gRewardId, cancel_reason: $('#cancel_reason').val() },
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
        <h2>Referrals</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('reward_date', 'Reward Date'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('upgrade_source', 'Upgrade Source'); ?></th>
                            <th><?php echo adminFunctions::t('user', 'User'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('amount', 'Amount'); ?></th>
                            <th><?php echo adminFunctions::t('original_order_number', 'Original Order #'); ?></th>
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
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to cancel this reward?</p>
    <form id="removeFileForm" class="form">
        <div class="clearfix">
            <label>Cancellation Reason:</label>
            <div class="input">
                <select name="cancel_reason" id="cancel_reason" class="large">
                    <option value="cancelled">Cancelled</option>
                    <option value="charged_back">Charged Back</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
        </div>
    </form>
</div>

<?php
include_once(ADMIN_ROOT.'/_footer.inc.php');
?>