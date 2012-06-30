window.service_events.register('link-update', function(anchor) {
	
	var _pattern = 'http://twitpic.com/';
	
	var _api_url = function(twitpic_url) {
		return 'api/twitpic.php?url=' + encodeURIComponent(twitpic_url);
	}
	
	if(anchor.getAttribute('data-quicklook-provider')) return;
		
	if(anchor.href.substr(0, _pattern.length) == _pattern) {
		anchor.setAttribute('data-quicklook-provider', 'twitpic');
		anchor.setAttribute('data-quicklook-href', _api_url(anchor.href));
		
		window.service_events.notify('link-update', [anchor]); // notify zodat de Quicklook zich erop kan storten
	}
});

window.service_events.register('link-update', function(anchor) {
	
	var _pattern = 'http://mobipicture.com/?';
	
	var _api_url = function(twitpic_url) {
		return 'api/mobipicture.php?url=' + encodeURIComponent(twitpic_url);
	}
	
	if(anchor.getAttribute('data-quicklook-provider')) return;
		
	if(anchor.href.substr(0, _pattern.length) == _pattern) {
		anchor.setAttribute('data-quicklook-provider', 'mobipicture');
		anchor.setAttribute('data-quicklook-href', _api_url(anchor.href));
		
		window.service_events.notify('link-update', [anchor]); // notify zodat de Quicklook zich erop kan storten
	}
});