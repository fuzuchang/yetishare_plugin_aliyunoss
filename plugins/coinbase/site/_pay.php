<?php

require_once('../../../core/includes/master.inc.php');
require_once('../includes/functions.inc.php');

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('coinbase');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$apiKey         = '';
if (strlen($pluginSettings))
{
    $pluginSettings = json_decode($pluginSettings, true);
    $apiKey         = $pluginSettings['apiKey'];
	$apiSecret      = $pluginSettings['apiSecret'];
}

if (!isset($_REQUEST['days']))
{
    coreFunctions::redirect(WEB_ROOT . '/index.html');
}

if (strlen($apiKey) == 0)
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

// Create order entry
$orderHash = MD5(time() . $userId);
$amount    = number_format(constant('SITE_CONFIG_COST_FOR_' . $days . '_DAYS_PREMIUM'), 2);
$order     = OrderPeer::create($userId, $orderHash, $days, $amount, $fileId);
if ($order)
{
    $desc											= $days.' days extension for '.$username;
	$settingsArr = array();
	$settingsArr['button']['name']					= $desc;
	$settingsArr['button']['price_string']			= $amount;
	$settingsArr['button']['price_currency_iso']	= SITE_CONFIG_COST_CURRENCY_CODE;
	$settingsArr['button']['variable_price']		= false;	
	$settingsArr['button']['custom']				= $orderHash;
	$settingsArr['button']['description']			= $desc;
	$settingsArr['button']['callback_url']			= PLUGIN_WEB_ROOT.'/coinbase/site/_payment_ipn.php';
	$settingsArr['button']['success_url']			= PLUGIN_WEB_ROOT.'/coinbase/site/payment_complete.'.SITE_CONFIG_PAGE_EXTENSION;
	$settingsArr['button']['cancel_url']			= PLUGIN_WEB_ROOT.'/coinbase/site/payment_failed.'.SITE_CONFIG_PAGE_EXTENSION;
	$settingsArr['button']['auto_redirect']			= true;
	$settingsArr['button']['auto_redirect_success']	= true;
	$settingsArr['button']['auto_redirect_cancel']	= true;
	$settings										= json_encode($settingsArr);
	$info											= coinbaseRequest("buttons", "post", $apiKey, $apiSecret, $settings);
	$code											= json_decode($info, true);
	
    // Create payment URL
	$paymentUrl = 'https://coinbase.com/checkouts/';
	$paymentUrl .= $code['button']['code'];
    // Redirect to coinbase to pay
    coreFunctions::redirect($paymentUrl);
}
