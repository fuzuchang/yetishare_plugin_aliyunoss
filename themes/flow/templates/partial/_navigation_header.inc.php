<?php
// man navigation items
$headerNavigation = array();

// non logged in users
if (SITE_CONFIG_ENABLE_USER_REGISTRATION != 'no')
{
    $headerNavigation['register'] = array(
        'link_url'      => coreFunctions::getCoreSitePath() . '/register.' . SITE_CONFIG_PAGE_EXTENSION,
        'link_text'     => t('register', 'register'),
        'link_key'      => 'register',
        'user_level_id' => array(0),
        'position'      => 100
    );
}

if (UserPeer::enableUpgradePage() == 'yes')
{
    $headerNavigation['premium'] = array(
        'link_url'      => coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION,
        'link_text'     => t('premium', 'premium'),
        'link_key'      => 'upgrade',
        'user_level_id' => array(0),
        'position'      => 200
    );
}

$headerNavigation['faq'] = array(
    'link_url'  => coreFunctions::getCoreSitePath() . '/faq.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('faq', 'faq'),
    'link_key'      => 'faq',
    'user_level_id' => array(0),
    'position'  => 300
);

$headerNavigation['login'] = array(
    'link_url'  => coreFunctions::getCoreSitePath() . '/login.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('login', 'login'),
    'link_key'      => 'login',
    'user_level_id' => array(0),
    'position'  => 400
);

// logged in users
$headerNavigation['your_files'] = array(
    'link_url'      => coreFunctions::getCoreSitePath() . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text'     => t('your_files', 'your files'),
    'link_key'      => 'your_files',
    'user_level_id' => range(1, 20),
    'position'      => 30
);

if (UserPeer::enableUpgradePage() == 'yes')
{
    $label         = t('uprade_account', 'upgrade account');
    if ($Auth->hasAccessLevel(2))
    {
        $label = t('extend_account', 'extend account');
    }
    $headerNavigation['upgrade'] = array(
        'link_url'      => coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION,
        'link_text'     => $label,
        'link_key'      => 'upgrade',
        'user_level_id' => range(1, 20),
        'position'      => 200
    );
}

// logged in users
if(SITE_CONFIG_ENABLE_FILE_SEARCH != 'no')
{
	$headerNavigation['public_files'] = array(
		'link_url'      => coreFunctions::getCoreSitePath() . '/search.' . SITE_CONFIG_PAGE_EXTENSION,
		'link_text'     => t('public_files', 'public files'),
		'link_key'      => 'public_files',
		'user_level_id' => range(1, 20),
		'position'      => 35
	);
}

$headerNavigation['settings'] = array(
    'link_url'      => coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text'     => t('file_manager_account_settings', 'Account Settings'),
    'link_key'      => 'settings',
    'user_level_id' => range(1, 20),
    'position'      => 999
);