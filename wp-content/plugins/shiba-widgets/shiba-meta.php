<?php
 
 // don't load directly
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_MetaData")) :

class Shiba_MetaData {

	// shiba_termmeta
 	function create_metadata_table($table_name, $type) {
		global $wpdb;

		if (!empty ($wpdb->charset))
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		if (!empty ($wpdb->collate))
			$charset_collate .= " COLLATE {$wpdb->collate}";
			
		  $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		  	meta_id bigint(20) NOT NULL AUTO_INCREMENT,
		  	{$type}_id bigint(20) NOT NULL default 0,
	
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
		   		
		  	UNIQUE KEY meta_id (meta_id)
		) {$charset_collate};";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
		
	
	function restore_old_data($table_name, $table_data, $table_fields) {
		global $wpdb;
		
		// nothing to write
		if (!is_array($table_data) || !is_array($table_fields) || empty($table_data) || empty($table_fields))
			return; 	
					
		$num_recs = count($table_data);
		$num_fields = count($table_fields); 
		
		$insert = "INSERT INTO {$table_name} ( ";

		for ($i = 0; $i < ($num_fields-1); $i++) { // less one for name, and another for the last element
			$insert = $insert . $table_fields[$i] . ', ';
		}
		$insert = $insert . $table_fields[$num_fields-1] . ") VALUES ";
		
		for ($j = 0; $j < $num_recs; $j++) {
			
			$insert = $insert . "( '";
			for ($i = 0; $i < $num_fields; $i++) {
				$insert = $insert . $table_data[$j][$i] . "','";
			}
			$insert = $insert . $table_data[$j][$num_fields];
			// replace the last , with );
			if ($j == ($num_recs-1)) 
				$insert = $insert . "');";
			else	
				$insert = $insert . "'),";
		}
		$results = $wpdb->query( $insert ); // results = # rows inserted if successful	
	}
	
	function metadata_install_type($type, $version = NULL) {
	   global $wpdb;
	
	   $table_name = $wpdb->prefix . $type . 'meta';

	   $installed_ver = get_option( $table_name );
	   if ( $version && ($installed_ver != $version)) {
			// get previous data			
			$sql = 'SELECT * FROM ' . $table_name;
			$previous_data = $wpdb->get_results($sql, ARRAY_N);

			$sql = "select COLUMN_NAME from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME = '{$table_name}'";
			$previous_fields = $wpdb->get_results($sql, ARRAY_N);
			
	   		// delete table and reinstall
			$wpdb->query("DROP TABLE " . $table_name);
			update_option($table_name, $version);
	   }
	   if ($wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'") != $table_name) {
		  
			$this->create_metadata_table($table_name, $type);
			// copy data from old table
			if ( $previous_data ) {
				$this->restore_old_data($table_name, $previous_data, $previous_fields);
			}
						
		}
	}
} // end class
endif;	
 ?>
