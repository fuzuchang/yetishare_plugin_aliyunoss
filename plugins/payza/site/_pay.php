<?php

require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('payza');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$payzaEmail     = '';
$payzaUrl       = 'https://secure.payza.com/checkout';
if (strlen($pluginSettings))
{
    $pluginSettingsArr = json_decode($pluginSettings, true);
    $payzaEmail        = $pluginSettingsArr['payza_email'];
    $useSandbox        = (int) $pluginSettingsArr['use_sandbox'];
    if ($useSandbox == 1)
    {
        $payzaUrl = 'https://sandbox.payza.com/sandbox/payprocess.aspx';
    }
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

    $payzaUrl = $payzaUrl . '?';
    $payzaUrl .= 'ap_merchant=' . urlencode($payzaEmail) . '&';
    $payzaUrl .= 'ap_purchasetype=item-goods&';
    $payzaUrl .= 'ap_itemname=' . urlencode($desc) . '&';
    $payzaUrl .= 'ap_amount=' . urlencode($amount) . '&';
    $payzaUrl .= 'ap_currency=' . urlencode(SITE_CONFIG_COST_CURRENCY_CODE) . '&';
    $payzaUrl .= 'ap_quantity=1&';
    $payzaUrl .= 'ap_description=' . urlencode($desc) . '&';
    $payzaUrl .= 'ap_returnurl=' . urlencode(WEB_ROOT . '/payment_complete.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $payzaUrl .= 'ap_cancelurl=' . urlencode(WEB_ROOT . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION) . '&';
    $payzaUrl .= 'ap_alerturl=' . urlencode(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/_payment_ipn.php') . '&';
    $payzaUrl .= 'apc_1=' . urlencode($orderHash) . '&';

    coreFunctions::redirect($payzaUrl);
}
