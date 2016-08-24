<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

//setup database
$db = Database::getDatabase(true);

// handle submission
if ((int) $_REQUEST['submitme'])
{
    // validation
    $fileId            = (int) $_REQUEST['fileId'];
    $shareRecipientName = substr(trim($_REQUEST['shareRecipientName']), 0, 255);
    $shareEmailAddress = substr(strtolower(trim($_REQUEST['shareEmailAddress'])), 0, 255);
    $shareExtraMessage = trim($_REQUEST['shareExtraMessage']);
    if (strlen($shareRecipientName) == 0)
    {
        notification::setError(t("please_enter_the_recipient_name", "Please enter the recipient name."));
    }
    elseif (strlen($shareEmailAddress) == 0)
    {
        notification::setError(t("please_enter_the_recipient_email_address", "Please enter the recipient email address."));
    }
    elseif (validation::validEmail($shareEmailAddress) == false)
    {
        notification::setError(t("please_enter_a_valid_recipient_email_address", "Please enter a valid recipient email address."));
    }
	elseif(coreFunctions::limitEmailsSentPerHour('1') == false)
	{
		notification::setError(t("send_via_email_limit_reached", "You have reached the maximum emails that you can send per hour."));
	}
    else
    {
        // make sure this user owns the file
        $file = file::loadById($fileId);
        if (!$file)
        {
            notification::setError(t("could_not_load_file", "There was a problem loading the file."));
        }
        elseif ($file->userId != $Auth->id)
        {
            notification::setError(t("could_not_load_file", "There was a problem loading the file."));
        }
    }

    // send the email
    if (!notification::isErrors())
    {
        // prepare variables
        $shareRecipientName = strip_tags($shareRecipientName);
        $shareEmailAddress = strip_tags($shareEmailAddress);
        $shareExtraMessage = strip_tags($shareExtraMessage);
        $shareExtraMessage = substr($shareExtraMessage, 0, 2000);
        
        // send the email
        $subject = t('account_file_details_share_via_email_subject', 'File shared by [[[SHARED_BY_NAME]]] on [[[SITE_NAME]]]', array('SITE_NAME' => SITE_CONFIG_SITE_NAME, 'SHARED_BY_NAME' => $Auth->getAccountScreenName()));

        $replacements = array(
            'SITE_NAME' => SITE_CONFIG_SITE_NAME,
            'WEB_ROOT' => WEB_ROOT,
            'RECIPIENT_NAME' => $shareRecipientName,
            'SHARED_BY_NAME' => $Auth->getAccountScreenName(),
            'SHARED_EMAIL_ADDRESS' => $Auth->email,
            'EXTRA_MESSAGE' => strlen($shareExtraMessage)?nl2br($shareExtraMessage):t('not_applicable_short', 'n/a'),
            'FILE_NAME' => $file->originalFilename,
            'FILE_URL' => $file->getFullShortUrl()
        );
        $defaultContent = "Dear [[[RECIPIENT_NAME]]],<br/><br/>";
        $defaultContent .= "[[[SHARED_BY_NAME]]] has shared the following file with you via <a href='[[[WEB_ROOT]]]'>[[[SITE_NAME]]]</a>:<br/><br/>";
        $defaultContent .= "<strong>File:</strong> [[[FILE_NAME]]]<br/>";
        $defaultContent .= "<strong>Download:</strong> [[[FILE_URL]]]<br/>";
        $defaultContent .= "<strong>From:</strong> [[[SHARED_BY_NAME]]] ([[[SHARED_EMAIL_ADDRESS]]])<br/>";
        $defaultContent .= "<strong>Message:</strong><br/>[[[EXTRA_MESSAGE]]]<br/><br/>";
        $defaultContent .= "Feel free to contact us if you have any difficulties downloading the file.<br/><br/>";
        $defaultContent .= "Regards,<br/>";
        $defaultContent .= "[[[SITE_NAME]]] Admin";
        $htmlMsg = t('account_file_details_share_via_email_content', $defaultContent, $replacements);

        coreFunctions::sendHtmlEmail($shareEmailAddress, $subject, $htmlMsg, SITE_CONFIG_DEFAULT_EMAIL_ADDRESS_FROM, strip_tags(str_replace("<br/>", "\n", $htmlMsg)));
        notification::setSuccess(t("file_sent_via_email_to_x", "File sent via email to [[[RECIPIENT_EMAIL_ADDRESS]]]", array('RECIPIENT_EMAIL_ADDRESS' => $shareEmailAddress)));
    }
}

// prepare result
$returnJson            = array();
$returnJson['success'] = false;
$returnJson['msg']     = t("problem_updating_item", "There was a problem sending the email, please try again later.");
if (notification::isErrors())
{
    // error
    $returnJson['success'] = false;
    $returnJson['msg']     = implode('<br/>', notification::getErrors());
}
else
{
    // success
    $returnJson['success'] = true;
    $returnJson['msg']     = implode('<br/>', notification::getSuccess());
}

echo json_encode($returnJson);