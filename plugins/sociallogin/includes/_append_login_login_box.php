<?php
// load hybridauth
$config = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('sociallogin');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
?>

<div class="pluginSocialMainLoginWrapper">
    <div class="pluginSocialLoginDivider">
        &nbsp;
    </div>
    <div class="clear"><!-- --></div>

    <div id="pageHeader">
        <h2><?php echo t("plugin_sociallogin_social_login", "Social Login"); ?></h2>
    </div>
    <div>
        <p class="introText">
            <?php echo t("plugin_sociallogin_social_login_intro_text", "Use your existing social network account to login securely below."); ?>
        </p>

        <div class="pluginSocialLoginSignin">
            <span class="fieldWrapper">
                <span class="field-name">&nbsp;</span>
                <div class="clear"><!-- --></div>
                <div class="pluginSocialLoginButtons">
                    <?php if((int)$pluginSettings['facebook_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Facebook" class="zocial facebook">Sign in with Facebook</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['twitter_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Twitter" class="zocial twitter">Sign in with Twitter</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['google_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Google" class="zocial google">Sign in with Google</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['aol_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=AOL" class="zocial aol">Sign in with AOL</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['instagram_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Instagram" class="zocial instagram">Sign in with Instagram</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['foursquare_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=Foursquare" class="zocial foursquare">Sign in with Foursquare</a>
                    <?php endif; ?>
                    
                    <?php if((int)$pluginSettings['linkedin_enabled'] == 1): ?>
                    <a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['folder_name']; ?>/site/social_login.php?provider=LinkedIn" class="zocial linkedin">Sign in with LinkedIn</a>
                    <?php endif; ?>
                    <div class="clear"><!-- --></div>
                </div>
            </span>
            <div class="clear"><!-- --></div>
        </div>
    </div>
</div>
