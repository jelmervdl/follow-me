/* Busy indicator gebruikt door de de load_more_items functie */
var busy_processes = 0;

function _busy() {
	busy_processes++;
	
	_update_busy_indicator();
}

function _done() {
	busy_processes--;
	
	_update_busy_indicator();
}

function _update_busy_indicator() {
	var indicator = document.getElementById('indicator');
	
	indicator.style.color = busy_processes > 0 ? 'black' : 'red';
	
	if(busy_processes > 0) {
		indicator.title = busy_processes + ' dingen in de wacht';
	} else {
		indicator.title = 'Laatst bijgewerkt op ' + (new Date()).toLocaleTimeString();
	}
}

/* URL van de service */
window.service_url = (function() {
	var _global_arguments = {};
	
	var _set_arguments = function(context, new_arguments) {
		for(var key in new_arguments) {
			context[key] = new_arguments[key];
		}
	}
	
	var _build = function(local_arguments) {
		var url;
		
		var components = {};
		
		_set_arguments(components, _global_arguments);
		_set_arguments(components, local_arguments);
		
		var path = (components.service_name ? components.service_name : 'index') + '.js';
		
		for(var key in components) {
			
			if(key == 'service_name') continue;
			
			//if(!components[key]) continue;
			
			if(!url) {
				url = path + '?';
			} else {
				url += '&';
			}
			
			url += key + '=' + encodeURIComponent(components[key]);
		}
		
		// In het geval er geen components zijn meegegeven
		if(!url) {
			url = path;
		}
		
		return url;
	}
	
	return {
		set_argument: function(key, value) {
			var arg = {}; arg[key] = value;
			
			_set_arguments(_global_arguments, arg);
		},
		
		set_arguments: function(args) {
			_set_arguments(_global_arguments, args);
		},
		
		build: _build
	};
})();

/* Event-systeem */

window.service_events = (function() {
	var _observers = [];
	
	return {
		register: function(event_name, callback) {
			if(!_observers[event_name]) {
				_observers[event_name] = [];
			}
			
			_observers[event_name].push(callback);
		},
		
		notify: function(event_name, args) {
			if(!_observers[event_name]) return;
			
			for(var i = 0; i < _observers[event_name].length; i++) {
				try {
					_observers[event_name][i].apply(null, args);
				} catch(e) {
					if(window.console) console.log(e, event_name, _observers[event_name][i], args);
				}
			}
		}
	}
})();

window.service_events.register('update', function(new_item_nodes) {
	for(var i = 0; i < new_item_nodes.length; i++) {
		
		var anchors = new_item_nodes[i].getElementsByTagName('a');
		
		for(var j = 0; j < anchors.length; j++) {
			window.service_events.notify('link-update', [anchors[j]]);
		}
	}
});

window.service_events.register('link-update', function(anchor) {
	anchor.title = anchor.getAttribute('data-quicklook-description') || anchor.href;
});

/* Notificatie-geluidje wanneer er nieuwe items zijn */

var notify_sound = false;

soundManager.url = 'assets/';

soundManager.onload = function() {
	notify_sound = soundManager.createSound({
		id: 'notify',
		url: 'assets/notification.mp3',
		autoLoad: false
	});
}


window.service_events.register('update', function(new_item_nodes, last_timestamp) {
	if(!notify_sound || last_timestamp === '' || new_item_nodes.length === 0) return;
	
	notify_sound.play();
});

/* Meer items inladen. Eventueel met timestamp om verkeer te beperken */

window.service_list = (function() {

	var _list_item_tree;
	
	var _registered_services = [];
	

	var _init = function() {
		_list_item_tree = document.getElementById('item_list');
	}
	
	var _register_service = function(service_name, last_timestamp, interval) {
		_registered_services[service_name] = {
			name: service_name,
			last_timestamp: last_timestamp,
			interval: interval,
			_interval_handler: null
		};
	}
	
	var _enable_service_timeout = function(service_name) {
		if(_registered_services[service_name]._interval_handler) {
			clearInterval(_registered_services[service_name]._interval_handler);
		}
		
		_registered_services[service_name]._interval_handler = setInterval(
			function() {
				_load_more_items({
					service_name: _registered_services[service_name].name,
					last_timestamp: _registered_services[service_name].last_timestamp},
					function(last_timestamp) {
						_registered_services[service_name].last_timestamp = last_timestamp
					});
			},
			_registered_services[service_name].interval
		);
	}
	
	var _disable_service_timeout = function(service_name) {
		if(_registered_services[service_name]._interval_handler) {
			clearInterval(_registered_services[service_name]._interval_handler);
		}
	}
	
	var _enable_all_timeouts = function() {
		for(var service_name in _registered_services) {
			_enable_service_timeout(service_name);
		}
	}
	
	var _disable_all_timeouts = function() {
		for(var service_name in _registered_services) {
			_disable_service_timeout(service_name);
		}
	}
	
	var _load_more_items = function(local_arguments, callback) {
		var client = new XMLHttpRequest();
	
		var url = service_url.build(local_arguments);
	
		callback = callback || function() {};
	
		client.open('GET', url, true);
	
		client.send(null);
	
		_busy();
	
		client.onreadystatechange = function() {
			if(client.readyState == 2) {
				_done();
			}
			else if(client.readyState == 4) {
				try {
					var service_items = eval(client.responseText);
				} catch(e) {
					if(window.console) console.log(e, client.responseText);
				
					var service_items = [];
				}
				
				var list_item_nodes = _list_item_tree.getElementsByTagName('li');
			
				var updated_item_nodes = [];
			
				for(var index in service_items) {
			
					var item = service_items[index];
				
					var item_node = null;
				
					if(!(item_node = document.getElementById(item.id))) {
						item_node = document.createElement('li');
						item_node.id = item.id;
					}
				
					updated_item_nodes.push(item_node);
				
					item_node.setAttribute('data-timestamp', item.timestamp);
					item_node.innerHTML = item.innerHTML;
				
					for(var i = 0; i < list_item_nodes.length; i++) {
						if(list_item_nodes[i].getAttribute('data-timestamp') < item.timestamp) {
							_list_item_tree.insertBefore(item_node, list_item_nodes[i]);
							break;
						}
					}
				
					if(!item_node.parentNode) // geen "latere" node gevonden
						_list_item_tree.appendChild(item_node);
				}
	
				window.service_events.notify('update', [updated_item_nodes, client.getResponseHeader('X-Last-Timestamp')]);
			
				callback(client.getResponseHeader('X-Last-Timestamp'), service_items ? service_items.length : 0);
			}
		}
	}
	
	var _prune = function() {
		if(MAX_ITEM_COUNT <= 0) return;

		var list_item_nodes = _list_item_tree.getElementsByTagName('li');

		while(list_item_nodes.length > MAX_ITEM_COUNT) {
			_list_item_tree.removeChild(list_item_nodes[list_item_nodes.length - 1]);
		}
	}
	
	var _clear = function() {
		while(_list_item_tree.childNodes.length > 0) {
			_list_item_tree.removeChild(_list_item_tree.firstChild);
		}
	}
	
	window.addEventListener('DOMContentLoaded', _init, false);
	
	return {
		clear: _clear,
		prune: _prune,
		load:  _load_more_items,
		register: _register_service,
		enable_updates: _enable_all_timeouts,
		disable_updates: _disable_all_timeouts
	};
})();

//window.service_events.register('update', prune_items);


/* Inifinite scrolling */
if(USE_INFINITE_SCROLLING) {

	var _current_offset = 0;

	var _update_state = 0;

	var _update_current_offset = function() {
		return document.getElementById('item_list').getElementsByTagName('li').length;
	}
	window.addEventListener('scroll', function() {
		
		if(_update_state > 0) return;
	
		if(window.scrollY + window.innerHeight + 250 > document.body.scrollHeight) {
			_update_state = 1;
		
			window.service_list.load({offset: _current_offset}, function(timestamp, item_count) {
				_update_state = item_count === 0 ? 2 : 0;
			});
		}
	}, false);
	
	window.service_events.register('update', function() {
		_current_offset = _update_current_offset();
	});
}

/* Fluid.app extraatjes */

if(window.fluid) {
	var focussed = true;
	
	window.addEventListener('focus', function() {
		focussed = true;
		window.fluid.dockBadge = null;
	}, false);
	
	window.addEventListener('blur', function() {
		focussed = false;
	}, false);
		
	window.service_events.register('update', function(new_item_nodes, last_timestamp) {
		if(last_timestamp == '' || new_item_nodes.length < 1) return;
		
		if(!focussed) {
			window.fluid.dockBadge = (window.fluid.dockBadge ? parseInt(window.fluid.dockBadge) : 0) + new_item_nodes.length;
		}
		
		window.fluid.playSoundNamed('notification');
	});
}

window.addEventListener('DOMContentLoaded', function() {
	var list_items = document.getElementById('item_list').getElementsByTagName('li');
	
	window.service_events.notify('update', [list_items]);	
}, false);