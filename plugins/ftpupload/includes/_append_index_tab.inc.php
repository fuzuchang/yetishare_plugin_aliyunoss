<?php

// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('ftpupload');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

$doRedirect = true;
if(($pluginSettings['paid_only'] == 1) && ($Auth->level_id <= 1))
{
    $doRedirect = false;
}

$showTab = false;
if(($pluginSettings['show_ftp_tab'] == 1) || ($doRedirect == true))
{
    $showTab = true;
}
?>

<?php if($showTab == true): ?>
<li>
    <a href="#ftpUpload" data-toggle="tab" <?php
if($doRedirect==false)
{
    if($Auth->loggedIn() == true)
    {
        echo 'onClick="window.location=\''.WEB_ROOT.'/upgrade.' . SITE_CONFIG_PAGE_EXTENSION . '\';"';
    }
    else
    {
        echo 'onClick="window.location=\''.WEB_ROOT.'/register.' . SITE_CONFIG_PAGE_EXTENSION . '\';"';
    }
}
?>>
        <?php echo UCWords(t('ftp_upload', 'ftp upload')); ?>
    </a>
</li>
<?php endif; ?>