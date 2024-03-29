function time() {
	// http://kevin.vanzonneveld.net
	// +   original by: GeekFG (http://geekfg.blogspot.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: metjay
	// *	 example 1: timeStamp = time();
	// *	 results 1: timeStamp > 1000000000 && timeStamp < 2000000000
	
	return Math.round(new Date().getTime()/1000);
}

function mktime() {
	// http://kevin.vanzonneveld.net
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: baris ozdil
	// +	  input by: gabriel paderni 
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: FGFEmperor
	// +	  input by: Yannoo
	// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	  input by: jakes
	// +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// *	 example 1: mktime(14, 10, 2, 2, 1, 2008);
	// *	 returns 1: 1201871402
	// *	 example 2: mktime(0, 0, 0, 0, 1, 2008);
	// *	 returns 2: 1196463600
	
	var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
	d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);
 
	var dateManip = {
		0: function(tt){ return d.setHours(tt); },
		1: function(tt){ return d.setMinutes(tt); },
		2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
		3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
		4: function(tt){ return d.setDate(tt+mb); },
		5: function(tt){ return d.setYear(tt+ma); }
	};
	
	for( i = 0; i < argc; i++ ){
		no = parseInt(argv[i]*1);
		if (isNaN(no)) {
			return false;
		} else {
			// arg is number, let's manipulate date object
			if(!dateManip[i](no)){
				// failed
				return false;
			}
		}
	}
 
	return Math.floor(d.getTime()/1000);
}

function date ( format, timestamp ) {
	// http://kevin.vanzonneveld.net
	// +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
	// +	  parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   improved by: MeEtc (http://yass.meetcweb.com)
	// +   improved by: Brad Touesnard
	// +   improved by: Tim Wiel
	// *	 example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
	// *	 returns 1: '09:09:40 m is month'
	// *	 example 2: date('F j, Y, g:i a', 1062462400);
	// *	 returns 2: 'September 2, 2003, 2:26 am'
 
	var a, jsdate=((timestamp) ? new Date(timestamp*1000) : new Date());
	var pad = function(n, c){
		if( (n = n + "").length < c ) {
			return new Array(++c - n.length).join("0") + n;
		} else {
			return n;
		}
	};
	var txt_weekdays = ["Sunday","Monday","Tuesday","Wednesday",
		"Thursday","Friday","Saturday"];
	var txt_ordin = {1:"st",2:"nd",3:"rd",21:"st",22:"nd",23:"rd",31:"st"};
	var txt_months =  ["", "January", "February", "March", "April",
		"May", "June", "July", "August", "September", "October", "November",
		"December"];
 
	var f = {
		// Day
			d: function(){
				return pad(f.j(), 2);
			},
			D: function(){
				t = f.l(); return t.substr(0,3);
			},
			j: function(){
				return jsdate.getDate();
			},
			l: function(){
				return txt_weekdays[f.w()];
			},
			N: function(){
				return f.w() + 1;
			},
			S: function(){
				return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th';
			},
			w: function(){
				return jsdate.getDay();
			},
			z: function(){
				return (jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0;
			},
 
		// Week
			W: function(){
				var a = f.z(), b = 364 + f.L() - a;
				var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;
 
				if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b){
					return 1;
				} else{
 
					if(a <= 2 && nd >= 4 && a >= (6 - nd)){
						nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
						return date("W", Math.round(nd2.getTime()/1000));
					} else{
						return (1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0);
					}
				}
			},
 
		// Month
			F: function(){
				return txt_months[f.n()];
			},
			m: function(){
				return pad(f.n(), 2);
			},
			M: function(){
				t = f.F(); return t.substr(0,3);
			},
			n: function(){
				return jsdate.getMonth() + 1;
			},
			t: function(){
				var n;
				if( (n = jsdate.getMonth() + 1) == 2 ){
					return 28 + f.L();
				} else{
					if( n & 1 && n < 8 || !(n & 1) && n > 7 ){
						return 31;
					} else{
						return 30;
					}
				}
			},
 
		// Year
			L: function(){
				var y = f.Y();
				return (!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0;
			},
			//o not supported yet
			Y: function(){
				return jsdate.getFullYear();
			},
			y: function(){
				return (jsdate.getFullYear() + "").slice(2);
			},
 
		// Time
			a: function(){
				return jsdate.getHours() > 11 ? "pm" : "am";
			},
			A: function(){
				return f.a().toUpperCase();
			},
			B: function(){
				// peter paul koch:
				var off = (jsdate.getTimezoneOffset() + 60)*60;
				var theSeconds = (jsdate.getHours() * 3600) +
								 (jsdate.getMinutes() * 60) +
								  jsdate.getSeconds() + off;
				var beat = Math.floor(theSeconds/86.4);
				if (beat > 1000) beat -= 1000;
				if (beat < 0) beat += 1000;
				if ((String(beat)).length == 1) beat = "00"+beat;
				if ((String(beat)).length == 2) beat = "0"+beat;
				return beat;
			},
			g: function(){
				return jsdate.getHours() % 12 || 12;
			},
			G: function(){
				return jsdate.getHours();
			},
			h: function(){
				return pad(f.g(), 2);
			},
			H: function(){
				return pad(jsdate.getHours(), 2);
			},
			i: function(){
				return pad(jsdate.getMinutes(), 2);
			},
			s: function(){
				return pad(jsdate.getSeconds(), 2);
			},
			//u not supported yet
 
		// Timezone
			//e not supported yet
			//I not supported yet
			O: function(){
			   var t = pad(Math.abs(jsdate.getTimezoneOffset()/60*100), 4);
			   if (jsdate.getTimezoneOffset() > 0) t = "-" + t; else t = "+" + t;
			   return t;
			},
			P: function(){
				var O = f.O();
				return (O.substr(0, 3) + ":" + O.substr(3, 2));
			},
			//T not supported yet
			//Z not supported yet
 
		// Full Date/Time
			c: function(){
				return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P();
			},
			//r not supported yet
			U: function(){
				return Math.round(jsdate.getTime()/1000);
			}
	};
 
	return format.replace(/[\\]?([a-zA-Z])/g, function(t, s){
		if( t!=s ){
			// escaped
			ret = s;
		} else if( f[s] ){
			// a date function exists
			ret = f[s]();
		} else{
			// nothing special
			ret = s;
		}
 
		return ret;
	});
}

var _tijden = [0, null];

function relative_timestamp(timestamp) {
	
	var nu = time();

	if(nu != _tijden[0]) {
		_tijden = [nu, {
			'nu'					: [nu + 3600,	nu],
			'net'					: [nu,			nu - 60],
			'vijf minuten geleden'	: [nu - 60,	nu - 450],
			'tien minuten geleden'	: [nu - 450,	nu - 900],
			'half uur geleden'		: [nu - 900,	nu - 2100],
			'uur geleden'			: [nu - 2100,	nu - 4000],
			'vanavond'				: [mktime(24, 0, 0), mktime(18, 0, 0)],
			'vanmiddag'				: [mktime(18, 0, 0), mktime(12, 0, 0)],
			'vanochtend'			: [mktime(12, 0, 0), mktime(7, 0, 0)],
			'vanacht'				: [
				mktime(7, 0, 0),
				mktime(24, 0, 0, date('n'), date('d') - 1)],
			'gisteravond'			: [
				mktime(24, 0, 0, date('n'), date('d') - 1),
				mktime(18, 0, 0, date('n'), date('d') - 1)],
			'gistermiddag'			: [
				mktime(18, 0, 0, date('n'), date('d') - 1),
				mktime(12, 0, 0, date('n'), date('d') - 1)],
			'gisterochtend'			: [
				mktime(12, 0, 0, date('n'), date('d') - 1),
				mktime(7,  0, 0, date('n'), date('d') - 1)]
		}];
	}
	
	for(format in _tijden[1]) {
		var range = _tijden[1][format];
		
		if(timestamp < range[0] && timestamp >= range[1]) {
			return format;
		}
	}

	return date('d-m-Y \o\m H:i', timestamp);
}

setInterval(function() {
	var item_nodes = document.getElementById('item_list').getElementsByTagName('li');
	
	for(var i = 0; i < item_nodes.length; i++) {
		var item_node = item_nodes[i];
		var item_node_timestamp = item_nodes[i].getElementsByTagName('span')[0];
		
		item_node_timestamp.innerHTML = relative_timestamp(item_node.getAttribute('data-timestamp'));
	}
}, 1000);