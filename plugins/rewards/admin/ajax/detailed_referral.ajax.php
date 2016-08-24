<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterByUser   = strlen($_REQUEST['filterByUser']) ? (int) $_REQUEST['filterByUser'] : false;
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : '';

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'reward_date';
switch ($sortColumnName)
{
    case 'reward_date':
        $sort = 'reward_date';
        break;
    case 'user':
        $sort = 'users.username';
        break;
    case 'reward_amount':
        $sort = 'reward_amount';
        break;
    case 'original_order_number':
        $sort = 'premium_order_id';
        break;
    case 'status':
        $sort = 'status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterByUser)
{
    $sqlClause .= " AND plugin_reward.reward_user_id = " . (int) $filterByUser;
}

if ($filterByStatus)
{
    $sqlClause .= " AND plugin_reward.status = " . $db->quote($filterByStatus);
}

$totalRS   = $db->getValue("SELECT COUNT(plugin_reward.id) AS total FROM plugin_reward LEFT JOIN users ON plugin_reward.reward_user_id = users.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT plugin_reward.*, users.username, premium_order.upgrade_file_id, premium_order.upgrade_user_id FROM plugin_reward LEFT JOIN users ON plugin_reward.reward_user_id = users.id LEFT JOIN premium_order ON plugin_reward.premium_order_id = premium_order.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $reward)
    {
        $lRow = array();
        $lRow[] = '<img src="../assets/img/icons/16px.png" width="16" height="16" title="rewards" alt="rewards"/>';
        $lRow[] = coreFunctions::formatDate($reward['reward_date'], SITE_CONFIG_DATE_TIME_FORMAT);

        $rewardType = 'Unknown';
        if ((int) $reward['upgrade_file_id'] > 0)
        {
            $rewardType  = 'File Download';
            $fileDetails = $db->getRow('SELECT originalFilename, shortUrl FROM file WHERE id=' . (int) $reward['upgrade_file_id'] . ' LIMIT 1');
            if ($fileDetails)
            {
                $rewardType .= ' (<a href="' . ADMIN_WEB_ROOT . '/file_manage.php?filterText=' . urlencode(_CONFIG_SITE_FILE_DOMAIN . '/' . $fileDetails['shortUrl']) . '">' . $fileDetails['originalFilename'] . '</a>)';
            }
        }
        else
        {
            $rewardType = 'Direct Referral';
            $rewardType .= ' (<a href="' . ADMIN_WEB_ROOT . '/user_edit.html?id=' . $reward['reward_user_id'] . '">' . $reward['username'] . '</a>)';
        }

        $lRow[] = $rewardType;
        $lRow[] = $reward['username'];
        $lRow[] = SITE_CONFIG_COST_CURRENCY_SYMBOL . $reward['reward_amount'] . ' (' . $reward['reward_percent'] . '%)';
        $lRow[] = '#' . $reward['premium_order_id'];
        $lRow[] = UCWords(str_replace("_", " ", $reward['status']));

        $links = array();
        if ($reward['status'] == 'pending')
        {
            $links[] = '<a href="#" onClick="confirmRemoveReward(' . (int) $reward['id'] . '); return false;">cancel</a>';
        }
        $lRow[]  = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
