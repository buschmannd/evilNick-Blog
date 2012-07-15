<?php
// don't load directly
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// create shiba widget manage class
global $shiba_widgets;

$title = __('Add Widget');
$action = NULL;
if (isset($_REQUEST['action'])) $action = esc_attr($_REQUEST['action']);

if (isset($_REQUEST['widget'])) {
	$id = absint($_REQUEST['widget']);
	$widget = get_term($id, 'shiba_widget');
	if (empty($_POST) && is_object($widget))
		$shiba_widgets->helper->set_sidebars_widgets($widget);

} else {
	$id= 0;
	$widget = NULL;
	
	if (empty($_POST)) {
		wp_set_sidebars_widgets(array());
	}
}	

if (isset($_POST['_wpnonce'])) $nonce=esc_attr($_POST['_wpnonce']);
else $nonce = '';

$location = 'themes.php?page=add_widget&action=edit_widget';

if (isset($_POST['delete_lost'])) {
	if ( ! current_user_can('switch_themes') )
		wp_die(__('You are not allowed to delete widgets.'));
	$sidebars_widgets = wp_get_sidebars_widgets();
	if (!empty($sidebars_widgets['wp_lost_widgets'])) {
//	$lost_widgets = $shiba_widgets->manage->retrieve_lost_widgets();
		$shiba_widgets->manage->delete_widget_instances($sidebars_widgets['wp_lost_widgets']);
		$location = add_query_arg('message', 10, $location);
		$location = add_query_arg('widget', $id, $location);
	}
	$shiba_widgets->general->javascript_redirect($location);
	exit;
} 
		
switch($action) {
case 'add_widget':

	if ( ! current_user_can('switch_themes') )
		wp_die(__('You are not allowed to create widgets.'));
	check_admin_referer('add_widget0');
	
	// Create widget object
	$data = $shiba_widgets->helper->get_sidebars_widgets();

	$widget_id = wp_insert_term($_POST['widget_title'], 'shiba_widget', array( 'description' => $data) );
	
	if ( !is_wp_error($widget_id)) {
		$id = $widget_id['term_id'];
		$location = add_query_arg('message', 6, $location);
		$title = __('Edit Widget');
		update_metadata('shiba_term', $id, 'theme', get_current_theme());
	} else {
		$location = add_query_arg('message', 7, $location);
	}
	break;

case 'edit_widget':
	if ( ! current_user_can('switch_themes') )
			wp_die(__('You are not allowed to edit widgets.'));

	$title = __('Edit Widget');
	break;

case 'update_widget':
	if ( ! current_user_can('switch_themes') )
		wp_die(__('You are not allowed to save widgets.'));
	$title = __('Edit Widget');
			
	if (isset($_POST['widget'])) {	
		check_admin_referer('update_widget'.$id);
		if (isset($_POST['restore_widget'])) { 
			// get previous widget configuration
			if (is_object($widget)) {
				$shiba_widgets->helper->set_sidebars_widgets($widget);
				$location = add_query_arg('message', 11, $location); break;
			}	 
		}

		// Update the widget in the database
		$data = $shiba_widgets->helper->get_sidebars_widgets();
		$update = wp_update_term(	$id, 'shiba_widget', 
									array(	'name' => $_POST['widget_title'], 'description' => $data) );

		if ( is_wp_error($update) ) {
			$location = add_query_arg('message', 9, $location); 
		} else {		
			// Update widget meta data
			$location = add_query_arg('message', 8, $location);			
			update_metadata('shiba_term', $id, 'theme', get_current_theme());
		}
	} else $location = add_query_arg('message', 9, $location);
	break;
default:
}	

if ($id && isset($_POST['action'])) {
	$location = add_query_arg('widget', $id, $location);
	$shiba_widgets->general->javascript_redirect($location);
	exit;
}	
	

$messages[6] = __('Widget added.');
$messages[7] = __('Widget creation failed.');
$messages[8] = __('Widget updated.');
$messages[9] = __('Widget update failed.');
$messages[10] = __('Lost widgets deleted.');
$messages[11] = __('Original widgets restored.');


if ( isset($_GET['message']) && (int) $_GET['message'] ) {
	$message = $messages[$_GET['message']];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

/*
 * Widget attributes
 *	
 */
 
if ($id) {
	$action = 'update_widget';
	$widget_name = $widget->name;
} else {
	$action = 'add_widget';
	$id=0; $widget_name = "";
}	
$options = get_option('shiba_widget_options');
if (!is_array($options)) $options = array();

?>
<div class="wrap">
<?php
	if ( !empty($message) ) : 
	?>
	<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
	<?php 
	endif; 
?>
<style>
#titlediv label {
	/* text-align:right; width:200px; */
	display:block; float:left;
	margin-right:20px;
}	
</style>
<form name="edit_widget" id="edit_widget" method="post" action="" class="">
    <input type="hidden" name="action" value="<?php echo $action;?>" />
    <input type="hidden" name="widget" value="<?php echo $id;?>" />
    <?php wp_nonce_field($action.$id); ?> 
    <div id="titlediv" style="width:500px; margin-top:50px;">
    	<label for="widget_title"><strong>Widget Set Title</strong></label>
		<input type="text" name="widget_title" id="widget_title" size="40" tabindex="1" value="<?php echo $widget_name;?>" autocomplete="off" />
        
	
    </div>

	<input name="save_widget" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Widget Set'); ?>"/>
	<input name="restore_widget" type="submit" class="button" value="<?php esc_attr_e('Restore Widget Set'); ?>"/>
    <?php if (isset($options['lost_widgets'])) :?>
	<input name="delete_lost" type="submit" class="button" value="<?php esc_attr_e('Delete Lost Widgets'); ?>"/>
    <?php endif; ?>

</form>
</div> <!-- End div wrap -->

<?php

global $wp_registered_sidebars, $sidebars_widgets;

//require(ABSPATH . 'wp-admin/widgets.php');
require(SHIBA_WIDGET_DIR . '/widgets.php');


?>