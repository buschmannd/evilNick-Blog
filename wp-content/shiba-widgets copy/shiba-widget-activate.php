<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Activate")) :

class Shiba_Widget_Activate {
	function update_version_1_0() {
		
			// Clear and update state from version 1.0
		$done = get_option('shiba_widget_1_0_update', FALSE);
		if ($done) return;
		
		// Convert old style and mcol widget settings (from version 1.0)
		$this->convert_old_widget_settings();

		// Convert previous tag and category description field metadata to table metadata
		$this->convert_all_tag_settings();
		
		// Add theme to current saved widgets if there isn't one
		$widget_sets = get_terms('shiba_widget', 'hide_empty=0');
		$current_theme = get_current_theme();
		foreach ($widget_sets as $widget_set) {
			$theme = get_metadata('shiba_term', $widget_set->term_id, 'theme', TRUE);
			if (!$theme || is_wp_error($theme)) {	
				update_metadata('shiba_term', $widget_set->term_id, 'theme', $current_theme);	
			}
		}		
		update_option('shiba_widget_1_0_update', 'done');
	}
	
	// Convert old style and multi-column widget settings (in version 1.0) to the new widget settings used by WordPress standard widgets
	function convert_old_widget_settings() {
		global $wp_registered_widgets, $shiba_widgets;

		// Clear settings cache
		$shiba_widgets->manage->settings_cache = array(); $shiba_widgets->manage->widget_number_cache = FALSE;

		$style_array = get_option('shiba_widget_styles', array());			
		$shiba_widgets->print_debug("style array = " . print_r($style_array, TRUE));	
		$shiba_widgets->print_debug("manage = " . print_r($shiba_widgets->manage, TRUE));	
		foreach ($style_array as $widget_id => $classes) {
			if (!$classes) continue;
			if (isset($wp_registered_widgets[$widget_id])) {
				$widget_info = $wp_registered_widgets[$widget_id];
				$result_args = $shiba_widgets->manage->get_widget_settings($widget_info);
				$shiba_widgets->print_debug("settings args = " . print_r($result_args, TRUE));	
				extract($result_args);
				if (is_array($settings)) {
					$settings[$widget_number]['style'] = str_replace(' ',',',$classes);
					$shiba_widgets->manage->settings_cache[$option_name] = $settings;					
				}
			}
		}
		$shiba_widgets->print_debug("settings cache = " . print_r($shiba_widgets->manage->settings_cache, TRUE));	

		$mcol_array = get_option('shiba_widget_mcol', array());			
		$shiba_widgets->print_debug("mcol array = " . print_r($mcol_array, TRUE));	
		foreach ($mcol_array as $widget_id => $data) {
			if (!is_array($data)) continue;
			if (isset($wp_registered_widgets[$widget_id])) {
				$widget_info = $wp_registered_widgets[$widget_id];
				$result_args = $shiba_widgets->manage->get_widget_settings($widget_info);
				$shiba_widgets->print_debug("settings args = " . print_r($result_args, TRUE));	
				extract($result_args);
				if (is_array($settings)) {
					$settings[$widget_number]['num_col'] = $data['num_col'];
					$settings[$widget_number]['height'] = $data['height'];
					$shiba_widgets->manage->settings_cache[$option_name] = $settings;					
				}
			}
		}
		$shiba_widgets->print_debug("settings cache = " . print_r($shiba_widgets->manage->settings_cache, TRUE));	
		$shiba_widgets->manage->save_widget_settings();	
	}
	
	// convert old tag and category settings to database metadata
	function convert_old_tag_settings($taxonomy) {
		global $wpdb, $shiba_widgets;
		$delete_tags = array();
		
		$tags = get_terms($taxonomy, 'hide_empty=0');
		foreach ($tags as $tag) {
			// Parse tag description
			if (strpos($tag->description, '[SHIBA WIDGET:') === FALSE) continue;
			$tag_widget = $shiba_widgets->general->substring($tag->description, '[SHIBA WIDGET: ', ']');	
			$shiba_widgets->print_debug("Found tag $tag_widget");

			if (get_metadata('shiba_term', $tag->term_id, 'shiba_widget', TRUE)) continue;
			update_metadata('shiba_term', $tag->term_id, 'shiba_widget', $tag_widget);
			$delete_tags[$tag->term_id] = $tag->description;
			
			// store back relationship in widget set object
			$widget_id = is_term( $tag_widget, 'shiba_widget' );	
			if (is_array($widget_id)) {
				$widget = get_term($widget_id['term_id'], 'shiba_widget');
				$term_attachments = maybe_unserialize(get_metadata('shiba_term', $widget_id['term_id'], 'term_attachments', TRUE));
				if (!is_array($term_attachments)) $term_attachments = array();
				$term_attachments[] = $tag->term_id;
				update_metadata('shiba_term', $widget_id['term_id'], 'term_attachments', maybe_serialize($term_attachments));
			}		
		} // end foreach tags
		return $delete_tags;		
	}
	
	function delete_previous_tag_settings($delete_tags, $taxonomy) {
		global $wpdb, $shiba_widgets;
		foreach ($delete_tags as $tag_id => $description) {
			$shiba_widgets->print_debug("Delete tag " . print_r($description,TRUE));
			// delete SHIBA WIDGET text '/\[SHIBA WIDGET:[^\]]\]/'
			$new_description = preg_replace('/\[SHIBA WIDGET:[^\]]+\]/', '', $description);
			wp_update_term( $tag_id, $taxonomy, array('description' => $new_description) );
		}
	}
	
	function convert_all_tag_settings() {
		$delete_tags = $this->convert_old_tag_settings('post_tag');	
		$this->delete_previous_tag_settings($delete_tags, 'post_tag');
		$delete_categories = $this->convert_old_tag_settings('category');	
		$this->delete_previous_tag_settings($delete_categories, 'category');
	}
} // end Shiba_Widget_Helper class
endif;

?>