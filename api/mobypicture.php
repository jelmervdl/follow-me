<?php

chdir('../');

include 'lib/common.php';

if(empty($_GET['url'])) {
	die('usage: mobipicture.php?url=http://mobypicture.com/?****');
}

try {
	if(!preg_match('{^http://mobypicture.com/\?[a-z0-9]+$}', $_GET['url']))
		throw new Exception('Invalid mobypicture URL');
	
	$html_file = fopen_cache_url($_GET['url'], 5 * MINUTEN);

	$document = new DOMDocument();
	try {
		$document->loadHTML(stream_get_contents($html_file));
	} catch(ErrorException $e) {}

	$hit = $document->getElementById('main_picture');

	$link = $hit->getAttribute('src');

	header('Status: 302 Found', true);
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
	header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 5 * MINUTEN) . ' GMT', true);
	header('Cache-Control: max-age=' . 5 * MINUTEN . ', public', true);
	header('Location: ' . $link);
	echo $link;
} catch(Exception $e) {
	header('Status: 500 Internal Server Error', true);
	echo $e->getMessage();
}
?>