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
$totalRewards = $db->getValue('SELECT COUNT(id) AS total FROM plugin_reward_ppd_detail WHERE reward_user_id = '.(int)$Auth->id);

// load filtered
$rewards = $db->getRows('SELECT *, plugin_reward_ppd_group.group_label, file.id AS fileId FROM plugin_reward_ppd_detail LEFT JOIN plugin_reward_ppd_group ON plugin_reward_ppd_detail.download_country_group_id = plugin_reward_ppd_group.id LEFT JOIN file ON plugin_reward_ppd_detail.file_id = file.id WHERE reward_user_id = '.(int)$Auth->id.' ORDER BY download_date DESC LIMIT '.$s.','.$l);

$data = array();
if ($rewards)
{
    foreach ($rewards AS $reward)
    {
        // load file
        $file = file::loadById($reward['fileId']);
        
        $lrs = array();
        $lrs[] = coreFunctions::formatDate($reward['download_date'], SITE_CONFIG_DATE_TIME_FORMAT);
        if($file)
        {
            $lrs[] = '<a href="'.$file->getFullShortUrl().'">'.validation::safeOutputToScreen($file->originalFilename, null, 38).'</a>';
        }
        else
        {
            $lrs[] = '<span style="color: #888;">['.t('removed', 'removed').']</span>';
        }
        $lrs[] = $reward['group_label'];
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
