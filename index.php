<?php

include 'lib/common.php';

include 'lib/emoji.php';

include 'config.php';

$max_item_count = isset($_GET['count']) ? intval($_GET['count']) : 100;

$offset = 0;

$response_type = isset($_GET['response_type']) ? $_GET['response_type'] : 'html';

error_reporting(E_ALL);

/* Tijdelijke oplossing om de List_Service_* klassen in te laden */
foreach(explode(' ', 'twitter last_fm rss icanlol') as $service_name) {
	load_service($service_name);
}

/* Services configureren */
$services = config_services();

$shared_store = config_shared_store();

foreach($services as $service) {
	if($service instanceof List_Service_Uses_Shared_Store) {
		$service->set_shared_store($shared_store);
	}
}

/* Decorators configureren */
$decorators = array(new List_Service_Item_Timestamp());

if($iphone_os_version >= 2.2 || !file_exists('assets/emoji')) {
	/* Emoji parser configureren en als decorator toevoegen*/
	$emoji_parser = Emoji_Parser::shared_instance();
	$emoji_parser->set_format('<img class="emoji-icon" src="assets/emoji/Keyboard-Emoji-ver220_%d.png">');
	array_unshift($decorators, new List_Service_Item_Emoji($emoji_parser));
}


/* Dispatch */

$combined_stream = new List_Service_Stream_Combined();

$async_services = array();

/* Één of meerdere services */
if(!empty($_GET['service_name'])) {
	
	$service = $services[$_GET['service_name']];

	try {
		$service->update();
	} catch(ErrorException $e) {
		error_log($e);
	}
	
	$services = array($_GET['service_name'] => $service);
}
/* Voor iedere service, voeg de stream toe aan de combined stream */
foreach($services as $service_name => $service) {
	if(!$service->is_up_to_date()) {
		$async_services[] = array($service_name, $service->last_timestamp());
	}
	
	$service_stream = $service->get_stream();
	$combined_stream->add_stream($service_stream);
}
/* Is er een zoekopdracht? Zo ja, voeg een filter toe aan de combined stream */
if(!empty($_GET['query'])) {
	$combined_stream->add_filter(new List_Service_Filter_Query($_GET['query']));
}
/* Is er een offset? Zo ja, schuif dan in de combined stream naar voren */
if(!empty($_GET['offset'])) {
	$offset = (int) $_GET['offset'];
	$combined_stream->seek($offset);
}
/* Willen we Javascript als antwoord terug? */
if($response_type == 'js') {
	
	$items = array();
	
	while($item = $combined_stream->pop()) {
		if(count($items) >= $max_item_count) break;
		
		if(empty($_GET['last_timestamp']) || $item->timestamp() > $_GET['last_timestamp']) {
			$items[] = array(
				'id'		=> $item->id(),
				'timestamp' => $item->timestamp(),
				'innerHTML' => List_Service_Item_decorate($item, $decorators)
			);
		}
	}
	
	header('Content-Type: text/javascript');
	
	header('X-Last-Timestamp: ' . $service->last_timestamp());
	
	echo json_encode($items);
	
	exit;
}
/* Of toch liever HTML als antwoord */

$mini_mode = isset($_GET['minimode']);
$i = 0;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<base target="_blank">
		<title>The world, revolving around me</title>
		
		<!-- Hij doet Last.fm, hij doet twitter, hij doet RSS, en hij doet vast
		nog meer wanneer ik via meer kanalen te volgen ben. Wil je de broncode?
		Geen probleem, stuur me even een mailtje. Zou er vraag zijn naar dit in
		de vorm van een webservice? -->
		
		<link rel="stylesheet" href="assets/screen.css" type="text/css" media="screen" title="standaard" charset="utf-8">
		
		<?php if($mini_mode): ?>
		<link rel="stylesheet" href="assets/sidebar.css" type="text/css" media="screen" title="standaard" charset="utf-8">
		<?php endif ?>
		
		<!-- Instellingen, constanten, you know -->
		<script type="text/javascript">
		
			USE_INFINITE_SCROLLING = <?=USE_INFINITE_SCROLLING?>;
			
			MAX_ITEM_COUNT = <?=$max_item_count ?>;
		
		</script>
		
		<?php
			include_js('assets/soundmanager2-nodebug-jsmin.js');
			include_js('assets/main.js');
			//include_js('assets/register.js');
			include_js('assets/quicklook.js');
			include_js('assets/last_fm.js');
			include_js('assets/tinyurl.js');
			include_js('assets/twitpic.js');
			include_js('assets/mobypicture.js');
			include_js('assets/twinkle.js');
			include_js('assets/flickr.js');
			include_js('assets/xkcd.js');
			
			if(USE_SEARCH) include_js('assets/quicksearch.js');
		?>
	</head>
	<body id="follow_me">
		<h1>My Un<span id="indicator" class="i">i</span>iverse</h1>
		<ul id="item_list">
		<?php while($i++ < $max_item_count && $item = $combined_stream->pop()): ?>
			<li id="<?=$item->id()?>" data-timestamp="<?=$item->timestamp() ?>">
				<?php echo List_Service_Item_decorate($item, $decorators) ?>
			</li>
		<?php endwhile ?>
		
		<?php if(!USE_INFINITE_SCROLLING): ?>
			<li class="more"><a href="index.php?offset=<?=$offset + $max_item_count?>">De rest</a></li>
		<?php endif ?>
		</ul>
		
		<div id="footer"></div>
		
		<div id="quicksearchbox" style="display: none"><input type="text" value="Zoekopdracht"></div>
		
		<script type="text/javascript">

		<?php if(isset($_GET['query'])): ?>
			window.service_url.set_argument('query', <?=json_encode($_GET['query'])?>);
		<?php endif ?>
		
		<?php if(isset($_GET['service_name'])): ?>
			window.service_url.set_argument('service_name', <?=json_encode($_GET['service_name'])?>);
		<?php endif ?>

		<?php foreach($async_services as $service): ?>
			window.service_list.load({service_name: '<?=$service[0]?>', last_timestamp: <?=$service[1]?>});
		<?php endforeach ?>

		<?php foreach($services as $service_name => $service): ?>
			window.service_list.register('<?=$service_name?>', <?=intval($service->last_timestamp())?>, <?=$service->time_to_live() + 3 ?> * 1000);
		<?php endforeach ?>

			window.service_list.enable_updates();

		</script>
		
		<?php ?>
	</body>
</html>