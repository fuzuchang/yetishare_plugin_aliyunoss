<?php

// includes and security
include_once ('../../../../core/includes/master.inc.php');
include_once (DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength = (int)$_REQUEST['iDisplayLength'];
$iDisplayStart = (int)$_REQUEST['iDisplayStart'];
$sSortDir_0 = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : null;
$filterByStatus = $_REQUEST['filterByStatus'] ? $_REQUEST['filterByStatus'] : null;

// get sorting columns
$iSortCol_0 = (int)$_REQUEST['iSortCol_0'];
$sColumns = trim($_REQUEST['sColumns']);
$arrCols = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort = 'plugin_torrentdownload_torrent.torrent_name';
switch ($sortColumnName)
{
    case 'torrent_name':
        $sort = 'plugin_torrentdownload_torrent.torrent_name';
        break;
    case 'size':
        $sort = 'plugin_torrentdownload_torrent.torrent_size';
        break;
    case 'status':
        $sort = 'plugin_torrentdownload_torrent.save_status';
        break;
    case 'progress':
        $sort = 'plugin_torrentdownload_torrent.download_percent';
        break;
    case 'download_speed':
        $sort = 'plugin_torrentdownload_torrent.download_speed';
        break;
    case 'time_remaining':
        $sort = 'plugin_torrentdownload_torrent.time_remaining';
        break;
    case 'user':
        $sort = 'users.username';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = strtolower($db->escape($filterText));
    $sqlClause .= "AND (LOWER(plugin_torrentdownload_torrent.torrent_name) LIKE '%" .
        $filterText . "%' OR ";
    $sqlClause .= "LOWER(plugin_torrentdownload_torrent.save_status) = '" . $filterText .
        "' OR ";
    $sqlClause .= "LOWER(users.username) = '" . $filterText . "')";
}

if ($filterByStatus)
{
    $sqlClause .= ' AND plugin_torrentdownload_torrent.save_status = ' . $db->quote($filterByStatus);
}

$sQL = "SELECT plugin_torrentdownload_torrent.*, users.username AS username FROM plugin_torrentdownload_torrent LEFT JOIN users ON plugin_torrentdownload_torrent.user_id=users.id ";
$sQL .= $sqlClause . " ";
$totalRS = $db->getRows($sQL);

$sQL .= "ORDER BY " . $sort . " " . $sSortDir_0 . " ";
$sQL .= "LIMIT " . $iDisplayStart . ", " . $iDisplayLength;
$limitedRS = $db->getRows($sQL);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS as $row)
    {
        $timeLeft = '-';
        if (($row['time_remaining'] != -1) && ($row['time_remaining'] != 0))
        {
            $timeLeft = coreFunctions::secsToHumanReadable($row['time_remaining']);
            $timeLeft = str_replace(' ', '', $timeLeft);
            $timeLeft = str_replace(array(t('weeks', 'weeks'), t('week', 'week')), 'w ', $timeLeft);
            $timeLeft = str_replace(array(t('days', 'days'), t('day', 'day')), 'd ', $timeLeft);
            $timeLeft = str_replace(array(t('hours', 'hours'), t('hour', 'hour')), 'h ', $timeLeft);
            $timeLeft = str_replace(array(t('minutes', 'minutes'), t('minute', 'minute')),
                'm ', $timeLeft);
            $timeLeft = str_replace(array(t('seconds', 'seconds'), t('second', 'second')),
                's ', $timeLeft);
        }

        $lRow = array();
        $icon = 'blue_arrow_down.png';
        if ($row['save_status'] == 'complete')
        {
            $icon = 'accept.png';
        }
        elseif ($row['save_status'] == 'cancelled')
        {
            $icon = 'block.png';
        }
        $lRow[] = '<img src="../assets/img/' . $icon .
            '" width="16" height="16" title="' . UCWords($row['save_status']) . '" alt="' .
            UCWords($row['save_status']) . '"/>';
        $lRow[] = '<a href="#" onClick="viewTorrentDetails(' . (int)$row['id'] .
            '); return false;">' . adminFunctions::makeSafe($row['torrent_name']) . '</a>';
        $lRow[] = adminFunctions::makeSafe(coreFunctions::formatSize($row['torrent_size']));
        $lRow[] = adminFunctions::makeSafe(UCWords($row['save_status']));
        $lRow[] = adminFunctions::makeSafe(number_format($row['download_percent'] / 10,
            2)) . ' %';
        $lRow[] = adminFunctions::makeSafe(coreFunctions::formatSize($row['download_speed'])) .
            's /<br/>' . adminFunctions::makeSafe(coreFunctions::formatSize($row['upload_speed'])) .
            's';
        $lRow[] = adminFunctions::makeSafe($timeLeft);
        $lRow[] = '<a href="' . ADMIN_WEB_ROOT . '/user_edit.php?id=' . $row['user_id'] .
            '">' . adminFunctions::makeSafe($row['username']) . '</a>';

        $links = array();
        $links[] = '<a href="#" onClick="viewTorrentDetails(' . (int)$row['id'] .
            '); return false;">torrent info</a>';
        if ($row['save_status'] == 'downloading')
        {
            $links[] = '<a href="#" onClick="confirmRemoveTorrent(' . (int)$row['id'] .
                '); return false;">cancel download</a>';
        }
        $lRow[] = implode("<br/>", $links);

        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"] = intval($_GET['sEcho']);
$resultArr["iTotalRecords"] = (int)COUNT($totalRS);
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"] = $data;

echo json_encode($resultArr);
