<?php

require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('skrill');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$skrillEmail    = '';
if (strlen($pluginSettings))
{
    $pluginSettingsArr = json_decode($pluginSettings, true);
    $skrillEmail       = $pluginSettingsArr['skrill_email'];
}

if (!isset($_REQUEST['days']))
{
    coreFunctions::redirect(WEB_ROOT . '/index.html');
}

// require login
if (!isset($_REQUEST['i']))
{
    $Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);
    $userId    = $Auth->id;
    $username  = $Auth->username;
    $userEmail = $Auth->email;
}
else
{
    $user = UserPeer::loadUserByIdentifier($_REQUEST['i']);
    if (!$user)
    {
        die('User not found!');
    }

    $userId    = $user->id;
    $username  = $user->username;
    $userEmail = $user->email;
}

$days = (int) (trim($_REQUEST['days']));

$fileId = null;
if (isset($_REQUEST['f']))
{
    $file = file::loadByShortUrl($_REQUEST['f']);
    if ($file)
    {
        $fileId = $file->id;
    }
}

// create order entry
$orderHash = MD5(time() . $userId);
$amount    = number_format(constant('SITE_CONFIG_COST_FOR_' . $days . '_DAYS_PREMIUM'), 2);
$order     = OrderPeer::create($userId, $orderHash, $days, $amount, $fileId);
if ($order)
{
    // redirect to the payment gateway
    $desc = $days . ' days extension for ' . $username;

    $gatewayUrl = 'https://www.moneybookers.com/app/payment.pl?';
    $gatewayUrl .= 'pay_to_email=' . urlencode($skrillEmail) . '&';
    $gatewayUrl .= 'recipient_description=' . urlencode(SITE_CONFIG_SITE_NAME) . '&';
    $gatewayUrl .= 'return_url=' . urlencode(WEB_ROOT . '/payment_complete.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $gatewayUrl .= 'return_url_target=1&';
    $gatewayUrl .= 'cancel_url=' . urlencode(WEB_ROOT . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $gatewayUrl .= 'cancel_url_target=1&';
    $gatewayUrl .= 'status_url=' . urlencode(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/_payment_ipn.php') . '&';
    $gatewayUrl .= 'language=EN&';
    $gatewayUrl .= 'confirmation_note=' . urlencode('Thanks for your payment, your account has been upgraded.') . '&';
    $gatewayUrl .= 'merchant_fields=custom&';
    $gatewayUrl .= 'custom=' . urlencode($orderHash) . '&';
    $gatewayUrl .= 'amount=' . urlencode($amount) . '&';
    $gatewayUrl .= 'currency='.SITE_CONFIG_COST_CURRENCY_CODE.'&';
    $gatewayUrl .= 'detail1_description=' . urlencode($desc) . '&';
    $gatewayUrl .= 'detail1_text='.urlencode('user: '.$username).'&';
    $gatewayUrl .= 'ondemand_max_currency='.SITE_CONFIG_COST_CURRENCY_CODE.'&';
    $gatewayUrl .= 'pm=NGP&';
    $gatewayUrl .= 'submit_id=Submit&';

    coreFunctions::redirect($gatewayUrl);
}
