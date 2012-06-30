<?php

/* services */

interface List_Service {
	public function id();
	public function update();
	public function get_stream();
	public function time_to_live();
	public function is_up_to_date();
	public function last_timestamp();
	
	public function import($id, $timestamp, $serialized_data);
	public function export(List_Service_Item $item);
}

abstract class List_Service_Abstract implements List_Service, List_Service_Stream_Delegate, List_Service_Uses_Shared_Store {
	
	protected $_time_to_live = 300;
	
	protected function _item_classname() {
		return sprintf('%s_Item', get_class($this));
	}
	
	public function set_time_to_live($ttl) {
		$this->_time_to_live = (int) $ttl;
	}
	
	/* Implementing List_Service_Uses_Shared_Store */
	public function set_shared_store(List_Service_Item_Store $store) {
		$this->_store = $store;
	}
	
	/* Implementing List_Service */	
	public function update() {
		foreach($this->_get_items() as $item) {
			$this->_store->save($this, $item);
		}
	}
	
	public function get_stream() {
		return new List_Service_Stream_Adaptor($this);
	}
	
	public function time_to_live() {
		return (int) $this->_time_to_live;
	}
	
	public function last_timestamp() {
		return $this->_store->last_timestamp($this);
	}
	
	public function import($id, $timestamp, $serialized_data) {
		$item_classname = $this->_item_classname();
		
		$item = new $item_classname($id, $timestamp);
		
		assert('$item instanceof List_Service_Item_Abstract');
		
		$item->set_data(unserialize($serialized_data));
		
		return $item;
	}
	
	public function export(List_Service_Item $item) {
		assert('$item instanceof List_Service_Item_Abstract');
		
		$data = $item->data();
		
		return serialize($data);
	}
	
	/* Implementing List_Service_Stream_Delegate */
	public function stream_get_items($offset, $count) {
		return $this->_store->load_items($this, (int) $offset, (int) $count);
	}
}

/* item streams */

interface List_Service_Stream {
	public function peek();
	public function pop();
	public function seek($distance);
	public function add_filter(List_Service_Filter $filter);
}

abstract class List_Service_Stream_Abstract implements List_Service_Stream {
	
	protected $_buffer = array();
	
	protected $_filters = array();
	
	protected $_index = -1;
	
	public function peek() {
		while($this->_has_next()) {
			$choice = $this->_buffer[$this->_index + 1];
		
			if(!$this->_is_valid($choice)) {
				$this->_index++;
			} else {
				return $choice;
			}
		}
		
		return false;
	}
	
	public function pop() {
		$item = $this->peek();
		
		$this->_index++;
		
		return $item;
	}
	
	public function seek($distance) {
		while($distance--> 0) {
			if($this->pop() === false) return;
		}
	}
	
	public function add_filter(List_Service_Filter $filter) {
		$this->_filters[] = $filter;
	}
	
	protected function _is_valid($choice) {
		
		foreach($this->_filters as $filter) {
			if(!$filter->test($choice)) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function _has_next() {
		if(!isset($this->_buffer[$this->_index + 1])) {
			$this->_load_items();
		}
		
		return isset($this->_buffer[$this->_index + 1]) && $this->_buffer[$this->_index + 1] != false;
	}
	
	abstract protected function _load_items();
}

class List_Service_Stream_Array extends List_Service_Stream_Abstract {
	
	public function __construct(array $items) {
		$this->_buffer = array_values($items);
		
		usort($this->_buffer, 'List_Service_Sort');
		
		$this->_buffer[] = false;
	}
	
	protected function _load_items() {
		return null;
	}
	
}

class List_Service_Stream_Combined implements List_Service_Stream, List_Service_Filter {
	
	protected $_streams = array();
	
	protected $_filters = array();
	
	protected $_item_ids = array();
	
	public function __construct() {
		$this->_filters[] = $this;
	}
	
	public function add_stream(List_Service_Stream $stream) {
		$this->_streams[] = $stream;
		
		// En even de filters synchroniseren
		foreach($this->_filters as $filter) {
			$stream->add_filter($filter);
		}
	}
	
	public function peek() {	
		$newest_item_stream = $this->_stream_of_topmost_item();
		
		return $newest_item_stream ? $newest_item_stream->peek() : false;
	}
	
	public function pop() {
		$newest_item_stream = $this->_stream_of_topmost_item();

		$item = $newest_item_stream ? $newest_item_stream->pop() : false;
		
		if($item) $this->_item_ids[] = $item->id();
		
		return $item;
	}
	
	public function seek($distance) {
		while($distance--> 0) {
			if($this->pop() === false) return;
		}
	}
	
	public function add_filter(List_Service_Filter $filter) {
		$this->_filters[] = $filter;
		
		foreach($this->_streams as $stream) {
			$stream->add_filter($filter);
		}
	}
	
	public function test(List_Service_Item $item) {
		
		if(in_array($item->id(), $this->_item_ids, true)) {
			return false;
		}
		
		return true;
	}
	
	protected function _stream_of_topmost_item() {
		$newest_item_timestamp = 0;
		$newest_item_stream = false;
		
		foreach($this->_streams as $stream) {
			$stream_item = $stream->peek();
			
			if(!$stream_item) continue;
			
			if($stream_item->timestamp() > $newest_item_timestamp) {
				$newest_item_timestamp = $stream_item->timestamp();
				$newest_item_stream = $stream;
			}
		}
		
		return $newest_item_stream;
	}
}

class List_Service_Stream_Adaptor extends List_Service_Stream_Abstract {
	
	protected $_delegate;
	
	protected $_items_at_a_time = 50;
	
	public function __construct(List_Service_Stream_Delegate $delegate) {
		$this->_delegate = $delegate;
	}
	
	protected function _load_items() {
		$items = $this->_delegate->stream_get_items($this->_index + 1, $this->_items_at_a_time);
		
		foreach($items as $item) {
			$this->_buffer[] = $item;
		}
		
		/* Einde van de stream aangeven */
		if(count($items) < $this->_items_at_a_time) {
			$this->_buffer[] = false;
		}
	}
	
}

interface List_Service_Stream_Delegate {
	public function stream_get_items($offset, $count);
}

/* items */

interface List_Service_Item {
	public function id();
	public function timestamp();
	public function draw();
	public function matches($query);
}

abstract class List_Service_Item_Abstract implements List_Service_Item {
	
	protected $_id;
	
	protected $_timestamp;
	
	protected $_data;
	
	public function __construct($id, $timestamp) {
		$this->_id = $id;
		$this->_timestamp = (int) $timestamp;
	}
	
	public function id() {
		return $this->_id;
	}
	
	public function timestamp() {
		return $this->_timestamp;
	}
	
	public function data() {
		return $this->_data;
	}
	
	public function set_data($data) {
		$this->_data = $data;
	}
	
	public function matches($query) {
		foreach($this->_data as $key => $value) {
			if(is_string($value) && stristr($value, $query)) return true;
		}
		
		return false;
	}
}

/* item decorators */

interface List_Service_Item_Decorator {
	public function decorate(List_Service_Item $item, $html);
}

class List_Service_Item_Timestamp implements List_Service_Item_Decorator {
	public function decorate(List_Service_Item $item, $html) {
		return sprintf("<span class=\"timestamp\">%s</span>\n%s",
			relative_timestamp($item), $html);
	}
}

class List_Service_Item_Star implements List_Service_Item_Decorator {
	
	public function decorate(List_Service_Item $item, $html) {
		return sprintf("<a class=\"mark\" href=\"marked.php?id=%s\">%s</a>\n%s",
			$item->id(), $this->_is_marked($item) ? 'is marked' : 'mark', $html);
	}
	
	protected function _is_marked(List_Service_Item $item) {
		return false;
	}
}

function List_Service_Item_decorate(List_Service_Item $item, array $decorators) {
	
	$html = $item->draw();
	
	foreach($decorators as $decorator) {
		
		assert('$decorator instanceof List_Service_Item_Decorator');
		
		$html = $decorator->decorate($item, $html);
	}
	
	return $html;
}

/* item stores */

interface List_Service_Uses_Shared_Store {
	public function set_shared_store(List_Service_Item_Store $store);
}

class List_Service_Item_Store {
	
	protected $_pdo;
	
	protected $_pdo_insert_stmt;
	
	public function __construct(PDO $pdo) {
		$this->_pdo = $pdo;
	}
	
	public function last_timestamp(List_Service $service) {
		$stmt = $this->_pdo->prepare('
			SELECT
				MAX(item_timestamp) as last_timestamp
			FROM
				list_service_stored_items
			WHERE
				service_id = :service_id
		');
		
		$stmt->bindValue(':service_id', $service->id());
		
		$stmt->execute();
		
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!$data) return 0;
		
		return (int) $data['last_timestamp'];
	}	
	
	public function save(List_Service $service, List_Service_Item $item) {
		if(!$this->_pdo_insert_stmt) {
			$this->_pdo_insert_stmt = $this->_pdo->prepare('
				REPLACE INTO list_service_stored_items (
					service_id,
					item_id,
					item_timestamp,
					item_data
				) VALUES (
					:service_id,
					:item_id,
					:item_timestamp,
					:item_data
				)');
		}
		
		$stmt = $this->_pdo_insert_stmt;
		
		$stmt->bindValue(':service_id', $service->id());
		$stmt->bindValue(':item_id', $item->id());
		$stmt->bindValue(':item_timestamp', $item->timestamp());
		$stmt->bindValue(':item_data', $service->export($item));
		
		return $stmt->execute();
	}
	
	public function load_item(List_Service $service, $item_id) {
		$stmt = $this->_pdo->prepare('
			SELECT
				item_id,
				item_timestamp,
				item_data
			FROM
				list_service_stored_items
			WHERE
				service_id = :service_id AND
				item_id = :item_id
		');
		
		$stmt->bindValue(':service_id', $service->id());
		$stmt->bindParam(':item_id', $item_id);
		
		$stmt->execute();
		
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!$data) return null;
		
		$item = $service->import(
			$data['item_id'],
			(int) $data['item_timestamp'],
			$data['item_data']);
			
		assert('$item instanceof List_Service_Item');
		
		return $item;
	}
	
	public function load_last_item(List_Service $service) {
		$stmt = $this->_pdo->prepare('
			SELECT
				item_id,
				item_timestamp,
				item_data
			FROM
				list_service_stored_items
			WHERE
				service_id = :service_id
			ORDER BY
				item_timestamp DESC
			LIMIT 1
		');
		
		$stmt->bindValue(':service_id', $service->id());
		
		$stmt->execute();
		
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!$data) return null;
		
		$item = $service->import(
			$data['item_id'],
			(int) $data['item_timestamp'],
			$data['item_data']);
			
		assert('$item instanceof List_Service_Item');
		
		return $item;
	}
		
	public function load_items(List_Service $service, $offset = 0, $limit = 100) {
		$stmt = $this->_pdo->prepare(sprintf('
			SELECT
				item_id,
				item_timestamp,
				item_data
			FROM
				list_service_stored_items
			WHERE
				service_id = :service_id
			ORDER BY
				item_timestamp DESC
			LIMIT %d OFFSET %d
		', $limit, $offset));
		
		$stmt->bindValue(':service_id', $service->id());
		
		$stmt->execute();
		
		$items = array();
		
		while($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$item = $service->import(
				$data['item_id'],
				(int) $data['item_timestamp'],
				$data['item_data']);
			
			assert('$item instanceof List_Service_Item');
			
			$items[] = $item;
		}
		
		return $items;
	}
}

/* Helper stuff */

function List_Service_sort($a, $b) {
	if($a->timestamp() == $b->timestamp()) {
		return 0;
	}
	
	return $a->timestamp() < $b->timestamp() ? 1 : -1;
}

function List_Service_reduce_to_newest_item($a, $b) {
	
	if(!$a instanceof List_Service_Item) return $b;
	
	return $a->timestamp() > $b->timestamp() ? $a : $b;
}

/* Filters */

interface List_Service_Filter {
	public function test(List_Service_Item $item);
}

class List_Service_Filter_Query implements List_Service_Filter {

	protected $_query;
	
	public function __construct($query) {
		$this->_query = $query;
	}
	
	public function test(List_Service_Item $item) {
		return $item->matches($this->_query);
	}
}

function load_service($service_name) {
	include sprintf('services/%s.php', strtolower($service_name));
}