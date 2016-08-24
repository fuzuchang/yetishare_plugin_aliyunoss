<?php
// available params
// $params['fileObj']
// $params['extraMenuItems']

// setup valid image extensions
$ext = array('jpg', 'jpeg', 'png', 'gif');

// check this is an image
if (in_array(strtolower($params['fileObj']->extension), $ext))
{
    // only for active files
    if($params['fileObj']->statusId == 1)
    {
        $params['extraMenuItems']['View'] = array("label"=>UCWords(t('account_file_details_view', 'View')), "separator_after"=>true, "action"=>"function() { window.open(downloadUrl); }");
    }
}
