<?php

require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('paypal');
$pluginSettings = $pluginConfig['data']['plugin_settings'];
$paypalEmail    = '';
$enable_sandbox_mode = 0;
if (strlen($pluginSettings))
{
    $pluginSettingsArr 		= json_decode($pluginSettings, true);
    $paypalEmail       		= $pluginSettingsArr['paypal_email'];
	$enable_sandbox_mode  	= (int)$pluginSettingsArr['enable_sandbox_mode'];
}

if (!isset($_REQUEST['user_level_pricing_id']))
{
    coreFunctions::redirect(WEB_ROOT . '/index.html');
}

// require login
if (!isset($_REQUEST['i']))
{
    $Auth->requireUser(WEB_ROOT.'/register.'.SITE_CONFIG_PAGE_EXTENSION);
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

$user_level_pricing_id = (int)$_REQUEST['user_level_pricing_id'];

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
$order = OrderPeer::createByPackageId($userId, $user_level_pricing_id, $fileId);
if ($order)
{    
    // redirect to the payment gateway
	$baseUrl = "https://www.paypal.com/cgi-bin/webscr";
	if($enable_sandbox_mode == 1)
	{
		$baseUrl = "https://www.sandbox.paypal.com/cgi-bin/webscr";
	}
    $paypalUrl = $baseUrl.'?cmd=_xclick&notify_url=' . urlencode(PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/_payment_ipn.php') . '&email=' . urlencode($userEmail) . '&return=' . urlencode(WEB_ROOT . '/payment_complete.' . SITE_CONFIG_PAGE_EXTENSION) . '&business=' . urlencode($paypalEmail) . '&item_name=' . urlencode($order->description) . '&item_number=1&amount=' . urlencode($order->amount) . '&no_shipping=2&no_note=1&currency_code=' . SITE_CONFIG_COST_CURRENCY_CODE . '&lc=' . substr(SITE_CONFIG_COST_CURRENCY_CODE, 0, 2) . '&bn=PP%2dBuyNowBF&charset=UTF%2d8&custom=' . $order->payment_hash;
    coreFunctions::redirect($paypalUrl);
    
}
