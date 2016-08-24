<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart  = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0     = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText     = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'plugin_newsletter.date_created';
switch ($sortColumnName)
{
    case 'title':
        $sort = 'plugin_newsletter.title';
        break;
    case 'date':
        $sort = 'plugin_newsletter.date_created';
        break;
    case 'subject':
        $sort = 'plugin_newsletter.subject';
        break;
    case 'status':
        $sort = 'plugin_newsletter.status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = strtolower($db->escape($filterText));
    $sqlClause .= "AND (LOWER(plugin_newsletter.title) LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "LOWER(plugin_newsletter.subject) LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "LOWER(plugin_newsletter.status) = '" . $filterText . "' OR ";
    $sqlClause .= "LOWER(plugin_newsletter.html_content) LIKE '%" . $filterText . "%')";
}

$sQL     = "SELECT * FROM plugin_newsletter ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();

        $icon = 'local';
        $lRow[] = '<img src="../assets/img/icons/16px_'.$row['status'].'.png" width="16" height="16" title="'.UCWords($row['status']).'" alt="'.UCWords($row['status']).'"/>';
        $lRow[] = adminFunctions::makeSafe(coreFunctions::formatDate($row['date_created'], SITE_CONFIG_DATE_TIME_FORMAT));
        $lRow[] = adminFunctions::makeSafe($row['title']);
        $lRow[] = adminFunctions::makeSafe($row['subject']);
        $lRow[] = '<span class="statusText' . str_replace(" ", "", UCWords($row['status'])) . '">' . UCWords($row['status']) . ($row['status']=='sent'?('&nbsp;&nbsp;<font style="color: #999;">('.coreFunctions::formatDate($row['date_sent'], SITE_CONFIG_DATE_TIME_FORMAT).')</font>'):'') . '</span>';

        $links = array();
        $links[] = '<a href="#" onClick="viewNewsletter(' . (int) $row['id'] . '); return false;">view</a>';
        if($row['status'] == 'draft')
        {
            $links[] = '<a href="#" onClick="editNewsletterForm(' . (int) $row['id'] . '); return false;">edit</a>';
            $links[] = '<a href="#" onClick="confirmRemoveNewsletter(' . (int) $row['id'] . ', \''.adminFunctions::makeSafe($row['serverLabel']).'\', '.(int)$row['totalFiles'].'); return false;">remove</a>';
        }
        $lRow[]  = implode(" | ", $links);

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);
