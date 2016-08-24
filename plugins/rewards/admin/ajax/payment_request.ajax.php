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
$sort           = 'requested_date';
switch ($sortColumnName)
{
    case 'date_requested':
        $sort = 'requested_date';
        break;
    case 'user':
        $sort = 'users.username';
        break;
    case 'amount':
        $sort = 'amount';
        break;
    case 'method':
        $sort = 'payment_method';
        break;
    case 'status':
        $sort = 'status';
        break;
}

$sqlClause = "WHERE 1=1 ";
if ($filterByUser)
{
    $sqlClause .= " AND plugin_reward_withdraw_request.reward_user_id = " . (int) $filterByUser;
}

if ($filterByStatus)
{
    $sqlClause .= " AND plugin_reward_withdraw_request.status = " . $db->quote($filterByStatus);
}

// preload payment urls
$paymentUrlsArr = array();
$paymentUrls = $db->getRows('SELECT name_key, admin_payment_link FROM plugin_reward_outpayment_method');
foreach($paymentUrls AS $paymentUrl)
{
    if(strlen($paymentUrl['admin_payment_link']))
    {
        $paymentUrlsArr[$paymentUrl{'name_key'}] = $paymentUrl['admin_payment_link'];
    }
}

$totalRS   = $db->getValue("SELECT COUNT(plugin_reward_withdraw_request.id) AS total FROM plugin_reward_withdraw_request LEFT JOIN users ON plugin_reward_withdraw_request.reward_user_id = users.id " . $sqlClause);
$limitedRS = $db->getRows("SELECT plugin_reward_withdraw_request.*, users.username, plugin_reward_affiliate_id.method_data_json, plugin_reward_affiliate_id.outpayment_method FROM plugin_reward_withdraw_request LEFT JOIN users ON plugin_reward_withdraw_request.reward_user_id = users.id LEFT JOIN plugin_reward_affiliate_id ON users.id = plugin_reward_affiliate_id.user_id " . $sqlClause . " ORDER BY " . $sort . " " . $sSortDir_0 . " LIMIT " . $iDisplayStart . ", " . $iDisplayLength);

$data = array();
if (COUNT($limitedRS) > 0)
{
    foreach ($limitedRS AS $request)
    {
        $lRow = array();
        $lRow[] = '<img src="../assets/img/icons/16px.png" width="16" height="16" title="request" alt="request"/>';
        $lRow[] = coreFunctions::formatDate($request['requested_date'], SITE_CONFIG_DATE_TIME_FORMAT);
        $lRow[] = validation::safeOutputToScreen($request['username']);
        $lRow[] = validation::safeOutputToScreen(UCWords($request['payment_method']));
        $lRow[] = SITE_CONFIG_COST_CURRENCY_SYMBOL . validation::safeOutputToScreen($request['amount']);
        $lRow[] = UCWords(str_replace("_", " ", $request['status']));

        $links = array();
        if($request['status'] == 'pending')
        {
            $paymentDetailsStr = $request['method_data_json'];
            if(strlen($paymentDetailsStr))
            {
                $paymentDetailsArr = json_decode($paymentDetailsStr, true);
                if($paymentDetailsArr)
                {
                    $paymentDetailsStr = '';
                    foreach($paymentDetailsArr AS $k=>$v)
                    {
                        $v = str_replace(array("\n"), '\n', $v);
                        $v = str_replace(array("\r"), '', $v);
                        $paymentDetailsStr .= UCWords(str_replace('_', ' ', validation::safeOutputToScreen($k)));
                        $paymentDetailsStr .= ':\n\n';
                        $paymentDetailsStr .= validation::safeOutputToScreen($v);
                        $paymentDetailsStr .= '\n';
                    }
                }
            }
            $links[] = '<a href="#" onClick="setAsPaidPopup('.(int)$request['id'].',\''.str_replace(array("'","\""),"",validation::safeOutputToScreen(UCWords($request['payment_method']))).'\',\''.str_replace(array("'","\""),"",validation::safeOutputToScreen($paymentDetailsStr)).'\');return false;">set paid</a>';
            if(isset($paymentUrlsArr[$request{'outpayment_method'}]))
            {
                // do replacements in url
                $url = $paymentUrlsArr[$request{'outpayment_method'}];
                $url = str_replace('[[[RETURN_PAGE]]]', urlencode(WEB_ROOT.'/plugins/rewards/admin/payment_request.php'), $url);
                $url = str_replace('[[[ITEM_NAME]]]', urlencode('Outpayment for PPS/PPD earnings. User '.$request['username'].'. Request #'.$request['id']), $url);
                $url = str_replace('[[[AMOUNT]]]', urlencode($request['amount']), $url);
                $url = str_replace('[[[CURRENCY]]]', urlencode(SITE_CONFIG_COST_CURRENCY_CODE), $url);
                foreach($paymentDetailsArr AS $k=>$v)
                {
                    $url = str_replace('[[['.strtoupper($k).']]]', urlencode($v), $url);
                }
                
                $links[] = '<a href="'.$url.'" onClick="alert(\'You will now be redirected to '.validation::safeOutputToScreen($request['outpayment_method']).' to send payment. Remember that you will still need to return to this page and set this request as paid.\');">pay via '.validation::safeOutputToScreen($request['outpayment_method']).'</a>';
            }
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
