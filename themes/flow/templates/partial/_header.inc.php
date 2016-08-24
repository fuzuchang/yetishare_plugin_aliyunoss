<?php
// local template functions
require_once(SITE_TEMPLATES_PATH . '/partial/_template_functions.inc.php');

// load theme functions
$themeObj = themeHelper::getLoadedInstance();
$themeSkin = $themeObj->getThemeSkin();
$homepageBackgroundImageUrl = $themeObj->getHomepageBackgroundImageUrl();
$homepageBackgroundVideoUrl = $themeObj->getHomepageBackgroundVideoUrl();

// top navigation
require_once(SITE_TEMPLATES_PATH . '/partial/_navigation_header.inc.php');
?>
<!DOCTYPE html>
<html lang="en" dir="<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'RTL' : 'LTR'; ?>" class="direction<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'Rtl' : 'Ltr'; ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo validation::safeOutputToScreen(SITE_CONFIG_SITE_NAME); ?></title>
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo validation::safeOutputToScreen(SITE_CONFIG_SITE_NAME); ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php pluginHelper::includeAppends('main_page_header.php', array('file' => (isset($file) ? $file : null), 'Auth' => $Auth)); ?>
        <meta property="og:image" content="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/apple-touch-icon-114x114.png" />
        <link rel="icon" type="image/x-icon" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/favicon.ico" />

        <!-- Social Share Icons -->
        <link rel="stylesheet" type="text/css" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/socialsider-v1.0/_css/socialsider-v1.0.css" media="all" />
        
        <!-- All Stylesheets -->
        <link href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/css/All-stylesheets.css" rel="stylesheet">
        <link href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/css/custom.css" rel="stylesheet">
        <link href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/css/colors/flow.css" rel="stylesheet">
        <link href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/css/responsive.css" rel="stylesheet">
        <link href="<?php echo SITE_CSS_PATH; ?>/font-icons/entypo/css/entypo.css" rel="stylesheet">
        <link href="<?php echo SITE_CSS_PATH; ?>/file-upload.css" rel="stylesheet">
		<?php echo $themeObj->outputCustomCSSCode(); ?>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <!-- Fav and touch icons -->		
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/apple-touch-icon-114x114.png">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon-precomposed" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/apple-touch-icon.png">
        <link rel="shortcut icon" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/favicon.png">

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
		<?php
		// adblock notification, if enabled via the site settings
		include_once(SITE_TEMPLATES_PATH . '/partial/_ad_block.inc.php');
		?>
        <section id="navigation">
            <div class="navbar navbar-inverse" role="navigation">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
                        <a class="navbar-brand" href="<?php echo coreFunctions::getCoreSitePath(); ?>" class="external"><img src="<?php echo $themeObj->getMainLogoUrl(); ?>" alt="<?php echo SITE_CONFIG_SITE_NAME; ?>"/></a> 
                    </div>

                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav pull-right">
                            <li<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? ' class="current"' : ''; ?>>
                                <a data-toggle="dropdown" class="dropdown-toggle" href="#"><?php echo t('home_dropdown', ' HOME'); ?> <i class="fa fa-caret-down"></i></a>
                                <ul role="menu" class="dropdown-menu">
                                    <li><a<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? '' : ' class="external"'; ?> href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_home', 'Home'); ?></a></li>
                                    <li><a<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? '' : ' class="external"'; ?> href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>#manage" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_store_and_manage', 'Store and Manage'); ?></a></li>
                                    <li><a<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? '' : ' class="external"'; ?> href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>#share" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_share_files', 'Share Files'); ?></a></li>
                                    <li><a<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? '' : ' class="external"'; ?> href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>#fast" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_fast_downloading', 'Fast Downloading'); ?></a></li>
                                    <li><a<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') ? '' : ' class="external"'; ?> href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>#safe" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_safe_and_secure', 'Safe and Secure'); ?></a></li>
                                    <?php if (!$Auth->loggedIn()): ?>
                                        <li><a class="external" href="<?php echo coreFunctions::getCoreSitePath(); ?>/register.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>" tabindex="-1" role="menuitem"><i class="fa fa-caret-right"></i>&nbsp;<?php echo t('navigation_register', 'Register'); ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
							
							<?php if ((SITE_CONFIG_ENABLE_FILE_SEARCH != 'no') && ($Auth->loggedIn() == false)): ?>
							<li<?php echo (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'search_files') ? ' class="current"' : ''; ?>>
                                <a class="external" href="<?php echo coreFunctions::getCoreSitePath(); ?>/search.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><?php echo t('home_search_files', 'SEARCH FILES'); ?><i class="entypo-search"></i></a>
                            </li>
							<?php endif; ?>
							
                            <?php
                            // add any other navigation items
                            $headerNavigation = pluginHelper::generateHeaderNavStructure($headerNavigation, $Auth->level_id);

                            // format nagivation for template
                            $navigationHtmlItems = array();
                            foreach ($headerNavigation AS $headerNavigationItem)
                            {
                                // skip settings menu option
                                if($headerNavigationItem['link_key'] == 'settings')
                                {
                                    continue;
                                }
                                
                                $navHtml = '<li';
                                if (defined('CURRENT_PAGE_KEY') && isset($headerNavigationItem['link_key']) && CURRENT_PAGE_KEY == $headerNavigationItem['link_key'])
                                {
                                    $navHtml .= ' class="current"';
                                }
                                $navHtml .= '><a role="menuitem" tabindex="-1" class="external" href="' . $headerNavigationItem['link_url'] . '"';
                                if (isset($headerNavigationItem['element_id']))
                                {
                                    $navHtml .= ' id="' . validation::safeOutputToScreen($headerNavigationItem['element_id']) . '"';
                                }
                                $navHtml .= '>' . validation::safeOutputToScreen(strtoupper($headerNavigationItem['link_text'])) . '</a></li>';

                                if (isset($headerNavigationItem['wrap_html']))
                                {
                                    $navHtml = str_replace('[[[NAV_ITEM_HTML]]]', $navHtml, $headerNavigationItem['wrap_html']);
                                }

                                $navigationHtmlItems[] = $navHtml;
                            }

// output nav
                            echo implode('', $navigationHtmlItems);
                            ?>
                        </ul>
                    </div>
                    <!--/.nav-collapse --> 
                </div>
            </div>
        </section>
        <!-- /.NAVIGATION -->
    
        <?php
        // if we're on the index page, show the uploader and slider
        if (defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index')
        {
            if (UserPeer::getAllowedToUpload() == true)
            {
                if (isset($_REQUEST['upload']))
                {
                    // auto show uploader
                    echo "<script>\n";
                    echo "$(document).ready(function() {\n";
                    echo "  showUploaderPopup();\n";
                    echo "});";
                    echo "</script>\n";
                }
                ?>
                <!-- uploader -->
                <div id="fileUploadWrapper" class="modal fade file-upload-wrapper">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <?php
                            // uploader code
                            require_once(SITE_TEMPLATES_PATH . '/partial/_uploader.inc.php');
                            ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            <!-- SLIDER STARTS --> 
            <section id="slider">
                <div class="tp-banner-container">
                    <div class="tp-banner tp-simpleresponsive">
                        <ul>
                            <!-- SLIDE NR. 1 -->
                            <li data-transition="fade" data-slotamount="5" data-masterspeed="100">                            
                                <!-- LAYER NR. 1 -->
                                <img src="<?php echo $homepageBackgroundImageUrl; ?>" data-bgfit="cover" data-bgposition="left top" data-bgrepeat="no-repeat"/>
                                <div class="tp-caption tp-fade fadeout fullscreenvideo tp-resizeme"
                                     data-x="0"
                                     data-y="0"
                                     data-autoplay="true"                            
                                     data-autoplayonlyfirsttime="false"
                                     data-nextslideatend="true"
                                     data-forceCover="1"
                                     data-dottedoverlay="twoxtwo"
                                     data-aspectratio="16:9"
                                     data-forcerewind="on"
                                     style="z-index: 2">
									 <?php if(strlen($homepageBackgroundVideoUrl)): ?>
                                    <video class="video-js vjs-default-skin hidden-xs" loop="loop" autoplay="autoplay" autobuffer="autobuffer" width="100%" height="100%" poster="<?php echo $homepageBackgroundImageUrl; ?>" data-setup="{}">
                                        <source src='<?php echo $homepageBackgroundVideoUrl; ?>' type='video/mp4' />
                                    </video>
									<?php endif; ?>
                                </div>
                                <div class="tp-caption very_large_text sfb customout tp-resizeme"
                                     data-x="center"
                                     data-y="20"
                                     data-customout="x:0;y:0;z:0;rotationX:0;rotationY:0;rotationZ:0;scaleX:0.75;scaleY:0.75;skewX:0;skewY:0;opacity:0;transformPerspective:600;transformOrigin:50% 50%;"
                                     data-speed="800"
                                     data-start="200"
                                     data-easing="Power4.easeOut"
                                     data-endspeed="300"
                                     data-endeasing="Power1.easeIn"
                                     data-captionhidden="off"
                                     style="z-index: 6"><?php echo SITE_CONFIG_SITE_NAME; ?>
                                </div>
                                <!-- LAYER NR. 2 -->
                                <div class="tp-caption sfb customout"
                                     data-x="center"
                                     data-y="150"
                                     data-customout="x:0;y:0;z:0;rotationX:0;rotationY:0;rotationZ:0;scaleX:0.75;scaleY:0.75;skewX:0;skewY:0;opacity:0;transformPerspective:600;transformOrigin:50% 50%;"
                                     data-speed="800"
                                     data-start="200"
                                     data-easing="Power4.easeOut"
                                     data-endspeed="300"
                                     data-endeasing="Power1.easeIn"
                                     data-captionhidden="off"
                                     style="z-index: 6"><a href="#" class="slider-btn slider-btn-upload" onClick="<?php if (UserPeer::getAllowedToUpload() == false): ?>window.location = '<?php echo coreFunctions::getCoreSitePath(); ?>/register.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>';<?php else: ?>showUploaderPopup(); return false;<?php endif; ?>"><?php echo strtoupper(t('upload_account', 'Upload')); ?> &nbsp;<i class="fa fa-upload"></i></a>
                                </div>
                                <!-- LAYER NR. 3 -->
                                <div class="tp-caption medium_text sfb customout"
                                     data-x="center"
                                     data-y="360"
                                     data-customout="x:0;y:0;z:0;rotationX:0;rotationY:0;rotationZ:0;scaleX:0.75;scaleY:0.75;skewX:0;skewY:0;opacity:0;transformPerspective:600;transformOrigin:50% 50%;"
                                     data-speed="800"
                                     data-start="400"
                                     data-easing="Power4.easeOut"
                                     data-endspeed="300"
                                     data-endeasing="Power1.easeIn"
                                     data-captionhidden="on"
                                     style="z-index: 6;"><?php echo t('upload_share_and_manage_your_files_for_free', 'Upload, share and manage your files for free.'); ?>
                                </div>
                                <!-- LAYER NR. 4 -->
                                <div class="tp-caption sfb customout"
                                     data-x="center"
                                     data-y="430"
                                     data-customout="x:0;y:0;z:0;rotationX:0;rotationY:0;rotationZ:0;scaleX:0.75;scaleY:0.75;skewX:0;skewY:0;opacity:0;transformPerspective:600;transformOrigin:50% 50%;"
                                     data-speed="800"
                                     data-start="400"
                                     data-easing="Power4.easeOut"
                                     data-endspeed="300"
                                     data-endeasing="Power1.easeIn"
                                     data-captionhidden="off"
                                     style="z-index: 6"><a class="btn btn-default btn-inverted" href="<?php echo coreFunctions::getCoreSitePath(); ?>/register.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><i class="fa fa-check"></i>&nbsp; <?php echo strtoupper(t('register_index_button', 'Register')); ?></a>
                                </div>
                                <div class="tp-caption"
                                     data-x="center"
                                     data-y="580"
                                     data-customout="x:0;y:0;z:0;rotationX:0;rotationY:0;rotationZ:0;scaleX:0.75;scaleY:0.75;skewX:0;skewY:0;opacity:0;transformPerspective:600;transformOrigin:50% 50%;"
                                     data-speed="800"
                                     data-start="600"
                                     data-easing="Power4.easeOut"
                                     data-endspeed="300"
                                     data-endeasing="Power1.easeIn"
                                     data-captionhidden="off"
                                     style="z-index: 6"><div class="homepage-next-section"><a href="#manage" class="smooth-anchor-link"><i class="fa fa-chevron-circle-down"></i></a></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
            <!-- /. SLIDER END -->
            <?php
        }
        ?>
        <?php
        // if not index page
        if (!defined('CURRENT_PAGE_KEY') || CURRENT_PAGE_KEY != 'index'):
            ?>
            
            <!-- social slider -->
            <div class="reponsiveMobileHide socialsider socialsider_right_middle socialsider_fixed socialsider_bgcolor_white socialsider_opacity">
                <ul>
                    <li><a data-socialsider="facebook" target="_blank" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo WEB_ROOT; ?>" title="Facebook"></a></li>
                    <li><a data-socialsider="twitter" target="_blank" href="https://twitter.com/share?url=<?php echo WEB_ROOT; ?>" title="Twitter"></a></li>
                    <li><a data-socialsider="google" target="_blank" href="https://plus.google.com/share?url=<?php echo WEB_ROOT; ?>" title="Google"></a></li>
                    <li><a data-socialsider="linkedin" target="_blank" href="https://www.linkedin.com/cws/share?url=<?php echo WEB_ROOT; ?>" title="Linkedin"></a></li>
                    <li><a data-socialsider="reddit" target="_blank" href="http://www.reddit.com/submit?url=<?php echo WEB_ROOT; ?>&title=<?php echo SITE_CONFIG_SITE_NAME; ?>" title="Reddit"></a></li>
                    <li><a data-socialsider="pinterest" target="_blank" href="https://pinterest.com/pin/create/bookmarklet/?media=<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/apple-touch-icon-114x114.png&url=<?php echo WEB_ROOT; ?>" title="Pinterest"></a></li>
                </ul>
            </div>
            <!-- end social slider -->
            
            <section class="section-padding" data-animation="fadeIn">
                <div class="container">
                    <div class="row">
                        <div class="heading-1"><?php echo validation::safeOutputToScreen(PAGE_NAME); ?></div>
                        <?php if((defined('TITLE_DESCRIPTION_LEFT')) && (strlen(TITLE_DESCRIPTION_LEFT))): ?>
                            <div class="description-1"><?php echo validation::safeOutputToScreen(TITLE_DESCRIPTION_LEFT); ?></div>
                        <?php endif; ?>
                        <div class="clear"></div>
                    <?php endif; ?>