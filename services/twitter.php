<?php

abstract class List_Service_Twitter_Abstract extends List_Service_Abstract {

	protected $_username;
	
	protected $_password;

	public function __construct($username, $password, $time_to_live = 300) {
		$this->_username = $username;
		
		$this->_password = $password;
		
		$this->set_time_to_live($time_to_live);
	}
	
	public function id() {
		return 'List_Service_Twitter#' . $this->_username;
	}
	
	public function is_up_to_date() {
		return cache_url_time_remaining(null, $this->_time_to_live, $this->id()) > 0;
	}
	
	protected function _get_items() {
		$last_item = $this->_store->load_last_item($this);
		
		if($last_item) {
			/* Sync */
			$content = $this->_request_xml(sprintf($this->_api_update_url,
				urlencode($this->_username), urlencode($this->_password), $last_item->tweet_id()));
		} else {
			/* Initial import */
			$content = $this->_request_xml(sprintf($this->_api_initial_url, 
				urlencode($this->_username), urlencode($this->_password)));
		}
		
		return $this->_parse_xml($content);
	}
	
	protected function _request_xml($url) {
		$res = fopen_cache_url($url, $this->_time_to_live, $this->id());
		
		return stream_get_contents($res);
	}
	
	protected function _parse_xml($content) {
		$dom = simplexml_load_string($content);
		
		$tweets = array();
		
		foreach($dom->status as $status_node) {
			$tweet = new List_Service_Twitter_Item(
			 	'List_Service_Twitter#' . intval($status_node->id),
				strtotime(strval($status_node->created_at)));
			
			$tweet->set_my_screen_name($this->_username);
			
			$tweet->set_data(array(
				'tweet_id'		=> intval($status_node->id),
				'user_name' 	=> strval($status_node->user->name),
				'screen_name' 	=> strval($status_node->user->screen_name),
				'profile_img'	=> strval($status_node->user->profile_image_url),
				'text'			=> strval($status_node->text),
				'in_reply_to'	=> !empty($status_node->in_reply_to_status_id) ? array(intval($status_node->in_reply_to_status_id), strval($status_node->in_reply_to_screen_name)) : null));
				
			$tweets[] = $tweet;
		}
		
		return $tweets;
	}

	protected function _item_classname() {
		return 'List_Service_Twitter_Item';
	}

	public function import($item_id, $item_timestamp, $item_data) {
		$item = parent::import($item_id, $item_timestamp, $item_data);
		
		$item->set_my_screen_name($this->_username);
		
		return $item;
	}
	
}

class List_Service_Twitter_Friends extends List_Service_Twitter_Abstract {
	
	protected $_api_initial_url = 'http://%s:%s@twitter.com/statuses/friends_timeline.xml?count=200';
	
	protected $_api_update_url = 'http://%s:%s@twitter.com/statuses/friends_timeline.xml?since_id=%s';
	
}

class List_Service_Twitter_Replies extends List_Service_Twitter_Abstract {
	
	protected $_api_initial_url = 'http://%s:%s@twitter.com/statuses/replies.xml?count=200';
	
	protected $_api_update_url = 'http://%s:%s@twitter.com/statuses/replies.xml?since_id=%s';

	public function __construct($username, $password, $time_to_live = 600) {
		parent::__construct($username, $password, $time_to_live);
	}
}

/*
class List_Service_Twitter_Direct_Messages extends List_Service_Twitter_Abstract {
	
	protected $_api_initial_url = 'http://%s:%s@twitter.com/direct_messages.xml?count=200';
	
	protected $_api_update_url = 'http://%s:%s@twitter.com/direct_messages.xml?since_id=%s';
	
	public function __construct($username, $password, $time_to_live = 1800) {
		parent::__construct($username, $parent, $time_to_live);
	}
	
}
*/

class List_Service_Twitter_Item extends List_Service_Item_Abstract {
	
	protected $_my_screen_name;
	
	public function set_my_screen_name($screen_name) {
		$this->_my_screen_name = $screen_name;
	}
	
	public function tweet_id() {
		return $this->_data['tweet_id'];
	}
	
	public function draw() {
		return sprintf('<div class="twitter_com %5$s %8$s"><a href="http://twitter.com/%s" class="author name"><img src="%s" class="icon avatar" onerror="this.src=\'http://static.twitter.com/images/default_profile_normal.png\'">%s</a> %6$s &ldquo;%s&rdquo; %7$s</div>',
			$this->_data['screen_name'],
			$this->_data['profile_img'],
			$this->_am_i() ? 'Ik' : $this->_data['user_name'],
			$this->_parse_text($this->_data['text']),
			$this->_am_i() ? 'me' : '',
			$this->_am_i() ? 'twitter' : '',
			$this->_data['in_reply_to'] ? $this->_draw_in_reply_to() : '',
			$this->_is_reply() ? 'reply' : ''
		);
	}
	
	public function matches($query) {
		return stristr($query, 'service:twitter')
			|| stristr($query, $this->_data['screen_name'])
			|| stristr($query, $this->_data['user_name'])
			|| stristr($query, $this->_data['text']);
	}
	
	protected function _draw_in_reply_to() {
		return sprintf('als <a class="in_reply_to" href="http://twitter.com/%s/status/%d">reactie op %1$s</a>',
			$this->_data['in_reply_to'][1], $this->_data['in_reply_to'][0]);
	}
	
	protected function _e($x) {
		return htmlentities($x, ENT_QUOTES, 'UTF-8');
	}
	
	protected function _parse_text($x) {
		
		$x = preg_replace('{https?://[^\$\(\)\s\^!]*}i', '<a href="$0">$0</a>', $x);
		
		$x = preg_replace('{(^|\s)@([a-z0-9\_]+)}i', '$1<a class="name" href="http://twitter.com/$2">@$2</a>', $x);
		
		$x = preg_replace('{(?:^|\s)(www\.[^\$\(\)\s\^!]*)}i', '<a href="http://$1">$0</a>', $x);
		
		$x = preg_replace_callback('{#[a-z0-9\_]+}i', array($this, '_replace_twitter_theme'), $x);
		
		return $x;
	}
	
	protected function _replace_twitter_theme($matches) {
		return sprintf('<a class="theme" href="http://search.twitter.com/search?q=%s">%s</a>',
			urlencode($matches[0]), $matches[0]);
	}
	
	protected function _am_i() {
		return $this->_data['screen_name'] == $this->_my_screen_name;
	}

	protected function _is_reply() {
		return stristr($this->_data['text'], $this->_my_screen_name);
	}
}
