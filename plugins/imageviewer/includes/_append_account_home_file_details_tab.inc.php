<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

// setup valid image extensions
$ext = array('jpg', 'jpeg', 'png', 'gif');

// check this is an image
if (in_array(strtolower($file->extension), $ext))
{
    ?>
    <?php if ($file->statusId == 1): ?>
    <li>
        <a href="#imageviewer-preview" data-toggle="tab"><i class="entypo-picture"></i> <?php echo UCWords(t('view_image', 'view image')); ?></a>
    </li>
    <?php endif; ?>
    
    <li>
        <a href="#imageviewer-extra-info" data-toggle="tab"><i class="entypo-camera"></i> <?php echo UCWords(t('extra_info', 'extra info')); ?></a>
    </li>
    <?php
}
?>