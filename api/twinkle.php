<?php

chdir('../');

include 'lib/common.php';

if(empty($_GET['url'])) {
	die('usage: twinkle.php?url=http://twinkle.tapulous.com/index.php?hash=***');
}

$html_file = fopen_cache_url($_GET['url'], 5 * MINUTEN);

$document = new DOMDocument();
@$document->loadHTML(stream_get_contents($html_file));

$image = $document->getElementById('content')->getElementsByTagName('img')->item(1);

$link = $image->getAttribute('src');

header('HTTP/1.0 302 Found', true);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT', true);
header('Cache-Control: max-age=604800, public', true);
header('Location: ' . $link);
echo $link;
?>