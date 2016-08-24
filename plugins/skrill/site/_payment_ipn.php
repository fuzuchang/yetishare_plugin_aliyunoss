<?php

require_once('../../../core/includes/master.inc.php');

// check for some required variables in the request
if ((!isset($_REQUEST['status'])) || (!isset($_REQUEST['pay_to_email'])))
{
    die();
}

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('skrill');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$skrillEmail    = '';
if (strlen($pluginSettings))
{
    $pluginSettingsArr = json_decode($pluginSettings, true);
    $skrillEmail       = $pluginSettingsArr['skrill_email'];
}

// make sure payment has completed and it's for the correct PayPal account
if (($_REQUEST['status'] == 2) && (strtolower($_REQUEST['pay_to_email']) == $skrillEmail))
{
    // load order using custom payment tracker hash
    $paymentTracker = $_REQUEST['custom'];
    $order          = OrderPeer::loadByPaymentTracker($paymentTracker);
    if ($order)
    {
        $extendedDays  = $order->days;
        $userId        = $order->user_id;
        $upgradeUserId = $order->upgrade_user_id;
        $orderId       = $order->id;

        // log in payment_log
        $response_vars = "";
        foreach ($_REQUEST AS $k => $v)
        {
            $response_vars .= $k . " => " . $v . "\n";
        }
        $dbInsert = new DBObject("payment_log",
                        array("user_id", "date_created", "amount",
                            "currency_code", "from_email", "to_email", "description",
                            "request_log", "payment_method")
        );
        $dbInsert->user_id = $userId;
        $dbInsert->date_created = date("Y-m-d H:i:s", time());
        $dbInsert->amount = $_REQUEST['mb_amount'];
        $dbInsert->currency_code = $_REQUEST['mb_currency'];
        $dbInsert->from_email = $_REQUEST['pay_from_email'];
        $dbInsert->to_email = $_REQUEST['pay_to_email'];
        $dbInsert->description = $extendedDays . ' days extension';
        $dbInsert->request_log = $response_vars;
        $dbInsert->payment_method = 'Skrill';
        $dbInsert->insert();

        // make sure the amount paid matched what we expect
        if ($_REQUEST['mb_amount'] != $order->amount)
        {
            // order amounts did not match
            die();
        }

        // make sure the order is pending
        if ($order->order_status == 'completed')
        {
            // order has already been completed
            die();
        }

        // update order status to paid
        $dbUpdate = new DBObject("premium_order", array("order_status"), 'id');
        $dbUpdate->order_status = 'completed';
        $dbUpdate->id = $orderId;
        $effectedRows = $dbUpdate->update();
        if ($effectedRows === false)
        {
            // failed to update order
            die();
        }

        // extend/upgrade user
        $rs = UserPeer::upgradeUser($userId, $order->days);
        if ($rs === false)
        {
            // failed to update user
            die();
        }

        // append any plugin includes
        pluginHelper::includeAppends('payment_ipn_paypal.php');
    }
}