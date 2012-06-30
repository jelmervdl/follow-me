var register = (function() {
	
	var _get_register_node = function(list_item) {
		var node_id = list_item.id.replace('#', '_');
		
		var node = document.getElementById('register_node_' + node_id);
		
		if(!node) {
			node = document.createElement('register_node_' + node_id);
			node.innerHTML = list_item.getAttribute('data-timestamp');
			document.body.appendChild(node);
			node.style.position = 'fixed';
			node.style.right = '0px';
		}
		
		return node;
	}
	
	var _update = function() {
		
		var window_height = window.innerHeight;
		
		var document_height = document.height;
		
		var item_nodes = document.getElementById('item_list').getElementsByTagName('li');
		
		var last_time = item_nodes[item_nodes.length - 1].getAttribute('data-timestamp');
		
		var delta_time = item_nodes[0].getAttribute('data-timestamp');
		
		var step_size = (window_height / document_height);
		
		var time_now = (new Date()).getTime();
		
		var next_time_marker = Infinity;
		
		var time_markers = [0, 60, 300, 600, 60 * 30, 3600, 6 * 3600, 24 * 3600];
		
		for(var i = 0; i < item_nodes.length; i++) {
			if(item_nodes[i].getAttribute('data-timestamp') < next_time_marker) {
				var node = _get_register_node(item_nodes[i]);
				node.style.top = (item_nodes[i].offsetTop * step_size) + 'px';
				
				next_time_marker = time_now - time_markers.shift();
			}
		}
		
	}
	
	window.addEventListener('DOMContentLoaded', _update, false);
	
	return {
		
	}
})();

/*
window_height / document_height
delta_time 
*/