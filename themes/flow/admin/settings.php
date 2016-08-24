<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'themes');
define('ADMIN_SELECTED_SUB_PAGE', 'theme_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load themes details
$themeFolder = substr(str_replace(DIRECTORY_SEPARATOR."admin", "", __DIR__), strrpos(str_replace(DIRECTORY_SEPARATOR."admin", "", __DIR__), DIRECTORY_SEPARATOR)+1);
$themes   = $db->getRow("SELECT * FROM theme WHERE folder_name = " . $db->quote($themeFolder) . " LIMIT 1");
if (!$themes)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/theme_manage.php?error=' . urlencode('There was a problem loading the theme details.'));
}
define('ADMIN_PAGE_TITLE', $themes['theme_name'] . ' Settings');

// load themes details
$themeObj 		= themeHelper::getInstance($themes['folder_name']);
$themeDetails 	= themeHelper::themeSpecificConfiguration($themes['folder_name']);
$themeConfig  	= $themeDetails['config'];

if (isset($_REQUEST['se']))
{
	// update theme config cache
    themeHelper::loadThemeConfigurationFiles(true);
    adminFunctions::setSuccess('Theme settings updated.');
}

// pre-load all site skins
$skinsPath = '../styles/skins/';
$skins = coreFunctions::getDirectoryListing($skinsPath);
sort($skins);

// load existing settings
if (strlen($themes['theme_settings']))
{
    $theme_settings = json_decode($themes['theme_settings'], true);
    if ($theme_settings)
    {
		$site_skin = $theme_settings['site_skin'];
		$css_code = $theme_settings['css_code'];
		$homepage_background_video_off = (int)$theme_settings['homepage_background_video_off'];
    }
}

// make sure the logo directory path exists
$logoStorageFolder = CACHE_DIRECTORY_ROOT . '/themes/'.$themeFolder;
$logoStorageUrl = CACHE_WEB_ROOT . '/themes/'.$themeFolder;
if(!file_exists($logoStorageFolder))
{
	mkdir($logoStorageFolder, 0777, true);
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
	$site_skin = $_REQUEST['site_skin'];
	$css_code = $_REQUEST['css_code'];
	$homepage_background_video_off = isset($_REQUEST['homepage_background_video_off'])?1:0;

    // validate submission
    if (coreFunctions::inDemoMode() == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
	
	if (adminFunctions::isErrors() == false)
    {
		if(strlen($_FILES["site_logo"]["tmp_name"]))
		{
			// check it's an image
			if (exif_imagetype($_FILES["site_logo"]["tmp_name"]) != IMAGETYPE_PNG)
			{
				adminFunctions::setError('Logo does not appear to be a PNG image. Please check and try again.');
			}
			elseif ($_FILES["site_logo"]["size"] > 200000)
			{
				adminFunctions::setError('Logo is bigger than 200k in size, please reduce and try again.');
			}
		}
		
		if(strlen($_FILES["site_logo_inverted"]["tmp_name"]))
		{
			// check it's an image
			if (exif_imagetype($_FILES["site_logo_inverted"]["tmp_name"]) != IMAGETYPE_PNG)
			{
				adminFunctions::setError('Logo does not appear to be a PNG image. Please check and try again.');
			}
			elseif ($_FILES["site_logo_inverted"]["size"] > 200000)
			{
				adminFunctions::setError('Logo is bigger than 200k in size, please reduce and try again.');
			}
		}
	}
	
	if (adminFunctions::isErrors() == false)
    {
		if(strlen($_FILES["homepage_background_image"]["tmp_name"]))
		{
			// check it's an image
			$fileExtension = strtolower(pathinfo($_FILES["homepage_background_image"]["name"], PATHINFO_EXTENSION));
			if (!in_array($fileExtension, array('jpg', 'jpeg', 'png')))
			{
				adminFunctions::setError('Homepage background image must be a jpg or png. Please check and try again.');
			}
			elseif ($_FILES["homepage_background_image"]["size"] > 10000000)
			{
				adminFunctions::setError('Homepage background image can not be bigger than 10MB in size, please reduce and try again.');
			}
		}
	}
	
	if (adminFunctions::isErrors() == false)
    {
		if(strlen($_FILES["homepage_background_video"]["tmp_name"]))
		{
			// check it's an image
			$fileExtension = strtolower(pathinfo($_FILES["homepage_background_video"]["name"], PATHINFO_EXTENSION));
			if (!in_array($fileExtension, array('mp4')))
			{
				adminFunctions::setError('Homepage background video must be a mp4. Please check and try again.');
			}
			elseif ($_FILES["homepage_background_video"]["size"] > 10000000)
			{
				adminFunctions::setError('Homepage background video can not be bigger than 10MB in size, please reduce and try again.');
			}
		}
	}

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                          			= array();
		$settingsArr['site_skin'] 		  				= $site_skin;
		$settingsArr['css_code'] 		  				= $css_code;
		$settingsArr['homepage_background_video_off'] 	= (int)$homepage_background_video_off;
        $settings                             			= json_encode($settingsArr);

        // update
        $dbUpdate                  = new DBObject("theme", array("theme_settings"), 'id');
        $dbUpdate->theme_settings  = $settings;
        $dbUpdate->id              = $themes['id'];
        $dbUpdate->update();
		
		// move logo into storage
		if(strlen($_FILES["site_logo"]["tmp_name"]))
		{
			$targetFile = $logoStorageFolder.'/logo.png';
			move_uploaded_file($_FILES["site_logo"]["tmp_name"], $targetFile);
		}
		
		if(strlen($_FILES["site_logo_inverted"]["tmp_name"]))
		{
			$targetFile = $logoStorageFolder.'/logo_inverse.png';
			move_uploaded_file($_FILES["site_logo_inverted"]["tmp_name"], $targetFile);
		}
		
		if(strlen($_FILES["homepage_background_image"]["tmp_name"]))
		{
			// delete any current ones first
			$extensions = array('jpg', 'jpeg', 'png');
			foreach($extensions AS $extension)
			{
				$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$themeFolder.'/homepage-background.'.$extension;
				if(file_exists($localCachePath))
				{
					unlink($localCachePath);
				}
			}
			$fileExtension = strtolower(pathinfo($_FILES["homepage_background_image"]["name"], PATHINFO_EXTENSION));
			$targetFile = $logoStorageFolder.'/homepage-background.'.$fileExtension;
			move_uploaded_file($_FILES["homepage_background_image"]["tmp_name"], $targetFile);
		}
		
		if(strlen($_FILES["homepage_background_video"]["tmp_name"]))
		{
			// delete any current ones first
			$extensions = array('mp4');
			foreach($extensions AS $extension)
			{
				$localCachePath = CACHE_DIRECTORY_ROOT . '/themes/'.$themeFolder.'/homepage-background.'.$extension;
				if(file_exists($localCachePath))
				{
					unlink($localCachePath);
				}
			}
			$fileExtension = strtolower(pathinfo($_FILES["homepage_background_video"]["name"], PATHINFO_EXTENSION));
			$targetFile = $logoStorageFolder.'/homepage-background.'.$fileExtension;
			move_uploaded_file($_FILES["homepage_background_video"]["tmp_name"], $targetFile);
		}
		
		// create custom css file
		$cssCodeFile = CACHE_DIRECTORY_ROOT . '/themes/'.$themeFolder.'/custom_css.css';
		if(strlen($settingsArr['css_code']))
		{
			file_put_contents($cssCodeFile, $settingsArr['css_code']);
		}
		else
		{
			unlink($cssCodeFile);
		}

		// clear cache
		themeHelper::clearCachedThemeSettings();
		
        adminFunctions::redirect('settings.php?se=1');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off" enctype="multipart/form-data">
				
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>General Site Settings</h3>
                            <p>Site logo, skin and homepage options.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix">
                                    <label>Main Site Logo:</label>
                                    <div class="input">
                                        <input type="file" name="site_logo"/>
										<br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'><br/>Shown on the front-end, on a white background. Leave blank to keep existing. Must be a transparent png. Download the <a href="../offline/main_logo_inverted.png" target="_blank" download>original png here</a>.</div>
										<br/>
										<br/>
										<img src="<?php echo $themeObj->getMainLogoUrl(); ?>?r=<?php echo md5(microtime()); ?>"/>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Inverse Logo:</label>
                                    <div class="input">
                                        <input type="file" name="site_logo_inverted"/>
										<br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'><br/>Shown on the file manager, on a dark background. Leave blank to keep existing. Must be a transparent png. Download the <a href="../offline/main_logo.png" target="_blank" download>original png here</a>.</div>
										<br/>
										<br/>
										<div class="image-hover">
											<img src="<?php echo $themeObj->getInverseLogoUrl(); ?>?r=<?php echo md5(microtime()); ?>"/>
										</div>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>Site Skin:</label>
                                    <div class="input">
                                        <select name="site_skin" id="site_skin" class="medium">
											<option value="">- none -</option>
                                            <?php
                                            foreach ($skins AS $option)
                                            {
												$option = str_replace($skinsPath, '', $option);
                                                echo '<option value="' . $option . '"';
                                                if ($site_skin == $option)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Home BG Image:<br/><br/>(JPG or PNG)</label>
                                    <div class="input">
                                        <input type="file" name="homepage_background_image"/>
										<br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'><br/>The homepage background image. Leave blank to keep existing. View the <a href="<?php echo $themeObj->getHomepageBackgroundImageUrl(); ?>" target="_blank">current one here</a>.</div>
                                    </div>
                                </div>
								
								<div class="clearfix">
                                    <label>Home BG Video:<br/><br/>(Optional MP4)</label>
                                    <div class="input">
                                        <input type="file" name="homepage_background_video"/>
										<br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'><br/>Optional homepage background video. Ensure you set the image above aswell for a fallback on mobile. Leave blank to keep existing. View the <a href="<?php echo $themeObj->getHomepageBackgroundVideoUrl(); ?>" target="_blank">current one here</a>.</div>
										<br/>
										<input type="checkbox" value="1" name="homepage_background_video_off" id="homepage_background_video_off" value="1" <?php echo ($homepage_background_video_off==1)?'CHECKED':''; ?>/>&nbsp;<label for="homepage_background_video_off" style="float: none;">No background video (only the image above)</label>
                                    </div>
                                </div>
								
								<div class="clearfix alt-highlight">
                                    <label>Custom CSS Code:</label>
                                    <div class="input">
                                        <textarea name="css_code" class="xxlarge" placeholder="css code..." style="font-family: monospace; height: 200px;"><?php echo validation::safeOutputToScreen($css_code); ?></textarea>
										<br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'>Optional. Use this field to override any of the site CSS without having to create a new theme. By right clicking on an element in your browser and selecting 'inspect', you can find the relating CSS rules. These changes will be kept after any script upgrades.</div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Cancel" class="button" onClick="window.location='<?php echo ADMIN_WEB_ROOT; ?>/theme_manage.php';"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $themeId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>