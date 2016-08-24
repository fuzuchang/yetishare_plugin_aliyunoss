<?php

// allow for a few hours
set_time_limit(60 * 60 * 2);

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$title                = trim($_REQUEST['title']);
$user_group           = trim($_REQUEST['user_group']);
$subject              = trim($_REQUEST['subject']);
$html_content         = trim($_REQUEST['html_content']);
$existingNewsletterId = (int) $_REQUEST['gEditNewsletterId'];
$send                 = (int) $_REQUEST['send'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (strlen($title) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_newsletter_enter_title", "Please enter the newsletter title.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
elseif (strlen($subject) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_newsletter_enter_subject", "Please enter the newsletter subject.");
}
elseif (strlen($html_content) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_newsletter_enter_content", "Please enter the newsletter content.");
}

if (strlen($result['msg']) == 0)
{
    if ($existingNewsletterId > 0)
    {
        // update the existing record
        $dbUpdate = new DBObject("plugin_newsletter", array("title", "subject", "html_content", "user_group"), 'id');
        $dbUpdate->title = $title;
        $dbUpdate->subject = $subject;
        $dbUpdate->html_content = $html_content;
        $dbUpdate->user_group = $user_group;
        $dbUpdate->id = $existingNewsletterId;
        $dbUpdate->update();

        $result['error'] = false;
        $result['msg']   = 'Newsletter updated.';
    }
    else
    {
        // add the newsletter
        $dbInsert = new DBObject("plugin_newsletter", array("title", "subject", "html_content", "user_group", "status", "date_created"));
        $dbInsert->title = $title;
        $dbInsert->subject = $subject;
        $dbInsert->html_content = $html_content;
        $dbInsert->user_group = $user_group;
        $dbInsert->status = 'draft';
        $dbInsert->date_created = date('Y-m-d H:i:s');
        if (!$dbInsert->insert())
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("plugin_newsletter_error_problem_record", "There was a problem adding the newsletter, please try again.");
        }
        else
        {
            $result['error']      = false;
            $result['msg']        = 'Newsletter added and saved as draft.';
            $existingNewsletterId = $dbInsert->id;
        }
    }
}

// should we attempt to send a test?
if (($result['error'] == false) && ($send == 2))
{
    // get instance
    $newslettersObj      = pluginHelper::getInstance('newsletters');
    $newslettersSettings = $newslettersObj->settings;

    $emailAddress = $newslettersSettings['test_email_address'];
    
    // prepare unsubscribe link
    $unsubscribeLink = PLUGIN_WEB_ROOT . '/newsletters/site/unsubscribe.php';

    // create email content
    $replacedHtmlContent = $html_content;
    $replacedSubject     = $subject;

    // add on unsubscribe text
    $replacedHtmlContent .= '<br/><br/><font style="font-size: 10px; color: #666;">' . $newslettersSettings['unsubscribe_text'] . '</font>';

    // other replacements
    $replacedHtmlContent = str_replace('[[[current_date]]]', date(SITE_CONFIG_DATE_FORMAT), $replacedHtmlContent);
    $replacedHtmlContent = str_replace('[[[current_time]]]', date('H:i'), $replacedHtmlContent);
    $replacedHtmlContent = str_replace('[[[unsubscribe_link]]]', $unsubscribeLink, $replacedHtmlContent);
    $replacedSubject     = str_replace('[[[current_date]]]', date(SITE_CONFIG_DATE_FORMAT), $replacedSubject);
    $replacedSubject     = str_replace('[[[current_time]]]', date('H:i'), $replacedSubject);
    $replacedSubject = str_replace('[[[unsubscribe_link]]]', $unsubscribeLink, $replacedSubject);

    // send
    $rs = $newslettersObj->sendNewsletter($replacedSubject, $replacedHtmlContent, $emailAddress, $newslettersSettings['send_email_from_email']);

    // update confirmation message
    $result['msg'] = 'Newsletter test sent to '.$emailAddress.'.';
}

// should we attempt to send the newsletter?
if (($result['error'] == false) && ($send == 1))
{
    // get instance
    $newslettersObj      = pluginHelper::getInstance('newsletters');
    $newslettersSettings = $newslettersObj->settings;

    // get all emails for newsletter
    $emailRecipients = $newslettersObj->getRecipients($user_group);

    // update status to sending
    $dbUpdate = new DBObject("plugin_newsletter", array("status"), 'id');
    $dbUpdate->status = 'sending';
    $dbUpdate->id = $existingNewsletterId;
    $dbUpdate->update();

    // loop recipients and send
    if (COUNT($emailRecipients))
    {
        foreach ($emailRecipients AS $emailRecipient)
        {
            // prepare unsubscribe link
            $unsubscribeLink = PLUGIN_WEB_ROOT . '/newsletters/site/unsubscribe.php?e=' . urlencode($emailRecipient['email']);

            // create email content
            $replacedHtmlContent = $html_content;
            $replacedSubject     = $subject;
            foreach ($emailRecipient AS $columName => $columValue)
            {
                $replacedHtmlContent = str_replace('[[[' . $columName . ']]]', $columValue, $replacedHtmlContent);
                $replacedSubject     = str_replace('[[[' . $columName . ']]]', $columValue, $replacedSubject);
            }

            // add on unsubscribe text
            $replacedHtmlContent .= '<br/><br/><font style="font-size: 10px; color: #666;">' . $newslettersSettings['unsubscribe_text'] . '</font>';

            // other replacements
            $replacedHtmlContent = str_replace('[[[current_date]]]', date(SITE_CONFIG_DATE_FORMAT), $replacedHtmlContent);
            $replacedHtmlContent = str_replace('[[[current_time]]]', date('H:i'), $replacedHtmlContent);
            $replacedHtmlContent = str_replace('[[[unsubscribe_link]]]', $unsubscribeLink, $replacedHtmlContent);
            $replacedSubject     = str_replace('[[[current_date]]]', date(SITE_CONFIG_DATE_FORMAT), $replacedSubject);
            $replacedSubject     = str_replace('[[[current_time]]]', date('H:i'), $replacedSubject);
            $replacedSubject = str_replace('[[[unsubscribe_link]]]', $unsubscribeLink, $replacedSubject);

            // send
            $rs = $newslettersObj->sendNewsletter($replacedSubject, $replacedHtmlContent, $emailRecipient['email'], $newslettersSettings['send_email_from_email']);

            // add to audit
            $dbInsert = new DBObject("plugin_newsletter_sent", array("to_email_address", "to_user_id", "subject", "html_content", "date_created", "date_sent", "newsletter_id", "status"));
            $dbInsert->to_email_address = $emailRecipient['email'];
            $dbInsert->to_user_id = $emailRecipient['id'];
            $dbInsert->subject = $replacedSubject;
            $dbInsert->html_content = $replacedHtmlContent;
            $dbInsert->date_created = date('Y-m-d H:i:s');
            $dbInsert->newsletter_id = $existingNewsletterId;
            if ($rs == true)
            {
                $dbInsert->date_sent = date('Y-m-d H:i:s');
                $dbInsert->status = 'sent';
            }
            else
            {
                $dbInsert->status = 'failed';
            }
            $dbInsert->insert();
        }
    }

    // update status to sent
    $dbUpdate = new DBObject("plugin_newsletter", array("status", "date_sent"), 'id');
    $dbUpdate->status = 'sent';
    $dbUpdate->date_sent = date('Y-m-d H:i:s');
    $dbUpdate->id = $existingNewsletterId;
    $dbUpdate->update();

    // update confirmation message
    $result['msg'] = 'Newsletter(s) sent.';
}

echo json_encode($result);
exit;
