<?php

function callApi($data) {
  $data['format'] = 'json';
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
  ));
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($curl, CURLOPT_TIMEOUT, 30);
  $url = 'http://atlasmuseum.net/w/api.php';
  $url = sprintf("%s?%s", $url, http_build_query($data, null, '&', PHP_QUERY_RFC3986));

  $url = str_replace('%5Cn', '%0A', $url);
  $url = str_replace('%253D', '%3D', $url);

  curl_setopt($curl, CURLOPT_URL, $url);
  return json_decode(curl_exec($curl));
}

function getArtwork($article) {
  $baseUrl = 'https://atlasmuseum.net/w/amapi/index.php';
  $parameters = [
    'action' => 'amgetartwork',
    'article' => $article
  ];

  $data = callApi([
    'action' => 'query',
    'prop' => 'revisions',
    'rvprop' => 'content',
    'titles' => $article
  ]);

  if (!is_null($data->query->pages->{'-1'})) {
    return '';
  } else {
    foreach ($data->query->pages as $artwork) {
      return $artwork->revisions[0]->{'*'};
    }
  }
}

function decodeText($text) {
  $data = [];

  foreach(preg_split("/((\r?\n)|(\r\n?))/", $text) as $line) {
    if ($line != "{{Notice d'œuvre" && $line != "}}") {
      $line2 = preg_replace('/^[\s]*\|[\s]*/', '', $line);
      $line2 = preg_replace('/^([^=]*)[\s]*=[\s]*/', '$1=', $line2);
      $key = preg_replace('/^([^=]*)=.*$/', '$1', $line2);
      $value = preg_replace('/^([^=]*)=/', '', $line2);
      $data[$key] = $value;
    }
  }

  return $data;
}

function merge($text1, $text2) {
  $data1 = decodeText($text1);
  $data2 = decodeText($text2);

  $data = [];
  foreach($data1 as $key => $value)
    $data[$key] = $value;
  foreach($data2 as $key => $value)
    $data[$key] = $value;

  $text = "{{Notice d'œuvre\n";
  foreach($data as $key => $value)
    $text .= "|" . $key . "=" . $value . "\n";
  $text .= "}}";

  var_dump($text);

  return $text;
}

$article = "Serpentine rouge (Jimmie Durham)";
$text = "{{Notice d'œuvre\n|titre=Test\n|image_principale=Serpentinerouge.jpg\n}}";

$originalText = getArtwork($article);

$mergedText = $text;
if ($originalText != '') {
  $mergedText = merge($originalText, $text);
}

var_dump($mergedText);
