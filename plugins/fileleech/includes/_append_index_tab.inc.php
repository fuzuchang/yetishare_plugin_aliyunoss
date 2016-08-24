<?php

// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('fileleech');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

// check show tab setting
$showTab = false;
if($pluginSettings['show_leech_tab'] == 1)
{
    $showTab = true;
}

// check user can access it
$userAllowed = false;
if(($Auth->level_id == 0) && ($pluginSettings['enabled_non_user'] == 1))
{
    $userAllowed = true;
}
elseif(($Auth->level_id == 1) && ($pluginSettings['enabled_free_user'] == 1))
{
    $userAllowed = true;
}
elseif(($Auth->level_id >= 2) && ($pluginSettings['enabled_paid_user'] == 1))
{
    $userAllowed = true;
}
if($userAllowed == false)
{
    $showTab = false;
}

?>

<?php if($showTab == true): ?>
<li>
    <a href="#fileLeech" data-toggle="tab">
        <?php echo UCWords(t('plugin_fileleech_file_leech', 'File Leech')); ?>
    </a>
</li>
<?php endif; ?>