<?php

class List_Service_LastFM extends List_Service_Abstract {
	
	protected $_username;
	
	protected $_api_url = 'http://ws.audioscrobbler.com/1.0/user/%s/recenttracks.xml?images=true&limit=%d&widget_id=%s';
	
	public function __construct($username, $time_to_live = 240) {
		$this->_username = $username;
		$this->set_time_to_live($time_to_live);
	}
	
	public function id() {
		return get_class($this) . '#' . $this->_username;
	}
	
	public function is_up_to_date() {
		return cache_url_time_remaining(
			sprintf($this->_api_url, $this->_username, $this->_item_count(), config_lastfm_api_key()),
			$this->_time_to_live,
			$this->id()) > 0;
	}
		
	protected function _get_items() {
		$content = $this->_request_xml(sprintf($this->_api_url,
			$this->_username, $this->_item_count(), config_lastfm_api_key()));
		
		$items = $this->_parse_xml($content);
		
		return $items;
	}
	
	protected function _request_xml($url) {
		$res = fopen_cache_url($url, $this->_time_to_live, $this->id());
		
		return stream_get_contents($res);
	}
	
	protected function _parse_xml($content) {
		$dom = simplexml_load_string($content);
		
		$items = array();
		
		foreach($dom->track as $track) {
			$track_id = get_class($this) .'#'. md5(
				strval($track->artist) . '#' .
				strval($track->album) . '#' .
				strval($track->name) . '#' .
				strval($track->date['uts']));
			
			$item = new List_Service_LastFM_Item(
				$track_id, intval($track->date['uts']));
				
			$item->set_data(array(
				'artist'	=> strval($track->artist),
				'album'		=> strval($track->album),
				'track'		=> strval($track->name),
				'url'		=> strval($track->url),
				'image'		=> strval($track->image),
				'preview'	=> $track['streamable'] == 'true'
			));
			
			$items[] = $item;
		}
		
		return $items;
	}
	
	protected function _item_count() {
		$passed_time = cache_url_time_remaining(null, $this->_time_to_live, $this->id());
		
		if($passed_time < 10) {
			return 2;
		}
		elseif($passed_time < 60) {
			return 3;
		}
		elseif($passed_time < 1800) {
			return 10;
		}
		elseif($passed_time < 3600) {
			return 20;
		}
		else {
			return 30;
		}
	}
}

class List_Service_LastFM_Item extends List_Service_Item_Abstract {
	
	protected $_default_thumbnail = 'http://www.ikhoefgeen.nl/follow-me/last_fm.png';
	
	public function draw() {
		return sprintf('<div class="last_fm me"><img src="%s" class="icon album_art" onerror="this.src=\'assets/last_fm.png\'" title="%3$s van %2$s">Ik luister naar %7$s<a href="%5$s">%4$s</a> van <a href="%6$s">%2$s</a></div>',
			$this->_data['image'] ? $this->_data['image'] : $this->_default_thumbnail,
			$this->_e($this->_data['artist']),
			$this->_e($this->_data['album']),
			$this->_e($this->_data['track']),
			$this->_data['url'],
			$this->_artist_url(),
			$this->_data['preview'] ? $this->_draw_preview() : '');
	}
	
	protected function _draw_preview() {
		return sprintf('<img src="assets/control_play.png" title="Preview" class="preview" onclick="return LastFM.preview(this)" data-track="%s" data-artist="%s">',
			$this->_e($this->_data['track']), $this->_e($this->_data['artist']));
	}
	
	protected function _artist_url() {
		return preg_replace('{/_/.+}', '', $this->_data['url']) . '/';
	}
	
	protected function _e($x) {
		return htmlentities($x, ENT_QUOTES, 'UTF-8');
	}
	
}