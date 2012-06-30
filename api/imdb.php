<?php

chdir('../');

include 'lib/imdb.php';

$imdb = new IMDB_API();

unset($response);

if(!empty($_GET['search'])) {
	$response = $imdb->search_movie($_GET['search']);
}
elseif(!empty($_GET['title'])) {
	$hits = $imdb->search_movie($_GET['title']);
	
	$response = isset($hits[0]) ? $hits[0]->details() : false;	
}
elseif(!empty($_GET['id'])) {
	$response = $imdb->find_movie($_GET['id']);
}
else {
	die('Usage: imdb.php?[search=*title* | title=*title* | id=*imdb_id* ]; "search" returns an array with matching titles, "title" returns the details for the best matching title, "id" returns the details for the matching movie-id. If an parameter named "c" is added to the querystring, the value of the paramter will be used as callback-name.');
}

if(isset($_GET['c'])) {
	printf('%s(%s)', $_GET['c'], json_export($response));
} else {
	printf('(%s)', json_export($response));
}