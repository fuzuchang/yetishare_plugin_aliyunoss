<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText = strlen($_REQUEST['filterText']) ? $_REQUEST['filterText'] : '';
$filterByStatus = strlen($_REQUEST['filterByStatus']) ? $_REQUEST['filterByStatus'] : '';

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'date_added';
switch ($sortColumnName)
{
    case 'date_added':
        $sort = 'plugin_mediaconverter_queue.date_added';
        break;
    case 'status':
        $sort = 'plugin_mediaconverter_queue.status';
        break;
    case 'date_started':
        $sort = 'plugin_mediaconverter_queue.date_started';
        break;
}

$sqlClause = 'WHERE 1=1';
if ($filterText)
{
    $sqlClause .= " AND file.originalFilename = " . $db->quote($filterText);
}

if ($filterByStatus)
{
    $sqlClause .= " AND plugin_mediaconverter_queue.status = " . $db->quote($filterByStatus);
}

$totalRS   = $db->getValue("SELECT COUNT(plugin_mediaconverter_queue.id) AS total FROM plugin_mediaconverter_queue LEFT JOIN file ON plugin_mediaconverter_queue.file_id = file.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT plugin_mediaconverter_queue.*, file.originalFilename, shortUrl FROM plugin_mediaconverter_queue LEFT JOIN file ON plugin_mediaconverter_queue.file_id = file.id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $converterItem)
    {
        $lRow = array();
        $lRow[] = '<img src="../assets/img/icons/16px.png" width="16" height="16" title="queue item" alt="queue item"/>';
        $lRow[] = coreFunctions::formatDate($converterItem['date_added'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = strlen($converterItem['originalFilename'])?('<a href="' . ADMIN_WEB_ROOT . '/file_manage.php?filterText=' . urlencode($converterItem['shortUrl']) . '">'.$converterItem['originalFilename'].'</a>'):'<span style="color: #cccccc;">[not found]</span>';
        $lRow[] = UCWords(str_replace("_", " ", $converterItem['status']));
        $lRow[] = coreFunctions::formatDate($converterItem['date_started'], SITE_CONFIG_DATE_TIME_FORMAT);

        $links = array();
        if ($converterItem['status'] == 'pending')
        {
            $links[] = '<a href="view_queue.php?cancel='.$converterItem['id'].'" onClick="return confirm(\'Are you sure you want to cancel this conversion?\');">cancel</a>';
        }
        if ($converterItem['status'] == 'processing')
        {
            $links[] = '<a href="view_queue.php?redo='.$converterItem['id'].'" onClick="return confirm(\'Are you sure you want to reset this conversion?\');">reset</a>';
        }
        if (($converterItem['status'] == 'completed') || ($converterItem['status'] == 'failed'))
        {
            $links[] = '<a href="view_queue.php?redo='.$converterItem['id'].'" onClick="return confirm(\'Are you sure you want to redo this conversion?\');">set pending</a>';
        }
        if (strlen($converterItem['notes']))
        {
            $links[] = '<a href="#" onClick="'.htmlspecialchars('alert('.json_encode($converterItem['notes']).');').' return false;">notes</a>';
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
