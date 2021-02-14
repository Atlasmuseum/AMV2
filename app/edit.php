<?php
$endPoint = "https://atlasmuseum.net/w/api.php";
$cookiefile = "/tmp/cookie.txt";

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

/*
function api_post($parameters) {
    global
      $endPoint,
      $cookiefile;
  
    //-- spécifie le format de réponse attendu
    $parameters['format'] = "php";
    
    //-- construit les paramètres à envoyer
    $postdata = http_build_query($parameters);
    
    //-- envoie la requête avec cURL
    $ch = curl_init();
    // $cookiefile = tempnam("/tmp", "CURLCOOKIE");
    curl_setopt_array($ch, array(
      CURLOPT_COOKIEFILE => $cookiefile,
      CURLOPT_COOKIEJAR => $cookiefile,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => TRUE
    ));
    curl_setopt($ch, CURLOPT_URL, $endPoint);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  
    //-- envoie les cookies actuels avec curl
    $cookies = array();
    foreach ($_COOKIE as $key => $value)
      if ($key != 'Array')
        $cookies[] = $key . '=' . $value;
    curl_setopt( $ch, CURLOPT_COOKIE, implode(';', $cookies) );
    
    //-- arrête la session en cours
    session_write_close();
    
    $result = unserialize(curl_exec($ch));
    curl_close($ch);
    
    //-- redémarre la session
    session_start();
    
    return $result;
  }
*/

/**
 * Demande de token d'édition, préalable nécessaire à une édition d'article.
 *
 * @return {String} Token demandé
 */
function getCSRFToken() {
  global
    $endPoint,
    $cookiefile;
  
  $params = [
    "action" => "query",
    "meta" => "tokens",
    "format" => "json"
  ];
  
  $url = $endPoint . "?" . http_build_query( $params );

  $ch = curl_init( $url );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
  $output = curl_exec( $ch );
  curl_close( $ch );
  
  $result = json_decode( $output, true );
  
  return $result["query"]["tokens"]["csrftoken"];
}

/**
 * Appel à l'API atlasmuseum pour une tentative de login.
 * Cette fonction est appelée deux fois lors d'un processus de login complet :
 * la première fois pour obtenir un token de login, la deuxième fois pour
 * finaliser la connexion avec ce token.
 *
 * @param {String} $user Login de l'utilisateur
 * @param {String} $pass Mot de passe de l'utilisateur
 * @param {String} [$token=null] Token de login
 * @return {String} Retour de l'API avec les paramètres de connexion fournis
 */
function loginRequest($user, $pass, $token = null ) {
  global
    $endPoint,
    $cookiefile;

  $params = [
    "action" => "login",
    "lgname" => $user,
    "lgpassword" => $pass,
    "lgtoken" => $token,
    "format" => "json"
  ];

  $ch = curl_init();

  curl_setopt( $ch, CURLOPT_URL, $endPoint );
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );

  $output = curl_exec( $ch );
  curl_close( $ch );

  return $output;
}

/**
 * Tentative de connexion au wiki atlasmuseum
 *
 * @param {String} $user Login de l'utilisateur
 * @param {String} $pass Mot de passe de l'utilisateur
 * @return {String} Retour de l'API avec les paramètres de connexion fournis
 */
function login($user, $pass) {  
    $result = loginRequest($user, $pass);
    $output = json_decode( $result, true );
    $token = $output['login']['token'];
    $result = loginRequest($user, $pass, $token);

    return $result;
}

/**
 * Edite un article (et le crée s'il n'existe pas déjà)
 *
 * @param {String} $article Nom de l'article
 * @param {String} $text Texte de l'article
 * @param {String} $token Token d'édition CSRF préalablement obtenu
 * @return {String} Réponse de l'API
 */
function edit($article, $text, $editToken) {
  global
    $endPoint,
    $cookiefile;

  $params = [
    "action" => "edit",
    "title" => $article,
    "format" => "json",
    "token" => $editToken,
    "text" => $text
  ];

  $ch = curl_init();

  curl_setopt( $ch, CURLOPT_URL, $endPoint );
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
  $output = curl_exec( $ch );
  curl_close( $ch );
  
  return $output;
}

/**
 * Fusionne les infos provenant d'un article existant (s'il existe) et celles
 * fournies par l'utilisateur.
 * 
 * @param {String} $article Nom de l'article
 * @param {String} $text Texte qu'on souhaite rajouter
 * @return {String} Contenu fusionné
 */
function mergeText($article, $text) {
  $originalText = getArtwork($article);

  $mergedText = $text;
  if ($originalText != '') {
    $data1 = decodeText($originalText);
    $data2 = decodeText($text);

    $data = [];
    foreach($data1 as $key => $value)
      $data[$key] = $value;
    foreach($data2 as $key => $value)
      $data[$key] = $value;

    $mergedText = "{{Notice d'œuvre\n";
    foreach($data as $key => $value)
      $mergedText .= "|" . $key . "=" . $value . "\n";
    $mergedText .= "}}";
  }

  return $mergedText;
}

/**
 * Programme principal
 */

$input = json_decode(file_get_contents('php://input'), true);

if ($input['action'] != null) {
  if ($input['action'] == 'edit' || $input['action'] == 'create') {
    $user = $input['user'];
    $password = $input['password'];
    if ($user != null && $password != null) {
      $result = json_decode(login($user, $password), true);
      $output = ['result' => 'error'];
      if ($result['login'] != null && $result['login']['result'] != null && $result['login']['result'] == 'Success') {
        $text = mergeText($input['article'], $input['text']);

        $output = ['result' => 'success'];

        $token = getCSRFToken();
        $result = json_decode(edit($input['article'], $text, $token), true);
        if ($result['edit'] != null && $result['edit']['result'] == 'Success')
          $output = ['result' => 'success'];
        else
          $output = ['result' => 'error', 'error' => json_encode($result)];
      }
      print json_encode($output);
    } else {
      print json_encode(['result' => 'error', 'error' => json_encode($result)]);
    }
  }
}
else
  print json_encode(['result' => 'error']);
