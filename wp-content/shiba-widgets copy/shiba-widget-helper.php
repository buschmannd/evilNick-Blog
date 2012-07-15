<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Helper")) :

class Shiba_Widget_Helper {

	function delete_term_metadata($term_id, $type='shiba_term') {
		global $wpdb;
		
	   	$table_name = $wpdb->prefix . $type . 'meta';
		$sql = $wpdb->prepare("DELETE FROM $table_name WHERE {$type}_id = %d", $term_id);
		$wpdb->query($sql);
	}
	
	function copy_term_metadata($old_id, $new_id, $type='shiba_term') {
		global $wpdb;
	   	$table_name = $wpdb->prefix . $type . 'meta';
		$sql = $wpdb->prepare("SELECT * FROM $table_name WHERE {$type}_id = %d", $old_id);
		$result = $wpdb->get_results($sql);
		if (is_array($result)) {
			foreach ($result as $meta) {
				update_metadata($type, $new_id, $meta->meta_key, $meta->meta_value);
			}	
		}
	}
	

	function clear_widgets($widget_ids) {
		if (!is_array($widget_ids))
			$widget_ids = array($widget_ids);
			
		foreach ($widget_ids as $widget_id) {
			$obj_attachments = maybe_unserialize(get_metadata('shiba_term', $widget_id, 'obj_attachments', TRUE));
			if (is_array($obj_attachments))
				foreach ($obj_attachments as $obj_id) {
					$ids = wp_get_object_terms($obj_id, 'shiba_widget', array('fields' => 'ids')); 
					if (!is_wp_error($ids) && !empty($ids) && in_array($ids[0], (array)$widget_ids)) {
						wp_set_object_terms( $obj_id, '', 'shiba_widget' );
					}	
				}	
		}
		
		// clear tag and category attachments
		foreach ($widget_ids as $widget_id) {
			$term_attachments = maybe_unserialize(get_metadata('shiba_term', $widget_id, 'term_attachments', TRUE));
			if (is_array($term_attachments))
				foreach ($term_attachments as $term_id) {
					$term = get_term($term_id, 'post_tag');
					if (!$term || is_wp_error($term))
						$term = get_term($term_id, 'category');
					if (!$term || is_wp_error($term)) continue;
					else delete_metadata('shiba_term', $term_id, 'shiba_widget');
				}	
			delete_metadata('shiba_term', $widget_id, 'term_attachments');
		}	
	
	}
	
	
	function get_sidebars_widgets() {
		$data = wp_get_sidebars_widgets();
		unset($data['wp_lost_widgets'],$data['theme']);
		$data = serialize($data);
		return $data;
	}
	
	
	function set_sidebars_widgets($widget) {
		$new_widgets = maybe_unserialize($widget->description);
		wp_set_sidebars_widgets($new_widgets);
	}
			

} // end Shiba_Widget_Helper class
endif;

?>