<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Manage")) :

class Shiba_Widget_Manage {
	var $settings_cache = array();
	var $widget_number_cache = FALSE;
	
	function get_widget_number($id_base, $widget_info) {
		if (is_array($this->widget_number_cache)) {
			if (!isset($this->widget_number_cache[$id_base]))
				$widget_number = next_widget_id_number($id_base);
			else
				$widget_number = $this->widget_number_cache[$id_base]+1;	
		} else $widget_number = $widget_info['params'][0]['number'];
		return $widget_number;
	}

	
	function get_widget_settings($widget_info) {	
		global $shiba_widgets;

		if (is_array($widget_info['callback']) && is_object($widget_info['callback'][0]) ) { // WP_Widgets object			
			$widget = $widget_info['callback'][0];

			$id_base = $widget->id_base;
			$widget_number = $this->get_widget_number($id_base, $widget_info);
			$widget_id = $id_base . '-' . $widget_number;
			$multi_number = ''; $add_new = 'multi';
			$settings_name = 'widget-' . $id_base;
			$option_name = $widget->option_name;
					
			if (isset($this->settings_cache[$option_name])) {
				$settings = $this->settings_cache[$option_name];
			} else {
				$settings = $widget->get_settings();
			}	
		} else { // specialized widget - has its own settings mechanism
			switch ($widget_info['classname']) {
			case 'akpc-widget':
				$id_base = $widget_info['classname'];
				$widget_number = $this->get_widget_number($id_base, $widget_info);
				$widget_id = $id_base . '-' . $widget_number;
				$multi_number = ''; $add_new = '';
				$settings_name = 'akpc';
				$option_name = 'akpc_widget_options';

				if (isset($this->settings_cache[$option_name])) {
					$settings = $this->settings_cache[$option_name];
				} else {
					$akpc_option = get_option($option_name);
					$settings = maybe_unserialize($akpc_option);
				}	
				$_POST['akpc'][$widget_number]['submit'] = "1";
				break;
			default:	
				$id_base = $widget_info['id'];
				$widget_id = $id_base;
				$widget_number = ''; 
				$multi_number = ''; $add_new = ''; //'single';
				$option_name = $widget_info['classname'];
				$settings_name = 'widget-' . $id_base;
				$settings = get_option($option_name);
			}	 
		}
		return(compact('id_base', 'widget_id', 'widget_number', 'add_new', 'option_name', 'settings_name', 'settings'));
	}
	
	
	function save_widget_settings($settings_array = NULL) {
		if (!$settings_array)
			$settings_array = $this->settings_cache;
		foreach ($settings_array as $option_name => $settings) {
			$settings['_multiwidget'] = 1;
			update_option( $option_name, $settings );
		}
	}

	/* 
	 * Delete widget functions 
	 *
	 */			
	
	function delete_widget($instance) {
		global $wp_registered_widgets, $shiba_widgets;

		$widget_info = $wp_registered_widgets[$instance]; 
		$shiba_widgets->print_debug("widget info = " . print_r($widget_info, TRUE));	
		if (!$widget_info) return;  // check that widget is registered

		$result_args = $this->get_widget_settings($widget_info);
		$shiba_widgets->print_debug("settings args = " . print_r($result_args, TRUE));	
		extract($result_args);	

		// delete instance settings which will delete the widget
		if (is_array($settings)) {
			$shiba_widgets->print_debug("delete settings = {$widget_number}");
			unset($settings[$widget_number]);
			$this->settings_cache[$option_name] = $settings;
		}	
	}
	
	// Deletes options for all instances within a sidebar = $widget_id
	function delete_widget_set($set_id) {
		global $shiba_widgets;
		
		$widget_set = get_term($set_id, 'shiba_widget');
		$shiba_widgets->print_debug("widget set = " . print_r($widget_set, TRUE));
		if (!$widget_set) return;
			
		$sidebars = maybe_unserialize($widget_set->description);
		
		// Clear settings cache
		$this->settings_cache = array(); $this->widget_number_cache = FALSE;
		foreach ($sidebars as $sidebar => $instances) {
			if ($sidebar == 'wp_lost_widgets') continue;
			if (is_array($instances)) {
				foreach ($instances as $instance) {
					
					$this->delete_widget($instance);
				} // end foreach $instances	
			} // end isset $wp_registered_sidebars[$sidebar]
		} // end foreach $sidebars			
		$this->save_widget_settings();
	}
	
	function delete_widget_instances($instances) {
		// Clear settings cache
		$this->settings_cache = array(); $this->widget_number_cache = FALSE;
		foreach ($instances as $instance) {
			$this->delete_widget($instance);
		} // end foreach $instances	
		$this->save_widget_settings();
	}
	
	
	/* 
	 * Copy widget functions 
	 *
	 */			
	function copy_widget($instance, $sidebar) {
		global $wp_registered_widgets, $wp_registered_widget_updates, $wp_registered_widget_controls;
		global $shiba_widgets;
		
		$widget_info = $wp_registered_widgets[$instance]; 
		$shiba_widgets->print_debug("widget info = " . print_r($widget_info, TRUE));	
		if (!$widget_info) return NULL;  // check that widget is registered

		// copy settings
		$result_args = $this->get_widget_settings($widget_info);
		extract($result_args);	
		if (is_array($settings))
			$this->settings_cache[$option_name] = $settings;
		$this->widget_number_cache[$id_base] = $widget_number;	

		$control = isset($wp_registered_widget_controls[$id_base]) ? $wp_registered_widget_controls[$id_base] : array();
		$update = isset($wp_registered_widget_updates[$id_base]) ? $wp_registered_widget_updates[$id_base] : array();

		$shiba_widgets->print_debug("conrtol = " . print_r($control,TRUE) );
		$shiba_widgets->print_debug("update = " . print_r($update,TRUE) );
		$shiba_widgets->print_debug("settings = " . print_r($settings,TRUE) );
				
		$old_number = isset($widget_info['params'][0]['number']) ? $widget_info['params'][0]['number'] : '';

		// fill in expected post arguments
		$_POST['sidebar'] = $sidebar;
		$_POST['id_base'] = $id_base;
		$_POST['widget-id'] = $widget_id;
		if (!empty($control)) {
			$_POST['widget-width'] = $control['width'];
			$_POST['widget-height'] = $control['height'];
		} else {
			unset($_POST['widget-width']);
			unset($_POST['widget-height']);
		}	

		$_POST['widget_number'] = $widget_number; 
		$_POST['multi_number'] = $multi_number;
		$_POST['add_new'] = $add_new;
		if ($old_number && is_array($settings[$old_number])) {
			foreach ($settings[$old_number] as $name => $value) {
				if ($value)
					$_POST[$settings_name][$widget_number][$name] = $value;
			}	
		} 	
		$shiba_widgets->print_debug("POST = " . print_r($_POST,TRUE) );
		if (is_object($update['callback'][0])) // Make sure updated is set to false so that a new widget will be created
			$update['callback'][0]->updated = FALSE;

		if ( is_callable($update['callback']) ) {	
			ob_start();
				call_user_func_array( $update['callback'], $update['params'] );
			ob_end_clean();
		}	
		unset($_POST[$settings_name]);	
		return $widget_id;
	}
	
	
	function copy_widget_set($widget_set) {
		global $wp_registered_sidebars, $wp_registered_widgets, $shiba_widgets;
		
		if (!$widget_set) return;
		wp_set_sidebars_widgets(array()); // Clear all sidebars
		$sidebars = maybe_unserialize($widget_set->description);
		
		// Clear cache
		$this->settings_cache = array(); $this->widget_number_cache = array();
		$copy_widget_set = array();
		foreach ($sidebars as $sidebar => $instances) {
			if ($sidebar == 'wp_lost_widgets') continue; // Don't copy lost widgets
			if (!is_array($instances)) {
				$copy_widget_set[$sidebar] = $instances;
				continue;
			}
			$copy_widget_set[$sidebar] = array();

			foreach ($instances as $instance) {					
				$widget_id = $this->copy_widget($instance, $sidebar);
				if ($widget_id) 
					$copy_widget_set[$sidebar][] = $widget_id;
									
			} // end foreach instances
		} // end foreach sidebars		
		return $copy_widget_set;		
	}
		
	
	/* 
	 * Widget set table functions 
	 *
	 */			

	function widget_columns($widget_columns) {
		// HTTP_REFERER check is to handle the admin-ajax callback for the quick-edit ability
		if ( (isset($_GET['page']) && ($_GET['page'] == 'manage_widget')) || (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], 'page=manage_widget') !== FALSE)) ) {
			$widget_columns = array(
				'cb' => '<input type="checkbox" />',
				'widget_name' => __('Name'),
//				'description' => __('Description'),
				'slug' => __('Slug'),
				'active_widget' => 'Active Widgets',
				'widget_theme' => 'Widget Theme',
				'posts' => __('Posts')
				);
	 
		}	
		return $widget_columns;
	}
	
	function manage_widget_columns($out, $column_name, $widget_id) {
		global $shiba_widgets, $taxonomy;
		
		$widget = get_term($widget_id, 'shiba_widget');
		$edit_link = "themes.php?page=add_widget&amp;action=update_widget&widget={$widget_id}";
		switch ($column_name) {
		case 'widget_name':
			$out .= '<strong><a class="row-title" href="' . $edit_link . '" title="' . esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $widget->name)) . '">' . $widget->name . '</a></strong><br />';
			$actions = array();
			$actions['edit'] = '<a href="' . $edit_link . '">' . __('Edit') . '</a>';
			$actions['inline hide-if-no-js'] = '<a href="#" class="editinline">' . __('Quick&nbsp;Edit') . '</a>';
			$actions['delete'] = "<a class='delete-tag' href='" . wp_nonce_url("themes.php?page=manage_widget&amp;action=delete&amp;taxonomy=$taxonomy&amp;tag_ID=$widget_id", 'delete-tag_' . $widget_id) . "'>" . __('Delete') . "</a>";
			$actions['copy'] = "<a href=\"" . wp_nonce_url("themes.php?page=manage_widget&amp;action=copy&amp;tag_ID=$widget_id", 'copy-tag_'.$widget_id)."\">Copy</a>";
			$actions = apply_filters('tag_row_actions', $actions, $widget);
			$action_count = count($actions);
			$i = 0;
			$out .= '<div class="row-actions">';
			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';
			$qe_data = get_term($widget_id, 'shiba_widget', object, 'edit');
			$out .= '<div class="hidden" id="inline_' . $qe_data->term_id . '">';
			$out .= '<div class="name">' . $qe_data->name . '</div>';
			$out .= '<div class="slug">' . apply_filters('editable_slug', $qe_data->slug) . '</div></div>';
			break;
		case 'active_widget':
			$new_widgets = maybe_unserialize($widget->description);
			$num_active = 0; $update = FALSE;
			if (is_array($new_widgets)) {
				global $wp_registered_sidebars, $wp_registered_widgets;
				foreach ( $new_widgets as $sidebar => $instances ) {
					if (isset($wp_registered_sidebars[$sidebar]) && is_array($instances)) {
						$num_active += count($instances);
					}
				}	
			}	
			$out .= $num_active;	
			break;
		case 'widget_theme':
			$theme = get_metadata('shiba_term', $widget->term_id, 'theme', TRUE);
			$out .= $theme;		
			break;
		default:
			break;
		}
		return $out;	
	}

	// Adapted from wp-admin/widgets.php
	// Look for "lost" widgets - but here theme changes DO NOT have any effect. The widget sets are still saved as they are.
	// Lost widgets are simply widgets that are somehow no longer assigned to any sidebar. In general there should not be any 
	// lost widgets, therefore this function is only used for debugging purposes and to clear out old widget settings.
	function retrieve_lost_widgets() {
		global $wp_registered_widgets;

		$valid_widgets = array();
		$widget_sets = get_terms('shiba_widget', 'hide_empty=0');
		foreach ($widget_sets as $widget_set) { // get all assigned widgets
			$sidebars_widgets = maybe_unserialize($widget_set->description);
			if (!is_array($sidebars_widgets)) continue;
			foreach ($sidebars_widgets as $sidebar => $instances) {
				if (is_array($instances))
					$valid_widgets = array_merge($valid_widgets, $instances);
			}		
		}
		
		// find hidden/lost multi-widget instances
		$lost_widgets = array();
		foreach ( $wp_registered_widgets as $key => $val ) {
			if ( in_array($key, $valid_widgets, true) )
				continue;
	
			$number = preg_replace('/.+?-([0-9]+)$/', '$1', $key);
	
			if ( 2 > (int) $number )
				continue;
	
			$lost_widgets[] = $key;
		}
		return $lost_widgets;
	}
} // end Shiba_Widget_Manage class
endif;

?>