<?php

require_once('../../../core/includes/master.inc.php');

// start logging
log::setContext('plugin_coinbase');
log::info('Received request on IPN for coinbase plugin. REQUEST: ' . print_r($HTTP_RAW_POST_DATA, true));

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('coinbase');
$pluginSettings = $pluginConfig['data']['plugin_settings'];

if (strlen($pluginSettings))
{
    $pluginSettings = json_decode($pluginSettings, true);
    $apiKey         = $pluginSettings['apiKey'];
	$apiSecret      = $pluginSettings['apiSecret'];
}

// Get the json encoded IPN data from Coinbase
$json = json_decode($HTTP_RAW_POST_DATA, true);

// load order using custom payment tracker hash
$paymentTracker				= $json['order']['custom'];
$order						= OrderPeer::loadByPaymentTracker($paymentTracker);
if($json['order']['status'] == 'completed')
{
	// Could not find transaction ID
	if(!$order)
	{
		log::error('Failed to get payment tracker settings.');
		die('Failed to get payment tracker settings.');
	}
	elseif($order)
	{
		$extendedDays	= $order->days;
		$userId			= $order->user_id;
		$upgradeUserId	= $order->upgrade_user_id;
		$orderId		= $order->id;
		$orderAmount	= $order->amount;
		$user			= UserPeer::loadUserById($userId);
		$userEmail		= $user->email;
		$transAmount	= bcdiv($json['order']['total_native']['cents'], 100, 2);
		$title			= $json['order']['button']['description'];
		$auth			= $json['order']['transaction']['id'];
		$currency		= $json['order']['total_native']['currency_iso'];
		$function		= $json['order']['status']; 
		$hash			= $json['order']['custom'];

		// Check if order amount is the same as the transaction amount
		if($orderAmount != $transAmount)
		{
			log::error('Invalid transaction values. Order Amount: '.$orderAmount.', Transaction Amount: '.$transAmount.'.');
			die('Invalid transaction values.');
		}
		// Do we have a user with that transaction ID?
		if(!$user)
		{
			log::error('User not found.');
			die('User Not Found');
		}    
		// Log in payment_log
		$coinbase_vars				= print_r($json, true);
		$dbInsert 					= new DBObject("payment_log", array("user_id", "date_created", "amount", "currency_code", "from_email", "to_email", "description", "request_log", "payment_method"));
		$dbInsert->user_id			= $userId;
		$dbInsert->date_created		= date("Y-m-d H:i:s", time());
		$dbInsert->amount			= $orderAmount;
		$dbInsert->currency_code	= $currency;
		$dbInsert->from_email		= $userEmail;
		$dbInsert->to_email			= urlencode(SITE_CONFIG_SITE_ADMIN_EMAIL);
		$dbInsert->description		= $extendedDays.' days extension';
		$dbInsert->request_log		= $coinbase_vars;
		$dbInsert->payment_method	= 'Coinbase';
		$dbInsert->insert();
		
		if($function == 'completed')
		{       
			// make sure the order is pending
			if($order->order_status == 'completed')
			{
				log::error('Order has already been completed.');
				die('Order has already been completed.');
			}
			// update order status to paid
			$dbUpdate					= new DBObject("premium_order", array("order_status"), 'id');
			$dbUpdate->order_status		= 'completed';
			$dbUpdate->id				= $orderId;
			$effectedRows				= $dbUpdate->update();

			if($effectedRows === false)
			{
				// failed to update order
				log::error('Failed to update order.');
				die('Failed to update order.');
			}
			// extend/upgrade user
			$rs = UserPeer::upgradeUser($userId, $order->days);

			if ($rs === false)
			{
				// failed to update user
				log::error('Failed to update user.');
				die('Failed to update user.');
			}			
			// append any plugin includes
			pluginHelper::includeAppends('payment_ipn_paypal.php');
			// Return "OK" status to Coinbase if all is good.
			echo 'ok';
		}
	}
}
log::breakInLogFile();
log::setContext('system');