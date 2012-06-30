window.service_events.register('link-update', function(anchor) {
	
	var _patterns = ['http://flickr.com/', 'http://www.flickr.com/'];
	
	var _matches_pattern = function(url) {
		for(var i = 0; i < _patterns.length; i++) {
			if(url.substr(0, _patterns[i].length) == _patterns[i]) return true;
		}
		
		return false;
	}
	
	var _api_url = function(url) {
		return 'api/flickr.php?url=' + encodeURIComponent(url);
	}
	
	/* Heeft al een Quicklook-hook */
	if(anchor.getAttribute('data-quicklook-provider')) return;
	
	/* Is niet een Flickr-URL */
	if(!_matches_pattern(anchor.href)) return;
	
	anchor.setAttribute('data-quicklook-provider', 'flickr');
	
	var client = new XMLHttpRequest();
	client.open('GET', _api_url(anchor.href), true);
	client.onreadystatechange = function() {
		if(client.readyState == 4) {
			try  {
				var response = null;
				eval('response = ' + client.responseText);
				anchor.setAttribute('data-quicklook-href', response.url);
				anchor.setAttribute('data-quicklook-description', response.title);
				anchor.title = response.title;
				window.service_events.notify('link-update', [anchor]);
				
			} catch(e) {
				if(window.console) console.log(e, client);
			}
		}
	}
	client.send(null);
});