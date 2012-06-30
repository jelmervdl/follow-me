<?php

include '../lib/http.php';

$client = new HTTP_Client();
$request = $client->open('HEAD', $_GET['url']);
$request->send(null);

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT', true);
header('Cache-Control: max-age=604800, public', true);

echo $request->response->headers['Location'];