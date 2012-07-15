<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

global $shiba_widgets;
$action = '';
$location = "themes.php?page=widget_options"; // based on the location of your sub-menu page

switch($action) :
default:
endswitch;

if (isset($_POST['save_widget_options'])) {
	if ( ! current_user_can('switch_themes') )
		wp_die(__('You are not allowed to change widget settings.'));
	check_admin_referer("shiba_widget_options");

	// remove non widget options from POST array
	unset($_POST['_wpnonce'], $_POST['_wp_http_referer'], $_POST['save_widget_options']);
	
	update_option('shiba_widget_options', $_POST);
	$location = add_query_arg('message', 1, $location);

	$shiba_widgets->general->javascript_redirect($location);
	exit;
}	

$messages[1] = __('Shiba widget settings updated.', 'shiba_widgets');
$messages[2] = __('Shiba widget settings failed to update.', 'shiba_widgets');

if ( isset($_GET['message']) && (int) $_GET['message'] ) {
	$message = $messages[$_GET['message']];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

		
$title = __('Shiba Widget Options', 'widget_options');
?>
<style>
#shiba-widget_options { margin-bottom:30px; }
#shiba-widget_options label {
	text-align:right; width:150px;
	display:block; float:left;
	margin-right:20px; }	
</style>

    <div class="wrap">   
    <?php screen_icon(); ?>
    <h2><?php echo esc_html( $title ); ?></h2>

	<?php
		if ( !empty($message) ) : 
		?>
		<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
		<?php 
		endif; 

		$widgets = get_terms('shiba_widget', 'hide_empty=0'); 		
		$options = get_option('shiba_widget_options');
		if (!is_array($options)) $options = array();
	?>

    <form name="shiba-widget_options" id="shiba-widget_options" method="post" action="" class="">
        <?php wp_nonce_field("shiba_widget_options"); ?> 
        <h3>Blog Widget Options</h3>
        <p><label>is_frontpage</label>  <?php $shiba_widgets->metabox->option_widget_metabox('frontpage', $widgets, $options); ?></p>
        <p><label>is_404</label>  <?php $shiba_widgets->metabox->option_widget_metabox('404', $widgets, $options); ?></p>
        <p><label>is_search</label>  <?php $shiba_widgets->metabox->option_widget_metabox('search', $widgets, $options); ?></p>
        <p><label>is_post</label>  <?php $shiba_widgets->metabox->option_widget_metabox('single', $widgets, $options); ?></p>
        <p><label>is_page</label>  <?php $shiba_widgets->metabox->option_widget_metabox('page', $widgets, $options); ?></p>
        <p><label>is_attachment</label>  <?php $shiba_widgets->metabox->option_widget_metabox('attachment', $widgets, $options); ?></p>
        <p><label>is_category</label>  <?php $shiba_widgets->metabox->option_widget_metabox('category', $widgets, $options); ?></p>
        <p><label>is_tag</label>  <?php $shiba_widgets->metabox->option_widget_metabox('tag', $widgets, $options); ?></p>
        </div>
        
        <div style="height:50px;"></div>
		<h3>Other Widget Settings</h3>
		<p><input type="checkbox" name="lost_widgets" <?php if (isset($options['lost_widgets'])) echo 'checked';?>/>  Show Lost Widgets</p>
        <small>Show widgets that are no longer assigned to any sidebar.</small>
		<p><input type="checkbox" name="inherit_parent" <?php if (isset($options['inherit_parent'])) echo 'checked';?>/>  Inherit Widget from Parent</p>
        <small>Determine whether child pages should inherit the widget set of their parent page.</small>
        <p>
		<input name="save_widget_options" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Options'); ?>"/></p>
    </form>
</div>


