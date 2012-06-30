<?php

class List_Service_RSS extends List_Service_Abstract {
	
	protected $_url;
	
	protected $_time_to_live;
	
	public function __construct($url, $time_to_live = 3600, array $defaults = array()) {
		$this->_url = $url;
		$this->set_time_to_live($time_to_live);
		
		$this->_defaults = $defaults;
	}
	
	public function id() {
		return get_class($this) . '#' . md5($this->_url);
	}
	
	public function is_up_to_date() {
		return cache_url_time_remaining($this->_url, $this->_time_to_live, $this->id()) > 0;
	}
	
	protected function _get_items() {
		$content = $this->_request_xml($this->_url);
		
		$headlines = $this->_parse_xml($content);
		
		return $headlines;
	}
	
	protected function _request_xml($url) {
		$res = fopen_cache_url($url, $this->_time_to_live, $this->id());
		
		return stream_get_contents($res);
	}
	
	protected function _parse_xml($content) {
		/* Soms gaat het fout en dan zit er rommel voor en na de xml elementen */
		$offset_start = strpos($content, '<');
		
		$offset_end = strrpos($content, '>') + 1;
		
		$content_length = $offset_end - $offset_start;
		
		$content = substr($content, $offset_start, $content_length);
		
		if(!($rss = simplexml_load_string($content))) {
			return array(); // helaas, geen inhoud vandaag
		}
		
		$source_url = strval($rss->channel->link);
		
		$source_name = strval($rss->channel->title);
		
		$source_logo = isset($rss->channel->image) ? strval($rss->channel->image->url) : null;
		
		$headlines = array();
		
		foreach($rss->channel->item as $item_node) {
			$headline = new List_Service_RSS_Item(
				get_class($this) . '#' . md5(strval($item_node->link)),
				strtotime($item_node->pubDate));
			
			$headline->set_defaults($this->_defaults);
			
			$headline->set_data(array(
				'source_url'	=> $this->_or($source_url, null),
				'source_name'	=> $this->_or($source_name, null),
				'source_logo'	=> $this->_or($source_logo, null),
				'title'	=> $this->_or(strval($item_node->title), null),
				'author'		=> $this->_or(strval($item_node->author), null),
				'link'			=> strval($item_node->link),
				'description'	=> $this->_or(strval($item_node->description), null)
			));
			
			$headlines[] = $headline;
		}
		
		return $headlines;
	}
	
	public function import($item_id, $item_timestamp, $item_data) {
		$headline = parent::import($item_id, $item_timestamp, $item_data);
		
		$headline->set_defaults($this->_defaults);
		
		return $headline;
	}
	
	protected function _or($a, $b) {
		return empty($a) ? $b : $a;
	}
}

class List_Service_RSS_Item extends List_Service_Item_Abstract {
	
	protected $_defaults = array();
	
	public function set_defaults(array $defaults) {
		$this->_defaults = $defaults;
	}
	
	public function draw() {
		return sprintf('<div class="rss">%s%s &ldquo;<a href="%s">%s</a>&rdquo; op <a href="%s">%s</a></div>',
			$this->_data('source_logo') ? sprintf('<img src="%s" class="icon logo">', $this->_data('source_logo')) : '',
			$this->_data('author') ? $this->_e($this->_data('author')) . ' schrijft' : '',
			$this->_data('link'),
			$this->_e($this->_data('title')),
			$this->_data('source_url'),
			$this->_e($this->_data('source_name')));
	}
	
	protected function _data($key) {
		if(isset($this->_defaults[$key])) {
			return $this->_defaults[$key];
		} elseif(isset($this->_data[$key])) {
			return $this->_data[$key];
		} else {
			return null;
		}
	}
	
	protected function _e($x) {
		return htmlentities($x, ENT_QUOTES, 'UTF-8');
	}
	
}
