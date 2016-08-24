<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('newsletters');
$pluginSettings = $pluginConfig['data']['plugin_settings'];

// prepare variables
$e = '';
if(isset($_REQUEST['e']))
{
    $e = trim($_REQUEST['e']);
}

// handle submissions
if(isset($_REQUEST['submitme']))
{
    // validation
    if (!strlen($e))
    {
        notification::setError(t("please_enter_your_email_address", "Please enter your email address"));
    }
    elseif (!validation::validEmail($e))
    {
        notification::setError(t("your_email_address_is_invalid", "Your email address is invalid"));
    }
    elseif(_CONFIG_DEMO_MODE == true)
    {
        notification::setError(t("no_changes_in_demo_mode"));
    }
    else
    {
        $account = UserPeer::loadUserByEmailAddress($e);
        if (!$account)
        {
            notification::setError(t("newsletter_subscribe_could_not_find_account", "Could not find an account with that email address"));
        }
    }

    // unsinscribe
    if (!notification::isErrors())
    {
        $db = Database::getDatabase(true);

        // set as subscribed
        $db->query('DELETE FROM plugin_newsletter_unsubscribe WHERE user_id='.(int)$account->id);
        notification::setSuccess(t("newsletter_subscribe_successfully_subscribed", "Your email address has been sucessfully added to our mailing lists"));
        $e = '';
    }
}

// setup page
define("PAGE_NAME", UCWords(t("newsletter_subscribe_title", "newsletter subscribe")));
define("PAGE_DESCRIPTION", t("newsletter_subscribe_description", "Subscribe"));
define("PAGE_KEYWORDS", t("newsletter_subscribe_meta_keywords", "newsletter, subscribe, file, hosting, site"));

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>

<div class="contentPageWrapper">
    <div class="contentPageWrapper">

    <?php
    if (notification::isSuccess())
    {
        echo notification::outputSuccess();
    }
    elseif (notification::isErrors())
    {
        echo notification::outputErrors();
    }
    ?>

    <!-- register form -->
    <div class="pageSectionMain ui-corner-all">
        <div class="pageSectionMainInternal">
            <div id="pageHeader" class="newsletter-header">
                <h2><?php echo t("newsletter_subscribe", "newsletter subscribe"); ?></h2>
            </div>
            <div>
                <p class="introText" style="padding-bottom: 12px;">
                    <?php echo t("newsletter_subscribe_intro_text", "Enter your email address below to be added to future newsletters from our site. Note, you must have a registered account to be added to the mailing list."); ?>
                </p>
                <form class="international" method="post" action="<?php echo PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/subscribe.php'; ?>" id="form-join" AUTOCOMPLETE="off">
                    <ul class="newsLetterRow">
                        <li class="field-container">
                            <label for="e">
                                <span class="field-name"><?php echo t("email_address", "email address"); ?></span>
                                <input type="text" tabindex="1" value="<?php echo isset($e) ? validation::safeOutputToScreen($e) : ''; ?>" id="e" name="e" class="uiStyle" onFocus="showHideTip(this);"></label>
                            <div id="loginUsernameMainTip" class="hidden formTip">
                                <?php echo t("newsletter_subscribe_email_tip", "Your registered email address."); ?>
                            </div>
                        </li>

                        <li class="field-container">
                            <span class="field-name"></span>
                            <input tabindex="99" type="submit" name="submit" value="<?php echo t("subscribe", "subscribe"); ?>" class="submitInput btn btn-info" />
                        </li>
                    </ul>

                    <input type="hidden" value="1" name="submitme"/>
                </form>

                <div class="clear"></div>
            </div>
        </div>
    </div>
    <?php include_once(SITE_TEMPLATES_PATH . '/partial/_banner_right_content.inc.php'); ?>
    <div class="clear"><!-- --></div>
    </div>
</div>

<?php
// include footer
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>
