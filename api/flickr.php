<?php

chdir('../');

include 'config.php';

include 'lib/flickr.php';

if(empty($_GET['url'])) {
	die('Usage: flickr.php?url=http://www.flickr.com/photos/username/photo-id/');
}

$photo_id = flickr_parse_url($_GET['url']);

$api = new Flickr_API(config_flickr_api_key());

$photo = $api->find_photo($photo_id);

$response = new stdClass();
$response->url = $photo->image_url();
$response->title = $photo->title();

header('Content-Type: application/json');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 1 * MINUTEN) . ' GMT', true);
header('Cache-Control: max-age=' . 1 * MINUTEN . ', public', true);

printf('(%s)', json_encode($response));