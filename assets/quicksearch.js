var Quicksearch = (function() {
	
	var _box;
	var _textfield;
	var _textfield_last_value;
	var _item_list;
	var _visible = false;
	var _search_timeout;
	
	var _init = function() {
		_item_list = document.getElementById('item_list');
		
		_box = document.getElementById('quicksearchbox');
		
		_textfield = _box.getElementsByTagName('input').item(0);
		
		_textfield.addEventListener('keyup', _catch_textfield_event, false);
		
		window.addEventListener('keydown', _catch_window_event, false);
	}
	
	var _catch_textfield_event = function(e) {
		if(e.keyCode == 27) {
			_hide_search();
			e.stopPropagation();
			e.preventDefault();
			return;
		}
		
		if(_textfield_last_value == _textfield.value) return;
		
		_textfield_last_value = _textfield.value;
		
		if(_search_timeout) {
			clearTimeout(_search_timeout);
		}
		
		_search_timeout = setTimeout(_search, 300);
	}
	
	var _catch_window_event = function(e) {
		
		console.log(e);
		
		if(e.keyCode == 27 && _visible) {
			e.stopPropagation();
			e.preventDefault();
			_hide_search();
		} else if(!e.metaKey && !e.ctrlKey && e.keyCode > 49 && e.keyCode < 200 && e.keyCode != 91) {
			_show_search();
		}
	}
	
	var _show_search = function() {
		if(_visible) return;
		
		_box.style.display = 'block';
		_textfield.value = '';
		_textfield.focus();
		
		_visible = true;
	}
	
	var _hide_search = function() {
		if(!_visible) return;
		
		if(_search_timeout) {
			clearTimeout(_search_timeout);
		}
		
		_textfield.blur();
		_box.style.display = 'none';
		
		window.service_url.set_argument('query', '');
		window.service_list.clear();
		window.service_list.load();
		
		_visible = false;
	}
	
	var _search = function() {
		window.service_url.set_arguments({query: _textfield.value});
		
		window.service_list.clear();
		window.service_list.load();
	}
	
	window.addEventListener('DOMContentLoaded', _init, false);
})();