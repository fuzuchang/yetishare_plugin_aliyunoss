<?php if (!defined('IS_INDEX_PAGE')): ?>
    </div>
    </div>
    </section>
<?php endif; ?>
<section id="copyright" class="dark-bluish-grey-bg copyright">
    <div class="footerAds">
        <?php if (UserPeer::showSiteAdverts()): ?>
            <!-- footer ads -->
            <?php echo SITE_CONFIG_ADVERT_SITE_FOOTER; ?>
        <?php endif; ?>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="footer-links">
                    <?php
                    // footer navigation links
                    $links = array();
                    if ($Auth->loggedIn() == false)
                    {
                        $title = t('main_navigation', 'Main Navigation');
                        $links['upload'] = '<a href="' . coreFunctions::getCoreSitePath() . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1">' . t('upload_file', 'upload file') . '</a>';
                        if (SITE_CONFIG_ENABLE_USER_REGISTRATION != 'no')
                        {
                            $links['register'] = '<a href="' . coreFunctions::getCoreSitePath() . '/register.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('register', 'register') . '</a>';
                        }
                        if (UserPeer::enableUpgradePage() == 'yes')
                        {
                            $links['upgrade'] = '<a href="' . coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('premium', 'premium') . '</a>';
                        }
                        $links['faq'] = '<a href="' . coreFunctions::getCoreSitePath() . '/faq.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('faq', 'faq') . '</a>';
                        $links['login'] = '<a href="' . WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('login', 'login') . '</a>';
                    }
                    else
                    {
                        $title = t('your_account', 'Your Account');
                        $links['upload'] = '<a href="' . coreFunctions::getCoreSitePath() . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION . '?upload=1">' . t('upload_file', 'upload file') . '</a>';
                        $links['home'] = '<a href="' . coreFunctions::getCoreSitePath() . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('your_files', 'your files') . '</a>';
                        $label = t('uprade_account', 'upgrade account');
                        if ($Auth->hasAccessLevel(2))
                        {
                            $label = t('extend_account', 'extend account');
                        }
                        if (UserPeer::enableUpgradePage() == 'yes')
                        {
                            $links['upgrade'] = '<a href="' . coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION . '">' . $label . '</a>';
                        }
                        $links['faq'] = '<a href="' . coreFunctions::getCoreSitePath() . '/faq.' . SITE_CONFIG_PAGE_EXTENSION . '">' . t('faq', 'faq') . '</a>';
                    }

                    // include any plugin includes
                    $links = pluginHelper::includeAppends('_footer_nav.php', $links);
                    ?>

                    <?php
                    // output nav
                    echo implode('&nbsp; | &nbsp;', $links);
                    ?>

                    &nbsp;|&nbsp;
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/terms.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><?php echo t('term_and_conditions', 'terms and conditions'); ?></a>
                    &nbsp;|&nbsp;
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/report_file.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?><?php echo defined('REPORT_URL') ? ('?file_url=' . urlencode(REPORT_URL)) : ''; ?>"><?php echo t('report_file', 'report file'); ?></a>
					&nbsp;|&nbsp;
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/link_checker.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><?php echo t('link_checker', 'link checker'); ?></a>
                    &nbsp;|&nbsp;
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/contact.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>"><?php echo t('contact', 'contact'); ?></a>
                </div>
                <?php
                if (SITE_CONFIG_SHOW_MULTI_LANGUAGE_SELECTOR == 'show')
                {
                    $activeLanguages = $db->getRows("SELECT languageName, flag FROM language WHERE isActive = 1 ORDER BY isLocked DESC");
                    if (COUNT($activeLanguages))
                    {
                        ?>
                        <div class="col-md-12">
                            <div class="footer-flags">
                                <?php
                                foreach ($activeLanguages AS $activeLanguage)
                                {
                                    echo '<a href="' . coreFunctions::getCoreSitePath() . '/index.' . SITE_CONFIG_PAGE_EXTENSION . '?_t=' . urlencode($activeLanguage['languageName']) . '">';
                                    echo '<img src="' . SITE_IMAGE_PATH . '/flags/' . $activeLanguage['flag'] . '.png" width="16" height="11" alt="' . $activeLanguage['languageName'] . '" title="' . htmlentities(t('switch_site_language_to', 'Switch site language to') . ' ' . t($activeLanguage['languageName'], $activeLanguage['languageName'])) . '" class="';
                                    if ($_SESSION['_t'] == $activeLanguage['languageName'])
                                    {
                                        echo 'flagSelected';
                                    }
                                    else
                                    {
                                        echo 'flagNoneSelected';
                                    }
                                    echo '"/>';
                                    echo '</a>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <?php pluginHelper::includeAppends('website_footer_base.php', array('Auth' => $Auth)); ?>
                <div class="col-md-12">
                    <div align="center"><?php echo t("copyright", "copyright"); ?> &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></div>
                </div>
            </div>
        </div>
</section>
<a href="#" class="scrollup" style="display:inline;">Scroll</a>      
<!-- Include all compiled plugins (below), or include individual files as needed --> 
<script src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/bootstrap/bootstrap.min.js"></script>
<!-- Animation --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/animation/jquery.appear.js"></script>  
<!-- Slider Revolution 4.x Scripts -->
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/rs-plugin/js/jquery.themepunch.plugins.min.js"></script>
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/rs-plugin/js/jquery.themepunch.revolution.js"></script> 
<script>
    var revapi;
    jQuery(document).ready(function() {
        revapi = jQuery('.tp-banner').revolution(
                {
                    delay: 0,
                    startwidth: 1170,
                    startheight: 500,
                    hideThumbs: 10,
                    fullWidth: "on",
                    fullScreen: "on",
                    keyboardNavigation: "off",
                    touchenabled: "off",
                    hideCaptionAtLimit: 400,
                    spinner: ""
                });
    });	//ready	
</script>
<!-- ScrollTo --> 
<script src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/nav/jquery.scrollTo.js"></script> 
<script src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/nav/jquery.nav.js"></script> 
<!-- Sticky --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/sticky/jquery.sticky.js"></script>
<!-- Isotope --> 
<script src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/isotope/jquery.isotope.min.js"></script> 
<script src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/isotope/custom-isotope.js"></script> 
<!-- Retina --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/retina/retina.js"></script> 
<!-- SmoothScroll --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/SmoothScroll/SmoothScroll.js"></script>
<!-- Custom --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/custom/custom.js"></script>
<!-- Gauge --> 
<script type="text/javascript" src="<?php echo SITE_THEME_PATH; ?>/frontend_assets/js/gauge.min.js"></script>

<?php if ((defined('CURRENT_PAGE_KEY') && CURRENT_PAGE_KEY == 'index') && (_CONFIG_DEMO_MODE == true)): ?>
    <script type="text/javascript">
        $(function() {
            $("#plugin_notice").dialog({
                resizable: false,
                width: 600,
                modal: true,
                position: ['center', 100],
                buttons: {
                    "Close": function() {
                        $(this).dialog("close");
                    }
                }
            });
        });
    </script>
    <div id="plugin_notice" title="All Plugins Demo" class="plugin-notice">
        <p>This demo has all the available YetiShare plugins <strong><?php echo (pluginHelper::demoPluginsEnabled()?'disabled':'enabled'); ?></strong>.<?php echo (pluginHelper::demoPluginsEnabled()?'':' This includes our FTP Uploader, Rewards Program and the Media Player Plugin.'); ?></p>
        <p>If you want to see what the core script looks like <?php echo (pluginHelper::demoPluginsEnabled()?'with':'without any'); ?> plugins, go to <a href="<?php echo WEB_ROOT; ?>/?_p=<?php echo (pluginHelper::demoPluginsEnabled()?'0':'1'); ?>"><?php echo WEB_ROOT; ?>/?_p=<?php echo (pluginHelper::demoPluginsEnabled()?'0':'1'); ?></a>.</p>
        <p>You can test the file manager by logging in above using 'admin' &amp; 'password' as the credentials.</p>
        <p>Click 'close' below to continue.</p>
    </div>
<?php endif; ?>

<?php include_once(SITE_TEMPLATES_PATH . '/partial/_clipboard_structure.inc.php'); ?>
<?php echo (defined('SITE_CONFIG_GOOGLE_ANALYTICS_CODE') && strlen(SITE_CONFIG_GOOGLE_ANALYTICS_CODE))?SITE_CONFIG_GOOGLE_ANALYTICS_CODE:''; ?>

</body>
</html>