<?php
// load plugin details
$pluginConfig = pluginHelper::pluginSpecificConfiguration('coinbase');

// create link to payment gateway
?>
<div style="text-align: center; padding: 3px;">
    <form id="form<?php echo $days; ?>" action="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/site/_pay.php" method="post">
        <input type="hidden" name="days" value="<?php echo $days; ?>" />
        <?php
        if (isset($_REQUEST['i']))
        {
            echo '<input type="hidden" name="i" value="' . htmlentities($_REQUEST['i']) . '" />';
        }
        if (isset($_REQUEST['f']))
        {
            echo '<input type="hidden" name="f" value="' . htmlentities($_REQUEST['f']) . '" />';
        }
        ?>
        <input type="image" src="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/assets/img/payment_button.png" title="Pay with Coinbase" alt="Pay with Coinbase" width="150" height="29" />
    </form>
</div>