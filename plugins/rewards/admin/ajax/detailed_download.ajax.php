<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength    = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart     = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0        = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterByUser      = strlen($_REQUEST['filterByUser']) ? (int) $_REQUEST['filterByUser'] : false;
$filterByStatus    = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : '';
$filterByGroupData = $_REQUEST['filterByGroupData'] == 'yes' ? 'yes' : 'np';

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'plugin_reward_ppd_detail.download_date';
switch ($sortColumnName)
{
    case 'download_date':
        $sort = 'plugin_reward_ppd_detail.download_date';
        break;
    case 'user':
        $sort = 'users.username';
        break;
    case 'file':
        $sort = 'file.originalFilename';
        break;
    case 'reward_group':
        $sort = 'plugin_reward_ppd_group.group_label';
        break;
    case 'status':
        $sort = 'plugin_reward_ppd_detail.status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterByUser)
{
    $sqlClause .= " AND  plugin_reward_ppd_detail.reward_user_id = " . (int) $filterByUser;
}

if ($filterByStatus)
{
    $sqlClause .= " AND  plugin_reward_ppd_detail.status = " . $db->quote($filterByStatus);
}

if ($filterByGroupData == 'yes')
{
    $totalRS   = $db->getValue("SELECT COUNT(users.id) AS total FROM plugin_reward_ppd_detail LEFT JOIN users ON plugin_reward_ppd_detail.reward_user_id = users.id LEFT JOIN file ON plugin_reward_ppd_detail.file_id = file.id " . $sqlClause . " GROUP BY users.id, plugin_reward_ppd_detail.status");
    $limitedRS = $db->getRows("SELECT plugin_reward_ppd_detail.*, SUM(reward_amount) AS reward_amount, users.username, file.originalFilename, file.shortUrl, plugin_reward_ppd_group.group_label FROM plugin_reward_ppd_detail LEFT JOIN users ON plugin_reward_ppd_detail.reward_user_id = users.id LEFT JOIN file ON plugin_reward_ppd_detail.file_id = file.id LEFT JOIN plugin_reward_ppd_group ON plugin_reward_ppd_detail.download_country_group_id = plugin_reward_ppd_group.id " . $sqlClause . " GROUP BY users.id, plugin_reward_ppd_detail.status ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);
}
else
{
    $totalRS   = $db->getValue("SELECT COUNT(plugin_reward_ppd_detail.id) AS total FROM plugin_reward_ppd_detail LEFT JOIN users ON plugin_reward_ppd_detail.reward_user_id = users.id LEFT JOIN file ON plugin_reward_ppd_detail.file_id = file.id " . $sqlClause);
    $limitedRS = $db->getRows("SELECT plugin_reward_ppd_detail.*, users.username, file.originalFilename, file.shortUrl, plugin_reward_ppd_group.group_label FROM plugin_reward_ppd_detail LEFT JOIN users ON plugin_reward_ppd_detail.reward_user_id = users.id LEFT JOIN file ON plugin_reward_ppd_detail.file_id = file.id LEFT JOIN plugin_reward_ppd_group ON plugin_reward_ppd_detail.download_country_group_id = plugin_reward_ppd_group.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);
}

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $reward)
    {
        $lRow   = array();
        $lRow[] = '<img src="../assets/img/icons/16px.png" width="16" height="16" title="rewards" alt="rewards"/>';
        if ($filterByGroupData == 'yes')
        {
            $lRow[] = '<span style="color: #aaa;">[grouped range]</span>';
        }
        else
        {
            $lRow[] = coreFunctions::formatDate($reward['download_date'], SITE_CONFIG_DATE_TIME_FORMAT);
        }
        $lRow[] = $reward['username'];

        $fileName    = 'File Download';
        $fileDetails = $db->getRow('SELECT originalFilename, shortUrl FROM file WHERE id=' . (int) $reward['file_id'] . ' LIMIT 1');
        if ($fileDetails)
        {
            $fileName = '<a href="' . ADMIN_WEB_ROOT . '/file_manage.php?filterText=' . urlencode($fileDetails['shortUrl']) . '">' . validation::safeOutputToScreen($fileDetails['originalFilename'], null, 38) . '</a>';
        }
        $lRow[] = $fileName;

        $lRow[] = $reward['group_label'];
        $lRow[] = SITE_CONFIG_COST_CURRENCY_SYMBOL . $reward['reward_amount'];
        $lRow[] = UCWords(str_replace("_", " ", $reward['status']));

        $links = array();
        if ($filterByGroupData != 'yes')
        {
            if ($reward['status'] == 'pending')
            {
                $links[] = '<a href="#" onClick="confirmRemoveReward(' . (int) $reward['id'] . '); return false;">cancel</a>';
            }
        }
        $lRow[] = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr                         = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
