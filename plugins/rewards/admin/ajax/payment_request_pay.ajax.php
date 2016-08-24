<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$gRequestId   = (int) $_REQUEST['gRequestId'];
$paypal_notes = $_REQUEST['paypal_notes'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    $db->query('UPDATE plugin_reward_withdraw_request SET status=\'paid\', payment_date = NOW(), payment_notes = :payment_notes WHERE id = :id', array('payment_notes' => $paypal_notes, 'id'            => $gRequestId));
    if ($db->affectedRows() == 1)
    {
        // update any aggregated data
        $db->query('UPDATE plugin_reward_aggregated SET status=\'paid\', payment_date=NOW() WHERE withdrawal_request_id = :id', array('id' => $gRequestId));

        $result['error'] = false;
        $result['msg']   = 'Payment request set as \'paid\'.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not update the payment request, please try again later.';
    }
}

echo json_encode($result);
exit;
