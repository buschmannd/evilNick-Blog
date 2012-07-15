<?php
/*
Plugin Name: Shiba Widgets
Plugin URI: http://shibashake.com/wordpress-theme/wordpress-custom-widgets-plugin
Description: This plugin allows you to create a variety of widget configurations and attach them to individual posts and pages, or to categories and tags. Free yourself from being tied to a single set of widgets for your entire blog.
Version: 1.2.2
Author: ShibaShake
Author URI: http://shibashake.com
*/


/*  Copyright 2010  ShibaShake

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// don't load directly
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );


define( 'SHIBA_WIDGET_DIR', WP_PLUGIN_DIR . '/shiba-widgets' );
define( 'SHIBA_WIDGET_URL', WP_PLUGIN_URL . '/shiba-widgets' );

require(SHIBA_WIDGET_DIR."/shiba-widget-general.php");
require(SHIBA_WIDGET_DIR."/shiba-widget-helper.php");
require(SHIBA_WIDGET_DIR."/shiba-widget-style.php");
require(SHIBA_WIDGET_DIR."/shiba-widget-mcol.php");
require(SHIBA_WIDGET_DIR."/shiba-widget-propagate.php");

if (!class_exists("Shiba_Widgets")) :


class Shiba_Widgets {
	var $general, $helper, $style, $mcol, $propagate;
	var $manage, $metabox, $metadata;
	var $add_widget, $manage_widget, $widget_options;
	var $debug = FALSE;
								
	function Shiba_Widgets() {
		if (class_exists("Shiba_Widget_General")) 
			$this->general = new Shiba_Widget_General();	
		if (class_exists("Shiba_Widget_Helper")) 
			$this->helper = new Shiba_Widget_Helper();	
		if (class_exists("Shiba_Widget_Style")) 
    		$this->style = new Shiba_Widget_Style();	
		if (class_exists("Shiba_Multi_Column_Class")) 
    		$this->mcol = new Shiba_Multi_Column_Class();	
		if (class_exists("Shiba_Widget_Propagate")) 
    		$this->propagate = new Shiba_Widget_Propagate();	

		add_action('admin_init', array(&$this,'init_admin') );
		add_action('admin_menu', array(&$this,'add_pages') );
		add_action('init', array(&$this,'init') );
		
		register_activation_hook( __FILE__, array(&$this,'activate') );
		register_deactivation_hook( __FILE__, array(&$this,'deactivate') );
	}
	
	
	function activate() {
		if (!is_taxonomy('shiba_widget')) {
			register_taxonomy( 'shiba_widget', 'shiba_post', 
								array( 	'hierarchical' => false, 'label' => __('Widget'), 'query_var' => 'shiba_widget', 
										'rewrite' => array( 'slug' => 'shiba_widget' ) ) );
			// Add a general default widget
			$this->add_default_widget();
		}

		// Create term metadata table if necessary
		require(SHIBA_WIDGET_DIR."/shiba-meta.php");
		if (class_exists("Shiba_MetaData")) {
			$this->metadata = new Shiba_MetaData();	
			$this->metadata->metadata_install_type('shiba_term');
		}
//		delete_option('shiba_widget_1_0_update');
	}

	function deactivate() {
		// set sidebar_widgets option to Default widget entry
		$widget_id = is_term( 'Default', 'shiba_widget' );	
		if (is_array($widget_id)) {
			$widget = get_term($widget_id['term_id'], 'shiba_widget');
			$default_widgets = maybe_unserialize($widget->description);
			if (is_array($default_widgets))
				wp_set_sidebars_widgets($default_widgets);
		}	
	}
	
	function init_admin() {	
		require(SHIBA_WIDGET_DIR.'/shiba-widget-metabox.php');
		require(SHIBA_WIDGET_DIR.'/shiba-manage-class.php');
		if (class_exists("Shiba_Widget_Manage"))
			$this->manage = new Shiba_Widget_Manage();	
		if (class_exists("Shiba_Widget_MetaBox"))
			$this->metabox = new Shiba_Widget_MetaBox();	


		if ( isset($_GET['activate']) && isset($_GET['plugin_status']) && isset($_GET['paged']) && 
			($_GET['plugin_status'] == 'all') && ($_GET['paged'] == 1) &&
			(strpos($_SERVER['REQUEST_URI'],'wp-admin/plugins.php') !== FALSE)) {
			// Run activate script
			require(SHIBA_WIDGET_DIR.'/shiba-widget-activate.php');
			if (class_exists("Shiba_Widget_Activate")) {
				$activate = new Shiba_Widget_Activate();
				$activate->update_version_1_0();
			}
		}	

		// for quick edit admin-ajax _tag_row				
		add_filter("manage_edit-shiba_widget_columns", array(&$this->manage,'widget_columns'));	
		// for manage widget page _tag_row					
		add_filter("manage_appearance_page_manage_widget_columns", array(&$this->manage,'widget_columns'));
		// for old style manage widget page
		add_filter("manage_edit-tags_columns", array(&$this->manage,'widget_columns'));
		add_filter("manage_shiba_widget_custom_column", array(&$this->manage,'manage_widget_columns'), 10, 3);

		// Style object
		add_action( 'in_widget_form', array(&$this->style,'style_form'), 10, 3); 
		add_filter( 'widget_update_callback', array(&$this->style,'update_form'), 10, 4);
		// Propagate object
		add_action( 'in_widget_form', array(&$this->propagate,'style_form'), 10, 3); 
		add_filter( 'widget_update_callback', array(&$this->propagate,'update_form'), 10, 4);
	}


	function init() {	
		global $wpdb;
		$wpdb->shiba_termmeta = $wpdb->prefix . 'shiba_termmeta';
		
		if (!is_taxonomy('shiba_widget')) {
			register_taxonomy( 'shiba_widget', 'shiba_post', 
								array( 	'hierarchical' => false, 'label' => __('Widget'), 'query_var' => 'shiba_widget', 
										'rewrite' => array( 'slug' => 'shiba_widget' ) ) );
		}

		if (is_admin()) return;	
		add_filter('sidebars_widgets', array(&$this,'sidebars_widgets')); 
		wp_enqueue_style('shiba-widgets', SHIBA_WIDGET_URL . '/shiba-widgets.css');	
		// Style object
		add_filter( 'dynamic_sidebar_params', array(&$this->style,'add_styles')); 
	}

	function add_pages() {	
		// Add a new submenu under Options:
		$this->add_widget = add_theme_page(	'Create Widget', 'Create Widget', 'administrator', 'add_widget', 
										array(&$this,'add_widget') );
		$this->manage_widget = add_theme_page(	'Manage Widgets', 'Manage Widgets', 'administrator', 'manage_widget', 
											array(&$this,'manage_widget') );
		$this->widget_options = add_theme_page(	'Widget Options', 'Widget Options', 'administrator', 'widget_options', 
											array(&$this,'widget_options') );
		
		add_action("admin_print_styles-$this->add_widget", array(&$this,'add_widget_admin_styles') );
		add_action("admin_print_scripts-$this->add_widget", array(&$this,'add_widget_admin_scripts') );
		add_action("admin_print_scripts-$this->manage_widget", array(&$this,'manage_widget_admin_scripts') );	
	}

	function add_widget_admin_styles() {
		wp_enqueue_style( 'widgets' );
	}
	
	function add_widget_admin_scripts() {
		wp_enqueue_script( 'admin-widgets' );
		wp_enqueue_script('admin-tags');
		wp_enqueue_script('inline-edit-tax');
	}

	function manage_widget_admin_scripts() {
		wp_enqueue_script('inline-edit-tax');
	}

	function manage_widget() {
		include('shiba-edit-widgets.php');
	}
	function add_widget() {
		include('shiba-widget-new.php');
	}
	
	function widget_options() {
		include('shiba-widget-options.php');
	}
	
	function print_debug($str) {
		if ($this->debug)
			echo "<!-- DEBUG: " . $str . " -->\n";
	}
	
			
	function get_post_widget($id) {
		$names = wp_get_object_terms($id, 'shiba_widget');
		if (is_array($names) && isset($names[0]) && is_object($names[0])) 
			return $names[0]->name;
	}
	
	function get_tag_widget($id) {
		$widget = get_metadata('shiba_term', $id, 'shiba_widget', TRUE);	
		return $widget;
	}

	function get_widget() {
		global $wp_query;
		$widget = NULL;
		$options = get_option('shiba_widget_options');
		if (!is_array($options)) $options = array();
					
		// Get post widget
		$widget = apply_filters('pre_shiba_get_widget', $widget, $wp_query->post);
		if ($widget) return $widget;
		
		if ( (is_single() || is_page()) && $wp_query->post ) {
			$widget = $this->get_post_widget($wp_query->post->ID);
			if ($widget) return $widget;
		
			// inherit widget from parent page
			if (is_page() && isset($options['inherit_parent'])) {
				$curID = $wp_query->post->post_parent;
				$i = 0;
				while ($curID) {
					$post = get_post($curID);
					$widget = $this->get_post_widget($post->ID);
					if ($widget) return $widget;
					$curID = $post->post_parent;
					if ($i > 10) break;	// only go 10 deep in case there is a loop
				}				
			}	
					
			// Get category widget
			$category = get_the_category($wp_query->post->ID); 
			for ($i = 0; $i < count($category); $i++) {
				$widget = $this->get_tag_widget($category[$i]->term_id);
				if ($widget) return $widget;
			}
			
			// Get tag widget
			$tags = wp_get_object_terms($wp_query->post->ID, 'post_tag');
			foreach ($tags as $tag) {
				$widget = $this->get_tag_widget($tag->term_id);
				if ($widget) return $widget;
			}
		} // end is_single

		if (is_tax() || is_category() || is_tag()) {
			$term = $wp_query->get_queried_object();
			$widget = $this->get_tag_widget($term->term_id);
			if ($widget) return $widget;
		}
		
		// Check for blog wide conditions
		if (is_front_page() && isset($options['frontpage'])) return $options['frontpage'];
		if (is_404() && isset($options['404'])) return $options['404'];
		if (is_search() && isset($options['search'])) return $options['search'];
		if (is_single() && isset($options['single'])) return $options['single'];
		if (is_page() && isset($options['page'])) return $options['page'];
		if (is_attachment() && isset($options['attachment'])) return $options['attachment'];
		if (is_category() && isset($options['category'])) return $options['category'];
		if (is_tag() && isset($options['tag'])) return $options['tag'];

		$widget = 'Default';
		$widget = apply_filters('post_shiba_get_widget', $widget, $wp_query->post);
		
		// Return the default widget
		return $widget;	
	}

	function get_widget_id() {
		$widget = $this->get_widget();
		
		$widget_id = is_term( $widget, 'shiba_widget' );	
		if (is_array($widget_id))
			return $widget_id['term_id'];
		else { // assigned widget has been deleted - return default widget id
			$widget_id = is_term( 'Default', 'shiba_widget' );
			if (!is_array($widget_id)) { // Default widget has been deleted - create a new one
				$widget_id = $this->add_default_widget();
			}	
			return $widget_id['term_id'];	
		}	
	}
	


	function add_default_widget() {
		// Add a general default widget
		$widget_id = is_term( 'Default', 'shiba_widget' );
		if (!is_array($widget_id)) {
			// Create empty default widget set - we don't want to get current sidebars because then it will be duplicate instances
			$default_widgets = serialize(wp_get_widget_defaults());
			$result =  wp_insert_term('Default', 'shiba_widget', array('description' => $default_widgets));
			return $result;
		} else
			return $widget_id;	
	}

	/* And we return the widgets tied to the current object rather than the global default stored in the sidebars_widget option. Must also set up a default widget object since the global default 'sidebars_widget' option now is no longer valid. Instead the option now only contains temporary results of the last edit.
	*/	
	function sidebars_widgets($sidebars_widgets) {
		static $widget_cache = array();
		
		// deliver widgets that is currently tied to the page
		$widget_id = $this->get_widget_id();
		if (isset($widget_cache[$widget_id])) return $widget_cache[$widget_id];
		
		$widget = get_term($widget_id, 'shiba_widget');
		$new_widgets = maybe_unserialize($widget->description);
		$widget_cache[$widget_id] = $new_widgets;
		
		// return widget sidebars - contained in 'description' field of taxonomy
		return $new_widgets;
	}
	
} // end class
endif;

global $shiba_widgets;
if (class_exists("Shiba_Widgets") && !$shiba_widgets) {
    $shiba_widgets = new Shiba_Widgets();	
}		
?>