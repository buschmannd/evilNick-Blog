<?php
// don't load directly
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_MetaBox")) :

class Shiba_Widget_MetaBox {	

	function Shiba_Widget_MetaBox() {
//		if ( ! is_admin() )
//			return;
	
//		add_action('admin_menu', array(&$this,'add_widget_box') );
		$this->add_widget_box();
		add_action ( 'edit_category_form_fields', array(&$this,'tag_widget_metabox') );
		add_action ( 'edit_tag_form_fields', array(&$this,'tag_widget_metabox') );
		add_action ( 'edited_terms', array(&$this,'save_tag_data') );
		
		/* Use the save_post action to save widget meta data */
		add_action('save_post', array(&$this,'save_widget_data') );
		add_action('save_page', array(&$this,'save_widget_data') );
		
	}

	function add_widget_box() {
		add_meta_box('shiba_widget_box', __('Widget'), array(&$this,'post_widget_metabox'), 'post', 'side', 'high');
		add_meta_box('shiba_widget_box', __('Widget'), array(&$this,'post_widget_metabox'), 'page', 'side', 'high');		
	}	

	function save_widget_data($post_id) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset($_POST['shiba_widget_noncename']) || !wp_verify_nonce( $_POST['shiba_widget_noncename'], 'shiba_widget'.$post_id )) {
			return $post_id;
		}
	
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
		// to do anything
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return $post_id;
	
		// Check permissions
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
			return $post_id;
		}
	
		// OK, we're authenticated: we need to find and save the data
	
		$post = get_post($post_id);
		if (($post->post_type == 'post') || ($post->post_type == 'page')) { 
			   // OR $post->post_type != 'revision'
			$widget = $_POST['post_widget'];
		   	wp_set_object_terms( $post_id, $widget, 'shiba_widget' );
			
			// store back relationship in widget set object
			$widget_id = is_term( $widget, 'shiba_widget' );	
			if (is_array($widget_id)) {
				$widget = get_term($widget_id['term_id'], 'shiba_widget');
				$obj_attachments = maybe_unserialize(get_metadata('shiba_term', $widget_id['term_id'], 'obj_attachments', TRUE));
				if (!is_array($obj_attachments)) $obj_attachments = array();
				$obj_attachments[] = $post_id;
				update_metadata('shiba_term', $widget_id['term_id'], 'obj_attachments', maybe_serialize($obj_attachments));
				
			}	
		}
		return $widget;
	}

	// This functions gets called in edit-form-advanced.php
	function post_widget_metabox($post) {
		global $wpdb, $shiba_widgets;

		echo '<input type="hidden" name="shiba_widget_noncename" id="shiba_widget_noncename" value="' . 
				wp_create_nonce( 'shiba_widget'.$post->ID ) . '" />';
		 
		 
		// Get all widget taxonomy terms
		$widgets = get_terms('shiba_widget', 'hide_empty=0'); 
		 
		?>
		<select name='post_widget' id='post_widget'>
			<!-- Display widgets as options -->
			<?php 
				$names = wp_get_object_terms($post->ID, 'shiba_widget'); 
				if (!is_wp_error($names) && !empty($names))
					$selected = $names[0]->name;
				else
					$selected = '';	
				echo $shiba_widgets->general->write_option('Default', '', $selected);
				foreach ($widgets as $widget) {
					if ($widget->name == 'Default') continue;
					echo $shiba_widgets->general->write_option($widget->name, $widget->name, $selected);
				}
		   ?>
		</select>
		<?php
	}


	function save_tag_data($term_id) {
		if (isset($_POST['tag_widget'])) {
			$tag_widget = esc_attr($_POST['tag_widget']);
			update_metadata('shiba_term', $term_id, 'shiba_widget', $tag_widget);
			
			// store back relationship in widget set object
			$widget_id = is_term( $tag_widget, 'shiba_widget' );	
			if (is_array($widget_id)) {
				$widget = get_term($widget_id['term_id'], 'shiba_widget');
				$term_attachments = maybe_unserialize(get_metadata('shiba_term', $widget_id['term_id'], 'term_attachments', TRUE));
				if (!is_array($term_attachments)) $term_attachments = array();
				$term_attachments[] = $term_id;
				update_metadata('shiba_term', $widget_id['term_id'], 'term_attachments', maybe_serialize($term_attachments));
				
			}	
		}	
	}
	
	
	function tag_widget_metabox($tag) {
		global $shiba_widgets;
		
		$selected = get_metadata('shiba_term', $tag->term_id, 'shiba_widget', TRUE);	

		// Get all widget sets
		$widgets = get_terms('shiba_widget', 'hide_empty=0'); 
	
	?>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="tag_widget"><?php _e('Widget') ?></label></th>
		<td>
		<select name='tag_widget' id='tag_widget'>
	
		<!-- Get all widgets and display them as options -->
		<?php
 		echo $shiba_widgets->general->write_option('Default', '', $selected);
        foreach ($widgets as $widget) {
			if ($widget->name == 'Default') continue;
 			echo $shiba_widgets->general->write_option($widget->name, $widget->name, $selected);
 		}
		?>
		</select>  
		 </td>
	</tr>
	
	<?php
	}
	
	function option_widget_metabox($action, $widgets, $options) {
		global $wpdb, $shiba_widgets;		 
		 
		// Get all widget taxonomy terms
		if (isset($options[$action]))
			$selected = $options[$action];
		else
			$selected = '';	
		 
		?>
		<select name='<?php echo $action;?>' id='option_widget_<?php echo $action;?>'>
			<!-- Display widgets as options -->
			<?php 
				echo $shiba_widgets->general->write_option('Default', '', $selected);
				foreach ($widgets as $widget) {
					if ($widget->name == 'Default') continue;
					echo $shiba_widgets->general->write_option($widget->name, $widget->name, $selected);
				}
		   ?>
		</select>
		<?php
	}
} // end class
endif;

	
?>