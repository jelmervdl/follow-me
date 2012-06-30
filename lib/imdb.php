<?php

require_once 'lib/common.php';

/* Based on the IMDB engine (1.50) from VideoDB, made by Andreas Gohr 
 * <a.gohr@web.de>, altered to fit in a nice class-based PHP script, because
 * I can.
 */

class IMDB_API {
	
	const SEARCH = 'search';
	const ACTOR = 'actor';
	const MOVIE = 'movie';
	const PLOT	= 'plot';
	const CREDITS = 'credits';
	
	protected $_server = 'http://www.imdb.com';
		
	protected $_cache_timeouts;
	
	public function __construct(array $cache_timeouts = null) {
		$default_timeouts = array(
			'search'=>		1 * UUR,
			'actor' =>		7 * DAG,
			'movie' =>		3 * DAG,
			'plot'	=>		7 * DAG,
			'credits' =>	7 * DAG
		);
		
		if(!$cache_timeouts) {
			$cache_timeouts = array();
		}
		
		$this->_cache_timeouts = array_merge($default_timeouts, $cache_timeouts);
	}
	
	public function find_movie($imdb_id) {
		
		$url = $this->_data_movie_query($imdb_id);
		
		$response = $this->_request($url, self::MOVIE);
		
		if(!$response) return false;
		
		$movie = new IMDB_Movie($this, $imdb_id);
		
		// Titles
		preg_match('/<TITLE>(?<title>.*?)(?:\s+-\s+(?<subtitle>.+?))?\s\([0-9]{4}\)<\/TITLE>/i', $response, $ary);
		
		$movie->set_title($this->_filter($ary['title']));
		if(!empty($ary['subtitle'])) {
			$movie->set_subtitle($this->_filter($ary['subtitle']));
		}

		// Year
		preg_match('/<A HREF="\/Sections\/Years\/[1-2][0-9][0-9][0-9]\/?">([1-2][0-9][0-9][0-9])<\/A>/i', $response, $ary);
		$movie->set_year($this->_filter($ary[1]));

		// Cover URL
		preg_match('/name="poster".+?<IMG.+?(http:\/\/.+?\.(jpe?g|gif))/i', $response, $ary);
		$movie->set_cover_url(trim($ary[1]));

		// MPAA Rating
		preg_match('/<A HREF="\/mpaa">MPAA<\/A>: ?<\/h5>(.+?)<\/div>/is', $response, $ary);
		$movie->set_mpaa_rating($ary[1]);

		// UK BBFC Rating
		preg_match('/>\s*UK:(.*?)<\/a>\s+/s', $response, $ary);
		$movie->set_bbfc_rating($ary[1]);

		// Runtime
		preg_match('/Runtime:?<\/h5>:?.*?([0-9,]+).*?<\/TD>/si', $response, $ary);
		$movie->set_runtime(preg_replace('/,/', '', trim($ary[1])));

		// Director
		preg_match('/<h5>Directors?:<\/h5>(.+?)<\/div>/si', $response, $ary);
		preg_match_all('/<A HREF="\/Name[?\/].+?">(.+?)<\/A>/si', $ary[1], $ary, PREG_PATTERN_ORDER);
		// TODO: Update templates to use multiple directors
		foreach($ary[1] as $director) {
			$movie->add_director($this->_filter($director));
		}

		// Rating
		preg_match('/<b>([0-9.]+)\/10<\/b>[^<]*<a href="ratings" class="tn15more">[0-9,]+ votes<\/a>/si', $response, $ary);
		$movie->set_rating($ary[1]);

		// Countries
		preg_match('/<h5>Country:<\/h5>(.+?)<\/div>/si', $response, $ary);
		preg_match_all('/<A HREF="\/Sections\/Countries\/.+?\/">(.+?)<\/A>/si', $ary[1], $ary, PREG_PATTERN_ORDER);
		foreach($ary[1] as $country) {
			$movie->add_country($this->_filter($country));
		}

		// Languages
		preg_match_all('/<A HREF="\/Sections\/Languages\/.+?\/">(.+?)<\/A>/si', $response, $ary, PREG_PATTERN_ORDER);
		foreach($ary[1] as $language) {
			$movie->add_language($this->_filter($language));
		}
		
		// Plot (movies in their early stages have the plot here but not yet in plotsummary?)
		preg_match('/<h5>Plot Outline:<\/h5>\s+(.*?)<\/div>/si', $response, $ary);
		if (!empty($ary[1])) $movie->set_plot($this->_filter($ary[1]));

		// Genres (as Array)
		preg_match('/<h5>Genre:<\/h5>(.+?)<\/div>/si', $response, $ary);
		preg_match_all('/<A HREF="\/Sections\/Genres\/.+?\/">(.+?)<\/A>/si', $ary[1], $ary, PREG_PATTERN_ORDER);
		foreach($ary[1] as $genre)
		{
			$movie->add_genre($this->_filter($genre));
		}

		// Plot (simple- from main page)
		preg_match('/<h5>Plot:<\/h5>(.+?)(\||<\/div>)/si', $response, $ary);
		if (!empty($ary[1])) $movie->set_plot($this->_filter($ary[1]));

		$credit_url = $this->_credits_query($imdb_id);

		// Fetch credits
		$response = $this->_request($credit_url, self::CREDITS);
		
		// Cast
		if($response) {

			if (preg_match_all('/<table class="cast">(.*?)<\/table>/si', $response, $match))
			{
				$allcast = implode('', $match[1]);

				if (preg_match_all('#<td class="nm"><a href="/name/(.*?)/?">(.*?)</a>.*?<td class="char">(.*?)</td>#si', $allcast, $ary, PREG_PATTERN_ORDER))
				{
					for ($i=0; $i < count($ary[0]); $i++)
					{
						$character = new IMDB_Cast_Member($this, $this->_filter($ary[1][$i]));
						$character->set_name($this->_filter($ary[2][$i]));
						$character->set_character($this->_filter($ary[3][$i]));
						
						$movie->add_cast_member($character);
					}
				}
			}
			
		}
		
		$plot_url = $this->_plot_query($imdb_id);

		// Fetch plot
		$response = $this->_request($plot_url, self::PLOT);
	
		// Plot
		if($response) {
			preg_match('/<P CLASS="plotpar">(.+?)<\/P>/is', $response, $ary);
			if ($ary[1])
			{
				$plot = trim($ary[1]);
				$plot = preg_replace('/&#34;/', '"', $plot);	 //Replace HTML " with "
				//Begin removal of 'Written by' section
				$plot = preg_replace('/<a href="\/SearchPlotWriters.*?<\/a>/', '', $plot);
				$plot = preg_replace('/Written by/', '', $plot);
				$plot = preg_replace('/<i>\s+<\/i>/', ' ', $plot);
				//End of removal of 'Written by' section
				$plot = preg_replace('/\s+/s', ' ', $plot);
			}
			
			$plot = $this->_filter($plot);
				 
			$movie->set_plot($plot);
		}
		
		return $movie;
	}
	
	public function find_actor($imdb_id) {
		$response = $this->_request(
			$this->_actor_query($imdb_id),
			self::ACTOR);
		
		return $this->_fetch_actor($response);
	}
	
	public function search_movie($title, $aka = false) {
		
		$url = $this->_search_movie_query($title, (bool) $aka);
		
		$response = $this->_request($url, self::SEARCH);
		
		if(!$response) return false;
		
		$movies = array();
		
		// direct match (redirecting to individual title)?
		if(preg_match('/^'.preg_quote($this->_server,'/').'\/[Tt]itle(\?|\/tt)([0-9?]+)\/?/', $url, $single))
		{	
			// Title
			preg_match('/<title>(.*?) \([1-2][0-9][0-9][0-9].*?\)<\/title>/i', $response, $m);
			list($t, $s)		= split(' - ', trim($m[1]), 2);
			
			$movie = new IMDB_SearchResult($this, $single[2]);
			$movie->set_title($this->_filter($t));
			$movie->set_subtitle($this->_filter($s));
			
			$movies[] = $movie;
		}

		// multiple matches
		elseif(preg_match_all('#<a href="/title/tt(?<id>\d+)/?"[^>]*>(?:<img[^>]+>)?(?<title>.+?)(?:\s+-\s+(?<subtitle>.+?))?</a>\s*\((?<year>[0-9?]+)\)?#i', $response, $multi, PREG_SET_ORDER))
		{
			foreach ($multi as $row) {
				
				//var_dump($row);
				
				$movie = new IMDB_SearchResult($this,$row['id']);
				$movie->set_title($this->_filter($row['title']));
				$movie->set_subtitle($this->_filter($row['subtitle']));
				$movie->set_year($this->_filter($row['year']));
				
				if(!isset($movies[$movie->id()])) {
					$movies[$movie->id()] = $movie;
				}
			}
		}
		
		/*
		$dom = new DOMDocument();
		@$dom->loadHTML($response);
		
		foreach($dom->getElementsByTagName('a') as $anchor) {
			if(preg_match('/^\/title\/tt(?<id>[0-9]+)\/$/', $anchor->getAttribute('href'), $matches)) {
				echo $matches['id'] . "\n";
				
				if(preg_match('/^(?<title>.+)(?:\s+-\s+(?<subtitle>.+?))?$/', $anchor->childNodes->item(0)->nodeValue, $matches)) {
					echo $matches['title'] . "\n";
				} else {
					echo 'kut voor ' . $anchor->childNodes->item(1)->nodeValue . "\n";
				}
				
				if(preg_match('/(?<year>[0-9]{4})/', $anchor->nextSibling->nodeValue, $matches)) {
					echo '(' . $matches['year'] . ')';
				}
			}
		}
		*/

		return array_values($movies);
	}
	
	public function search_actor($name) {
		$response = $this->_request(
			$this->_search_actor_query($name),
			self::ACTOR);
		
		return $this->_fetch_actor($response);
	}
	
	protected function _fetch_actor($response) {
		// if not direct match load best match
		if (preg_match('#<b>Popular Names</b>.+?<a\s+href="(.*?)">#i', $response, $m) ||
			preg_match('#<b>Names \(Exact Matches\)</b>.+?<a\s+href="(.*?)">#i', $response, $m) ||
			preg_match('#<b>Names \(Approx Matches\)</b>.+?<a\s+href="(.*?)">#i', $resonse, $m))
		{
			if (!preg_match('/http/i', $m[1])) {
				$m[1] = $this->_server.$m[1];
			}
			
			$response = $this->_request($m[1], self::ACTOR);
		}
		
		if (preg_match('/<a\s+name="headshot"\s+href="(.+?)">\s*<img\s+.*?src="(.*?)"/i', $response, $m))
		{
			$url = $m[1];
			$thumbnail = $m[2];
			
			return array($url, $thumbnail);
		}
		
		return false;
	}
	
	protected function _search_movie_query($title, $aka) {
		return $this->_server . '/find?q=' . urlencode($title) . ($aka ? ';s=tt;site=aka' : '');
	}
	
	protected function _search_actor_query($name) {
		return sprintf('%s/Name?%s', $this->_server, urlencode($name));
	}
	
	protected function _actor_query($id) {
		return sprintf('%s/name/%s/', $this->_server, urlencode($id));
	}
	
	protected function _data_movie_query($id) {
		return sprintf('%s/title/tt%d/', $this->_server, $id);
	}
	
	protected function _credits_query($id) {
		return sprintf('%s/title/tt%d/fullcredits', $this->_server, $id);
	}
	
	protected function _plot_query($id) {
		return sprintf('%s/title/tt%d/plotsummary', $this->_server, $id);
	}
	
	protected function _request($url, $type) {
		
		$timeout = $this->_cache_timeouts[$type];
		
		$fhandle = fopen_cache_url($url, $timeout);
		
		assert('is_resource($fhandle)');
		
		if(!$fhandle) return false;
		
		$response = stream_get_contents($fhandle);
		
		assert('strlen($response) > 0');
		
		return $response;
	}

	protected function _filter($data) {
		$data = html_entity_decode($data);
		$data = strip_tags($data);
		
		return trim($data);
	}
	
}

class IMDB_SearchResult {
	
	protected $_api;
	
	protected $_id;
	
	protected $_title;
	
	protected $_subtitle;
	
	protected $_year;
	
	public function __construct(IMDB_API $api, $id) {
		$this->_api = $api;
		$this->_id = trim($id);
	}
	
	public function id() {
		return (int) $this->_id;
	}
	
	public function title() {
		return $this->_title;
	}
	
	public function set_title($title) {
		$title = trim($title);
		
		/* vreemd parser bugje oplossen */
		if(ord(substr($title, 0, 1)) == 160) {
			$title = substr($title, strpos($title, '.') + 1);
		}
		
		$this->_title = trim($title);
	}
	
	public function subtitle() {
		return $this->_subtitle;
	}
	
	public function set_subtitle($subtitle) {
		$this->_subtitle = trim($subtitle);
	}
	
	public function year() {
		return $this->_year;
	}
	
	public function set_year($year) {
		$this->_year = intval($year);
	}
	
	public function __toString() {
		return  $this->_year ? sprintf('%s (%d)', $this->title(), $this->year()) : $this->title();
	}
	
	public function details() {
		return $this->_api->find_movie($this->id());
	}
}

class IMDB_Movie extends IMDB_SearchResult {
	
	protected $_cover_url;
	
	protected $_mpaa_rating;
	
	protected $_bbfc_rating;
	
	protected $_runtime;
	
	protected $_directors = array();
	
	protected $_rating;
	
	protected $_countries = array();
	
	protected $_languages = array();
	
	protected $_genres = array();
	
	protected $_cast = array();

	protected $_plot;
	
	public function cover_url() {
		return $this->_cover_url;
	}
	
	public function set_cover_url($url) {
		$this->_cover_url = trim($url);
	}
	
	public function mpaa_rating() {
		return $this->_mpaa_rating;
	}
	
	public function set_mpaa_rating($mpaa_rating) {
		$this->_mpaa_rating = $mpaa_rating;
	}
	
	public function bbfc_rating() {
		return $this->_bbfc_rating;
	}
	
	public function set_bbfc_rating($rating) {
		$this->_bbfc_rating = $rating;
	}
	
	public function runtime() {
		return $this->_runtime;
	}
	
	public function set_runtime($runtime) {
		$this->_runtime = $runtime;
	}
	
	public function directors() {
		return $this->_directors;
	}
	
	public function add_director($director) {
		$this->_directors[] = $director;
	}

	public function rating() {
		return $this->_rating;
	}
	
	public function set_rating($rating) {
		$this->_rating = floatval($rating);
	}

	public function countries() {
		return $this->_countries;
	}
	
	public function add_country($country) {
		$this->_countries[] = $country;
	}
	
	public function languages() {
		return $this->_languages;
	}
	
	public function add_language($language) {
		$this->_languages[] = $language;
	}
	
	public function genres() {
		return $this->_genres;
	}
	
	public function add_genre($genre) {
		$this->_genres[] = $genre;
	}
	
	public function cast() {
		return $this->_cast;
	}
	
	public function add_cast_member(IMDB_Cast_Member $member) {
		$this->_cast[] = $member;
	}
	
	public function plot() {
		return $this->_plot;
	}
	
	public function set_plot($plot) {
		$this->_plot = $plot;
	}
}

class IMDB_Actor {
	protected $_api;
	
	protected $_id;
	
	protected $_name;
	
	public function __construct(IMDB_API $api, $id) {
		$this->_api = $api;
		$this->_id = $id;
	}
	
	public function id() {
		return $this->_id;
	}
	
	public function name() {
		return $this->_name;
	}
	
	public function set_name($name) {
		$this->_name = trim($name);
	}
}

class IMDB_Cast_Member extends IMDB_Actor {
	
	protected $_character;
	
	public function character() {
		return $this->_character;
	}
	
	public function set_character($character) {
		$this->_character = trim($character);
	}
}
