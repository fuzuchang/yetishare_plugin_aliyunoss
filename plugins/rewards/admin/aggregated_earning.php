<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Aggregated Earnings');
define('ADMIN_SELECTED_PAGE', 'rewards');
define('ADMIN_SELECTED_SUB_PAGE', 'rewards_aggregated_earning');

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
$statusDetails = array('available', 'cancelled', 'requested', 'payment_in_progress', 'paid');

// load all months
$monthData = $db->getRows('SELECT period FROM plugin_reward_aggregated GROUP BY period ORDER BY period DESC');
$filterMonths = array();
if($monthData)
{
    foreach($monthData AS $monthRow)
    {
        $filterMonths[date('F Y', strtotime($monthRow{'period'}))] = $monthRow['period'];
    }
}

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

$filterByMonth = null;
if (isset($_REQUEST['filterByMonth']))
{
    $filterByMonth = $_REQUEST['filterByMonth'];
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
            "sAjaxSource": 'ajax/aggregated_earning.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'period', sWidth: '9%' },
                { sName: 'user', sWidth: '15%' , sClass: "adminResponsiveHide"},
                { sName: 'description' , sClass: "adminResponsiveHide"},
                { sName: 'amount', sWidth: '11%', sClass: "center adminResponsiveHide" },
                { sName: 'status', sWidth: '13%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                aoData.push( { "name": "filterByMonth", "value": $('#filterByMonth').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/aggregated_earning.ajax.php",
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
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
    <div class="widget clearfix">
        <h2>Earnings</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('period', 'Period'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('user', 'User'); ?></th>
                            <th><?php echo adminFunctions::t('description', 'Description'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('amount', 'Amount'); ?></th>
                            <th><?php echo adminFunctions::t('status', 'Status'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <?php echo $rewardObj->settings['payment_lead_time']; ?> day clearing period on all rewards. Next update <?php echo date('jS F Y', SITE_CONFIG_NEXT_CHECK_FOR_REWARDS_AGGREGATION); ?>.
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label class="adminResponsiveHide" style="padding-left: 6px;">
        By Month:
        <select name="filterByMonth" id="filterByMonth" onChange="reloadTable(); return false;" style="width: 160px;">
            <option value="">- all -</option>
            <?php
            if (COUNT($filterMonths))
            {
                foreach ($filterMonths AS $filterMonth)
                {
                    echo '<option value="' . date('Y-m', strtotime($filterMonth)) . '"';
                    if (($filterByMonth) && ($filterByMonth == $filterMonth))
                    {
                        echo ' SELECTED';
                    }
                    echo '>' . date('F Y', strtotime($filterMonth)) . '</option>';
                }
            }
            ?>
        </select>
    </label>
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

<?php
include_once(ADMIN_ROOT.'/_footer.inc.php');
?>