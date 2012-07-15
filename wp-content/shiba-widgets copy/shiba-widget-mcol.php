<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_Multi_Column")) :

class Shiba_Widget_Multi_Column extends WP_Widget {

	var $last; // last widget in multi column sidebar
	var $style_id; // id of multi column widget
	var $num_widgets; // number of widgets in multi column sidebar
	var $num_col; // number of columns in multi column sidebar
	var $height; // height of multi column sidebar
	var $sidebar; // sidebar containing the multi column widget
	
	function Shiba_Widget_Multi_Column() {
		parent::WP_Widget(false, $name = 'Multi-Column Widget');
	}
		
	function form($instance) {
		global $shiba_widgets;

		$num_col = (isset($instance['num_col'])) ? absint($instance['num_col']) : 0;
		$height = (isset($instance['height'])) ? absint($instance['height']) : 0;
		?>
        Number of columns  
        <select id="<?php echo $this->get_field_id('num_col');?>"  name="<?php echo $this->get_field_name('num_col');?>">
        	<?php 
			for ($i = 2; $i <= 4; $i++) {
				echo $shiba_widgets->general->write_option($i, $i, $num_col);
			}
			?>	
		</select>
        <p><input id="<?php echo $this->get_field_id('height');?>" name="<?php echo $this->get_field_name('height');?>" type="text" size="5" 
        value="<?php echo $height;?>"> px</p>
        <p><small>Enter pixel height of widget. [0px = variable height].</small></p>
        <?php
	}

	function widget($args, $instance) {
		// outputs the content of the widget
		// Check that it is the first widget
		$this->sidebar = $args['id'];
		$sidebars_widgets = wp_get_sidebars_widgets();
		$this->num_widgets = count($sidebars_widgets[$this->sidebar]);
		
		$this->num_col = isset($instance['num_col']) ? $instance['num_col'] : 2;
		$this->height = isset($instance['height']) ? $instance['height'] : 0;
		if ($sidebars_widgets[$this->sidebar][0] == $this->id) {
			$this->style_id = "multi-column-".$this->sidebar;
			$this->add_styles();
			?>
			<div id="<?php echo $this->style_id;?>" class="multi-column-div">
			<div class="inner">
			<?php
			$sidebars_widgets = wp_get_sidebars_widgets();
			$this->last = end($sidebars_widgets[$this->sidebar]);
			add_filter( 'dynamic_sidebar_params', array(&$this, 'end_widget')) ;
			
			// add widget class to widgets that do not have them
			add_filter( 'dynamic_sidebar_params', array(&$this, 'add_widget_class')) ;
		}	
	}
	
	function end_widget( $params ) {
		if ($params[0]['widget_id'] == $this->last) {
			$output = $params[0]['after_widget'];
//			$output .= $this->add_bottom_border($output);
			$output .= "<div style=\"clear:both;\"></div>\n";
			$output .= "</div></div> <!-- end multi column div -->\n";
			$params[0]['after_widget'] = $output;

			remove_filter( 'dynamic_sidebar_params', array(&$this, 'end_widget')) ;
			remove_filter( 'dynamic_sidebar_params', array(&$this, 'add_widget_class')) ;

		}
		return $params;
	}

	function add_bottom_border($output) {
		$output .= "<div style=\"clear:both;\"></div>\n";
		$output .= "<div id=\"bottom-{$this->style_id}\" class=\"multi-column-border\">\n";
		
		for ($i=0; $i<  $this->num_col; $i++) { 
			$output .= "<div class=\"bottom-border\"></div>\n";
 		}
		$output .= "</div>\n";	
		return $output;
	}

	function add_widget_class($params) {
		global $shiba_widgets;
		if ($this->sidebar == $params[0]['id'])	{
			$classes = $shiba_widgets->general->substring($params[0]['before_widget'],'class="', '"');
			// check if it contains widget class
			$class_array = explode(' ', $classes);
			if (!in_array('widget', (array)$class_array)) {
				$new_classes = $classes . " widget";
				$params[0]['before_widget'] = str_replace($classes, $new_classes, $params[0]['before_widget']);
			}
		}	
		return $params;
	}
		
	function add_styles() {
			
		?>
		<style type="text/css">
		<?php 
		if ($this->height) : ?>
		#<?php echo $this->style_id?> {  
			<?php
			if ($this->num_widgets)
				echo "height:{$this->height}px;\n";
			else	  
				echo "height:0px;\n";
			echo "overflow:hidden;\n";
			?>	
		}	
		<?php
		endif;	
		
		if ($this->num_col >= 1) : ?>
		#<?php echo $this->style_id?> .inner .widget {  
			<?php 
			switch ($this->num_col) {
			case 1:
				echo "width: 100%;";
				break;
			case 2:
				echo "width: 50%;";
				break;
			case 3:
				echo "width: 33%;";
				break;
			default:	
				echo "width: 25%;";
				break;
			}	
	 		?>
		}			
						
		#<?php echo "bottom-".$this->style_id?> .bottom-border {  
			<?php 
			switch ($this->num_col) {
			case 1:
				echo "width: 100%;";
				break;
			case 2:
				echo "width: 50%;";
				break;
			case 3:
				echo "width: 33%;";
				break;
			default:	
				echo "width: 25%;";
				break;
			}
			?>
		}
		<?php	
		endif;
		?>
		</style>
        <?php
	}

} // end Shiba_Widget_Multi_Column class
add_action('widgets_init', create_function('', 'return register_widget("Shiba_Widget_Multi_Column");'));	
endif;
?>