window.service_events.register('link-update', function(anchor) {
	
	var _pattern = /http:\/\/xkcd\.com\/(\d+)\//;
	
	var _api_url = function(xkcd_id) {
		return 'api/xkcd.php?id=' + encodeURIComponent(xkcd_id);
	}
	
	var _find_details = function(xkcd_id, anchor) {
		var client = new XMLHttpRequest();
		client.open('GET', _api_url(xkcd_id), true);
		client.send(null);
		client.onreadystatechange = function() {
			if(client.readyState == 4) {
				try {
					if(client.status != 200) {
						throw new Error('noo! no hit or api down');
					}
					
					var data = eval(client.responseText);
					
					anchor.setAttribute('data-quicklook-href', data.image);
					anchor.setAttribute('data-quicklook-description', data.note);
				} catch(e) {
					
					console.log(e);
					
					anchor.removeAttribute('data-quicklook-provider');
					anchor.setAttribute('data-quicklook-xkcd-failed', true);
				}
			
				window.service_events.notify('link-update', [anchor]);
			}
		}
	}
	
	if(anchor.getAttribute('data-quicklook-provider')) return;
	
	if(anchor.getAttribute('data-quicklook-xkcd-failed')) return''
	
	var matches = anchor.href.match(_pattern);
	
	if(matches) {
		anchor.setAttribute('data-quicklook-provider', 'xkcd');
		
		_find_details(matches[1], anchor);
		
		window.service_events.notify('link-update', [anchor]); // notify zodat de Quicklook zich erop kan storten
	}
});