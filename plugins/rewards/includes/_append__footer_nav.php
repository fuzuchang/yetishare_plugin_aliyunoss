<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('rewards');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// only if user is logged in
$Auth = Auth::getAuth();

$newParams = array();
$count = 0;
foreach ($params AS $k => $param)
{
    $newParams[$k] = $param;

    // add rewards link after upgrade link
    if ($count == 1)
    {
        $link = '<a href="' . coreFunctions::getCoreSitePath() . '/rewards.html">' . t('rewards', 'rewards') . '</a>';
        if ($Auth->loggedIn())
        {
            $link = '<a href="' . coreFunctions::getCoreSitePath() . '/account_rewards.html">' . t('rewards', 'rewards') . '</a>';
        }
        $newParams['rewards'] = $link;
    }
	
	$count++;
}

$params = $newParams;
