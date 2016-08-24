<?php

class PluginFtpupload extends Plugin
{

    public $config   = null;
    public $data     = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include(DOC_ROOT . '/plugins/ftpupload/_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->data   = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->quote($this->config['folder_name']) . ' LIMIT 1');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

    public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();

        // remove plugin specific tables
        $sQL = 'DROP TABLE plugin_ftp_account';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_ftp_proftpd_user';
        $db->query($sQL);

        return parent::uninstall();
    }

    public function getFTPAccountDetails($userId)
    {
        // setup database
        $db = Database::getDatabase();

        // load the user details
        $user = $db->getRow("SELECT username, email, status FROM users WHERE id=" . $userId . " LIMIT 1");
        if (!$user)
        {
            return false;
        }

        // response values
        $rs = array();

        // check for ftp functions
        if ($this->ftpFunctionsExist() === false)
        {
            $rs['success'] = false;
            $rs['msg']     = t("plugin_ftp_problem_finding_ftp_functions", "There was a problem finding PHP FTP functions on the server. Please try again later.");

            return $rs;
        }

        // see if we have existing ftp details for the account
        $existingFTP = $db->getRow("SELECT * FROM plugin_ftp_account WHERE user_id=" . $userId);
        if ($existingFTP)
        {
            $rs['success']      = true;
            $rs['ftp_user']     = $existingFTP['ftp_user'];
            $rs['ftp_password'] = $existingFTP['ftp_password'];
            $rs['ftp_path']     = $existingFTP['ftp_path'];
        }
        else
        {
            // create ftp
            $ftpRs = $this->createCpanelFTPAccount($user['username']);
            if ($ftpRs['success'] == true)
            {
                // delete any existing by username, not really needed but might help keen data syncd
                $db->query("DELETE FROM plugin_ftp_account WHERE ftp_user=:ftp_user", array('ftp_user' => $ftpRs['ftp_user']));

                // add to db
                $dbInsert               = new DBObject("plugin_ftp_account", array("user_id", "ftp_user", "ftp_password",
                    "ftp_path", "date_created")
                );
                $dbInsert->user_id      = $userId;
                $dbInsert->ftp_user     = $ftpRs['ftp_user'];
                $dbInsert->ftp_password = $ftpRs['ftp_password'];
                $dbInsert->ftp_path     = $ftpRs['ftp_path'];
                $dbInsert->date_created = date("Y-m-d H:i:s", time());
                $dbInsert->insert();

                $rs['success']      = $ftpRs['success'];
                $rs['ftp_user']     = $ftpRs['ftp_user'];
                $rs['ftp_password'] = $ftpRs['ftp_password'];
                $rs['ftp_path']     = $ftpRs['ftp_path'];
            }
            else
            {
                $rs['success'] = false;
                $rs['msg']     = $ftpRs['msg'] ? $ftpRs['msg'] : t("plugin_ftp_problem_creating_ftp", "There was a problem creating the FTP account, please try again later.");
            }
        }

        return $rs;
    }

    public function createCpanelFTPAccount($ftpUser)
    {
        $ftpUser = $this->_createFTPUsername($ftpUser);

        // cpanel api functions
        include_once(PLUGIN_DIRECTORY_ROOT . 'ftpupload/xmlapi.php');

        // prepare data
        $rs                 = array();
        $rs['success']      = false;
        $rs['ftp_user']     = $ftpUser;
        $rs['ftp_password'] = $this->_createFTPPassword();
        $rs['ftp_path']     = $this->settings['home_dir_path'] . $rs['ftp_user'];

        // for cPanel based FTP accounts
        if ($this->settings['connection_type'] == 'cpanel')
        {
            try
            {
                // create actual FTP account
                $xmlapi = new xmlapi($this->settings['connection_cpanel_host']);
                $xmlapi->password_auth($this->settings['connection_cpanel_user'], $this->settings['connection_cpanel_password']);
                $xmlapi->set_port('2083');
                $xmlapi->set_output('json');

                // find a user which doesn't exist
                $xmlListRs = $xmlapi->api2_query($this->settings['connection_cpanel_user'], "Ftp", "listftp");
                if ($xmlListRs)
                {
                    $xmlListRs = json_decode($xmlListRs, true);
                    if ((isset($xmlListRs['cpanelresult']['error'])) && (strlen($xmlListRs['cpanelresult']['error']) > 0))
                    {
                        $rs['msg'] = $xmlListRs['cpanelresult']['error'];

                        return $rs;
                    }
                    else
                    {
                        foreach ($xmlListRs['cpanelresult']['data'] AS $ftpAccount)
                        {
                            if ($ftpAccount['user'] == $rs['ftp_user'])
                            {
                                $rs['ftp_user'] .= rand(100, 999);
                                $rs['ftp_path'] = $this->settings['home_dir_path'] . $rs['ftp_user'];
                            }
                        }
                    }
                }

                // create
                $xmlRS = $xmlapi->api2_query($this->settings['connection_cpanel_user'], "Ftp", "addftp", array('user'    => $rs['ftp_user'], 'pass'    => $rs['ftp_password'], 'quota'   => $this->settings['ftp_account_quota'], 'homedir' => $rs['ftp_path']));
                if ($xmlRS)
                {
                    $xmlRS = json_decode($xmlRS, true);
                    if ((isset($xmlRS['cpanelresult']['error'])) && (strlen($xmlRS['cpanelresult']['error']) > 0))
                    {
                        $rs['msg'] = $xmlRS['cpanelresult']['error'];
                    }
                    else
                    {
                        $rs['success'] = true;
                    }
                }
            }
            catch (Exception $e)
            {
                $rs['msg'] = 'Problem creating FTP account, possible issue connecting to cPanel host API. More info: ' . $e->getMessage();
            }
        }
        // for proFTPD
        elseif ($this->settings['connection_type'] == 'proftpd')
        {
            // get database
            $db = Database::getDatabase();
            
            // prepare password
            $hashedPassword= "{md5}".base64_encode(pack("H*", md5($rs['ftp_password'])));

            // get last uid used
            $lastUID = (int)$db->getValue('SELECT uid FROM plugin_ftp_proftpd_user ORDER BY uid DESC LIMIT 1');
            if($lastUID < 5500)
            {
                $lastUID = 5500;
            }

            // add to db
            $dbInsert               = new DBObject("plugin_ftp_proftpd_user", array("user_id", "passwd", "uid",
                "gid", "home_dir", "shell")
            );
            $dbInsert->user_id      = $rs['ftp_user'];
            $dbInsert->passwd     = $hashedPassword;
            $dbInsert->uid = $lastUID+1;
            $dbInsert->gid     = 5500;
            $dbInsert->home_dir = $this->settings['home_dir_path'].$rs['ftp_user'];
            $dbInsert->shell = '/sbin/nologin';
            $result = $dbInsert->insert();

            if (!$result)
            {
                $rs['msg'] = 'Problem creating FTP account, possible issue with ProFTPD.';
            }
            else
            {
                $rs['success']      = true;
            }
        }

        return $rs;
    }

    public function deleteFTPAccount($userId)
    {
        // setup database
        $db = Database::getDatabase();

        // get ftp details
        $existingFTP = $db->getRow("SELECT * FROM plugin_ftp_account WHERE user_id=" . $userId);
        if ($existingFTP)
        {
            // actual account
            $rs = $this->deleteCpanelFTPAccount($existingFTP['ftp_user']);

            // reference in our database
            $db->query("DELETE FROM plugin_ftp_account WHERE user_id=:user_id", array('user_id' => $userId));
        }

        return true;
    }

    public function deleteCpanelFTPAccount($ftpUser)
    {
        $ftpUser = strtolower($ftpUser);

        // cpanel api functions
        include_once(PLUGIN_DIRECTORY_ROOT . 'ftpupload/xmlapi.php');

        // prepare data
        $rs            = array();
        $rs['success'] = false;
        $rs['msg']     = '';

        if ($this->settings['connection_type'] == 'cpanel')
        {
            // create actual FTP account
            $xmlapi = new xmlapi($this->settings['connection_cpanel_host']);
            $xmlapi->password_auth($this->settings['connection_cpanel_user'], $this->settings['connection_cpanel_password']);
            $xmlapi->set_port('2083');
            $xmlapi->set_output('json');

            // make sure user exists
            // delete ftp user
            $xmlRS = $xmlapi->api2_query($this->settings['connection_cpanel_user'], "Ftp", "delftp", array('user'    => $ftpUser, 'destroy' => 1));
            if ($xmlRS)
            {
                $xmlRS = json_decode($xmlRS, true);
                if ((isset($xmlRS['cpanelresult']['error'])) && (strlen($xmlRS['cpanelresult']['error']) > 0))
                {
                    $rs['msg'] = $xmlRS['cpanelresult']['error'];
                }
                else
                {
                    $rs['success'] = true;
                }
            }
        }
        elseif ($this->settings['connection_type'] == 'proftpd')
        {
            // setup database
            $db = Database::getDatabase();

            // delete proftpd user from our database
            $db->query("DELETE FROM plugin_ftp_proftpd_user WHERE user_id=:ftp_user", array('ftp_user' => $rs['ftp_user']));
        }

        return $rs;
    }

    private function _createFTPUsername($username)
    {
        return validation::removeInvalidCharacters($username, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ12345678900');
    }

    private function _createFTPPassword()
    {
        $alpha   = "abcdefghijklmnopqrstuvwxyz";
        $alpha .= strtoupper($alpha);
        $alpha .= "0123456789";
        $special = "[]{}@%+~=*().?_";

        $pw  = '';
        $len = strlen($alpha);
        for ($i = 0; $i < 5; $i++)
        {
            $pw .= substr($alpha, rand(0, $len - 1), 1);
        }

        $len = strlen($special);
        for ($i = 0; $i < 2; $i++)
        {
            $pw .= substr($special, rand(0, $len - 1), 1);
        }

        $len = strlen($alpha);
        for ($i = 0; $i < 5; $i++)
        {
            $pw .= substr($alpha, rand(0, $len - 1), 1);
        }

        // the finished password
        $pw = str_shuffle($pw);

        return $pw;
    }

    public function handleFileTransfer($fileName, $userId, $rowId)
    {
        // some security bits
        $fileName = str_replace(array('../', './', '/'), '', $fileName);

        // setup db connection
        $db = Database::getDatabase(true);

        // get upload handler
        $uploadHandler = new uploader();

        // load user type
        $userTypeId = 0;
        if ((int) $userId > 0)
        {
            $userTypeId = (int) $db->getValue('SELECT level_id FROM users WHERE id = ' . $userId . ' LIMIT 1');
        }

        $uploadHandler->options['max_file_size'] = (int) UserPeer::getMaxUploadFilesize($userTypeId);

        // prepare response
        $fileUpload        = new stdClass();
        $fileUpload->name  = $fileName;
        $fileUpload->size  = 0;
        $fileUpload->type  = '';
        $fileUpload->error = null;
        $fileUpload->rowId = $rowId;

        // get remote ftp file, get ftp access details first
        // load plugin details
        $ftpAcc = $this->getFTPAccountDetails($userId);
        if ($ftpAcc['success'] == false)
        {
            $fileUpload->error = 'Could not load ftp details for user id ' . $userId;
            header('Content-type: application/json');
            echo json_encode($fileUpload);
            exit;
        }

        // check for pending files, connect via ftp
        $ftpHost = strlen($this->settings['ftp_host_override']) ? $this->settings['ftp_host_override'] : $this->settings['connection_cpanel_host'];
        $conn_id = ftp_connect($ftpHost, 21, 10);
        if ($conn_id === false)
        {
            $fileUpload->error = "FTP ERROR: Failed connecting to " . $ftpHost . " via FTP.";
            header('Content-type: application/json');
            echo json_encode($fileUpload);
            exit;
        }

        // authenticate
        $usernameAppend = '';
        if ((isset($this->settings['append_username'])) && (strlen($this->settings['append_username'])))
        {
            $usernameAppend = '@' . $this->settings['append_username'];
        }
        $login_result = ftp_login($conn_id, $ftpAcc['ftp_user'] . $usernameAppend, $ftpAcc['ftp_password']);
        if ($login_result === false)
        {
            // close this connection
            ftp_close($conn_id);
            $fileUpload->error = "FTP ERROR: Could not authenticate with FTP server " . $ftpHost;
            header('Content-type: application/json');
            echo json_encode($fileUpload);
            exit;
        }

        // get ftp file size - not all ftp servers support this
        $remoteFilesize = (int) ftp_size($conn_id, utf8_decode($fileName));
        if (((int) $remoteFilesize > 0) && ($remoteFilesize > $uploadHandler->options['max_file_size']))
        {
            // close this connection
            ftp_close($conn_id);
            $fileUpload->error = 'File is larger than permitted. (max ' . coreFunctions::formatSize($uploadHandler->options['max_file_size']) . ')';
        }
        else
        {
            // try to get the file locally
            $tmpDir = _CONFIG_FILE_STORAGE_PATH . '_tmp/';
            if (!file_exists($tmpDir))
            {
                @mkdir($tmpDir);
            }
            $tmpName   = MD5($url . microtime());
            $localFile = $tmpDir . $tmpName;
            if (!ftp_get($conn_id, $localFile, utf8_decode($fileName), FTP_BINARY))
            {
                $fileUpload->error = 'Could not find the file via FTP: \'' . validation::safeOutputToScreen($fileName) . '\'';
            }
            else
            {
                // reconnect db if it's gone away
                $db                = Database::getDatabase(true);
                $db->close();
                $db                = Database::getDatabase(true);
                $size              = (int) filesize($localFile);
                $fileUpload->error = $uploadHandler->hasError($localFile, $fileUpload, $error);
                if (intval($size) == 0)
                {
                    $fileUpload->error = 'File received has zero size.';
                }
                elseif (((int) $uploadHandler->options['max_file_size'] > 0) && (intval($size) > $uploadHandler->options['max_file_size']))
                {
                    $fileUpload->error = 'File received is larger than permitted. (max ' . coreFunctions::formatSize($uploadHandler->options['max_file_size']) . ')';
                }

                if (!$fileUpload->error && $fileUpload->name)
                {
                    // filesize
                    $fileUpload->size = filesize($localFile);

                    // get mime type
                    $mimeType = file::estimateMimeTypeFromExtension($fileUpload->name, 'application/octet-stream');
                    if (($mimeType == 'application/octet-stream') && (class_exists('finfo', false)))
                    {
                        $finfo    = new finfo;
                        $mimeType = $finfo->file($localFile, FILEINFO_MIME);
                    }
                    $fileUpload->type = $mimeType;

                    // save into permanent storage
                    $fileUpload = $uploadHandler->moveIntoStorage($fileUpload, $localFile);

                    // delete remote ftp file
                    ftp_delete($conn_id, utf8_decode($fileName));
                }
                else
                {
                    @unlink($localFile);
                }
            }
        }

        // close this connection
        ftp_close($conn_id);

        // no error, add success html
        if ($fileUpload->error === null)
        {
            $fileUpload->success_result_html = uploader::generateSuccessHtml($fileUpload);
        }
        else
        {
            $fileUpload->error_result_html = uploader::generateErrorHtml($fileUpload);
        }

        header('Content-type: application/json');
        echo json_encode($fileUpload);
    }

    public function ftpFunctionsExist()
    {
        return function_exists('ftp_connect');
    }

}
