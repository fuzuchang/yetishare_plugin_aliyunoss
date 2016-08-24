<?php

// load reward details
$rewardsConfig   = pluginHelper::pluginSpecificConfiguration('rewards');
$rewardsSettings = json_decode($rewardsConfig['data']['plugin_settings'], true);

// load order
$paymentTracker = isset($_REQUEST['apc_1'])?$_REQUEST['apc_1']:$_REQUEST['custom'];
$order          = OrderPeer::loadByPaymentTracker($paymentTracker);
if ($order)
{
    $upgradeUserId = $order->upgrade_user_id;
    $orderId       = $order->id;
	$orderAmount   = $order->amount;

    // add reward entry
    $dbInsert = new DBObject("plugin_reward",
                    array("reward_user_id", "premium_order_id", "reward_percent",
                        "reward_amount", "reward_date", "status")
    );
    $dbInsert->reward_user_id = $upgradeUserId;
    $dbInsert->premium_order_id = $orderId;
    $dbInsert->reward_percent = (int) $rewardsSettings['user_percentage'];
    $dbInsert->reward_amount = number_format(($orderAmount / 100) * (int) $rewardsSettings['user_percentage'], 2);
    $dbInsert->reward_date = date("Y-m-d H:i:s", time());
    $dbInsert->status = 'pending';
    $dbInsert->insert();
}
