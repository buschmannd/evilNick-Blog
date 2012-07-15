<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Propagate")) :

class Shiba_Widget_Propagate {
	
	function update_form($instance, $new_instance, $old_instance, $widget) {		
		if (!isset($_POST['widget-'.$widget->id_base][$widget->number]['propagate'])) {
			$instance['propagate'] = FALSE;
			return $instance;
		}
		
		$instance['propagate'] = TRUE;
		$instance['old_text'] = $old_instance['text'];

		return $instance;
	}
		
	// Add new form elements to widgets
	function style_form($widget, $return, $instance) {
		global $shiba_widgets;
		if ($widget->widget_options['classname'] != 'widget_text') return;

		$settings = $widget->get_settings();
		
		$new_text = (isset($instance['text'])) ? $instance['text'] : NULL; 
		$old_text = (isset($instance['old_text'])) ? $instance['old_text'] : NULL;

		$new_settings = array();
		if (is_array($settings) && $old_text && $new_text) {
			foreach ($settings as $key => $widget_options) {
				if ($key == $widget->number) {
					$new_settings[$key] = $widget_options;
					continue;
				}
				if ($widget_options['text'] == $old_text) { 
					$widget_options['text'] = $new_text;
				}	
				$new_settings[$key] = $widget_options;
			}	
			$widget->save_settings($new_settings);
		}
					
		// only add for text widgets
		?>
        <input id="<?php echo $widget->get_field_id('propagate');?>" name="<?php echo $widget->get_field_name('propagate');?>" 
			type="checkbox" <?php if (isset($instance['propagate']) && $instance['propagate']) echo "checked";?>> Propagate Settings
        <?php
		$return = null;
	} 
} // end Shiba_Background_Style class
endif;

?>