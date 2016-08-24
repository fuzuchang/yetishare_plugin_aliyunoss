<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// setup initial params
$s = (int)$_REQUEST['iDisplayStart'];
$l = (int)$_REQUEST['iDisplayLength'];
$db = Database::getDatabase(true);

// get instance
$rewardObj = pluginHelper::getInstance('rewards');
$rewardObj->clearPendingRewards();
$rewardObj->aggregateRewards();
$rewardsSettings = $rewardObj->settings;

// load all rewards for this account
$totalAggregated = $db->getValue('SELECT COUNT(id) AS total FROM plugin_reward_aggregated WHERE reward_user_id = '.(int)$Auth->id);

// total available for withdrawal
$availableForWithdraw = $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_aggregated WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('available')");

// load filtered
$rewardsAggregated = $db->getRows('SELECT * FROM plugin_reward_aggregated WHERE reward_user_id = '.(int)$Auth->id.' ORDER BY period DESC LIMIT '.$s.','.$l);

$data = array();
if ($rewardsAggregated)
{
    foreach ($rewardsAggregated AS $rewardAggregated)
    {
        $lrs = array();

        $lrs[] = coreFunctions::formatDate(strtotime($rewardAggregated['period']), SITE_CONFIG_DATE_FORMAT);
        $lrs[] = $rewardAggregated['description'];
        $lrs[] = SITE_CONFIG_COST_CURRENCY_SYMBOL.$rewardAggregated['amount'];
        $lrs[] = UCWords(str_replace("_", " ", $rewardAggregated['status']));
        
        $links = array();
        if($availableForWithdraw >= $rewardsSettings['payment_threshold'])
        {
            $links[] = '<a href="#" onClick="requestWithdrawal(); return false;">'.t('withdraw', 'withdraw').'</a>';
        }
        $lrs[] = implode(' | ', $links);

        $data[] = $lrs;
    }
}

// create json response
$result = array();
$result['sEcho']                = intval($_GET['sEcho']);
$result['iTotalRecords']        = $totalAggregated;
$result['iTotalDisplayRecords'] = $totalAggregated;
$result['aaData']               = $data;

echo json_encode($result);
