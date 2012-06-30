<?php

chdir('../');

include 'lib/cache.php';

$xkcd_url_pattern = 'http://xkcd.com/%d/';

$html_file = fopen_cache_url(sprintf($xkcd_url_pattern, $_GET['id']), 7 * DAYS);

$html = stream_get_contents($html_file);

$has_title = preg_match('#\<h1\>(\w+)\</h1\>#', $html, $title_matches);

$has_image = preg_match('#\<img\s+src="([^"]+)"\s+title="([^"]+)"\salt="(?:[^"]*)"\s/\>#', $html, $matches);

if($has_image) {
	
	$xkcd_entry = new stdClass();
	$xkcd_entry->image = html_entity_decode($matches[1]);
	$xkcd_entry->note = html_entity_decode($matches[2]);
	
	if($has_title) {
		$xkcd_entry->title = html_entity_decode($title_matches[1]);
	}
	
	header('Content-Type: text/javascript');
	printf('(%s)', json_encode($xkcd_entry));
} else {
	header('Status: 404 Not Found');
	printf('(%s)', json_encode(false));
}