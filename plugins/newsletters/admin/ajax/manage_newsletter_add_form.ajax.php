<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load values
$allUsersTotal = (int)$db->getValue('SELECT COUNT(id) AS total FROM users WHERE status=\'active\' AND id NOT IN (SELECT user_id FROM plugin_newsletter_unsubscribe)');
$accountTypeDetails = $db->getRows('SELECT id, level_id, label FROM user_level WHERE id > 0 ORDER BY level_id ASC');

// prepare variables
$title = '';
$user_group = '';
$subject = '';

// is this an edit?
$editNewsletterId = (int)$_REQUEST['gEditNewsletterId'];
if($editNewsletterId > 0)
{
    $sQL           = "SELECT * FROM plugin_newsletter WHERE id=".$editNewsletterId;
    $newsletterDetails = $db->getRow($sQL);
    if($newsletterDetails)
    {
        $title = $newsletterDetails['title'];
        $user_group = $newsletterDetails['user_group'];
        $subject = $newsletterDetails['subject'];
        $html_content = $newsletterDetails['html_content'];
		
		// backwards compatible with older levels
		switch($user_group)
		{
			case 'free only':
				$user_group = 1;
				break;
			case 'premium only':
				$user_group = 2;
				break;
			case 'moderator only':
				$user_group = 10;
				break;
			case 'moderator only':
				$user_group = 20;
				break;
			case (int)$user_group:
				break;
			default:
				$user_group = '';
		}
    }
}

// prepare result
$result = array();
$result['error'] = false;
$result['msg'] = '';
$result['html'] = 'Could not load the form, please try again later.';

$result['html'] = '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addNewsletterForm" class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>'.UCWords(adminFunctions::t("newsletter_title", "title")).':</label>
                        <div class="input">
                            <input name="title" id="title" type="text" value="'.adminFunctions::makeSafe($title).'" class="xxlarge"/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label>'.UCWords(adminFunctions::t("newsletter_user_group", "send to")).':</label>
                        <div class="input">
                            <select name="user_group" id="user_group" class="xxlarge">
                                <option value=""'.($user_group==''?' SELECTED':'').'>All Registered Accounts ('.$allUsersTotal.')</option>';
								foreach($accountTypeDetails AS $accountTypeDetail)
								{
									$usersTotal = (int)$db->getValue('SELECT COUNT(id) AS total FROM users WHERE status=\'active\' AND level_id = '.(int)$accountTypeDetail['id'].' AND id NOT IN (SELECT user_id FROM plugin_newsletter_unsubscribe)');
									$result['html'] .= '<option value="'.$accountTypeDetail['id'].'"'.($user_group==$accountTypeDetail['id']?' SELECTED':'').'>'.adminFunctions::makeSafe(UCWords($accountTypeDetail['label'])).' Accounts Only ('.$usersTotal.')</option>';
								}
$result['html'] .= '        </select>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>'.UCWords(adminFunctions::t("newsletter_subject", "subject")).':</label>
                        <div class="input">
                            <input name="subject" id="subject" type="text" value="'.adminFunctions::makeSafe($subject).'" class="xxlarge"/>
                        </div>
                    </div>';

$result['html'] .= '<div class="clearfix">
                        <label style="width: 13.5em;">'.UCWords(adminFunctions::t("newsletter_html_content", "newsletter content")).':</label><br/><br/>
                        <div class="input">
                            <textarea name="html_content" id="html_content" class="xxlarge">'.adminFunctions::makeSafe($html_content).'</textarea>
                                <br/>
                                <div style="width: 500px; color: #777; font-size: 11px;">Replacements: 
                                    <a href="#" onClick="insertReplacement(\'[[[title]]]\'); return false;">title</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[firstname]]]\'); return false;">firstname</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[lastname]]]\'); return false;">lastname</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[username]]]\'); return false;">username</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[level]]]\'); return false;">level</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[current_date]]]\'); return false;">current_date</a>, 
                                    <a href="#" onClick="insertReplacement(\'[[[current_time]]]\'); return false;">current_time</a>
                                </div>
                        </div>
                    </div>';

$result['html'] .= '</form>';

echo json_encode($result);
exit;
