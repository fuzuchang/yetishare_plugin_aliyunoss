<div class="responsiveAccountBenefitWrapper">
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('direct_downloads_no_waiting', 'Direct downloads. No waiting.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('no_advertising', 'No advertising.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('unlimited_simultaneous_downloads', 'Unlimited simultaneous downloads.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('maximum_downloads_speeds_possible', 'Maximum download speeds possible.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('download_manager_support', 'Download manager support.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('unlimited_storage', 'Unlimited storage.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('files_kept_for_x_days', 'Files kept for [[[DAYS]]] days.', array('DAYS'=>(strlen(UserPeer::getDaysToKeepInactiveFiles(2)) ? UserPeer::getDaysToKeepInactiveFiles(2) : 'unlimited'))); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('upload_files_up_to_x_in_size', 'Upload files up to [[[MAX_UPLOAD_FILESIZE]]] in size.', array('MAX_UPLOAD_FILESIZE' => coreFunctions::formatSize(UserPeer::getMaxUploadFilesize(2)))); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('no_limits_on_the_amount_of_downloads', 'No limits on the amount of downloads.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('low_price_per_day', 'Low price per day.'); ?>
        </div>
    </div>
    <div class="accountBenefitWrapper">
        <div class="accountBenefit">
            <?php echo t('no_subscriptions', 'No subscriptions.'); ?>
        </div>
    </div>
</div>
<div class="clear"><!-- --></div>
