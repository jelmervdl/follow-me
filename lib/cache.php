<?php

if(!defined('FOPEN_CACHE_TEMP_PATH')) {
	define('FOPEN_CACHE_TEMP_PATH', dirname(__FILE__) . '/../tmp/http_%s');
}

function get_head_headers($url) {
	
	$url = parse_url($url);
	
	$socket = fsockopen($url['host'], 80, $error_no, $error_str, 3);
	
	if(!$socket) {
		return false;
	}
	
	$request = sprintf("HEAD %s HTTP/1.1\r\nHost: %s\r\nConnection: Close\r\n\r\n",
		$url['path'], $url['host']);
	
	fwrite($socket, $request);
	
	$response = '';
	
	while(!feof($socket)) {
		$response .= fgets($socket, 128);
	}
	
	fclose($socket);
	
	$headers = array();
	
	foreach(explode("\n", $response) as $header) {
		$header = explode(':', $header, 2);
		
		if(count($header) == 2) {
			list($name, $value) = $header;
			$headers[trim($name)] = trim($value);
		} else {
			$headers[] = $header[0];
		}
	}
	
	return $headers;
}

function fopen_cache_url($url, $ttl, $id = null) {
	$cache_file = sprintf(FOPEN_CACHE_TEMP_PATH, md5($id ? $id : $url));
	
	$refresh_required = true;
	
	if(file_exists($cache_file)) {
		if(filemtime($cache_file) + $ttl >= time()) {
			$refresh_required = false;
		} else {			
			$headers = get_head_headers($url, true);
			
			/* Wanneer de remote site offline is, dan hoeft de cache niet herladen te worden */
			if(!$headers) {
				$refresh_required = false;
			}
			elseif(array_key_exists('Last-Modified', $headers)) {
				$server_last_modified = strtotime($headers['Last-Modified']);
				$cache_last_modified = filemtime($cache_file);
				if($cache_last_modified >= $server_last_modified) {					
					@touch($cache_file);
					$refresh_required = false;
				}
			}
		}
	}
	
	if($refresh_required) {
		if(!@copy($url, $cache_file)) {
			return fopen($url, 'r');
		}
	}
	
	return fopen($cache_file, 'r');
}

function cache_url_time_remaining($url, $ttl, $id = null) {
	$cache_file = sprintf(FOPEN_CACHE_TEMP_PATH, md5($id ? $id : $url));
	
	if(!file_exists($cache_file)) {
		return -1;
	} else {
		return filemtime($cache_file) + $ttl - time();
	}
}
/* Unused 
class KeyValueCache {
	
	protected $_pdo;
	
	public function __construct(PDO $pdo) {
		$this->_pdo = $pdo;
	}
	
	public function set($key, $value, $ttl) {
		$stmt = $this->_pdo->prepare('INSERT OR REPLACE INTO stored_values (key_name, php_data, expiration) VALUES(?, ?, ?)');
		
		$stmt->execute($key);
	}
	
	protected function _create_tables() {
		$this->_pdo->exec('
			CREATE TABLE stored_values (
				key_name VARCHAR(100),
				php_data TEXT,
				expiration INT,
				CONSTRAINT key_name PRIMARY KEY
			)
		');
	}
}
*/