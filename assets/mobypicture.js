window.service_events.register('link-update', function(anchor) {
	
	var _pattern = 'http://mobypicture.com/?';
	
	var _api_url = function(twitpic_url) {
		return 'api/mobypicture.php?url=' + encodeURIComponent(twitpic_url);
	}
	
	if(anchor.getAttribute('data-quicklook-provider')) return;
		
	if(anchor.href.substr(0, _pattern.length) == _pattern) {
		anchor.setAttribute('data-quicklook-provider', 'mobypicture');
		anchor.setAttribute('data-quicklook-href', _api_url(anchor.href));
		
		window.service_events.notify('link-update', [anchor]); // notify zodat de Quicklook zich erop kan storten
	}
});