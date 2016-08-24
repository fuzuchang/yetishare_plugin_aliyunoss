<?php
// checked whether user is logged in
if($Auth->loggedIn())
{
    // load recent from account
    $files = file::loadAllRecentByAccount($Auth->id, true);
}
else
{
    // load recent from IP
    $files = file::loadAllRecentByIp(coreFunctions::getUsersIPAddress(), true);
}
?>

<div class="rightContentWrapper ui-corner-all">
    <div class="rightContent">
        <div id="pageHeader">
            <h2><?php echo t("your_recent_files", "Your Files"); ?> <?php echo COUNT($files)?'('.COUNT($files).')':''; ?></h2>
        </div>
        <p>
            <?php
            // load all urls for current user  
            if(COUNT($files))
            {
                $tracker = 0;
                foreach($files AS $url)
                {
                    $class = 'divOdd';
                    if($tracker%2==1)
                    {
                        $class = 'divEven';
                    }
                    echo "<div class='".$class."'>";
                    echo "<div style='padding: 3px;'>";
                    echo "  <div style='float: right; text-decoration: underline;'>";
                    echo "      <a href='".WEB_ROOT."/".$url['shortUrl']."~i?".$url['deleteHash']."'>".t("info", "info")."</a>";
                    echo "  </div>";
                    echo "  <div style='float: left; overflow: hidden; width: 170px;'>";
                    echo "      <a href='".file::getFileUrl($url['id'])."'>".validation::safeOutputToScreen($url['originalFilename'])."</a>";
                    echo "  </div>";
                    echo "  <div class='clear'></div>";
                    echo "</div>";
                    echo "<div class='clear'></div>";
                    echo "</div>";
                    echo "<div class='clear'></div>";
                    $tracker++;
                }
            }
            else
            {
                $replacements = array();
                $replacements['WEB_ROOT'] = WEB_ROOT;
                $replacements['PAGE_EXTENSION'] = SITE_CONFIG_PAGE_EXTENSION;
                echo t('you_have_not_uploaded_any_file_recently', '- You have not uploaded any files recently. <a href="[[[WEB_ROOT]]]/index.[[[PAGE_EXTENSION]]]">Click here</a> to upload some now.', $replacements);
                echo '<br/><br/>';
                echo t('if_you_have_an_account_signin', 'If you have an account, <a href="[[[WEB_ROOT]]]/login.[[[PAGE_EXTENSION]]]">click here</a> to login and manage your files.', $replacements);
            }
            ?>
        </p>
    </div>
</div>
