<?php

if (!isset($file))
{
    die("Error: No file found.");
}

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>

<?php
if (notification::isErrors())
{
    echo notification::outputErrors();
}
?>

<div class="contentPageWrapper">
    <div class="pageSectionMainFull ui-corner-all">
        <div class="pageSectionMainInternal">
            
            <!-- top ads -->
            <div class="metaRedirectWrapperTopAds">
            <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_TOP; ?>
            </div>

            <div class="captchaPageTable">
                <form method="POST" action="<?php echo $file->getFullLongUrl().'?pt='.urlencode($_REQUEST['pt']); ?>" autocomplete="off" id="form-join">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="captchaContainer">
                                        <div class="captchaBox responsiveCaptchaWrapper">
                                            <?php echo coreFunctions::outputCaptcha(); ?>
                                            <div class="clear"><!-- --></div>
                                        </div>
                                        <div class="captchaFileText">
                                            <strong><?php echo validation::safeOutputToScreen($file->originalFilename); ?> (<?php echo coreFunctions::formatSize($file->fileSize); ?>)</strong>
                                        </div>
                                        <div class="captchaPageText">
                                            <?php echo t("in_order_to_prevent_abuse_captcha_intro", "In order to prevent abuse of this service, please copy the words into the text box on the right."); ?>
                                        </div>
                                        <div class="captchaPageButton">
                                            <input name="submit" type="submit" value="<?php echo t('continue', 'continue'); ?>" class="submitInput"/>
                                            <input type="hidden" name="submitted" value="1"/>
                                            <input type="hidden" name="d" value="1"/>
                                        </div>
                                        <div class="clear"><!-- --></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
                <div class="clear"><!-- --></div>
            </div>

            <!-- bottom ads -->
            <div class="metaRedirectWrapperBottomAds">
            <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_BOTTOM; ?>
            </div>
            
        </div>
    </div>
</div>

<?php
// include footer
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>
