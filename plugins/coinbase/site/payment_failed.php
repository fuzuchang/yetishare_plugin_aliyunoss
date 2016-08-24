<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// setup page
define("PAGE_NAME", t("payment_failed_page_name", "Payment Failed"));
define("PAGE_DESCRIPTION", t("payment_failed_meta_description", "Payment failed"));
define("PAGE_KEYWORDS", t("payment_failed_meta_keywords", "payment, failed, file, hosting, site"));

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>

<div class="contentPageWrapper">

    <!-- main section -->
    <div class="pageSectionMain ui-corner-all">
        <div class="pageSectionMainInternal">
            <p>
                <?php echo t('payment_failure_notification_text', 'Sorry, there has been a problem processing your payment, if this continues, please contact the site admin.'); ?>
            </p>
        </div>
    </div>
    <?php include_once("../../../_bannerRightContent.inc.php"); ?>
    <div class="clear"><!-- --></div>
</div>

<?php
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>