<?php
/**
 * Manage Widgets Administration Panel.
 *
 */
/** WordPress Administration Widgets API */
require_once(ABSPATH . 'wp-admin/includes/widgets.php');

$title = __('Manage Widgets');

global $shiba_widgets, $action, $post_type;
wp_reset_vars( array('action') );
$taxonomy = 'shiba_widget';
$post_type = 'post';

if ( !is_taxonomy($taxonomy) )
	wp_die(__('Invalid taxonomy'));

$parent_file = 'themes.php';
$submenu_file = "themes.php?page=manage_widget&amp;taxonomy=$taxonomy";
$location = 'themes.php?page=manage_widget';

if ( isset( $_GET['action'] ) && isset($_GET['delete_tags']) && ( 'delete' == $_GET['action'] || 'delete' == $_GET['action2'] ) )
	$action = 'bulk-delete';

switch($action) {
case 'copy':
	$tag_ID = (int) $_GET['tag_ID'];
	if ( ! current_user_can('switch_themes') )
		wp_die(__('You are not allowed to create widgets.'));
	check_admin_referer('copy-tag_' .  $tag_ID);

	$widget_set = get_term($tag_ID, 'shiba_widget');
	if ( is_wp_error($widget_set)) {
		$location = add_query_arg('message', 8, $location);
		$shiba_widgets->general->javascript_redirect($location);
	}
		
	$shiba_widgets->print_debug("widget set = " . print_r($widget_set, TRUE));
	$copy_widget_set = $shiba_widgets->manage->copy_widget_set($widget_set);
	$shiba_widgets->print_debug("copy widget result = " . print_r($copy_widget_set, TRUE));	

	// Create widget object
	$copy_name = 'Copy ' . $widget_set->name;
	$widget_id = wp_insert_term($copy_name, 'shiba_widget', 
								array( 	'slug' => sanitize_title($copy_name.'-'.time()),
										'description' => serialize($copy_widget_set)) );
	$shiba_widgets->helper->copy_term_metadata($widget_set->term_id, $widget_id['term_id']);
	
	if ( !is_wp_error($widget_id)) {
		$location = add_query_arg('message', 7, $location);
	} else {
		$location = add_query_arg('message', 8, $location);
	}

	$shiba_widgets->general->javascript_redirect($location);
	exit;
	break;
case 'delete':
	if ( !isset( $_GET['tag_ID'] ) ) {
		$shiba_widgets->general->javascript_redirect($location);
		exit;
	}

	$tag_ID = (int) $_GET['tag_ID'];
	check_admin_referer('delete-tag_' .  $tag_ID);

	if ( !current_user_can('manage_categories') )
		wp_die(__('Cheatin&#8217; uh?'));

	$shiba_widgets->manage->delete_widget_set($tag_ID);
	$shiba_widgets->helper->clear_widgets($tag_ID);
	$shiba_widgets->helper->delete_term_metadata($tag_ID);
	wp_delete_term( $tag_ID, $taxonomy);

	if ( $referer = wp_get_referer() ) {
		if ( false !== strpos($referer, 'themes.php?page=manage_widget') )
			$location = $referer;
	}

	$location = add_query_arg('message', 2, $location);
	$shiba_widgets->general->javascript_redirect($location);
	exit;

break;

case 'bulk-delete':
	check_admin_referer('bulk-tags');

	if ( !current_user_can('manage_categories') )
		wp_die(__('Cheatin&#8217; uh?'));

	$tags = (array) $_GET['delete_tags'];
	$shiba_widgets->helper->clear_widgets($tags);
	foreach( $tags as $tag_ID ) {
		$shiba_widgets->manage->delete_widget_set($tag_ID);
		$shiba_widgets->helper->delete_term_metadata($tag_ID);
		wp_delete_term( $tag_ID, $taxonomy);
	}
	
	if ( $referer = wp_get_referer() ) {
		if ( false !== strpos($referer, 'themes.php?page=manage_widget') )
			$location = $referer;
	}

	$location = add_query_arg('message', 6, $location);
	$shiba_widgets->general->javascript_redirect($location);
	exit;

break;

default:

if ( isset($_GET['_wp_http_referer']) && ! empty($_GET['_wp_http_referer']) ) {
	 wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI']) ) );
	 exit;
}

$messages[1] = __('Widget added.');
$messages[2] = __('Widget deleted.');
$messages[3] = __('Widget updated.');
$messages[4] = __('Widget not added.');
$messages[5] = __('Widget not updated.');
$messages[6] = __('Widgets deleted.'); 
$messages[7] = __('Widget copy created.'); 
$messages[8] = __('Widget copy failed.'); ?>

<div class="wrap nosubsub">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title );
if ( isset($_GET['s']) && $_GET['s'] )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( stripslashes($_GET['s']) ) ); ?>
</h2>

<?php if ( isset($_GET['message']) && ( $msg = (int) $_GET['message'] ) ) : ?>
<div id="message" class="updated fade"><p><?php echo $messages[$msg]; ?></p></div>
<?php $_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif; ?>
<div id="ajax-response"></div>

<form class="search-form" action="" method="get">
<input type="hidden" name="page" value="manage_widget" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<p class="search-box">
	<label class="screen-reader-text" for="tag-search-input"><?php _e( 'Search Tags' ); ?>:</label>
	<input type="text" id="tag-search-input" name="s" value="<?php _admin_search_query(); ?>" />
	<input type="submit" value="<?php esc_attr_e( 'Search Tags' ); ?>" class="button" />
</p>
</form>
<br class="clear" />

<form id="posts-filter" action="" method="get">
<input type="hidden" name="page" value="manage_widget" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<div class="tablenav">
<?php
$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 0;
if ( empty($pagenum) )
	$pagenum = 1;

$tags_per_page = (int) get_user_option( 'edit_tags_per_page', 0, false );
if ( empty($tags_per_page) || $tags_per_page < 1 )
	$tags_per_page = 20;
$tags_per_page = apply_filters( 'edit_tags_per_page', $tags_per_page );
$tags_per_page = apply_filters( 'tagsperpage', $tags_per_page ); // Old filter

$page_links = paginate_links( array(
	'base' => add_query_arg( 'pagenum', '%#%' ),
	'format' => '',
	'prev_text' => __('&laquo;'),
	'next_text' => __('&raquo;'),
	'total' => ceil(wp_count_terms($taxonomy) / $tags_per_page),
	'current' => $pagenum
));

if ( $page_links )
	echo "<div class='tablenav-pages'>$page_links</div>";
?>

<div class="alignleft actions">
<select name="action">
<option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
<option value="delete"><?php _e('Delete'); ?></option>
</select>
<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
<?php wp_nonce_field('bulk-tags'); ?>
</div>

<br class="clear" />
</div>

<div class="clear"></div>

<table class="widefat tag fixed" cellspacing="0">
	<thead>
	<tr>
<?php print_column_headers('edit-tags'); ?>
	</tr>
	</thead>

	<tfoot>
	<tr>
<?php print_column_headers('edit-tags', false); ?>
	</tr>
	</tfoot>

	<tbody id="the-list" class="list:tag">
<?php

$searchterms = isset( $_GET['s'] ) ? trim( $_GET['s'] ) : '';

$count = tag_rows( $pagenum, $tags_per_page, $searchterms, $taxonomy );
?>
	</tbody>
</table>

<div class="tablenav">
<?php
if ( $page_links )
	echo "<div class='tablenav-pages'>$page_links</div>";
?>

<div class="alignleft actions">
<select name="action2">
<option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
<option value="delete"><?php _e('Delete'); ?></option>
</select>
<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
</div>

<br class="clear" />
</div>

<br class="clear" />
</form>
</div><!-- /wrap -->

<?php 
	$version = get_bloginfo('version');	
	if (version_compare($version, '3', '>=')) {
		inline_edit_term_row('edit-tags', $taxonomy); 
	} else	inline_edit_term_row('edit-tags');

break;
}

include('admin-footer.php');

?>