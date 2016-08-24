<?php
if (!isset($file))
{
    die("Error: No file found.");
}
/* setup page */
define("TITLE_DESCRIPTION_LEFT", t("download_page_captcha_title_page_description_left", "In order to prevent abuse of this service, please copy the words into the text box below."));
define("TITLE_DESCRIPTION_RIGHT", t("download_page_captcha_title_page_description_right", ""));

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>
<?php
if (notification::isErrors())
{
    echo notification::outputErrors();
}
?>
<div>
    <!-- top ads -->
    <div class="metaRedirectWrapperTopAds">
        <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_TOP; ?>
    </div>
    <div>
        <form method="POST" action="<?php echo $file->getFullLongUrl() . '?pt=' . urlencode($_REQUEST['pt']); ?>" autocomplete="off" id="form-join">
            <table class="text-center" style="margin-left:auto; margin-right:auto;">
                <tbody>
                    <tr>
                        <td>
                            <div class="download-captcha-container">
                                <?php echo coreFunctions::outputCaptcha(); ?>
                            </div>
                            <div class="form-buttons" style="margin-bottom: 12px;">
                                <span>
                                    <button class="btn btn-default" type='submit' value='Submit' name='submit' id="submit" style="width:100%;"><i class="fa fa-check"></i> <?php echo t('continue', 'continue'); ?></button>
                                    <input type="hidden" name="submitted" value="1"/>
                                    <input type="hidden" name="d" value="1"/>
                                </span> 
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <!-- bottom ads -->
    <div class="metaRedirectWrapperBottomAds">
        <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_BOTTOM; ?>
    </div>
</div>
<?php
// include footer
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>
