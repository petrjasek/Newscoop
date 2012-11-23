<?php

require_once dirname(dirname(__FILE__)) . '/db_connect.php';
require_once dirname(dirname(__FILE__)) . '/classes/Input.php';
require_once dirname(dirname(__FILE__)) . '/classes/Extension/WidgetContext.php';
require_once dirname(dirname(__FILE__)) . '/classes/Extension/WidgetManager.php';

camp_load_translation_strings('home');
camp_load_translation_strings('articles');
camp_load_translation_strings('api');
camp_load_translation_strings('extensions');
camp_load_translation_strings('globals');

// install default widgets for admin
WidgetManager::SetDefaultWidgetsAll();

// add title
echo camp_html_breadcrumbs(array(
    array(getGS('Dashboard'), ''),
));

if (!SystemPref::get('stat_ask_time')) SystemPref::set('stat_ask_time', 0);

if (!SystemPref::get('installation_id')) {
    $installationId = sha1($_SERVER['SERVER_ADDR'].$_SERVER['SERVER_NAME'].mt_rand());
    SystemPref::set('installation_id', $installationId);
}

$request_only = false;
if (!SystemPref::get('support_send') && SystemPref::get('stat_ask_time') <= time() && empty($_SESSION['statDisplayed'])) {
    $statUrl = $Campsite['WEBSITE_URL'].'/admin/support/popup';
    $request_only = true;
    ?><a style="display: none;" id="dummy_stat_link" href="<?php echo($statUrl); ?>"></a><?php
}

// clear cache
$clearCache = Input::Get('clear_cache', 'string', 'no', true);
if ((CampCache::IsEnabled() || CampTemplateCache::factory()) && ($clearCache == 'yes')
        && $g_user->hasPermission('ClearCache')) {
    // Clear cache engine's cache
    CampCache::singleton()->clear('user');
    CampCache::singleton()->clear();
    SystemPref::DeleteSystemPrefsFromCache();

    // Clear compiled templates
    require_once dirname(dirname(__FILE__)) . '/template_engine/classes/CampTemplate.php';
    CampTemplate::singleton()->clearCompiledTemplate();

    // Clear template cache storage
    if (CampTemplateCache::factory()) CampTemplateCache::factory()->clean();

    $actionMsg = getGS('Newscoop cache was cleaned up');
    $res = 'OK';
}

?>

<?php if (!empty($actionMsg)) { ?>
<script type="text/javascript">
$(function() {
    <?php if ($res == 'OK') { ?>
    flashMessage('<?php echo $actionMsg; ?>', null, true);
    <?php } else { ?>
    flashMessage('<?php echo $actionMsg; ?>', 'error', true);
    <?php } ?>
});
</script>
<?php } ?>

<?php camp_html_display_msgs("0.25em", "0.25em"); ?>

<div class="links"><a href="<?php echo $Campsite['WEBSITE_URL']; ?>/admin/widgets.php" title="<?php putGS('Add more widgets'); ?>"><?php putGS('Add more widgets'); ?></a></div>

<div id="dashboard">

<div class="column">
<?php
if (!$request_only) {
    $context = new WidgetContext('dashboard1');
    $context->render();
}
?>
</div>

<div class="column">
<?php
if (!$request_only) {
    $context = new WidgetContext('dashboard2');
    $context->render();
}
?>
</div>

</div><!-- /#dashboard -->

<div class="clear"></div>
<script src="<?php echo $Campsite['WEBSITE_URL']; ?>/js/jquery/jquery.cookie.js" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('#dummy_stat_link').fancybox({
        showCloseButton: true,
        overlayShow: true,
        hideOnOverlayClick: false,
        hideOnContentClick: false,
        enableEscapeButton: false,
        centerOnScroll: true,
        onClosed: function() {
            $.getJSON("<?php echo($Campsite['WEBSITE_URL'].'/admin/support/close'); ?>", function(data) {
                window.location.reload();
            });
        },
        overlayOpacity: 0.8,
        overlayColor: '#666',
        onStart: function(){
            $("#fancybox-overlay").css({
                'background-color': '#666',
                opacity: 0.8,
                height: $(document).height()
            }).show();
        }
    }).trigger('click');
    
    $('.context').widgets({
        localizer: {
            remove: '<?php putGS('Remove widget'); ?>',
            info: '<?php putGS('Widget info'); ?>',
        }
    });
});
</script>

<?php camp_html_copyright_notice(); ?>
</body>
</html>
