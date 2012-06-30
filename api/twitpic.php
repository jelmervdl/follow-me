<?php

chdir('../');

include 'lib/common.php';

if(empty($_GET['url'])) {
	die('usage: twitpic.php?url=http://twitpic.com/****');
}

try {
	$html_file = fopen_cache_url($_GET['url'], 5 * MINUTEN);

	$document = new DOMDocument();
	try {
		$document->loadHTML(stream_get_contents($html_file));
	} catch(ErrorException $e) {}

	$hit = $document->getElementById('photo-display');

	$link = $hit->getAttribute('src');

	if($link[0] == '/') {
		$link = 'http://twitpic.com' . $link;
	}

	//header('HTTP/1.0 302 Found', true); Goedkope KUT-webhosting...
	header('Status: 302 Found', true);
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 5 * MINUTEN) . ' GMT', true);
	header('Cache-Control: max-age=' . 5 * MINUTEN . ', public', true);
	header('Location: ' . $link);
	echo $link;
} catch(ErrorException $e) {
	header('Status: 500 Internal Server Error', true);
	echo $e->getMessage();
}
?>