<?php

chdir('../');

error_reporting(E_ALL);

ini_set('display_errors', true);

include 'config.php';

/* Dit kan eigenlijk veel mooier in een klasse. Nu is het zo hub hub barbaratruc-achtig*/

if(empty($_GET['artist']) || empty($_GET['track'])) {
	die('usage: last_fm.php?artist=***&track=***; redirects to the stream');
}

$last_fm_api_url = 'http://ws.audioscrobbler.com/1.0/webclient/getresourceplaylist.php?resourceType=9&artistName=%s&trackName=%s&resourceType=9&widget_id=%s';

$request_uri = sprintf($last_fm_api_url,
	urlencode($_GET['artist']), urlencode($_GET['track']), config_lastfm_api_key());
	
$response = file_get_contents($request_uri);

$response = base64_decode($response);

$response = urldecode($response);

$playlist = simplexml_load_string($response);

$url = strval($playlist->trackList->track->location);

if($url) {
	//header('HTPP/1.0 302 Found', true);
	header('Location: ' . $url);
	echo $url;
} else {
	header('HTTP/1.0 404 Not Found', true);
	echo 'Last.fm service did not return a track location';
}
?>