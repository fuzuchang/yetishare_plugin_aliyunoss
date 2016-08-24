<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// setup initial params
$s = (int)$_REQUEST['iDisplayStart'];
$l = (int)$_REQUEST['iDisplayLength'];
$db = Database::getDatabase(true);

// load all rewards for this account
$totalRewards = $db->getValue('SELECT COUNT(id) AS total FROM plugin_reward WHERE reward_user_id = '.(int)$Auth->id);

// load filtered
$rewards = $db->getRows('SELECT * FROM plugin_reward WHERE reward_user_id = '.(int)$Auth->id.' ORDER BY reward_date DESC LIMIT '.$s.','.$l);

$data = array();
if ($rewards)
{
    foreach ($rewards AS $reward)
    {
        $lrs = array();

        $lrs[] = coreFunctions::formatDate($reward['reward_date'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lrs[] = SITE_CONFIG_COST_CURRENCY_SYMBOL.$reward['reward_amount'];
        $lrs[] = $reward['reward_percent'].'%';
        $lrs[] = UCWords(str_replace("_", " ", $reward['status']));

        $data[] = $lrs;
    }
}

// create json response
$result = array();
$result['sEcho']                = intval($_GET['sEcho']);
$result['iTotalRecords']        = $totalRewards;
$result['iTotalDisplayRecords'] = $totalRewards;
$result['aaData']               = $data;

echo json_encode($result);
