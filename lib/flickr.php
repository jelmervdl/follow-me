<?php

include_once 'lib/common.php';

class Flickr_API {
	
	protected $_api_url = 'http://api.flickr.com/services/rest/?method=%s&api_key=%s&%s';
	
	protected $_api_key;
	
	protected $_time_to_live;
	
	public function __construct($api_key) {
		$this->_api_key = $api_key;
		$this->_time_to_live = 1 * MINUTEN;
	}
	
	public function find_photo($photo_id) {
		$response = $this->_api_call('flickr.photos.getInfo', array('photo_id' => $photo_id));
		
		$document = simplexml_load_string($response);
		
		if(!strval($document['stat']) == 'ok') return false;
		
		$photo = Flickr_Photo::from_xml($document);
		
		return $photo;
	}
	
	protected function _api_call($method, $arguments) {
		$url = sprintf($this->_api_url, $method, $this->_api_key, http_build_query($arguments));
		
		$res = fopen_cache_url($url, $this->_time_to_live);
		
		return stream_get_contents($res);
	}
}

class Flickr_Photo {
	
	static public function from_xml($document) {
		$photo = new self();
		
		$photo_node = $document->photo;
		
		$photo->_id 		= strval($photo_node['id']);
		$photo->_secret 	= strval($photo_node['secret']);
		$photo->_farm		= intval($photo_node['farm']);
		$photo->_server		= strval($photo_node['server']);
		$photo->_title		= strval($photo_node->title);
		$photo->_description= strval($photo_node->description);
		$photo->_photopage_url = strval($photo_node->urls->url[0]);
		
		return $photo;
	}
	
	protected $_id;
	
	protected $_secret;
	
	protected $_farm;
	
	protected $_server;
	
	protected $_title;
	
	protected $_description;
	
	protected $_photopage_url;
	
	
	public function __construct() {
		
	}
	
	public function title() {
		return $this->_title;
	}
	
	public function description() {
		return $this->_description;
	}
	
	public function photopage_url() {
		return $this->_photopage_url;
	}
	
	public function image_url() {
		return sprintf('http://farm%d.static.flickr.com/%s/%s_%s.jpg?v=0',
			$this->_farm, $this->_server, $this->_id, $this->_secret);		
	}
}

function flickr_parse_url($url) {
	$url = explode('/', $url);
	
	foreach($url as $i => $component) {
		if($component == 'www.flickr.com' || $component == 'flickr.com') {
			$offset = $i;
			break;
		}
	}
	
	return $url[$offset + 3];
}