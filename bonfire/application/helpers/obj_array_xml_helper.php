<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('object_2_xml')){
	function object_2_xml($object, $xml_string,$level = 0){
		$next_level = $level;
		if(!is_string($object) && sizeof($object) > 0){
			foreach ($object as $key => $value) {
				$value = (is_object($value) ? get_object_vars($value) :  $value);
				if(isset($value['v@lue']) && $value['v@lue'] != ''){
					if(isset($value['c@ment_line'])){
						if(is_array($value['c@ment_line'])){
							foreach($value['c@ment_line'] as $comment_line){
								if($comment_line != '' && !is_array($comment_line)){
									$xml_string .= "\n".str_repeat("\t", $level).'<!-- '.htmlentities($comment_line).' -->';
								}
							}
						}else if($value['c@ment_line'] != ''){
							$xml_string .= "\n".str_repeat("\t", $level).'<!-- '.htmlentities($value['c@ment_line']).' -->';
						}
					}
					
					if($value['v@lue'] !== null && $value['v@lue'] !== ''){
						$attributes = (isset($value['@ttributes']) ? $value['@ttributes'] : null );
						if(function_exists('preg_replace')){
							$key = preg_replace('/^@@[0-9]+_\|/', '', $key);
						}else{
							$key = eregi_replace('/^@@[0-9]+_\|/', '', $key);
						}
						$xml_string .= "\n".str_repeat("\t", $level).'<'.$key.set_attributes_tags($attributes).'>';
						if(!is_string($value['v@lue']) && sizeof($value['v@lue']) > 0 && !is_numeric($value['v@lue'])){
							$next_level = $level + 1;
							$xml_string = object_2_xml($value['v@lue'], $xml_string, $next_level);
						}else{
							$next_level = $level;
							$escape = (isset($value['esc@pe']) ? $value['esc@pe'] : false  );
							if($escape){
								$xml_string .= '<![CDATA['.$value['v@lue'].']]>';
							}else{
								$xml_string .= $value['v@lue'];
							}
						}
						$xml_string .= ($next_level != $level ? "\n".str_repeat("\t", $level) : '' ).'</'.$key.'>';
					}
				}
			}
		}else{
			$xml_string = '';
		}
		return $xml_string;
	}
}

if(!function_exists('set_attributes_tags')){
 function set_attributes_tags($attributes){
  $tags_string = '';
  if(sizeof($attributes) > 0 ){
   foreach ($attributes as $key => $value) {
    $tags_string .= ' '.$key.'="'.$value.'"' ;
   }
  }
  return $tags_string;
 }
}

//========================================================================================================

if(!function_exists('get_content_from_path')){
	function get_content_from_path ($object = null, $path = null){
		$return = null;
		if($object && !is_null($path)){
			if(strpos($path, '/')){
				$path = explode('/',$path);
				$current_path = (is_numeric($path[0]) ? (float)$path[0] : (string)$path[0] );
			}else{
				$current_path = (is_numeric($path) ? (float)$path : (string)$path );
			}
			
			$object = (array)$object;
			if(isset($object[$current_path])){
				if(count($path) > 1){
					unset($path[0]);
					if(count($path) > 1){
						$path = implode('/',$path);
					}elseif(count($path) == 1){
						$path = $path[1];
					}else{
						return $object[$current_path];
					}
					
					if(!is_null($path)){
						$path = (string)$path;
						$return = get_content_from_path($object[$current_path], $path);
					}
				}else{
					$return = $object[$current_path];
				}
			}else{
				$return = null;
			}
		}
		return $return;
	}
}

//========================================================================================================

if(!function_exists('get_data_from_object')){
	function get_data_from_object($object = null, $data = null){
		$retun = null;
		if($object && $data){
			$object = (array)$object;
			if(is_list($object)){
				foreach($object as $key => $details){
					foreach($data as $field => $path){
						$retun[$key][$field] = get_content_from_path($details, $path);
					}
				}
			}else{
				foreach($data as $field => $path){
					$retun[0][$field] = get_content_from_path($object, $path);
				}
			}
		}
		return $retun;
	}
}

//========================================================================================================

if(!function_exists('is_list')){
	function is_list ($array){
		if (!is_array ($array))
		  return false;
			
		$keys = array_keys ($array);
		foreach ($keys as $key) {
			if (!is_numeric ($key))
				return false;
		}
		return true;
	}
}

//========================================================================================================

if(!function_exists('list_to_table')){
	//"list_to_table" converts an array list in a html table
	//$header build the "<th> layout"
	//$array is the data
	//$attributes is the optional table tag
	function list_to_table($header = array(),$array = array(), $attributes = array()){
		$return = false;
		if(is_array($array) && sizeof($array) > 0){
			$return = '<table cellpadding="0" cellspacing="0"';
			
			if(isset($attributes) && is_array($attributes) && sizeof($attributes) > 0){
				foreach($attributes as $k => $v){
					$return .= ' '.$k.'="'.$v.'" ';
				}
			}
			
			$return .= ">\n";
			
			if(is_array($header) && sizeof($header) > 0){
				$return .= "<tr>\n<th>";
				$return .= implode('</th><th>', $header);
				$return .= "</th>\n</tr>\n";
			}
			
			foreach($array as $v){
				$return .= "<tr>\n<td>";
				$return .= implode("</td>\n<td>", $v);
				$return .= "</td>\n</tr>\n";
			}
			
			$return .= '</table>';
		}
		return $return;
	}
}


//========================================================================================================

	/*
	 *@function equivalen_times_by_zone converts an time zone string from a determinate origin zone to another different, this function is based in a preview Edwin's version
	 *@param String $date_string is a valid string that represents a date
	 *@param String $zone_origin is the origin where we want to get the equivalent date
	 *@param String $zonde_destination is the destination date to get
	 *@param String $output_format is the format how we want to get the equivalent time, if it is missing, the unix time is retreive
	*/
	
if(!function_exists('equivalen_times_by_zone')){
	function equivalen_times_by_zone($date_string, $zone_origin, $zonde_destination, $output_format = false){
		$return = false;
		//We need at least 3 parametters to work
		if($date_string && $zone_origin && $zonde_destination){
			//If receibe a Unix Time get the formated date
			$date_string = (is_numeric($date_string) ? date('Y-m-d H:i:s', $date_string) : $date_string);
			//create a DateTime object using the $zone_origin
			$return = new DateTime($date_string, new DateTimeZone($zone_origin));
			if($return){
				//if the new DateTime was successful then try to convert
				$new_time_zone = new DateTimeZone($zonde_destination);
				if($return->setTimeZone($new_time_zone) !== false){
					//If it was possible return the date according with the output format
					if($output_format){
						//Return the time formatted
						$return = $return->format($output_format);
					}else{
						//Return Unix time
						$return = strtotime($return->format('Y-m-d H:i:s'));
					}
				}
			}
		}
		return $return;
	}
}

//========================================================================================================
//========================================================================================================
//========================================================================================================
//========================================================================================================
