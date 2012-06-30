<?php

define('MINUTEN', 60);

define('UUR', 60 * MINUTEN);

define('DAG', 12 * UUR);

include 'lib/cache.php';

include 'lib/services.php';

function exceptions_error_handler($severity, $message, $filename, $lineno) {
	throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

set_error_handler('exceptions_error_handler');

/* Dit ding is kut, moet beter. En eigenlijk moet hij geport/verplaatst worden
 * naar main.js, javascript style, zodat de tijden tenminte mee updaten */
function relative_timestamp(List_Service_Item $item) {
	
	static $tijden;
	
	if(!$tijden) {
		$nu = time();
	
		$tijden = array(
			'nu'					=> array($nu + 3600,	$nu),
			'net'					=> array($nu, 			$nu - 60),
			'vijf minuten geleden'	=> array($nu - 60, 		$nu - 450),
			'tien minuten geleden'	=> array($nu - 450, 	$nu - 900),
			'half uur geleden'		=> array($nu - 900, 	$nu - 2100),
			'uur geleden'			=> array($nu - 2100, 	$nu - 4000),
			'vanavond'				=> array(mktime(24, 0, 0), mktime(18, 0, 0)),
			'vanmiddag'				=> array(mktime(18, 0, 0), mktime(12, 0, 0)),
			'vanochtend'			=> array(mktime(12, 0, 0),  mktime(7, 0, 0)),
			'vanacht'				=> array(
				mktime(7, 0, 0),
				mktime(24, 0, 0, date('n'), date('d') - 1)),
			'gisteravond'			=> array(
				mktime(24, 0, 0, date('n'), date('d') - 1),
				mktime(18, 0, 0, date('n'), date('d') - 1)),
			'gistermiddag'			=> array(
				mktime(18, 0, 0, date('n'), date('d') - 1),
				mktime(12, 0, 0, date('n'), date('d') - 1)),
			'gisterochtend'			=> array(
				mktime(12, 0, 0, date('n'), date('d') - 1),
				mktime(7,  0, 0, date('n'), date('d') - 1))
		);
	}
	
	$timestamp = $item->timestamp();

	foreach($tijden as $format => $range) {		
		if($timestamp < $range[0] && $timestamp > $range[1]) {
			return $format;
		}
	}

	return date('d-m-Y \o\m H:i', $timestamp);
}

/* Verandert een hiërarchie van complexe objecten met standaard getters,
 * dat zijn getters met $name die de propertie _$name representeren,
 * omzet in een setje stdClass instanties
 */

function simplify_object($instance) {
	if(is_array($instance)) {
		foreach($instance as $key => $value) {
			$instance[$key] = simplify_object($value);
		}
		
		return $instance;
	}
	elseif(is_object($instance)) {
		$object = new ReflectionClass($instance);
	
		$data = new stdClass();
	
		foreach($object->getProperties() as $property) {
			$method_name = substr($property->getName(), 1);
			if($object->hasMethod($method_name)) {
				$method = $object->getMethod($method_name);
				$value = $method->invoke($instance);
			
				$value = simplify_object($value);
			
				$data->$method_name = $value;
			}
		}
	
		return $data;
	}
	else {
		return $instance;
	}
}

function json_export($data) {
	return json_encode(simplify_object($data));
}

/* Waarom nu weer een aparte functie om script-tags te schrijven?!
 * Omdat deze even de mtime in de url zet, zodat ik niet meer op m'n
 * reload-knopje en shift-knopje hoef te hameren en hopen dat het goed komt */
function include_js($path) {
	printf('<script type="text/javascript" src="%s?%d"></script>',
		htmlentities($path, ENT_QUOTES),
		filemtime($path));
}

/* iPhone detectie, zodat we de emoji-parser uit kunnen schakelen */
$iphone_os_version = false;

if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('{CPU iPhone OS (\d+)_(\d+)}', $_SERVER['HTTP_USER_AGENT'], $matches)) {
	$iphone_os_version = $matches[1] + ($matches[2] / 10);
}
