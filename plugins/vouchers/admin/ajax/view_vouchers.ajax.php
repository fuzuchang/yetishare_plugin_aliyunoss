<?php

// includes and security
include_once('/home/admin/web/uploadbox.co/public_html/core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$iDisplayLength        = (int) $_REQUEST['iDisplayLength'];
$iDisplayStart         = (int) $_REQUEST['iDisplayStart'];
$sSortDir_0            = $_REQUEST['sSortDir_0'] ? $_REQUEST['sSortDir_0'] : "desc";
$filterText            = $_REQUEST['filterText'] ? $_REQUEST['filterText'] : "";

// get sorting columns
$iSortCol_0     = (int) $_REQUEST['iSortCol_0'];
$sColumns       = trim($_REQUEST['sColumns']);
$arrCols        = explode(",", $sColumns);
$sortColumnName = $arrCols[$iSortCol_0];
$sort           = 'voucher';
switch ($sortColumnName)
{
    case 'id':
        $sort = 'id';
        break;
    case 'code':
        $sort = 'voucher';
        break;
    case 'valid_for':
        $sort = 'length';
        break;
    case 'redeemed':
        $sort = 'redeemed';
        break;
    case 'redeemed_by':
        $sort = 'user_redeemed';
        break;
    case 'redeemed_on':
        $sort = 'date_redeemed';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterText)
{
    $filterText = $db->escape($filterText);
    $sqlClause .= "voucher LIKE '%" . $filterText . "%' OR ";
    $sqlClause .= "redeemed = '%" . $filterText . "%' OR ";
    $sqlClause .= "id = '" . $filterText . "')";
}

$totalRS   = $db->getValue("SELECT COUNT(plugin_vouchers.id) AS total FROM plugin_vouchers " . $sqlClause);
$limitedRS = $db->getRows("SELECT * FROM plugin_vouchers ".$sqlClause." ORDER BY ".$sort." ".$sSortDir_0." LIMIT ".$iDisplayStart.", ".$iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $row)
    {
        $lRow = array();
        $icon   = '../assets/img/icons/16px.png';
        $lRow[] = '<img src="' . $icon . '" width="16" height="16" title="Voucher" alt="Voucher"/>';
        $lRow[] = htmlentities($row['id']);
		if($row['redeemed'] == '1' || $row['redeemed'] == '2' || $row['expiry_date'] <= time())
		{
			$lRow[] = '<span title="Expired Code - Cannot Be Used" style="text-decoration:line-through;">'.htmlentities($row['voucher']).'</span>';
		}
		else
		{
			$lRow[] = htmlentities($row['voucher']);
		}
        $lRow[] = UCWords(htmlentities($row['length'])).' Days';
		if($row['expiry_date'] == '4070930400' || empty($row['expiry_date']))
		{
			$lRow[] = 'Never';
		}
		else
		{
			$lRow[] = date("d/m/Y", $row['expiry_date']);
		}
		if($row['unlimited'] != '1' && $row['max_uses'] != '0')
		{
			if(empty($row['times_used']))
			{
				$used = '0';
			}
			else
			{
				$used = $row['times_used'];
			}
			$lRow[] = $used.'/'.$row['max_uses'];
		}		
		elseif($row['unlimited'] == '1')
		{
			$lRow[] = $row['times_used'].'/&#8734;';
		}
		if($row['redeemed'] == '0')	
		{
			$redeemed = 'No';
		}
		elseif($row['redeemed'] == '2') 
		{
			$redeemed = 'Expired';
		}
		else 
		{
			$redeemed = 'Yes';
		}
		$search = $db->numRows("SELECT * FROM plugin_vouchers_logs WHERE code = '".$row[voucher]."'");
		if($search >= '1')
		{
			$lRow[] = '<a href="#" onclick="Popup=window.open(\'view_vouchers.users.php?c='.$row[voucher].'\',\'Popup\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes,width=600,height=600,left=430,top=23\'); return false;">View Users</a>';
		}
		else
		{
			$lRow[] = '&nbsp;';
		}
		$links = array();
		$lRow[] = '<input type="checkbox" name="id[]" id="id[]" value="'.$row['voucher'].'"/>';
        $data[] = $lRow;
    }
}

$resultArr = array();
$resultArr["sEcho"]                = intval($_GET['sEcho']);
$resultArr["iTotalRecords"]        = (int) $totalRS;
$resultArr["iTotalDisplayRecords"] = $resultArr["iTotalRecords"];
$resultArr["aaData"]               = $data;

echo json_encode($resultArr);