<?php

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

?>
<!doctype html>  
<html lang="en-us">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge;chrome=1" >
        <meta charset="utf-8">
        <link rel="stylesheet" href="../assets/css/styles.css" type="text/css" media="screen" />
    </head>
    <body>

    <?php

    // make sure we have an id
    $newsletterId = (int)$_REQUEST['id'];
    if(!$newsletterId)
    {
        die('Error: Newsletter id not found.');
    }

    // load newsletter
    $newsletter = $db->getRow('SELECT * FROM plugin_newsletter WHERE id='.$newsletterId.' LIMIT 1');
    if(!$newsletter)
    {
        die('Error: Newsletter not found.');
    }

    echo $newsletter['html_content'];

    ?>

    </body>
</html>