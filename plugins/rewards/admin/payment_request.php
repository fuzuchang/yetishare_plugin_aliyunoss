<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Payment Requests');
define('ADMIN_SELECTED_PAGE', 'rewards');
define('ADMIN_SELECTED_SUB_PAGE', 'rewards_payment_request');

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
$statusDetails = array('paid', 'pending', 'cancelled');

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
    gRequestId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/payment_request.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date_requested', sWidth: '15%' , sClass: "dataTableFix"},
                { sName: 'user' , sClass: "adminResponsiveHide"},
                { sName: 'method', sWidth: '15%', sClass: "center" },
                { sName: 'amount', sWidth: '10%', sClass: "center" },
                { sName: 'status', sWidth: '13%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterByUser", "value": $('#filterByUser').val() } );
                aoData.push( { "name": "filterByStatus", "value": $('#filterByStatus').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/payment_request.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
        
        // dialog box
        $( "#setAsPaidPopup" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Set Paid": function() {
                    updateRequestProcess();
                    $("#setAsPaidPopup").dialog("close");
                },
                "Cancel": function() {
                    $("#setAsPaidPopup").dialog("close");
                }
            }
        });
    });

    function reloadTable()
    {
        oTable.fnDraw();
    }
    
    function setAsPaidPopup(requestId, usersPaymentMethod, paymentDetailsStr)
    {
        $('#payment_method').val(usersPaymentMethod);
        $('#payment_detail').val(paymentDetailsStr);
        $('#setAsPaidPopup').dialog('open');
        gRequestId = requestId;
    }

    function updateRequestProcess()
    {
        $.ajax({
            type: "POST",
            url: "ajax/payment_request_pay.ajax.php",
            data: { gRequestId: gRequestId, paypal_notes: $('#paypal_notes').val() },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    $('#setAsPaidPopup').dialog('close');
                    showSuccess(json.msg);
                    $('#paypal_notes').val('');
                    reloadTable();
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
        <h2>Payment Requests</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo adminFunctions::t('date_requested', 'Date Requested'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('user', 'User'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('method', 'Method'); ?></th>
                            <th class="align-left"><?php echo adminFunctions::t('amount', 'Amount'); ?></th>
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

<div id="setAsPaidPopup" title="Confirm Action">
    <p>Are you sure you want to set this payment as paid?</p>
    <form id="setPaidForm" class="form">
        <div class="clearfix">
            <label>Requested Method:</label>
            <div class="input">
                <input name="payment_method" id="payment_method" value="" type="text" class="xxlarge" READONLY/>
            </div>
        </div>
        <div class="clearfix alt-highlight">
            <label>Payment Details:</label>
            <div class="input">
                <textarea name="payment_detail" id="payment_detail"class="xxlarge" READONLY>dsf</textarea>
            </div>
        </div>
        <div class="clearfix">
            <label>Payment Notes:<br/>(reference etc)</label>
            <div class="input">
                <textarea name="paypal_notes" id="paypal_notes" class="xxlarge"></textarea>
            </div>
        </div>
    </form>
</div>

<?php
include_once(ADMIN_ROOT.'/_footer.inc.php');
?>