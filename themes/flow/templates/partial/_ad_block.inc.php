<?php
// make sure it's not disabled in the site settings
$blockPage = false;
if(SITE_CONFIG_ADBLOCK_LIMITER != 'Disabled')
{
	// make sure we should be showing ads for this user type
	if(UserPeer::showSiteAdverts() == true)
	{
		if(SITE_CONFIG_ADBLOCK_LIMITER == 'Block Download Pages')
		{
			// see if this is a download request
			if(defined('_INT_DOWNLOAD_REQ') && (_INT_DOWNLOAD_REQ == true))
			{
				// only show on download pages
				$blockPage = true;
			}
		}
		else
		{
			$blockPage = true;
		}
	}
}

if($blockPage == true)
{
?>
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/advertisement.js"></script>
<div id="adblockinfo" style="position:absolute;left:0px;top:0px;z-index:999999;display:none;width:100%;height:100vh;background-color:#ffffff;text-align:center;padding-top:90px;color:#D24D32;font-weight:bold;">
	<a href="http://www.wikihow.com/Disable-Adblock" target="_blank"><img src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/adblock/primary.jpg" style="max-width: 100%;margin:20px 0px;"/></a>
	<div class="clear"></div>
	<br/>
	<?php echo t("ad_block_please_disable_your_ad_block_extension", "Please disable your ad block extension to browse this site."); ?><br/>
	<a href="http://www.wikihow.com/Disable-Adblock" target="_blank"><?php echo t("ad_block_click_here_for_detailed_instructions_on_how_to_disable_it", "Click here for detailed instructions on how to disable it"); ?></a><br/><br/>
	<?php echo t("ad_block_watch_a_youtube_video_showing_how_to_disable_it", "Watch a YouTube video showing how to disable it:"); ?><br/>
	<a href="https://www.youtube.com/watch?v=roWd3cISn2M" target="_blank"><?php echo t("ad_block_chrome", "Chrome"); ?></a><br/>
	<a href="https://www.youtube.com/watch?v=yTKlAPUNTwk" target="_blank"><?php echo t("ad_block_firefox", "Firefox"); ?></a><br/>
	<a href="https://www.youtube.com/watch?v=LgIQY3uavf8" target="_blank"><?php echo t("ad_block_internet_explorer", "Internet Explorer"); ?>
</div>

<script type="text/javascript">
if (document.getElementById("bannerad") == undefined)
{
	document.getElementById("adblockinfo").style.display = "block";
	$(document).ready(function()
	{
		$('section').remove();
	});
}
</script>
<?php
}
?>