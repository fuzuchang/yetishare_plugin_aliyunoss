<!DOCTYPE html>
<html lang="en" dir="<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'RTL' : 'LTR'; ?>" class="direction<?php echo SITE_LANGUAGE_DIRECTION == 'RTL' ? 'Rtl' : 'Ltr'; ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />    
        <link rel="icon" type="image/x-icon" href="<?php echo SITE_THEME_PATH; ?>/frontend_assets/images/icons/favicon/favicon.ico" />

        <?php
        // add css files, use the htmlHelper::addCssFile() function so files can be joined/minified
		pluginHelper::addCssFile(SITE_CSS_PATH . '/fonts.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/font-icons/entypo/css/entypo.css');
		pluginHelper::addCssFile(SITE_CSS_PATH . '/font-icons/font-awesome/css/font-awesome.min.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/bootstrap.css');
		if ($themeSkin)
		{
			pluginHelper::addCssFile(SITE_CSS_PATH . '/skins/'.$themeSkin);
        }
        pluginHelper::addCssFile(SITE_CSS_PATH . '/flow-core.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/flow-theme.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/flow-forms.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/responsive.css');
        if (SITE_LANGUAGE_DIRECTION == 'RTL')
        {
            // include RTL styles
            pluginHelper::addCssFile(SITE_CSS_PATH . '/flow-rtl.css');
        }
        pluginHelper::addCssFile(SITE_CSS_PATH . '/daterangepicker-bs3.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/custom.css');
        pluginHelper::addCssFile(SITE_CSS_PATH . '/file-upload.css');

        // output css
        pluginHelper::outputCss();
		$themeObj = themeHelper::getLoadedInstance();
		echo $themeObj->outputCustomCSSCode();
        ?>

        <script src="<?php echo SITE_JS_PATH; ?>/jquery-1.11.0.min.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.ckie.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.jstree.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drag-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drag.live-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drop-2.2.js"></script>
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery.event.drop.live-2.2.js"></script>

        <!--[if lt IE 9]><script src="<?php echo SITE_JS_PATH; ?>/ie8-responsive-file-warning.js"></script><![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
                <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
                <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

        <script type="text/javascript">
            var WEB_ROOT = "<?php echo WEB_ROOT; ?>";
<?php echo translate::generateJSLanguageCode(); ?>
        </script>
        <?php
// add js files, use the htmlHelper::addJsFile() function so files can be joined/minified
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
        pluginHelper::addJsFile(SITE_JS_PATH . '/daterangepicker/moment.min.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/daterangepicker/daterangepicker.js');
        pluginHelper::addJsFile(SITE_JS_PATH . '/global.js');

// output js
        pluginHelper::outputJs();
        ?>
    </head>