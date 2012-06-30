window.service_events.register('link-update', function(anchor) {
	
	var _pattern = 'http://twinkle.tapulous.com/index.php?hash=';
	
	var _api_url = function(twinkle_url) {
		return 'api/twinkle.php?url=' + encodeURIComponent(twinkle_url);
	}
	
	if(anchor.getAttribute('data-quicklook-provider')) return;
		
	if(anchor.href.substr(0, _pattern.length) == _pattern) {
		anchor.setAttribute('data-quicklook-provider', 'twinkle');
		anchor.setAttribute('data-quicklook-href', _api_url(anchor.href));
		
		window.service_events.notify('link-update', [anchor]); // notify zodat de Quicklook zich erop kan storten
	}
});