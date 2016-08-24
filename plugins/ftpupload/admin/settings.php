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

// plugin object for functions
$pluginObj = pluginHelper::getInstance('ftpupload');

// connection types
$connectionTypes           = array();
$connectionTypes['cpanel'] = 'cPanel (for WHM/cPanel servers only)';
$connectionTypes['proftpd'] = 'ProFTPD (needs some config to ProFTP, detailed below)';

// prepare variables
$plugin_enabled             = (int) $plugin['plugin_enabled'];
$connection_type            = 'cpanel';
$connection_cpanel_host     = _CONFIG_SITE_HOST_URL;
$connection_cpanel_user     = '';
$connection_cpanel_password = '';
$home_dir_path              = 'public_html/';
$ftp_account_quota          = 2000;
$paid_only                  = 0;
$show_ftp_tab               = 0;
$append_username            = '';
$ftp_host_override          = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $connection_type            = $plugin_settings['connection_type'];
        $connection_cpanel_host     = $plugin_settings['connection_cpanel_host'];
        $connection_cpanel_user     = $plugin_settings['connection_cpanel_user'];
        $connection_cpanel_password = $plugin_settings['connection_cpanel_password'];
        $home_dir_path              = $plugin_settings['home_dir_path'];
        $ftp_account_quota          = $plugin_settings['ftp_account_quota'];
        $paid_only                  = $plugin_settings['paid_only'];
        $show_ftp_tab               = $plugin_settings['show_ftp_tab'];
        $append_username            = $plugin_settings['append_username'];
        $ftp_host_override          = $plugin_settings['ftp_host_override'];
    }
}

// check for php ftp functions
if ($pluginObj->ftpFunctionsExist() === false)
{
    adminFunctions::setError(adminFunctions::t("plugin_ftp_php_functions_not_exist", "PHP FTP functions have not been found on the current server. Please enable via php.ini and try again."));
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled             = (int) $_REQUEST['plugin_enabled'];
    $connection_type            = $_REQUEST['connection_type'];
    $connection_cpanel_host     = strtolower(trim($_REQUEST['connection_cpanel_host']));
    $connection_cpanel_host     = str_replace(array("http://", "https://", "www."), "", $connection_cpanel_host);
    $connection_cpanel_user     = trim($_REQUEST['connection_cpanel_user']);
    $connection_cpanel_password = trim($_REQUEST['connection_cpanel_password']);
    $home_dir_path              = trim($_REQUEST['home_dir_path']);
    if (substr($home_dir_path, strlen($home_dir_path) - 1, 1) != '/')
    {
        $home_dir_path = $home_dir_path . '/';
    }
    if (substr($home_dir_path, 0, 1) == '/')
    {
        $home_dir_path = substr($home_dir_path, 1, strlen($home_dir_path) - 1);
    }
    $home_dir_path     = '/' . $home_dir_path;
    $ftp_account_quota = (int) trim($_REQUEST['ftp_account_quota']);
    $paid_only         = isset($_REQUEST['paid_only']) ? (int) $_REQUEST['paid_only'] : 0;
    $show_ftp_tab      = isset($_REQUEST['show_ftp_tab']) ? (int) $_REQUEST['show_ftp_tab'] : 0;
    $append_username   = trim($_REQUEST['append_username']);
    $ftp_host_override = trim($_REQUEST['ftp_host_override']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // test ftp path
    if (strlen($home_dir_path) == 0)
    {
        adminFunctions::setError(adminFunctions::t("set_the_ftp_path", "Please set the path to store ftp accounts."));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                               = array();
        $settingsArr['connection_type']            = $connection_type;
        $settingsArr['connection_cpanel_host']     = $connection_cpanel_host;
        $settingsArr['connection_cpanel_user']     = $connection_cpanel_user;
        $settingsArr['connection_cpanel_password'] = $connection_cpanel_password;
        $settingsArr['home_dir_path']              = $home_dir_path;
        $settingsArr['paid_only']                  = $paid_only;
        $settingsArr['ftp_account_quota']          = $ftp_account_quota;
        $settingsArr['show_ftp_tab']               = $show_ftp_tab;
        $settingsArr['append_username']            = $append_username;
        $settingsArr['ftp_host_override']          = $ftp_host_override;
        $settings                                  = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

if (_CONFIG_DEMO_MODE == true)
{
    $connection_cpanel_password = '****************';
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
    $(function() {
        hideShowOptions();
    });
    
    function hideShowOptions()
    {
        $('.cPanelOptions').hide();
        $('.proFTPDOptions').hide();
        if ($('#connection_type').val() == 'cpanel')
        {
            $('.cPanelOptions').show();
        }
        else if ($('#connection_type').val() == 'proftpd')
        {
            $('.proFTPDOptions').show();
        }
    }
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
                            <h3>Plugin State</h3>
                            <p>Whether the ftp upload plugin is available.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
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
                            <h3>FTP Connection Type</h3>
                            <p>Which FTP method to use.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Method:</label>
                                    <div class="input">
                                        <select name="connection_type" id="connection_type" onChange="hideShowOptions(); return false;">
                                            <?php
                                            foreach ($connectionTypes AS $k => $connectionType)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($k == $connection_type)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $connectionType . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>FTP Host:</label>
                                    <div class="input"><input id="connection_cpanel_host" name="connection_cpanel_host" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($connection_cpanel_host); ?>"/>&nbsp;&nbsp;IP or domain name of FTP host.</div>
                                </div>
                                <div class="clearfix cPanelOptions alt-highlight">
                                    <label>cPanel Username:</label>
                                    <div class="input"><input id="connection_cpanel_user" name="connection_cpanel_user" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($connection_cpanel_user); ?>"/>&nbsp;&nbsp;Your access username for cPanel.</div>
                                </div>
                                <div class="clearfix cPanelOptions">
                                    <label>cPanel Password:</label>
                                    <div class="input"><input id="connection_cpanel_password" name="connection_cpanel_password" type="password" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($connection_cpanel_password); ?>"/>&nbsp;&nbsp;Your access password for cPanel.</div>
                                </div>
                                
                                <div class="clearfix alt-highlight proFTPDOptions">
                                    <div style="margin: 8px;">
                                        First install ProFTPD on your server. Then use the information below to configure your ProFTPD installation.<br/><br/>
                                        ProFTPD will be configured to work in virtual mode, so the FTP accounts will be created in your YetiShare database, then ProFTPD is pointed at the database for it's authentication. If you've installed ProFTPD on a different host, you'll need to make sure the database user allows that host for connectivity.<br/><br/>
                                        Note: The paths below may need amending depending on where you've installed ProFTPD. Replace any values below in [[[SQUARE_BRACKETS]]]. If you have any issues please contact us via the support system.<br/><br/>
                                        
                                        <br/>
                                        <code>
                                            /etc/proftpd/sql.conf
                                        </code>
                                        <br/><br/>
                                        <textarea class="xxlarge" style="width: 100%; max-width: 100%; font-family: 'Lucida Console', Consolas,'Courier New',Courier,monospace;">
SQLBackend        mysql

#Passwords in MySQL are encrypted using CRYPT
SQLAuthTypes            OpenSSL Crypt
SQLAuthenticate         users groups

# used to connect to the database
# databasename@host database_user user_password
SQLConnectInfo  <?php echo _CONFIG_DB_NAME; ?>@<?php echo _CONFIG_SITE_HOST_URL; ?> [[[REPLACE_WITH_DATABASE_USER]]] [[[REPLACE_WITH_DATABASE_PASS]]]

# Here we tell ProFTPd the names of the database columns in the "usertable"
# we want it to interact with. Match the names with those in the db
SQLUserInfo     plugin_ftp_proftpd_user user_id passwd uid gid home_dir shell

# Here we tell ProFTPd the names of the database columns in the "grouptable"
# we want it to interact with. Again the names match with those in the db
SQLGroupInfo    plugin_ftp_proftpd_group group_name gid members

# set min UID and GID - otherwise these are 999 each
SQLMinID        500

# Update count every time user logs in
SQLLog PASS updatecount
SQLNamedQuery updatecount UPDATE "count=count+1, accessed=now() WHERE user_id='%u'" plugin_ftp_proftpd_user

# Update modified everytime user uploads or deletes a file
SQLLog  STOR,DELE modified
SQLNamedQuery modified UPDATE "modified=now() WHERE user_id='%u'" plugin_ftp_proftpd_user

SqlLogFile /var/log/proftpd/sql.log
                                        </textarea>
                                        
                                        <br/><br/><br/>
                                        
                                        <code>
                                            /etc/proftpd/proftpd.conf
                                        </code>
                                        <br/><br/>
                                        <textarea class="xxlarge" style="width: 100%; max-width: 100%; font-family: 'Lucida Console', Consolas,'Courier New',Courier,monospace;">
#
# /etc/proftpd/proftpd.conf -- This is a basic ProFTPD configuration file.
# To really apply changes, reload proftpd after modifications, if
# it runs in daemon mode. It is not required in inetd/xinetd mode.
#

# Includes DSO modules
Include /etc/proftpd/modules.conf

# Set off to disable IPv6 support which is annoying on IPv4 only boxes.
UseIPv6                         on
# If set on you can experience a longer connection delay in many cases.
IdentLookups                    off

ServerName                      "[[[REPLACE_FTP_SERVER_NAME_SUCH_AS_<?php echo _CONFIG_SITE_HOST_URL; ?>]]]"
ServerType                      standalone
DeferWelcome                    off

MultilineRFC2228                on
DefaultServer                   on
ShowSymlinks                    on

TimeoutNoTransfer               600
TimeoutStalled                  600
TimeoutIdle                     1200

DisplayLogin                    welcome.msg
DisplayChdir                    .message true
ListOptions                     "-l"

DenyFilter                      \*.*/

# Use this to jail all users in their homes
DefaultRoot                     ~

# Users require a valid shell listed in /etc/shells to login.
# Use this directive to release that constrain.
# RequireValidShell             off

# Port 21 is the standard FTP port.
Port                            21

# In some cases you have to specify passive ports range to by-pass
# firewall limitations. Ephemeral ports can be used for that, but
# feel free to use a more narrow range.
# PassivePorts                  49152 65534

# If your host was NATted, this option is useful in order to
# allow passive tranfers to work. You have to use your public
# address and opening the passive ports used on your firewall as well.
# MasqueradeAddress             1.2.3.4

# This is useful for masquerading address with dynamic IPs:
# refresh any configured MasqueradeAddress directives every 8 hours
<IfModule mod_dynmasq.c>
# DynMasqRefresh 28800
</IfModule>

# To prevent DoS attacks, set the maximum number of child processes
# to 30.  If you need to allow more than 30 concurrent connections
# at once, simply increase this value.  Note that this ONLY works
# in standalone mode, in inetd mode you should use an inetd server
# that allows you to limit maximum number of processes per service
# (such as xinetd)
MaxInstances                    30

# Set the user and group that the server normally runs at.
User                            proftpd
Group                           nogroup

# Umask 022 is a good standard umask to prevent new files and dirs
# (second parm) from being group and world writable.
Umask                           022  022
# Normally, we want files to be overwriteable.
AllowOverwrite                  on

# Uncomment this if you are using NIS or LDAP via NSS to retrieve passwords:
# PersistentPasswd              off

# This is required to use both PAM-based authentication and local passwords
# AuthOrder                     mod_auth_pam.c* mod_auth_unix.c

# Be warned: use of this directive impacts CPU average load!
# Uncomment this if you like to see progress and transfer rate with ftpwho
# in downloads. That is not needed for uploads rates.
#
# UseSendFile                   off

TransferLog /var/log/proftpd/xferlog
SystemLog   /var/log/proftpd/proftpd.log

# Logging onto /var/log/lastlog is enabled but set to off by default
#UseLastlog on

# In order to keep log file dates consistent after chroot, use timezone info
# from /etc/localtime.  If this is not set, and proftpd is configured to
# chroot (e.g. DefaultRoot or <Anonymous>), it will use the non-daylight
# savings timezone regardless of whether DST is in effect.
#SetEnv TZ :/etc/localtime

<IfModule mod_quotatab.c>
QuotaEngine off
</IfModule>

<IfModule mod_ratio.c>
Ratios off
</IfModule>


# Delay engine reduces impact of the so-called Timing Attack described in
# http://www.securityfocus.com/bid/11430/discuss
# It is on by default.
<IfModule mod_delay.c>
DelayEngine on
</IfModule>

<IfModule mod_ctrls.c>
ControlsEngine        off
ControlsMaxClients    2
ControlsLog           /var/log/proftpd/controls.log
ControlsInterval      5
ControlsSocket        /var/run/proftpd/proftpd.sock
</IfModule>

<IfModule mod_ctrls_admin.c>
AdminControlsEngine off
</IfModule>

#
# Alternative authentication frameworks

#
#Include /etc/proftpd/ldap.conf
#Include /etc/proftpd/sql.conf

#
# This is used for FTPS connections
#
#Include /etc/proftpd/tls.conf

#
# Useful to keep VirtualHost/VirtualRoot directives separated
#
#Include /etc/proftpd/virtuals.conf

# A basic anonymous configuration, no upload directories.

# <Anonymous ~ftp>
#   User                                ftp
#   Group                               nogroup
#   # We want clients to be able to login with "anonymous" as well as "ftp"
#   UserAlias                   anonymous ftp
#   # Cosmetic changes, all files belongs to ftp user
#   DirFakeUser on ftp
#   DirFakeGroup on ftp
#
#   RequireValidShell           off
#
#   # Limit the maximum number of anonymous logins
#   MaxClients                  10
#
#   # We want 'welcome.msg' displayed at login, and '.message' displayed
#   # in each newly chdired directory.
#   DisplayLogin                        welcome.msg
#   DisplayChdir                .message
#
#   # Limit WRITE everywhere in the anonymous chroot
#   <Directory *>
#     <Limit WRITE>
#       DenyAll
#     </Limit>
#   </Directory>
#
#   # Uncomment this if you're brave.
#   # <Directory incoming>
#   #   # Umask 022 is a good standard umask to prevent new files and dirs
#   #   # (second parm) from being group and world writable.
#   #   Umask                           022  022
#   #            <Limit READ WRITE>
#   #            DenyAll
#   #            </Limit>
#   #            <Limit STOR>
#   #            AllowAll
#   #            </Limit>
#   # </Directory>
#
# </Anonymous>

# Include other custom configuration files
Include /etc/proftpd/conf.d/
Include /etc/proftpd/sql.conf
RequireValidShell off

# auto create home
CreateHome on 711
                                        </textarea>
                                        
                                        <br/><br/><br/>
                                        
                                        <code>
                                            /etc/proftpd/modules.conf
                                        </code>
                                        <br/><br/>
                                        <textarea class="xxlarge" style="width: 100%; max-width: 100%; font-family: 'Lucida Console', Consolas,'Courier New',Courier,monospace;">
#
# This file is used to manage DSO modules and features.
#

# This is the directory where DSO modules reside

ModulePath /usr/lib/proftpd

# Allow only user root to load and unload modules, but allow everyone
# to see which modules have been loaded

ModuleControlsACLs insmod,rmmod allow user root
ModuleControlsACLs lsmod allow user *

LoadModule mod_ctrls_admin.c
LoadModule mod_tls.c

# Install one of proftpd-mod-mysql, proftpd-mod-pgsql or any other
# SQL backend engine to use this module and the required backend.
# This module must be mandatory loaded before anyone of
# the existent SQL backeds.
LoadModule mod_sql.c

# Install proftpd-mod-ldap to use this
#LoadModule mod_ldap.c

#
# 'SQLBackend mysql' or 'SQLBackend postgres' (or any other valid backend) directives
# are required to have SQL authorization working. You can also comment out the
# unused module here, in alternative.
#

# Install proftpd-mod-mysql and decomment the previous
# mod_sql.c module to use this.
LoadModule mod_sql_mysql.c

# Install proftpd-mod-pgsql and decomment the previous
# mod_sql.c module to use this.
#LoadModule mod_sql_postgres.c

# Install proftpd-mod-sqlite and decomment the previous
# mod_sql.c module to use this
#LoadModule mod_sql_sqlite.c

# Install proftpd-mod-odbc and decomment the previous
# mod_sql.c module to use this
#LoadModule mod_sql_odbc.c

# Install one of the previous SQL backends and decomment
# the previous mod_sql.c module to use this
#LoadModule mod_sql_passwd.c

LoadModule mod_radius.c
LoadModule mod_quotatab.c
LoadModule mod_quotatab_file.c

# Install proftpd-mod-ldap to use this
#LoadModule mod_quotatab_ldap.c

# Install one of the previous SQL backends and decomment
# the previous mod_sql.c module to use this
#LoadModule mod_quotatab_sql.c
LoadModule mod_quotatab_radius.c
LoadModule mod_wrap.c
LoadModule mod_rewrite.c
LoadModule mod_load.c

LoadModule mod_rewrite.c
LoadModule mod_load.c
LoadModule mod_ban.c
LoadModule mod_wrap2.c
LoadModule mod_wrap2_file.c
# Install one of the previous SQL backends and decomment
# the previous mod_sql.c module to use this
#LoadModule mod_wrap2_sql.c
LoadModule mod_dynmasq.c
LoadModule mod_exec.c
LoadModule mod_shaper.c
LoadModule mod_ratio.c
LoadModule mod_site_misc.c

LoadModule mod_sftp.c
LoadModule mod_sftp_pam.c
# Install one of the previous SQL backends and decomment
# the previous mod_sql.c module to use this
#LoadModule mod_sftp_sql.c

LoadModule mod_facl.c
LoadModule mod_unique_id.c
LoadModule mod_copy.c
LoadModule mod_deflate.c
LoadModule mod_ifversion.c
LoadModule mod_tls_memcache.c

# Install proftpd-mod-geoip to use the GeoIP feature
#LoadModule mod_geoip.c

# keep this module the last one
LoadModule mod_ifsession.c
                                        </textarea>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>FTP Account Details</h3>
                            <p>Storage path &amp; FTP account quota. The FTP quota does not affect their file hosting account, it's only a limit on the total file size they can upload via FTP at once.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight cPanelOptions">
                                    <label>FTP Home Dir Path:</label>
                                    <div class="input"><input id="home_dir_path" name="home_dir_path" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($home_dir_path); ?>"/>&nbsp;&nbsp;Relative to your cPanel account root<br/><span class="formFieldFix" style="width: 500px; display: inline-block; padding-top: 10px;"><strong style="color: red;">* IMPORTANT *</strong> This should be outside of your webroot, so not within the www or public_html folder. If it's within the webroot you risk users uploading files via FTP and executing them via a browser directly.</span></div>
                                </div>
                                <div class="clearfix">
                                    <label>Account Quota: (in MB)</label>
                                    <div class="input"><input id="ftp_account_quota" name="ftp_account_quota" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($ftp_account_quota); ?>"/>&nbsp;&nbsp;MB, 0 for unlimited</div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Append FTP Username:</label>
                                    <div class="input"><input id="append_username" name="append_username" type="text" class="large" value="<?php echo adminFunctions::makeSafe($append_username); ?>"/>&nbsp;&nbsp;In the format 'yourdomain.com'<br/><span class="formFieldFix" style="width: 500px; display: inline-block; padding-top: 10px;">Generally leave blank. If you're having FTP login issues, try using your domain here (in the format 'yourdomain.com'). Some cPanel hosts need the username is the format 'username@yourdomain.com', others use just 'username'.</span></div>
                                </div>
                                <div class="clearfix">
                                    <label>FTP Host Override:</label>
                                    <div class="input"><input id="ftp_host_override" name="ftp_host_override" type="text" class="large" value="<?php echo adminFunctions::makeSafe($ftp_host_override); ?>"/><br/><span class="formFieldFix" style="width: 500px; display: inline-block; padding-top: 10px;">If the FTP host different from the 'FTP Host' above. i.e. ftp.yourhost.com might be used for FTP connections whereas cPanel is just yourhost.com</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Display Settings</h3>
                            <p>Which types of accounts can have access. How to show the tab on the homepage. Whether to enable folder support via FTP.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Only For Paid Users:</label>
                                    <div class="input"><input id="paid_only" name="paid_only" type="checkbox" value="1" <?php echo($paid_only == 1) ? 'CHECKED' : ''; ?>/>&nbsp;&nbsp;If ticked, only paid users will have the FTP upload option</div>
                                </div>
                                <div class="clearfix">
                                    <label>Always Show FTP Tab:</label>
                                    <div class="input"><input id="show_ftp_tab" name="show_ftp_tab" type="checkbox" value="1" <?php echo($show_ftp_tab == 1) ? 'CHECKED' : ''; ?>/>&nbsp;&nbsp;Non-users will see the FTP tab on the homepage (prompt to register on click)</div>
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
                                    <input type="reset" value="Reset" class="button grey">
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