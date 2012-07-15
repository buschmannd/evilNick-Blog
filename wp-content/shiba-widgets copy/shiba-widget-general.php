<?php
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if (!class_exists("Shiba_Widget_General")) :

class Shiba_Widget_General {
	
	// General base functions	
	function javascript_redirect($location) {
		// redirect after header here can't use wp_redirect($location);
		?>
		  <script type="text/javascript">
		  <!--
		  window.location= <?php echo "'" . $location . "'"; ?>;
		  //-->
		  </script>
		<?php
		exit;
	}
	
	function substring($str, $startPattern, $endPattern) {
			
		$pos = strpos($str, $startPattern);
		if($pos === false) {
			return "";
		}
	 
		$pos = $pos + strlen($startPattern);
		$temppos = $pos;
		$pos = strpos($str, $endPattern, $pos);
		$datalength = $pos - $temppos;
	 
		$data = substr($str, $temppos , $datalength);
		return $data;
	}
	
	function write_option($name, $value, $selected) {
		if ($selected && ($value == $selected)) 
			return "<option class='theme-option' value='" . $value . "' selected>" . $name . "</option>\n"; 
		else
			return "<option class='theme-option' value='" . $value . "'>" . $name . "</option>\n"; 
	}
	
	function write_array($name, $arr) {
		echo 'var '.$name.' =new Array("'.$arr[0].'"';
		for ($i = 1; $i < count($arr); $i++) {
			echo ',"' .$arr[$i].'"';
		}
		echo ');';
	}
	
	function create_anonymous_nonce($action = -1) {
		$i = wp_nonce_tick();
		return substr(wp_hash($i . $action . 0, 'nonce'), -12, 10);
	}
} // end Shiba_General class
endif;

?>