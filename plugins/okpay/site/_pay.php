<?php

require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('okpay');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$okReceiver    = '';
if (strlen($pluginSettings))
{
    $pluginSettingsArr = json_decode($pluginSettings, true);
    $okReceiver       = $pluginSettingsArr['ok_receiver'];
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
$amount    = str_replace(",", "", constant('SITE_CONFIG_COST_FOR_' . $days . '_DAYS_PREMIUM'));
$order     = OrderPeer::create($userId, $orderHash, $days, $amount, $fileId);
if ($order)
{    
    // redirect to the payment gateway
    $desc = $days . ' days extension for ' . $username;
    
    $paymentUrl = 'https://www.okpay.com/process.html?';
    $paymentUrl .= 'ok_receiver=' . urlencode($okReceiver) . '&';
    $paymentUrl .= 'ok_item_1_name=' . urlencode($desc) . '&';
    $paymentUrl .= 'ok_item_1_price=' . urlencode($amount) . '&';
    $paymentUrl .= 'ok_currency=' . urlencode(SITE_CONFIG_COST_CURRENCY_CODE) . '&';
    $paymentUrl .= 'ok_return_success=' . urlencode(WEB_ROOT . '/payment_complete.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $paymentUrl .= 'ok_return_fail=' . urlencode(WEB_ROOT . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $paymentUrl .= 'ok_ipn=' . urlencode(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/_payment_ipn.php') . '?custom='.urlencode($orderHash).'&';
    $paymentUrl .= 'ok_item_1_custom_1_value=' . urlencode($orderHash) . '&';

    coreFunctions::redirect($paymentUrl);
}
