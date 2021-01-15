<?php

//TODO flesh out composer.json
//TODO when this gets automated, add logging

require_once './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$newIndex = fopen('index_new.php', 'w');
//TODO replace manual XML creation with XMLWriter (https://www.php.net/manual/en/book.xmlwriter.php) or SimpleXML (https://www.php.net/manual/en/book.simplexml.php)
fwrite($newIndex, 
    "<?php header('Content-type: application/xml'); 
    echo '<?xml version=\"1.0\" encoding=\"UTF-8\"?>' . PHP_EOL;
    echo '<rss version=\"2.0\"' . PHP_EOL;
    echo 'xmlns:content=\"http://purl.org/rss/1.0/modules/content/\"' . PHP_EOL;
    echo 'xmlns:dc=\"http://purl.org/dc/elements/1.1/\"' . PHP_EOL;
    echo 'xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\"' . PHP_EOL;
    echo 'xmlns:trackback=\"http://madskills.com/public/xml/rss/module/trackback/\">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    ?>"
);

//TODO extract to a dedicated class
$tokenUrl = "https://accounts.spotify.com/api/token";
$options = array(
    'http' => array(
        'header'  => 'Authorization: Basic ' . base64_encode($_SERVER['SPOTIFY_CLIENT_ID'] . ':' . $_SERVER['SPOTIFY_CLIENT_SECRET']) . "\r\n" . 
	    "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query(['grant_type' => 'client_credentials']),
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($tokenUrl, false, $context);
if ($result === FALSE) { exit('Token fetch failed'); }

$tokenResult = json_decode($result, true);

$spotifyUrl = 'https://api.spotify.com/v1/shows/4rOoJ6Egrf8K2IrywzwOMk?market=US';
$options = array(
    'http' => array(
        'header'  => 'Authorization: Bearer  ' . $tokenResult['access_token'],
        'method'  => 'GET',
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($spotifyUrl, false, $context);
if ($result === FALSE) { exit('Show fetch failed'); }

$show = json_decode($result, true);

//TODO add fallbacks for missing data
fwrite($newIndex, "<title>{$show['name']}</title>" . PHP_EOL);
fwrite($newIndex, "<description>{$show['description']}</description>" . PHP_EOL);
fwrite($newIndex, "<link>{$show['external_urls']['spotify']}</link>" . PHP_EOL);
fwrite($newIndex, "<language>{$show['languages'][0]}</language>" . PHP_EOL);
fwrite($newIndex, "<pubDate>{$show['episodes']['items'][0]['release_date']}</pubDate>" . PHP_EOL);
fwrite($newIndex, "<image>" . PHP_EOL);
fwrite($newIndex, "<url>{$show['images'][0]['url']}</url>" . PHP_EOL);
fwrite($newIndex, "<title>{$show['name']}</title>" . PHP_EOL);
fwrite($newIndex, "<link>{$show['external_urls']['spotify']}</link>" . PHP_EOL);
fwrite($newIndex, "</image>" . PHP_EOL);

$pageCount = 0;
do {
$offset = $pageCount * 50;
$spotifyUrl = "https://api.spotify.com/v1/shows/4rOoJ6Egrf8K2IrywzwOMk/episodes/?limit=50&offset=$offset&market=US";
$options = array(
    'http' => array(
        'header'  => 'Authorization: Bearer  ' . $tokenResult['access_token'],
        'method'  => 'GET',
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($spotifyUrl, false, $context);
if ($result === FALSE) { exit('Episode fetch failed'); }

$episodes = json_decode($result, true)['items'];

foreach ($episodes as $episode) {
    $duration = floor($episode['duration_ms'] / 1000);
    $itunesDuration = floor($duration / 3600) . ':' . floor($duration / 60) % 60 . ':' . $duration % 60;
    
    fwrite($newIndex, "<item>" . PHP_EOL);
    fwrite($newIndex, "<title>" . htmlspecialchars($episode['name']) . "</title>" . PHP_EOL);
    fwrite($newIndex, "<link>{$episode['external_urls']['spotify']}</link>" . PHP_EOL);
    fwrite($newIndex, "<description>" . htmlspecialchars($episode['description']) . "</description>" . PHP_EOL);
    fwrite($newIndex, "<enclosure url=\"https://anon-podcast.scdn.co/" . array_slice(explode('/', $episode['audio_preview_url']), -1)[0] . "\" length=\"" . floor($episode['duration_ms'] / 1000) . "\" type=\"audio/mpeg\" />" . PHP_EOL);
    fwrite($newIndex, "<pubDate>{$episode['release_date']}</pubDate>" . PHP_EOL);
    fwrite($newIndex, "<guid>{$episode['uri']}</guid>" . PHP_EOL);
    fwrite($newIndex, "<itunes:explicit>{$episode['explicit']}</itunes:explicit>" . PHP_EOL);
    fwrite($newIndex, "<itunes:subtitle>" . htmlspecialchars($episode['description']) . "</itunes:subtitle>" . PHP_EOL);
    fwrite($newIndex, "<itunes:image>{$episode['images'][0]['url']}</itunes:image>" . PHP_EOL);
    fwrite($newIndex, "<itunes:duration>{$duration}</itunes:duration>" . PHP_EOL);
    fwrite($newIndex, "</item>" . PHP_EOL);
}

$pageCount++;
usleep(500);
} while (count($episodes) > 0);

fwrite($newIndex, "<generator>Podify</generator>" . PHP_EOL);
fwrite($newIndex, "<itunes:image>{$show['images'][0]['url']}</itunes:image>" . PHP_EOL);
fwrite($newIndex, "<itunes:author>{$show['publisher']}</itunes:author>" . PHP_EOL);
fwrite($newIndex, "<itunes:summary>{$show['description']}</itunes:summary>" . PHP_EOL);
fwrite($newIndex, "</channel>" . PHP_EOL);
fwrite($newIndex, "</rss>" . PHP_EOL);

fclose($newIndex);

unlink('index.php');
rename("index_new.php", "index.php");
