<?php

class List_Service_ICanLol extends List_Service_Abstract {
	
	protected $_url;
	
	protected $_time_to_live;
	
	public function __construct($url, $time_to_live = 3600) {
		$this->_url = $url;
		$this->set_time_to_live($time_to_live);
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
		
		$lols = array();
		
		foreach($rss->channel->item as $lol_node) {
			try {
				$keys = array('icon', 'image');
			
				$data = array();
			
				$data['pubdate'] = strtotime($lol_node->pubDate);
			
				$data['caption'] = strval($lol_node->title);
			
				$data['link'] = strval($lol_node->guid);
			
				$i = 0;
			
				foreach($lol_node->children('http://search.yahoo.com/mrss/') as $lol_media_node) {
					if($i >= 2) break;
					$attributes = $lol_media_node->attributes();
					$data[$keys[$i++]] = strval($attributes['url']);
				}
				
				if($i < 2) {
					throw new LogicException('Expected at least two images in the item');
				}
			
				$lol = new List_Service_ICanLol_Item(get_class($this) . '#' . md5($data['link']), $data['pubdate']);
			
				$lol->set_data($data);
			
				$lols[] = $lol;
			} catch(Exception $e) {
				// yeah, just skip the item, nothing to worry about
			}
		}
		
		return $lols;
	}

}

class List_Service_ICanLol_Item extends List_Service_Item_Abstract {
	
	public function draw() {
		return preg_replace_callback('{\$\{([a-z]+?)\}}', array($this, '_template_callback'),
			'<div class="icanlol"><img class="icon" src="${icon}" title="${caption}"><a href="${link}" data-quicklook-provider="icanlol" data-quicklook-description="${caption}" data-quicklook-href="${image}">${caption}</a></div>');
	}
	
	public function _template_callback($matches) {
		try {
			return htmlentities($this->_data[$matches[1]], ENT_QUOTES, 'UTF-8');
		} catch(ErrorException $e) {
			return '';
		}
	}
	
}
