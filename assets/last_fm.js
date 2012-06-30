var LastFM = (function() {
	
	var _img_play = new Image();
	_img_play.src = 'assets/control_play.png';
	
	var _img_stop = new Image();
	_img_stop.src = 'assets/control_stop.png';
	
	var _img_load = new Image();
	_img_load.src = 'assets/control_load.gif';
	
	var _img_fail = new Image();
	_img_fail.src = 'assets/control_fail.png';
	
	var _sender_do_nothing = function() {
		return false;
	}
	
	var _sender_stop_playing = function(sender, kill_callback) {
		kill_callback();
		
		sender.src = _img_play.src;
		
		sender.onclick = function() {
			return _sender_start_playing(sender);
		}
		
		return false;
	}
	
	var _sender_start_playing = function(sender) {
		_preview(sender, sender.getAttribute('data-artist'), sender.getAttribute('data-track'));
		
		return false;
	}
	
	var _preview = function(sender, artist, track_name) {
		sender.onclick = function() {
			return _sender_do_nothing(sender);
		}
		
		sender.src = _img_load.src;
		
		var progress_bar = sender.parentNode.parentNode;
		var old_classname = progress_bar.className;
		progress_bar.className = 'progress-bar';
		
		try {
			var background_y_offset = window.getComputedStyle(progress_bar, null).getPropertyValue('background-position').match(/\d+[%ptx]+\s(\d+[%ptx]+)/)[1];
		} catch(e) {
			var background_y_offset = '0px';
		}
		
		var track_uri = 'api/last_fm.php?artist=' + encodeURIComponent(artist) + '&track=' + encodeURIComponent(track_name);
		
		if(window.console) console.log(track_uri);
		
		var track = soundManager.createSound({
			id: escape(track_uri),
			url: track_uri,
			onplay: function() {
				sender.src = _img_stop.src;
			},
			onerror: function() {
				console.log('error', this);
				sender.src = _img_fail.src;
				sender.title = 'Preview failed to load';
				sender.onclick = null;
			},
			whileplaying: function() {
				progress_bar.style.backgroundPosition = Math.round(this.position / this.durationEstimate * progress_bar.clientWidth) + 'px ' + background_y_offset;
				progress_bar.className = 'progress-bar'; // omdat Webkit anders niet de background update
			},
			onfinish: function() {
				_sender_stop_playing(sender, function() {
					progress_bar.className = old_classname;
					track.destruct();
				});
			}
		});
		
		track.play();
		
		sender.onclick = function() {
			return _sender_stop_playing(sender, function() {
				progress_bar.className = old_classname;
				track.destruct();
			});
		}
	}
	
	return {
		preview: function(sender) {
			return _sender_start_playing(sender);
		}
	}
})();