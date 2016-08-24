<?php

// handle errors
if ((isset($_REQUEST['plugin_social_login_error'])) && (strlen($_REQUEST['plugin_social_login_error'])))
{
    notification::setError(validation::safeOutputToScreen($_REQUEST['plugin_social_login_error']));
}

// only if user is not logged in
$Auth = Auth::getAuth();
$config    = PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/config.php';
require_once(PLUGIN_DIRECTORY_ROOT . 'sociallogin/includes/hybridauth/Hybrid/Auth.php');
if ($Auth->loggedIn() == false)
{
    // try to get already authenticated provider list
    $user_data = false;
    try
    {
        $hybridauth              = new Hybrid_Auth($config);
        $connected_adapters_list = $hybridauth->getConnectedProviders();
        $adapter                 = false;
        if (COUNT($connected_adapters_list))
        {
            foreach ($connected_adapters_list as $provider)
            {
                // if already authenticated
                if ($adapter != false)
                {
                    continue;
                }

                // load logged in adapter
                $adapter = $hybridauth->getAdapter($provider);

                // grab the user profile
                $user_data = $adapter->getUserProfile();
            }
        }
    }
    catch (Exception $e)
    {
        //$pluginSocialLoginError = "Ooophs, we got an error: " . $e->getMessage();
        //$pluginSocialLoginError .= " Error code: " . $e->getCode();
        //$pluginSocialLoginError .= "<br /><br />Please try again.";
        //$pluginSocialLoginError .= "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>";
        $user_data              = false;
    }

    // setup changes to user session and login user
    if ($user_data != false)
    {
        // generate username based on provider and user id
        $username = $adapter->id.'|'.$user_data->identifier;
        
        // attempt to load user from db
        $db     = Database::getDatabase();
        $config = Config::getConfig();

        // check if user exists
        $user = $db->getRow('SELECT * FROM users WHERE username = ' . $db->quote($username));
        if(!$user)
        {
            // if user not found, create new
            $newPassword = passwordPolicy::generatePassword();
            $emailAddress = $user_data->email;
            $title = '';
            $firstname = $user_data->firstName;
            $lastname = $user_data->lastName;
            $newUser     = UserPeer::create($username, $newPassword, $emailAddress, $title, $firstname, $lastname);
            
            // reload user details
            $user = $db->getRow('SELECT * FROM users WHERE username = ' . $db->quote($username));
        }

        // setup session
        if($user)
        {
            $hashedPw = $user['password'];
            $Auth->impersonate($username);
            if($Auth->loggedIn() == true)
            {
                $_SESSION['socialLogin'] = true;
                $_SESSION['socialProvider'] = $provider;
                $_SESSION['socialData'] = serialize($user_data);
            }
        }
    }
}
?>
