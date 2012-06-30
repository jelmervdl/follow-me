<?php

class HTTP_Client {
	public function __construct() {
		
	}
	
	public function open($method, $url) {
		return new HTTP_Client_Request($this, $method, $url);
	}
}

class HTTP_Client_Request {
	
	protected $_method;
	
	protected $_defaults = array(
		'scheme'	=> 'http',
		'port'		=> 80,
		'path'		=> '/'
	);
		
	protected $_url;
	
	public $response;
	
	public function __construct(HTTP_Client $client, $method, $url) {
		$this->_method = $method;
		$this->_url = array_merge($this->_defaults, parse_url($url));
	}
	
	public function send($data) {
		extract($this->_url);
		
		$socket = fsockopen($host, $port);
		
		if(!$socket) throw new Exception('Socket failed to open');
		
		fwrite($socket, sprintf("%s %s HTTP/1.0\r\nHost: %s\r\nConnection: Close\r\n\r\n%s",
			$this->_method, $path, $host, $data));
		
		$this->response = HTTP_Client_Response::read($socket);
		
		fclose($socket);
	}
}

class HTTP_Client_Response {
	static public function read($socket) {
		$response = new self();
		
		while(!feof($socket)) {
			list($name, $value) = array_merge(explode(':', fgets($socket, 1024), 2), array('', ''));
			
			if(!trim($name)) break;
			
			if($value) {
				$response->headers[trim($name)] = trim($value);
			} else {
				$response->headers[] = trim($value);
			}
		}
		
		while(!feof($socket)) {
			$response->body .= fgets($socket, 1024);
		}
		
		return $response;
	}
	
	public $headers = array();
	
	public $body = '';
}