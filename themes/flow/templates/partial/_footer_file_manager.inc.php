</div>
<!-- Bottom Scripts -->
<script src="<?php echo SITE_JS_PATH; ?>/gsap/main-gsap.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/bootstrap.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/joinable.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/resizeable.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/flow-api.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/toastr.js" type="text/javascript"></script>
<script src="<?php echo SITE_JS_PATH; ?>/custom.js" type="text/javascript"></script>

<?php include_once(SITE_TEMPLATES_PATH . '/partial/_clipboard_structure.inc.php'); ?>
<?php echo (defined('SITE_CONFIG_GOOGLE_ANALYTICS_CODE') && strlen(SITE_CONFIG_GOOGLE_ANALYTICS_CODE))?SITE_CONFIG_GOOGLE_ANALYTICS_CODE:''; ?>

</body>
</html>

<?php
// START PERFORMANCE TESTING
/*
$db = Database::getDatabase();
define('PERF_END_TIME', microtime(true));
$execution_time = (PERF_END_TIME - PERF_START_TIME);
echo '<div style="position: absolute; top: 100px; z-index: 999999; right: 0; width: 80%; background-color: #fff; font-size: 12px;"><table class="table table-striped" style="color: red; font-family: \'Courier New\', Courier, monospace;">';
echo '<tr><td><b>Total Execution Time:</b></td><td>'.number_format($execution_time, 5).' seconds</td></tr>';
echo '<tr><td><b>Total DB Queries:</b></td><td>'.$db->numQueries().' <a href="#" onClick="$(\'#perf_sql_queries\').toggle();">(show)</a>';
echo '<div id="perf_sql_queries" style="display: none;"><br/><table class="table table-striped" style="width: 90%">';
foreach(Database::$queries AS $query)
{
	echo '<tr><td style="width: 80px;">'.$query['total'].'</td><td>'.$query['sql'].'</td></tr>';
}
echo '</table><br/></div></td></tr>';

echo '<tr><td><b>Memory Usage:</b></td><td>'.coreFunctions::formatSize(memory_get_usage()).'</td></tr>';
echo '<tr><td style="width: 220px;"><b>Page Url:</b></td><td>'._INT_PAGE_URL.'</td></tr>';
echo '<tr><td><b>Test Date/Time:</b></td><td>'.date('Y-m-d H:i:s').'</td></tr>';
echo '</table></div>';
*/
// END PERFORMANCE TESTING