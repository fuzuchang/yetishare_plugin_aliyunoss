<?php

// setup db connection
$db = Database::getDatabase(true);

// get user id
$userId = (int)$_REQUEST['id'];

// load user
$user   = $db->getRow("SELECT * FROM users WHERE id = " . (int) $userId . " LIMIT 1");
if ($user)
{
    // if they have are not active, make sure we get rid of any ftp account
    if($user['status'] != 'active')
    {
        $pluginObj = pluginHelper::getInstance('ftpupload');
        $pluginObj->deleteFTPAccount($userId);
    }
}
