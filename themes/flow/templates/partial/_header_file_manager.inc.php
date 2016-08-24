<?php
// local template functions
require_once(SITE_TEMPLATES_PATH . '/partial/_template_functions.inc.php');

// load theme functions
$themeObj = themeHelper::getLoadedInstance();
$themeSkin = $themeObj->getThemeSkin();

// top navigation
require_once(SITE_TEMPLATES_PATH . '/partial/_navigation_header.inc.php');

// per page options
$perPageOptions = array(15, 30, 50, 100, 250);
$defaultPerPage = 100;

// load all files
$sQL         = "SELECT COUNT(id) AS total, SUM(fileSize) AS totalFilesize, IF(statusId=1,'active','inactive') AS status FROM file WHERE userId = " . (int) $Auth->id . " GROUP BY IF(statusId=1,'active','inactive')";
$totalData   = $db->getRows($sQL);
$totalActiveFileSize = 0;
foreach($totalData AS $totalDataItem)
{
	if($totalDataItem['status'] == 'active')
	{
		$totalActive = (int)$totalDataItem['total'];
		$totalActiveFileSize = (int)$totalDataItem['totalFilesize'];
	}
	else
	{
		$totalTrash = (int)$totalDataItem['total'];
	}
}

// account stats
$totalFileStorage    = UserPeer::getMaxFileStorage($Auth->id);
$storagePercentage   = 0;
if (($totalActiveFileSize > 0) && ($totalFileStorage > 0))
{
    $storagePercentage = ($totalActiveFileSize / $totalFileStorage) * 100;
    if ($storagePercentage < 1)
    {
        $storagePercentage = 1;
    }
    else
    {
        $storagePercentage = floor($storagePercentage);
    }
}

// header top
require_once(SITE_TEMPLATES_PATH . '/partial/_header_file_manager_top.inc.php');
?>
<body class="page-body">
    <div class="page-container horizontal-menu with-sidebar fit-logo-with-sidebar">	
        <header class="navbar navbar-fixed-top"><!-- set fixed position by adding class "navbar-fixed-top" -->
            <div class="navbar-inner">
                <!-- logo -->
                <div class="navbar-brand">
                    <a href="<?php echo coreFunctions::getCoreSitePath(); ?>/index.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>">
                        <img src="<?php echo $themeObj->getInverseLogoUrl(); ?>" alt="<?php echo SITE_CONFIG_SITE_NAME; ?>" />
                    </a>
                </div>
                <!-- main menu -->
                <ul class="navbar-nav">
                    <?php
// add any other navigation items
                    $headerNavigation = pluginHelper::generateHeaderNavStructure($headerNavigation, $Auth->level_id);

// format nagivation for template
                    $navigationHtmlItems = array();
                    foreach ($headerNavigation AS $headerNavigationItem)
                    {
                        $navHtml = '<li class="opened';
                        if (isset($headerNavigationItem['link_key']) && $headerNavigationItem['link_key'] == SELECTED_NAVIGATION_LINK)
                        {
                            $navHtml .= ' active';
                        }
                        $navHtml .= '"><a href="' . $headerNavigationItem['link_url'] . '"';
                        $navHtml .= '><i class="entypo-' . (strlen($headerNavigationItem['link_key']) ? $headerNavigationItem['link_key'] : 'default') . '"></i><span>' . validation::safeOutputToScreen(UCWords($headerNavigationItem['link_text'])) . '</span></a></li>';
                        $navigationHtmlItems[] = $navHtml;
                    }

// output nav
                    echo implode('', $navigationHtmlItems);
                    ?>
					
					<?php
					// reload user level from database encase they've just upgraded
					$user = UserPeer::loadUserById((int)$Auth->id);
					$packageId = $user->level_id;
					if($packageId == 20):
						$label = 'ADMIN AREA';
					?>
						<!-- mobile only admin link -->
						<li class="visible-xs">
							<a href="<?php echo ADMIN_WEB_ROOT.'/'; ?>" target="_blank">
								<i class="entypo-user" title="<?php echo UCWords(strtolower(t(str_replace(' ', '_', strtolower($label)), $label))); ?>"></i> <strong><span><?php echo UCWords(strtolower(t(str_replace(' ', '_', strtolower($label)), $label))); ?></span></strong>
							</a>
						</li>
					<?php endif; ?>
					
					<!-- mobile only logout -->
                    <li class="visible-xs">
                        <a href="<?php echo coreFunctions::getCoreSitePath() . '/logout.' . SITE_CONFIG_PAGE_EXTENSION; ?>">
                            <i class="entypo-logout" title="<?php echo t('logout', 'logout'); ?>"></i> <span><?php echo UCWords(t('logout', 'logout')); ?></span>
                        </a>
                    </li>
                </ul>
                <!-- notifications -->
                <?php
                // load all in the past 14 days for current user
                $internalNotifications = internalNotification::loadRecentByUser($Auth->id);
                $unreadCount           = 0;
                foreach ($internalNotifications AS $internalNotification)
                {
                    if ($internalNotification['is_read'] == 0)
                    {
                        $unreadCount++;
                    }
                }
                ?>
                <ul class="nav navbar-right pull-right internal-notification">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                            <i class="entypo-globe"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge badge-warning"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <!-- dropdown menu -->
                        <ul class="dropdown-menu">
                            <li class="top">
                                <p class="small">
                                    <?php if ($unreadCount > 0): ?>
                                        <a href="#" class="pull-right mark-read-link" onClick="markInternalNotificationsRead();
                    return false;"><?php echo t('file_manager_mark_all_read', 'Mark all Read'); ?></a>
                                       <?php endif; ?>
                                    <span class="unread-count"><?php echo $unreadCount != 1 ?t('file_manager_you_have_x_new_notifications', 'You have <strong>[[[UNREAD]]]</strong> new notifications.', array('UNREAD' => $unreadCount)):t('file_manager_you_have_x_new_notification', 'You have <strong>[[[UNREAD]]]</strong> new notification.', array('UNREAD' => $unreadCount)); ?></span>
                                </p>
                            </li>
                            <li>
                                <ul class="dropdown-menu-list scroller">
                                    <?php foreach ($internalNotifications AS $internalNotification): ?>
                                        <li class="<?php echo $internalNotification['is_read'] == 0 ? 'unread' : 'read'; ?> notification-<?php echo $internalNotification['is_read'] == 0 ? 'info' : 'default'; ?>">
                                            <a href="<?php echo strlen($internalNotification['href_url']) ? $internalNotification['href_url'] : '#'; ?>" <?php echo strlen($internalNotification['onclick']) ? (' onClick="' . addslashes($internalNotification['onclick']) . '"') : ''; ?>>
                                                <i class="<?php echo validation::safeOutputToScreen($internalNotification['notification_icon']); ?> pull-right"></i>
                                                <span class="line <?php echo $internalNotification['is_read'] == 0 ? 'text-bold' : ''; ?>">
                                                    <?php echo $internalNotification['content']; // allow for html   ?>
                                                </span>
                                                <span class="line small">
                                                    <?php echo coreFunctions::timeToRelativeString($internalNotification['date_added']); ?>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        </ul>
                    </li>

                    <li class="sep"></li>
                    <li class="root-level">
                        <?php
                        $className = 'info';
                        $label = 'FREE USER';
                        $url = coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION;
                        switch($packageId)
                        {
                            case 10:
                                $className = 'secondary';
                                $label = 'MODERATOR';
                                $url = ADMIN_WEB_ROOT.'/';
                                break;
                            case 20:
                                $className = 'danger';
                                $label = 'ADMIN AREA';
                                $url = ADMIN_WEB_ROOT.'/';
                                break;
							default:
                                $className = 'success';
                                $label = strtoupper(UserPeer::getLevelLabel($packageId));
                                $url = coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION;
                                break;
                        }
                        ?>
                        <a href="<?php echo $url; ?>">
                            <span class="badge badge-<?php echo $className; ?>"><?php echo strtoupper(t(str_replace(' ', '_', strtolower($label)), $label)); ?></span>
                        </a>
                    </li>
                    <li class="sep"></li>
                        
                    <li>
                        <a href="<?php echo coreFunctions::getCoreSitePath() . '/logout.' . SITE_CONFIG_PAGE_EXTENSION; ?>">
                            <i class="entypo-logout" title="<?php echo t('logout', 'logout'); ?>"></i><span><?php echo UCWords(t('logout', 'logout')); ?></span>
                        </a>
                    </li>
                    <!-- mobile only -->
                    <li class="visible-xs">	
                        <!-- open/close menu icon (do not remove if you want to enable menu on mobile devices) -->
                        <div class="horizontal-mobile-menu visible-xs">
                            <a href="#" class="with-animation"><!-- add class "with-animation" to support animation -->
                                <i class="entypo-menu"></i>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </header>
        <div class="sidebar-menu">
            <div class="sidebar-user-info">
                <div class="sui-normal">
                    <a class="user-link" href="#">
                        <img class="img-square" alt="<?php echo validation::safeOutputToScreen($Auth->getAccountScreenName()); ?>" src="<?php echo CORE_PAGE_WEB_ROOT; ?>/account_view_avatar.php?width=44&height=44">
                        <span><?php echo t('file_manager_welcome', 'Welcome'); ?>,</span>
                        <strong><?php echo validation::safeOutputToScreen($Auth->getAccountScreenName()); ?></strong>
                    </a>
                </div>
                <div class="sui-hover inline-links animate-in">				
                    <a href="<?php echo coreFunctions::getCoreSitePath() . '/account_edit.' . SITE_CONFIG_PAGE_EXTENSION; ?>">
                        <i class="entypo-cog"></i>
                        <?php echo t('file_manager_account_settings', 'Account Settings'); ?>
                    </a>
                    <a href="<?php echo coreFunctions::getCoreSitePath() . '/logout.' . SITE_CONFIG_PAGE_EXTENSION; ?>">
                        <i class="entypo-logout"></i>
                        <?php echo t('file_manager_logout', 'Logout'); ?>
                    </a>
                    <span class="close-sui-popup">×</span>
                </div>
            </div>
            <div id="folderTreeview"></div>
            <?php if ($totalFileStorage > 0): ?>
                <div class="remaining-storage responsiveMobileHide">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tile-progress" onClick="window.location = '<?php echo WEB_ROOT; ?>/account_edit.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>';">
                                <div class="tile-footer">
                                    <div class="progress">
                                        <div style="width: <?php echo $storagePercentage; ?>%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $storagePercentage; ?>" role="progressbar" class="progress-bar progress-bar-success"></div>
                                    </div>
                                    <span><span id="totalActiveFileSize"><?php echo validation::safeOutputToScreen(coreFunctions::formatSize($totalActiveFileSize)); ?></span> / <?php echo validation::safeOutputToScreen(coreFunctions::formatSize($totalFileStorage)); ?> <?php echo t("used_storage", "Used Storage"); ?> (<?php echo $storagePercentage; ?>%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php pluginHelper::includeAppends('file_manager_left_nav_base.php', array('Auth' => $Auth)); ?>
        </div>