<?php
// man navigation items
$headerNavigation = array();

// non logged in users
if (SITE_CONFIG_ENABLE_USER_REGISTRATION != 'no')
{
    $headerNavigation['register'] = array(
        'link_url'      => coreFunctions::getCoreSitePath() . '/register.' . SITE_CONFIG_PAGE_EXTENSION,
        'link_text'     => t('register', 'register'),
        'user_level_id' => array(0),
        'position'      => 100
    );
}

if (UserPeer::enableUpgradePage() == 'yes')
{
    $headerNavigation['premium'] = array(
        'link_url'      => coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION,
        'link_text'     => t('premium', 'premium'),
        'user_level_id' => array(0),
        'position'      => 200
    );
}

$headerNavigation['faq'] = array(
    'link_url'  => coreFunctions::getCoreSitePath() . '/faq.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('faq', 'faq'),
    'user_level_id' => array(0),
    'position'  => 300
);

$headerNavigation['login'] = array(
    'link_url'  => coreFunctions::getCoreSitePath() . '/login.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text' => t('login', 'login'),
    'user_level_id' => array(0),
    'position'  => 400,
    'element_id' => 'loginLink',
    'wrap_html' => '<span id="loginLinkWrapper" class="loginLink">&nbsp;[[[NAV_ITEM_HTML]]]&nbsp;</span>'
);

// logged in users
$headerNavigation['home'] = array(
    'link_url'      => coreFunctions::getCoreSitePath() . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text'     => t('your_files', 'your files'),
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
        'user_level_id' => range(1, 20),
        'position'      => 200
    );
}

$headerNavigation['settings'] = array(
    'link_url'      => coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text'     => t('settings', 'settings'),
    'user_level_id' => range(1, 20),
    'position'      => 300
);

$headerNavigation['logout'] = array(
    'link_url'      => coreFunctions::getCoreSitePath() . '/logout.' . SITE_CONFIG_PAGE_EXTENSION,
    'link_text'     => t('logout', 'logout') . ' (' . $Auth->username . ')',
    'user_level_id' => range(1, 20),
    'position'      => 1000
);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0;">
        <link rel="icon" type="image/x-icon" href="<?php echo SITE_IMAGE_PATH; ?>/favicon.ico" />
        <?php
        // add css files, use the htmlHelper::addCssFile() function so files can be joined/minified
        pluginHelper::addCssFile(SITE_CSS_PATH . '/jquery-ui-1.8.9.custom.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/screen.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/responsive.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/tabview-core.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/data_table.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/gh-buttons.css');

        // output css
        pluginHelper::outputCss();
        ?>

        <script type="text/javascript">
            var WEB_ROOT = "<?php echo WEB_ROOT; ?>";
<?php echo translate::generateJSLanguageCode(); ?>
        </script>
        <?php
        // add js files, use the htmlHelper::addJsFile() function so files can be joined/minified
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery-1.11.0.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery-ui.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.dataTables.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.tmpl.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/load-image.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/canvas-to-blob.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.iframe-transport.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-process.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-resize.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-validate.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/jquery.fileupload-ui.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/zeroClipboard/ZeroClipboard.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/global.js');

        // output js
        pluginHelper::outputJs();
        ?>
    </head>

    <body>
        <?php if (_CONFIG_DEMO_MODE == true): ?>
            <div id="demoBanner">
                <span onClick="window.location = 'http://www.yetishare.com';">Want a copy of this site? <a href="http://www.yetishare.com">Click here</a> for more information.&nbsp;&nbsp;</span><?php echo pluginHelper::currentlyInPluginDemoMode() ? '<a href="' . WEB_ROOT . '/?_p=0" style="text-decoration: none;">[disable all plugins]</a>&nbsp;&nbsp;' : '<a href="' . WEB_ROOT . '/?_p=1" style="text-decoration: none;">[enable all plugins]</a>&nbsp;&nbsp;'; ?><a href="#" onClick="$('#demoBanner').fadeOut();
                    return false;" style="text-decoration: none;">[close this]</a>
            </div>
        <?php endif; ?>
        <div class="globalPageWrapper">
            <!-- header section -->
            <div class="headerBar">

                <!-- main logo -->
                <div class="mainLogo">
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>"><img src="<?php echo SITE_IMAGE_PATH; ?>/main_logo.jpg" height="48" alt="<?php echo SITE_CONFIG_SITE_NAME; ?>"/></a>
                </div>

                <!-- main site navigation -->
                <div class="mainNavigation">
                    <?php
                    // add any other navigation items
                    $headerNavigation = pluginHelper::generateHeaderNavStructure($headerNavigation, $Auth->level_id);
                    
                    // format nagivation for template
                    $navigationHtmlItems = array();
                    foreach($headerNavigation AS $headerNavigationItem)
                    {
                        $navHtml = '<a href="'.$headerNavigationItem['link_url'].'"';
                        if(isset($headerNavigationItem['element_id']))
                        {
                            $navHtml .= ' id="'.validation::safeOutputToScreen($headerNavigationItem['element_id']).'"';
                        }
                        $navHtml .= '>'.validation::safeOutputToScreen($headerNavigationItem['link_text']).'</a>';
                        
                        if(isset($headerNavigationItem['wrap_html']))
                        {
                            $navHtml = str_replace('[[[NAV_ITEM_HTML]]]', $navHtml, $headerNavigationItem['wrap_html']);
                        }
                        
                        $navigationHtmlItems[] = $navHtml;
                    }

                    // output nav
                    echo implode('&nbsp;&nbsp;|&nbsp;&nbsp;', $navigationHtmlItems);
                    ?>
                </div>
                
                <!-- responsive navigation -->
                <div class="responsiveNavigation">
                    <?php
                    // format nagivation for template
                    $navigationHtmlItems = array();
                    $navigationHtmlItems[] = '<select name="navigationPage" onChange="window.location=$(this).val();">';
                    $navigationHtmlItems[] = '<option value="">'.t('responsive_navigation_select_page', '- select page -').'</option>';
                    foreach($headerNavigation AS $headerNavigationItem)
                    {
                        $navHtml = '<option value="'.$headerNavigationItem['link_url'].'"';
                        $navHtml .= '>'.strtolower($headerNavigationItem['link_text']).'</option>';
                        
                        $navigationHtmlItems[] = $navHtml;
                    }
                    $navigationHtmlItems[] = '</select>';

                    // output nav
                    echo implode('', $navigationHtmlItems);
                    ?>
                </div>

                <!-- Code for Login Link -->
                <!-- xHTML Code -->
                <div class="loginWrapper">
                    <div id="loginPanel" class="loginPanel">
                        <form action="<?php echo coreFunctions::getCoreSitePath(); ?>/login.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>" method="post" AUTOCOMPLETE="off">
                            <span class="fieldWrapper">
                                <label for="loginUsername">
                                    <span class="field-name"><?php echo t("username", "username"); ?></span>
                                    <input type="text" tabindex="50" value="" id="loginUsername" name="loginUsername" style="padding:3px;"/>
                                </label>
                            </span>
                            <div class="clear"><!-- --></div>

                            <span class="fieldWrapper">
                                <label for="loginPassword">
                                    <span class="field-name"><?php echo t("password", "password"); ?></span>
                                    <input type="password" tabindex="51" value="" id="loginPassword" name="loginPassword" style="padding:3px;"/>
                                </label>
                            </span>
                            <div class="clear"><!-- --></div>

                            <?php
                            // if we're viewing the file countdown page
                            if (isset($file))
                            {
                                echo '<input name="loginShortUrl" type="hidden" value="' . urlencode($file->shortUrl) . '"/>';
                            }
                            ?>

                            <div class="submitButton">
                                <input name="submit" value="<?php echo t("login", "login"); ?>" type="submit" class="submitInput"/>
                            </div>
                            <div class="forgotPassword">
                                <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/forgot_password.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><?php echo t("forgot_password", "forgot password"); ?>?</a>
                            </div>
                            <div class="clear"><!-- --></div>

                            <input name="submitme" type="hidden" value="1" />
                        </form>

                        <?php
                        // include any plugin includes
                        pluginHelper::includeAppends('_header_login_box.php');
                        ?>

                    </div>
                </div>

                <div class="clear"><!-- --></div>
            </div>

            <!-- body section -->
            <div class="bodyBarWrapper">
                <div class="bodyBar">
