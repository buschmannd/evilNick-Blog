<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Style")) :

class Shiba_Widget_Style {


	function add_styles($params) {
		global $wp_registered_widgets, $shiba_widgets;
		$shiba_widgets->print_debug("params = " . print_r($params, TRUE));	

		$widget_id = $params[0]['widget_id'];
		$widget_info = $wp_registered_widgets[$widget_id];
		$shiba_widgets->print_debug("widget info = " . print_r($widget_info, TRUE));	
		if (!is_array($widget_info)) return $params;
		if (!is_array($widget_info['callback'])) return $params; // no settings
			
		$widget = $widget_info['callback'][0];
		$instance = $widget->get_settings(); $instance = $instance[$params[1]['number']];
		$shiba_widgets->print_debug("instance = " . print_r($instance, TRUE));	
		
		if (is_array($instance) && isset($instance['style']))	{
			$classes = $shiba_widgets->general->substring($params[0]['before_widget'],'class="', '"');
			$new_classes = $classes . " " . str_replace(',', ' ', $instance['style']);
			$params[0]['before_widget'] = str_replace($classes, $new_classes, $params[0]['before_widget']);
			$shiba_widgets->print_debug("classes = {$classes} {$params[0]['before_widget']} replace with {$new_classes}");
		}	
		$shiba_widgets->print_debug(print_r($params, TRUE));
		return $params;
	}
	

	function update_form($instance, $new_instance, $old_instance, $widget) {		
		$value = $_POST['widget-'.$widget->id_base][$widget->number]['style'];	
		$classes = esc_attr($value);
		$instance['style'] = $classes;
		return $instance;
	}
		
	// Add new form elements to widgets
	function style_form($widget, $return, $instance) {
		global $shiba_widgets;

		?>
        <input id="<?php echo $widget->get_field_id('style');?>" name="<?php echo $widget->get_field_name('style');?>" type="text" size="33" 
        value="<?php if (isset($instance['style'])) echo $instance['style'];?>">
        <p><small>Enter a list of comma separated widget style classes.</small></p>
        <?php
		$return = null;
	} 
} // end Shiba_Background_Style class
endif;

?>