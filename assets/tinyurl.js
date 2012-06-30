var TinyURL = (function() {
	
	var _url_patterns = [
		'http://tinyurl.com/',
		'http://snipurl.com/',
		'http://is.gd/',
		'http://bit.ly/'
	];
	
	var _is_supported_url = function(url) {
		for(var i = 0; i < _url_patterns.length; i++) {
			if(url.substr(0, _url_patterns[i].length) == _url_patterns[i]) {
				return url.substr(-4, 4) != '#tmp';
			}
		}
		
		return false;
	}
	
	var _find_real_url = function(tinyurl, callback) {
		var client = new XMLHttpRequest();
		client.open('GET', 'api/tinyurl.php?url=' + encodeURIComponent(tinyurl.tinyurl), true);
		client.send(null);
		client.onreadystatechange = function() {
			if(client.readyState == 4) {
				tinyurl.real_url = client.responseText;
				callback();
			}
		}
	}
	
	var _register_events = function(anchor) {
		if(_is_supported_url(anchor.href)) {
			anchor.href += '#tmp';
			new TinyURL_Anchor(anchor);
		}
	}
	
	var TinyURL_Anchor = function(anchor) {
		this.anchor = anchor;
		this.tinyurl = anchor.href.substr(0, anchor.href.length - 4);
		this.real_url = null;
		
		var self = this;
		
		_find_real_url(this, function() {
			self.anchor.href = self.real_url;
			window.service_events.notify('link-update', [self.anchor]);
		});
	}
	
	return _register_events;
})();

if(window.service_events) {
	window.service_events.register('link-update', TinyURL);
}