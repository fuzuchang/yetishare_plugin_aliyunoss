<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');
// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// prepare variables
$plugin_enabled = (int) $plugin['plugin_enabled'];
$oss_access_key = '';
$oss_secret_key = '';
$oss_host       = '';
$oss_endpoint   = '';
$oss_bucket     = '';
$oss_iscname    = '';
$oss_dir_name    = '';
//$oss_max_upload_bytes    = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $oss_access_key = $plugin_settings['oss_access_key'];
        $oss_secret_key = $plugin_settings['oss_secret_key'];
        $oss_host       = $plugin_settings['oss_host'];
        $oss_endpoint   = $plugin_settings['oss_endpoint'];
        $oss_bucket     = $plugin_settings['oss_bucket'];
        $oss_iscname    = $plugin_settings['oss_iscname'];
        $oss_dir_name    = $plugin_settings['oss_dir_name'];
//        $oss_max_upload_bytes    = $plugin_settings['oss_max_upload_bytes'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled = $plugin_enabled != 1 ? 0 : 1;
    $oss_access_key = trim($_REQUEST['oss_access_key']);
    $oss_secret_key = trim($_REQUEST['oss_secret_key']);
    $oss_host       = trim($_REQUEST['oss_host']);
    $oss_endpoint   = trim($_REQUEST['oss_endpoint']);
    $oss_bucket     = trim($_REQUEST['oss_bucket']);
    $oss_dir_name   = trim($_REQUEST['oss_dir_name']);
    $oss_iscname    = intval($_REQUEST['oss_iscname']) ? true : false ;
//    $oss_max_upload_bytes   = trim($_REQUEST['oss_max_upload_bytes']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($oss_access_key) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_please_enter_your_oss_access_key", "请输入OSS Access Key ID."));
    }
    elseif (strlen($oss_secret_key) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_please_enter_your_oss_secret_key", "请输入OSS Access Key Secret."));
    }
    elseif (strlen($oss_host) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_please_enter_your_oss_secret_key", "请输入OSS外网域名."));
    }
    elseif (strlen($oss_dir_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_oss_dir_name_please_enter_your_oss_secret_key", "请输入上传目录."));
    }
    elseif (strlen($oss_endpoint) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_please_enter_your_oss_endpoint", "请输入OSS Endpoint."));
    }
    elseif (strlen($oss_bucket) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_please_enter_your_oss_bucket", "请输入OSS bucket 名称."));
    }
    /*elseif (strlen($oss_max_upload_bytes) == 0)
    {
       // adminFunctions::setError(adminFunctions::t("plugin_oss_max_upload_bytes_please_enter_your_oss_bucket", "请输入最大上传文件字节数"));
    }*/

    // try to authenticate the details
    if (adminFunctions::isErrors() == false)
    {
        // get required classes
        require_once(PLUGIN_DIRECTORY_ROOT . 'aliyunoss/includes/alioss/autoload.php');
        
        // check that we can connect
        $ossClient = new \OSS\OssClient($oss_access_key,$oss_secret_key,$oss_endpoint,$oss_iscname);
        if(is_null($ossClient))
        {
            // failed connecting
            adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_could_not_autheticate_access_details", "Could not connect to OSS using the keys you entered, please try again."));
        }
        else
        {
           /* // check bucket
            if(!$ossClient->doesBucketExist($oss_bucket))
            {
                // failed getting bucket
                adminFunctions::setError(adminFunctions::t("plugin_aliyunoss_could_not_load_bucket", "We could not find the bucket or the OSS keys are incorrect, please try again."));
            }*/
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                   = array();
        $settingsArr['oss_access_key'] = $oss_access_key;
        $settingsArr['oss_secret_key'] = $oss_secret_key;
        $settingsArr['oss_bucket']     = $oss_bucket;
        $settingsArr['oss_host']       = $oss_host;
        $settingsArr['oss_endpoint']   = $oss_endpoint;
        $settingsArr['oss_iscname']    = $oss_iscname;
        $settingsArr['oss_dir_name']    = $oss_dir_name;
//        $settingsArr['oss_max_upload_bytes']    = $oss_max_upload_bytes;
        $settings                      = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        // update plugin config
		pluginHelper::loadPluginConfigurationFiles(true);
		adminFunctions::setSuccess('Plugin settings updated.');
    }
}

if(_CONFIG_DEMO_MODE == true)
{
    $oss_secret_key = '**********************************';
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
    $(function() {
        // formvalidator
        $("#userForm").validationEngine();
    });
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>插件说明</h3>
                            <p>如果您使用OSS存储文件,无论OSS存储插件是否启用,请勿禁用。</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>启用插件:</label>
                                    <div class="input">
                                        <select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($plugin_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>OSS Credentials</h3>
                            <p>OSS access details and bucket name. Note: The bucket name should already exist. If you've just created it, it may take up to a minute for it to be distributed and this page to see it.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label for="oss_access_key">OSS Access Key ID:</label>
                                    <div class="input">
                                        <input id="oss_access_key" name="oss_access_key" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_access_key); ?>"/>
                                    </div>
                                </div>

                                <div class="clearfix">
                                    <label for="oss_secret_key">OSS Access Key Secret :</label>
                                    <div class="input">
                                        <input id="oss_secret_key" name="oss_secret_key" type="password" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_secret_key); ?>"/>
                                    </div>
                                </div>

                                <div class="clearfix alt-highlight">
                                    <label for="oss_bucket">OSS Bucket名称 :</label>
                                    <div class="input">
                                        <input id="oss_bucket" name="oss_bucket" type="text" class="xlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_bucket); ?>"/>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label for="oss_dir_name">上传目录 :</label>
                                    <div class="input">
                                        <input id="oss_dir_name" name="oss_dir_name" type="text" class="xlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_dir_name); ?>"/>
                                    </div>
                                </div>
                                <!--<div class="clearfix alt-highlight">
                                    <label for="oss_max_upload_bytes">最大上传文件大小 :</label>
                                    <div class="input">
                                        <input id="oss_max_upload_bytes" name="oss_max_upload_bytes" type="text" class="xlarge validate[required]" value="<?php /*echo adminFunctions::makeSafe($oss_max_upload_bytes); */?>"/>
                                    </div>
                                    <div class="input">
                                    (以字节为单位，默认1GB (1048576000字节))
                                    </div>
                                </div>-->
                                <div class="clearfix alt-highlight">
                                    <label for="oss_host">OSS外网域名:</label>
                                    <div class="input">
                                        <input id="oss_host" name="oss_host" type="text" class="xlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_host); ?>"/>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label for="oss_endpoint" title="主要在SDK中使用">OSS EndPoint:</label>
                                    <div class="input">
                                        <input id="oss_endpoint" name="oss_endpoint" type="text" class="xlarge validate[required]" value="<?php echo adminFunctions::makeSafe($oss_endpoint); ?>"/>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>是否绑定自定义域名:</label>
                                    <div class="input">
                                        <select name="oss_iscname" id="oss_iscname" class="medium" title="是否对Bucket做了域名绑定，并且Endpoint参数填写的是自己的域名">
                                            <?php
                                            $enabledOptions = array(0 => '否', 1 => '是');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($oss_iscname == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
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
                                    <input type="submit" value="Submit" class="button blue"/>
									<input type="reset" value="Cancel" class="button" onClick="window.location='<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php';"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $pluginId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>