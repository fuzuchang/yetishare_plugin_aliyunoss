<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup initial params
$params = array();
foreach($_REQUEST AS $k=>$param)
{
    $params[$k] = strip_tags(trim($param));
}

$result            = array();
$result['error']   = false;
$result['msg']     = '';
$db                = Database::getDatabase(true);

// validate payment method to stop abuse
$paymentMethods = $db->getRows('SELECT name_key FROM plugin_reward_outpayment_method WHERE is_enabled = 1');
$found          = false;
foreach ($paymentMethods AS $paymentMethod)
{
    if ($paymentMethod['name_key'] == $params['outpayment_method'])
    {
        $found = true;
    }
}
if ($found == false)
{
    $params['outpayment_method'] = 'paypal';
}

// get instance
$rewardObj       = pluginHelper::getInstance('rewards');
$rewardObj->clearPendingRewards();
$rewardObj->aggregateRewards();
$rewardsSettings = $rewardObj->settings;

// get rewards available for withdrawal
$availableForWithdraw = $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_aggregated WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('available')");
$availableForWithdrawRaw = $availableForWithdraw;
$availableForWithdraw = substr(number_format($availableForWithdraw, 3), 0, strlen(number_format($availableForWithdraw, 3)) - 1);

// get total aggregated rewards
$totalAggregated = (float) $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_aggregated WHERE reward_user_id = " . (int) $Auth->id);

// double check the amount is above the threshold
if ((float)$availableForWithdrawRaw < (float)$rewardsSettings['payment_threshold'])
{
    $result['error'] = true;
    $result['msg']   = 'The amount available within your account ' . $availableForWithdraw . ' is less than the payment threshold (' . $rewardsSettings['payment_threshold'] . ').';
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    // add payment request
    $dbInsert                 = new DBObject("plugin_reward_withdraw_request", array("reward_user_id", "amount",
        "requested_date", "status", "payment_method")
    );
    $dbInsert->reward_user_id = (int) $Auth->id;
    $dbInsert->amount         = (float)$availableForWithdrawRaw;
    $dbInsert->requested_date = date("Y-m-d H:i:s", time());
    $dbInsert->status         = 'pending';
    $dbInsert->payment_method = $params['outpayment_method'];
    $dbInsert->insert();

    // update aggregated rewards
    $db->query("UPDATE plugin_reward_aggregated SET status='payment_in_progress', withdrawal_request_id=" . (int) $dbInsert->id . " WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('available')");

    // store outpayment details
    $jsonArr = $params;
    unset($jsonArr['outpayment_method']);
    $jsonStr = json_encode($jsonArr);
    $db->query("UPDATE plugin_reward_affiliate_id SET outpayment_method=" . $db->quote($params['outpayment_method']) . ", method_data_json=".$db->quote($jsonStr)." WHERE user_id = " . (int) $Auth->id . " LIMIT 1");

    // send notification to admin
    $subject        = t('rewards_request_withdrawal_email_to_admin_subject', 'Rewards withdrawal request for [[[AMOUNT]]]', array('AMOUNT' => SITE_CONFIG_COST_CURRENCY_SYMBOL . $availableForWithdraw));
    $replacements   = array(
        'SITE_NAME'      => SITE_CONFIG_SITE_NAME,
        'ADMIN_WEB_ROOT' => ADMIN_WEB_ROOT
    );
    $defaultContent = "Dear Admin,<br/><br/>";
    $defaultContent .= "A rewards withdrawal request has been received. Please login to [[[SITE_NAME]]] and process the request:<br/><br/>";
    $defaultContent .= "<a href='[[[ADMIN_WEB_ROOT]]]'>[[[ADMIN_WEB_ROOT]]]</a><br/><br/>";
    $defaultContent .= "Regards,<br/>";
    $defaultContent .= "[[[SITE_NAME]]] Admin";
    $htmlMsg        = t('rewards_request_withdrawal_email_to_admin_content', $defaultContent, $replacements);
    coreFunctions::sendHtmlEmail(SITE_CONFIG_SITE_ADMIN_EMAIL, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));

    $result['error'] = false;
    $result['msg']   = t('rewards_withdraw_confirmation_on_screen', 'Your withdrawal request has been made. You\'ll receive further information once the request has been approved.');
}

echo json_encode($result);
