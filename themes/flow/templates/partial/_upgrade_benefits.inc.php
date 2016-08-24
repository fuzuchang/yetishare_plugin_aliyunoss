<div class="row">
    <div class="col-sm-4">
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('direct_downloads_no_waiting', 'Direct downloads. No waiting.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('no_advertising', 'No advertising.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('unlimited_simultaneous_downloads', 'Unlimited simultaneous downloads.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('maximum_downloads_speeds_possible', 'Maximum download speeds possible.'); ?>
    </div>
    <div class="col-sm-4">
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('download_manager_support', 'Download manager support.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('unlimited_storage', 'Unlimited storage.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i>
		<?php
		if((int)UserPeer::getDaysToKeepInactiveFiles(2) > 0)
		{
			echo t('files_kept_for_x_days', 'Files kept for [[[DAYS]]] days.', array('DAYS' => ((int)UserPeer::getDaysToKeepInactiveFiles(2))));
		}
		else
		{
			echo t('files_kept_forever', 'Files in your premium account kept forever.');
		}
		?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('upload_files_up_to_x_in_size', 'Upload files up to [[[MAX_UPLOAD_FILESIZE]]] in size.', array('MAX_UPLOAD_FILESIZE' => coreFunctions::formatSize(UserPeer::getMaxUploadFilesize(2)))); ?>
    </div>
    <div class="col-sm-4">
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('no_limits_on_the_amount_of_downloads', 'No limits on the amount of downloads.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('low_price_per_day', 'Low price per day.'); ?>
        <div class="clear"></div>
        <i class="fa account-benefits fa-check">&nbsp;</i><?php echo t('no_subscriptions', 'No subscriptions.'); ?>
    </div>
</div>