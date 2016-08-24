<?php

// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');

// includes and security
include_once ('../../../core/includes/master.inc.php');
include_once (DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int)$_REQUEST['id'];
$plugin = $db->getRow("SELECT * FROM plugin WHERE id = " . (int)$pluginId .
    " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' .
        urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// plugin object for functions
$pluginObj = pluginHelper::getInstance('torrentdownload');

// prepare variables
$plugin_enabled = (int)$plugin['plugin_enabled'];
$utorrent_host = _CONFIG_SITE_HOST_URL;
// remove port from host, if exists
$url_parts = parse_url($utorrent_host);
if (!isset($url_parts['host']))
{
    $utorrent_host = $url_parts['path'];
}
else
{
    $utorrent_host = $url_parts['host'];
}

$utorrent_port = '8080';
$utorrent_username = 'admin';
$utorrent_password = '';
$show_torrent_tab_paid = 1;
$show_torrent_tab = 0;
$max_torrents_per_day_free = 5;
$max_concurrent_free = 1;
$max_torrents_per_day_paid = 10;
$max_concurrent_paid = 3;
$use_max_upload_settings = 1;
$torrent_server = 'transmission';

// transmission settings
$transmission_host = _CONFIG_SITE_HOST_URL;
// remove port from host, if exists
$url_parts = parse_url($transmission_host);
if (!isset($url_parts['host']))
{
    $transmission_host = $url_parts['path'];
}
else
{
    $transmission_host = $url_parts['host'];
}

$transmission_port = '9091';
$transmission_username = 'admin';
$transmission_password = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $utorrent_host = $plugin_settings['utorrent_host'];
        $utorrent_port = $plugin_settings['utorrent_port'];
        $utorrent_username = $plugin_settings['utorrent_username'];
        $utorrent_password = $plugin_settings['utorrent_password'];
        $show_torrent_tab_paid = $plugin_settings['show_torrent_tab_paid'];
        $show_torrent_tab = $plugin_settings['show_torrent_tab'];
        $max_torrents_per_day_free = $plugin_settings['max_torrents_per_day_free'];
        $max_concurrent_free = $plugin_settings['max_concurrent_free'];
        $max_torrents_per_day_paid = $plugin_settings['max_torrents_per_day_paid'];
        $max_concurrent_paid = $plugin_settings['max_concurrent_paid'];
        $use_max_upload_settings = (int)$plugin_settings['use_max_upload_settings'];
        $torrent_server = isset($plugin_settings['torrent_server'])?$plugin_settings['torrent_server']:'utorrent';
        $transmission_host = isset($plugin_settings['transmission_host'])?$plugin_settings['transmission_host']:$transmission_host;
        $transmission_port = isset($plugin_settings['transmission_port'])?$plugin_settings['transmission_port']:$transmission_port;
        $transmission_username = $plugin_settings['transmission_username'];
        $transmission_password = $plugin_settings['transmission_password'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled = (int)$_REQUEST['plugin_enabled'];
    $utorrent_host = strtolower(trim($_REQUEST['utorrent_host']));
    $url_parts = parse_url($utorrent_host);
    if (!isset($url_parts['host']))
    {
        $utorrent_host = $url_parts['path'];
    }
    else
    {
        $utorrent_host = $url_parts['host'];
    }

    $utorrent_port = (int)$_REQUEST['utorrent_port'];
    $utorrent_username = trim($_REQUEST['utorrent_username']);
    $utorrent_password = trim($_REQUEST['utorrent_password']);
    $show_torrent_tab_paid = isset($_REQUEST['show_torrent_tab_paid']) ? (int)$_REQUEST['show_torrent_tab_paid'] :
        0;
    $show_torrent_tab = isset($_REQUEST['show_torrent_tab']) ? (int)$_REQUEST['show_torrent_tab'] :
        0;
    $max_torrents_per_day_free = (int)$_REQUEST['max_torrents_per_day_free'];
    $max_concurrent_free = (int)$_REQUEST['max_concurrent_free'];
    $max_torrents_per_day_paid = (int)$_REQUEST['max_torrents_per_day_paid'];
    $max_concurrent_paid = (int)$_REQUEST['max_concurrent_paid'];
    $use_max_upload_settings = (int)$_REQUEST['use_max_upload_settings'];
    $torrent_server = $_REQUEST['torrent_server'];
    
    // transmission settings
    $transmission_host = strtolower(trim($_REQUEST['transmission_host']));
    $url_parts = parse_url($transmission_host);
    if (!isset($url_parts['host']))
    {
        $transmission_host = $url_parts['path'];
    }
    else
    {
        $transmission_host = $url_parts['host'];
    }

    $transmission_port = (int)$_REQUEST['transmission_port'];
    $transmission_username = trim($_REQUEST['transmission_username']);
    $transmission_password = trim($_REQUEST['transmission_password']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr = array();
        $settingsArr['utorrent_host'] = $utorrent_host;
        $settingsArr['utorrent_port'] = $utorrent_port;
        $settingsArr['utorrent_username'] = $utorrent_username;
        $settingsArr['utorrent_password'] = $utorrent_password;
        $settingsArr['show_torrent_tab_paid'] = $show_torrent_tab_paid;
        $settingsArr['show_torrent_tab'] = $show_torrent_tab;
        $settingsArr['max_torrents_per_day_free'] = $max_torrents_per_day_free;
        $settingsArr['max_concurrent_free'] = $max_concurrent_free;
        $settingsArr['max_torrents_per_day_paid'] = $max_torrents_per_day_paid;
        $settingsArr['max_concurrent_paid'] = $max_concurrent_paid;
        $settingsArr['use_max_upload_settings'] = $use_max_upload_settings;
        $settingsArr['torrent_server'] = $torrent_server;
        $settingsArr['transmission_host'] = strlen($transmission_host)?$transmission_host:'9091';
        $settingsArr['transmission_port'] = $transmission_port;
        $settingsArr['transmission_username'] = $transmission_username;
        $settingsArr['transmission_password'] = $transmission_password;
        $settings = json_encode($settingsArr);

        // update the user
        $dbUpdate = new DBObject("plugin", array("plugin_enabled", "plugin_settings"),
            'id');
        $dbUpdate->plugin_enabled = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id = $pluginId;
        $dbUpdate->update();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

if (_CONFIG_DEMO_MODE == true)
{
    $utorrent_password = '****************';
    $transmission_password = '****************';
}

// page header
include_once (ADMIN_ROOT . '/_header.inc.php');

?>

<script>
    $(function() {
        // formvalidator
        $("#pluginForm").validationEngine();
        switchTorrentEngineInfo();
    });
    
    function switchTorrentEngineInfo()
    {
        if($("#torrent_server").val() == 'utorrent')
        {
            $('.transmission').hide();
            $('.utorrent').show();
        }
        else
        {
            $('.utorrent').hide();
            $('.transmission').show();
        }
    }
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php

echo adminFunctions::compileNotifications();

?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the torrent download plugin is available.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
                                    <div class="input">
                                        <select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
                                            <?php

                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions as $k => $enabledOption)
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
                            <h3>Torrent Engine</h3>
                            <p>Which torrent engine to use. You will need to set this up and configure it on your server, full details supplied.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Torrent Engine:</label>
                                    <div class="input">
                                        <select name="torrent_server" id="torrent_server" class="xxlarge validate[required]" onChange="switchTorrentEngineInfo(); return false;">
                                            <?php

                                            $options = array('transmission' => 'Transmission (recommended)', 'utorrent' => 'uTorrent');
                                            foreach ($options as $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($torrent_server == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12 utorrent">
                        <div class="col_4">
                            <h3>uTorrent Installation</h3>
                            <p>How to install uTorrent and configure it correctly.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        This plugin requires uTorrent to manage the actual torrent downloads. uTorrent can be found via the following url.<br/><br/>
										- <a href="http://www.utorrent.com/downloads/linux" target="_blank">http://www.utorrent.com/downloads/linux</a>
										<br/><br/>
										It needs to be installed on a server which has the full YetiShare script installed on it and setup as a 'direct' file server. It is recommended you use Debian or Ubuntu for uTorrent, there are known issues with it working on CentOS. To install uTorrent on Ubuntu via SSH (replace the wget url with the correct download):
										<pre>cd /root
wget http://download.utorrent.com/linux/utorrent-server-3.0-25053.tar.gz
sudo cp utorrent-server-3.0-25053.tar.gz /opt/
cd /opt/
sudo tar -xvf utorrent-server-3.0-25053.tar.gz
sudo rm -rf utorrent-server-3.0-25053.tar.gz
sudo chmod 777 -R utorrent-server-v3_0/
sudo ln -s /opt/utorrent-server-v3_0/utserver /usr/bin/utserver
utserver -settingspath /opt/utorrent-server-v3_0/ &</pre>
                                        Once installed, via the web interface (http://yourdomain.com:8080/gui/, username: admin, password: [blank]), settings icon, ensure you update the "Put new downloads in" setting to (replace path to match torrent server):<br/><br/>
                                        <code>
                                            <?php echo DOC_ROOT; ?>/files/_tmp
                                        </code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12 utorrent">
                        <div class="col_4">
                            <h3>Useful Resources</h3>
                            <p>Other useful commands, sites etc.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        uTorrent Site: <a href="http://www.utorrent.com/downloads/linux" target="_blank">http://www.utorrent.com/downloads/linux</a><br/><br/>
										Change uTorrent Port/Create utserver.conf File: <a href="http://askubuntu.com/a/202436" target="_blank">http://askubuntu.com/a/202436</a><br/><br/>
										Start Server From Command Line:<br/>
										<pre>utserver -settingspath /opt/utorrent-server-v3_0/ &</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12 utorrent">
                        <div class="col_4">
                            <h3>uTorrent Connection Details</h3>
                            <p>Host, port, username and password for the uTorrent interface.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>uTorrent Host/IP:</label>
                                    <div class="input"><input id="utorrent_host" name="utorrent_host" type="text" class="large validate[required]" value="<?php

echo adminFunctions::makeSafe($utorrent_host);

?>"/>&nbsp;&nbsp;Exclude http:// and any forward slashes</div>
                                </div>
                                <div class="clearfix">
                                    <label>uTorrent Port:</label>
                                    <div class="input"><input id="utorrent_port" name="utorrent_port" type="text" class="large validate[required]" placeholder="9091" value="<?php

echo adminFunctions::makeSafe($utorrent_port);

?>"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>uTorrent Username:</label>
                                    <div class="input"><input id="utorrent_username" name="utorrent_username" type="text" class="large validate[required]" value="<?php

echo adminFunctions::makeSafe($utorrent_username);

?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>uTorrent Password:</label>
                                    <div class="input"><input id="utorrent_password" name="utorrent_password" type="password" class="large" value="<?php

echo adminFunctions::makeSafe($utorrent_password);

?>"/></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="clearfix col_12 transmission">
                        <div class="col_4">
                            <h3>Transmission Installation</h3>
                            <p>How to install Transmission and configure it correctly.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        This plugin requires Transmission to manage the actual torrent downloads. Transmission can be found via the following url.<br/><br/>
										- <a href="http://www.transmissionbt.com/download/" target="_blank">http://www.transmissionbt.com/download/</a>
										<br/><br/>
										It needs to be installed on a server which has the full YetiShare script installed on it and setup as a 'direct' file server. Transmission has better choice in Unix distros compared to uTorrent so it's preferred. To install Transmission on CentOS 6 via SSH:
										<pre>wget http://mirror.pnl.gov/epel/6/i386/epel-release-6-8.noarch.rpm
rpm -Uvh epel-release-6-8.noarch.rpm
yum -y update
yum -y install transmission transmission-daemon</pre>
                                        Once installed, start it using this command:<br/>
                                        <pre>service transmission-daemon start</pre>
                                        To configure it for remote access, first shutdown the service.<br/>
                                        <pre>service transmission-daemon stop</pre>
                                        Find the settings file:<br/>
                                        <pre>updatedb
locate settings.json</pre>
                                        Once you know the path open it for editing:<br/>
                                        <pre>nano /etc/transmission-daemon/settings.json</pre>
                                        Change the following lines to allow remote access from your server. When a "White List" is set it means only those IP addresses can access the software. If you want to use the White List then set the appropriate IP addresses here (in place of "127.0.0.1"). Otherwise we can just set the whitelist to "false" like so:<br/>
                                        <pre>"rpc-whitelist": "127.0.0.1",
"rpc-whitelist-enabled": false,</pre>
                                        Find the "download-dir" setting and update to this (change base path if running on external server):<br/>
                                        <pre>"download-dir": "<?php echo DOC_ROOT; ?>/files/_tmp",</pre>
                                        Save and exit. Then start the Transmission service:<br/>
                                        <pre>service transmission-daemon start</pre>
                                        You can login to Transmission using the url you've set. Example:<br/>
                                        <pre>http://<?php echo adminFunctions::makeSafe($transmission_host); ?>:<?php echo adminFunctions::makeSafe($transmission_port); ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12 transmission">
                        <div class="col_4">
                            <h3>Transmission Connection Details</h3>
                            <p>Host, port, username and password for the Transmission interface.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Transmission Host/IP:</label>
                                    <div class="input"><input id="transmission_host" name="transmission_host" type="text" class="large validate[required]" value="<?php

echo adminFunctions::makeSafe($transmission_host);

?>"/>&nbsp;&nbsp;Exclude http:// and any forward slashes</div>
                                </div>
                                <div class="clearfix">
                                    <label>Transmission Port:</label>
                                    <div class="input"><input id="transmission_port" name="transmission_port" type="text" class="large validate[required]" value="<?php

echo adminFunctions::makeSafe($transmission_port);

?>"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Transmission Username:</label>
                                    <div class="input"><input id="transmission_username" name="transmission_username" type="text" class="large" value="<?php

echo adminFunctions::makeSafe($transmission_username);

?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>Transmission Password:</label>
                                    <div class="input"><input id="transmission_password" name="transmission_password" type="password" class="large" value="<?php

echo adminFunctions::makeSafe($transmission_password);

?>"/></div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Display Settings</h3>
                            <p>How to show the tab on the homepage.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Paid Only:</label>
                                    <div class="input"><input id="show_torrent_tab_paid" name="show_torrent_tab_paid" type="checkbox" value="1" <?php

echo ($show_torrent_tab_paid == 1) ? 'CHECKED' : '';

?>/>&nbsp;&nbsp;only paid users will have access to use the torrent download</div>
                                </div>
                                <div class="clearfix">
                                    <label>Always Show Torrent Tab:</label>
                                    <div class="input"><input id="show_torrent_tab" name="show_torrent_tab" type="checkbox" value="1" <?php

echo ($show_torrent_tab == 1) ? 'CHECKED' : '';

?>/>&nbsp;&nbsp;non/free-users will see the torrent tab on the homepage (prompt to register/upgrade<br/>on click)</div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Other Settings</h3>
                            <p>Limitations for free and paid users.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Free Max Per Day:</label>
                                    <div class="input"><input id="max_torrents_per_day_free" name="max_torrents_per_day_free" type="text" class="small" value="<?php

echo adminFunctions::makeSafe($max_torrents_per_day_free);

?>"/>&nbsp;&nbsp;Free (registered) users max torrent downloads permitted per day.</div>
                                </div>
                                <div class="clearfix">
                                    <label>Free Max Concurrent:</label>
                                    <div class="input"><input id="max_concurrent_free" name="max_concurrent_free" type="text" class="small" value="<?php

echo adminFunctions::makeSafe($max_concurrent_free);

?>"/>&nbsp;&nbsp;Free (registered) users max concurrent torrents.</div>
                                </div>
								<div class="clearfix alt-highlight">
                                    <label>Paid Max Per Day:</label>
                                    <div class="input"><input id="max_torrents_per_day_paid" name="max_torrents_per_day_paid" type="text" class="small" value="<?php

echo adminFunctions::makeSafe($max_torrents_per_day_paid);

?>"/>&nbsp;&nbsp;Paid users max torrent downloads permitted per day.</div>
                                </div>
                                <div class="clearfix">
                                    <label>Paid Max Concurrent:</label>
                                    <div class="input"><input id="max_concurrent_paid" name="max_concurrent_paid" type="text" class="small" value="<?php

echo adminFunctions::makeSafe($max_concurrent_paid);

?>"/>&nbsp;&nbsp;Paid users max concurrent torrents.</div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Use Max Upload Size:</label>
                                    <div class="input">
                                    <select id="use_max_upload_settings" name="use_max_upload_settings" class="small">
                                        <option value="0"<?php echo $use_max_upload_settings==0?' SELECTED':''; ?>>No</option>
                                        <option value="1"<?php echo $use_max_upload_settings==1?' SELECTED':''; ?>>Yes</option>
                                    </select>
                                    &nbsp;&nbsp;Users can not add torrents larger than their account max upload size.</div>
                                </div>
                            </div>
                        </div>
                    </div>
					
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Cron Task</h3>
                            <p>How to setup the background cron task to sync YetiShare with your torrent engine.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        The cron task is needed to update any current torrent download progress and to also import any torrents which have finished. It should be run every minute.<br/><br/>
                                        The cron script is located here:<br/><br/>
                                        <code>
                                            <?php echo DOC_ROOT; ?>/plugins/torrentdownload/site/track_torrents.cron.php
                                        </code>
                                        <br/><br/><br/>
                                        To execute it, set it up on the same server as your torrent engine as a cron task. This is the line for the crontab file (replace path to match torrent server):<br/><br/>
                                        <code>
                                            * * * * * php <?php echo DOC_ROOT; ?>/plugins/torrentdownload/site/track_torrents.cron.php >> /dev/null 2>&1
                                        </code>
                                        <br/><br/><br/>
                                        See <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/" target="_blank">here for more information</a> on executing scripts via a cron task.<br/><br/>
                                        The script must be run on the same server as your torrent engine so it can copy the files when finished. Replace the domain path above if you are not running this on your main site.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    $torrentGuiUrl = "http://".$utorrent_host.":".$utorrent_port."/gui/";
                    $torrent_host = $utorrent_host;
                    if($torrent_server == 'transmission')
                    {
                        $torrentGuiUrl = "http://".$transmission_host.":".$transmission_port;
                        $torrent_host = $transmission_host;
                    }                    
                    ?>

                    <div class="clearfix col_12">
                        <div class="col_4">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Reset" class="button grey">
									<input type="reset" value="Trigger Torrent Cron" class="button grey" onClick="window.open('http://<?php echo adminFunctions::makeSafe($torrent_host); ?>/plugins/torrentdownload/site/track_torrents.cron.php');"/>
									<input type="reset" value="Torrent Engine GUI" class="button grey" onClick="window.open('<?php echo adminFunctions::makeSafe($torrentGuiUrl); ?>');"/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php

echo $pluginId;

?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<?php

include_once (ADMIN_ROOT . '/_footer.inc.php');

?>