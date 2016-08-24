<?php

// global includes
require_once('../../../core/includes/master.inc.php');

// log response
log::setContext('paypal');
log::info('Received called back on IPN: '.print_r($_REQUEST, true));

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('paypal');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$paypalEmail    = '';
$enable_sandbox_mode = 0;
if (strlen($pluginSettings))
{
    $pluginSettingsArr = json_decode($pluginSettings, true);
    $paypalEmail       = $pluginSettingsArr['paypal_email'];
	$enable_sandbox_mode = (int)$pluginSettingsArr['enable_sandbox_mode'];
}

// whether to validate ipn notifications by calling PayPal, recommended to keep as 'true'
// set to 'false' if you're having problems with automatic upgrades.
define('PAYPAL_VALIDATE_IPN_CALLBACK', true);

// validate IPN with PayPal
if ((PAYPAL_VALIDATE_IPN_CALLBACK == true) && ($enable_sandbox_mode != 1))
{
    // validate request originated from PayPal
    $req = 'cmd=_notify-validate';
    foreach ($_POST as $key => $value)
    {
        $value = urlencode(stripslashes($value));
        $req .= "&" . $key . "=" . $value;
    }

    // post back to PayPal system to validate
    $verified = false;
    $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
    $fp       = @fsockopen('ssl://www.paypal.com', 443, $errno, $errstr, 30);
    if (!$fp)
    {
        $verified = false;
    }
    else
    {
        fputs($fp, $header . $req);
        while (!feof($fp))
        {
            $res = fgets($fp, 1024);
            if (strcmp($res, "VERIFIED") == 0)
            {
                $verified = true;
            }
            else if (strcmp($res, "INVALID") == 0)
            {
                $verified = false;
            }
        }
        fclose($fp);
    }

    if ($verified == false)
    {
        // could not confirm payment params where from paypal
		log::info('Failed - could not confirm payment params where from paypal');
        die('Error: Please contact support.');
    }
}

// check for some required variables in the request
if ((!isset($_REQUEST['payment_status'])) || (!isset($_REQUEST['business'])))
{
    die();
}

// make sure payment has completed and it's for the correct PayPal account
if (($_REQUEST['payment_status'] == "Completed") && (strtolower($_REQUEST['business']) == $paypalEmail))
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
        $paypal_vars = "";
        foreach ($_REQUEST AS $k => $v)
        {
            $paypal_vars .= $k . " => " . $v . "\n";
        }
        $dbInsert                 = new DBObject("payment_log", array("user_id", "date_created", "amount",
            "currency_code", "from_email", "to_email", "description",
            "request_log", "payment_method")
        );
        $dbInsert->user_id        = $userId;
        $dbInsert->date_created   = date("Y-m-d H:i:s", time());
        $dbInsert->amount         = $_REQUEST['mc_gross'];
        $dbInsert->currency_code  = $_REQUEST['mc_currency'];
        $dbInsert->from_email     = $_REQUEST['payer_email'];
        $dbInsert->to_email       = $_REQUEST['business'];
        $dbInsert->description    = $order->description;
        $dbInsert->request_log    = $paypal_vars;
        $dbInsert->payment_method = 'PayPal';
        $dbInsert->insert();

        // make sure the amount paid matched what we expect
        if ($_REQUEST['mc_gross'] != $order->amount)
        {
            // order amounts did not match
			log::info('Failed - order amounts did not match');
            die();
        }

        // make sure the order is pending
        if ($order->order_status == 'completed')
        {
            // order has already been completed
			log::info('Failed - order has already been completed');
            die();
        }

        // update order status to paid
        $dbUpdate               = new DBObject("premium_order", array("order_status"), 'id');
        $dbUpdate->order_status = 'completed';
        $dbUpdate->id           = $orderId;
        $effectedRows           = $dbUpdate->update();
        if ($effectedRows === false)
        {
            // failed to update order
			log::info('Failed - failed to update order');
            die();
        }

        // extend/upgrade user
        $rs = UserPeer::upgradeUserByPackageId($userId, $order);
        if ($rs === false)
        {
            // failed to update user
			log::info('Failed - failed to update user');
            die();
        }

        // append any plugin includes
        pluginHelper::includeAppends('payment_ipn_paypal.php', array('order' => $order));
    }
}