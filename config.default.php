<?php

define('USE_INFINITE_SCROLLING', true);

define('USE_SEARCH', true);

/** 
 * Helaas, je zal de bestanden in het mapje "services" in moeten duiken om
 * de exacte volgorde en betekenis van de argumenten te achterhalen. Most of
 * the time is het username/password/source, aantal items, time to live voor
 * de cache, en eventueel nog wat advanced opties zoals default values voor
 * de rss service.
 */
function config_services() {
	return array(
		'twitter'		=> new List_Service_Twitter('somefoolwitha', 'password'),
		'last_fm'		=> new List_Service_LastFM('somefoolwitha'),
		'tweakers'		=> new List_Service_RSS('http://feeds.feedburner.com/tweakers/mixed', 20 * MINUTEN),
		'waar_is_hidde'	=> new List_Service_RSS('http://feeds.feedburner.com/waarishidde?format=xml', 1 * UUR, array(
			'author' => 'Hidde',
			'source_logo' => 'http://s3.amazonaws.com/twitter_production/profile_images/63393669/Photo_13_bigger.jpg')),
		'arjan_eising' => new List_Service_RSS('http://arjaneising.nl/feed/', 3 * UUR, array(
			'author' => 'Arjan',
			'source_logo' => 'http://s3.amazonaws.com/twitter_production/profile_images/52214694/profile-photo-arjan-eising_bigger.jpg')),
		'xkcd' => new List_Service_RSS('http://xkcd.com/rss.xml', 3 * UUR, array(
			'source_logo' => 'http://s3.amazonaws.com/twitter_production/profile_images/33466022/xkcd_bigger.png'))
		// nee, geen WeeWar meer
	);
}

/* Waar wil je je tweets, last-fms, rss-items op laten slaan? Ik ben bang dat ik
 * door een REPLACE ipv INSERT alleen support voor MySQL momenteel heb, al moet
 * dat niet lastig om zijn te bouwen naar SQLite. Zie lib/services.php en dan de
 * klasse List_Service_Item_Store voor details en queries. De tabelstructuur:
 * 
 * CREATE TABLE `list_service_stored_items` (
 *   `item_id` varchar(255) NOT NULL,
 *   `service_id` varchar(255) NOT NULL,
 *   `item_timestamp` int(11) NOT NULL,
 *   `item_data` text NOT NULL,
 *   PRIMARY KEY  (`item_id`),
 *   KEY `idx_service_id` (`service_id`),
 *   KEY `idx_item_timestamp` (`item_timestamp`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 */
function config_shared_store() {
	$pdo = new PDO('mysql:host=localhost;dbname=my_universe', 'root', 'password');
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	return new List_Service_Item_Store($pdo);
}

/* Flickr API key voor de quicklook-feature. Als je je niet misdraagt kan je 
 * deze key gewoon blijven gebruiken, en anders kan je heel gemakkelijk een
 * eigen aanvragen. Of je haalt Flickr-support eruit door de verwijzing naar
 * assets/flickr.js uit index.php te halen.
 */
function config_flickr_api_key() {
	return '942b8ae8266a91607b3cfc280b8cae23';
}

/* Last.fm API key wordt gebruikt voor de previews en het ophalen van je
 * recent afgespeelde nummers.
 */
function config_lastfm_api_key() {
	/* Ik heb hem uit m'n lastfm widget gestolen: (wel even eigen username gebruiken natuurlijk)
	 * http://www.last.fm/widgets/popup?colour=red&chartType=recenttracks&user=somefoolwitha&chartFriends=1&from=preview&widget=chart&resize=1&resize=0
	 * daar is hij in de broncode te vinden in de flashvars-property als widget_id.
	 */
	return 'chart_93cb0fee5e7b93fc96f02bc4b82b57c3';
}