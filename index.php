<?php header('Content-type: application/xml'); 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

print_r($_ENV['SPOTIFY_CLIENT_ID']);

echo <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
  <channel>
    <title>The Joe Rogan Experience</title>
    <link>https://open.spotify.com/show/4rOoJ6Egrf8K2IrywzwOMk</link>
    <description>The official podcast of comedian Joe Rogan. Follow The Joe Rogan Clips show page for some of the best moments from the episodes.</description>
    <language>en-US</language>
    <pubDate>Wed, 13 Jan 2021 00:00:00 +0000</pubDate>
    <image>
      <url>https://i.scdn.co/image/9af79fd06e34dea3cd27c4e1cd6ec7343ce20af4</url>
      <title>The Joe Rogan Experience</title>
      <link>https://open.spotify.com/show/4rOoJ6Egrf8K2IrywzwOMk</link>
    </image>
  </channel>
</rss>
EOL;
