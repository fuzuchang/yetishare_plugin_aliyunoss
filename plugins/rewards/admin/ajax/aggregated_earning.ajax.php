<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterByUser   = strlen($_REQUEST['filterByUser']) ? (int) $_REQUEST['filterByUser'] : false;
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : '';
$filterByMonth = strlen($_REQUEST['filterByMonth']) ? $_REQUEST['filterByMonth'] : false;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'period';
switch ($sortColumnName)
{
    case 'period':
        $sort = 'period';
        break;
    case 'user':
        $sort = 'users.username';
        break;
    case 'description':
        $sort = 'description';
        break;
    case 'amount':
        $sort = 'amount';
        break;
    case 'status':
        $sort = 'status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterByUser)
{
    $sqlClause .= " AND plugin_reward_aggregated.reward_user_id = " . (int) $filterByUser;
}

if ($filterByStatus)
{
    $sqlClause .= " AND plugin_reward_aggregated.status = " . $db->quote($filterByStatus);
}

if ($filterByMonth)
{
	// get start of month
	$startMonth = strtotime($filterByMonth.'-01 00:00:00');
	$endMonth = strtotime($filterByMonth.'-'.date('t', $startMonth).' 23:59:59');
	
    $sqlClause .= ' AND UNIX_TIMESTAMP(plugin_reward_aggregated.period) >= ' . $startMonth .' AND UNIX_TIMESTAMP(plugin_reward_aggregated.period) <= ' . $endMonth;
}

$totalRS   = $db->getValue("SELECT COUNT(plugin_reward_aggregated.id) AS total FROM plugin_reward_aggregated LEFT JOIN users ON plugin_reward_aggregated.reward_user_id = users.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT plugin_reward_aggregated.*, users.username FROM plugin_reward_aggregated LEFT JOIN users ON plugin_reward_aggregated.reward_user_id = users.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $reward)
    {
        $lRow = array();
        $lRow[] = '<img src="../assets/img/icons/16px.png" width="16" height="16" title="rewards" alt="rewards"/>';
        $lRow[] = coreFunctions::formatDate(strtotime($reward['period']), SITE_CONFIG_DATE_FORMAT);
        $lRow[] = $reward['username'];
        $lRow[] = $reward['description'];
        $lRow[] = SITE_CONFIG_COST_CURRENCY_SYMBOL . $reward['amount'];
        $lRow[] = UCWords(str_replace("_", " ", $reward['status']));

        $links = array();
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
