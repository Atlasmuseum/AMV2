<?php
$endPoint = "https://atlasmuseum.net/w/api.php";
$cookiefile = "/tmp/cookie.txt";

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

function getCSRFToken() {
    global
      $endPoint,
      $cookiefile;
  
      $params1 = [
          "action" => "query",
          "meta" => "tokens",
          "format" => "json"
      ];
  
      $url = $endPoint . "?" . http_build_query( $params1 );

      $ch = curl_init( $url );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
      curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
      $output = curl_exec( $ch );
      curl_close( $ch );
  
    $result = json_decode( $output, true );
  
    return $result["query"]["tokens"]["csrftoken"];
}

  function loginRequest($amLogin, $amPass, $logintoken = null ) {
    global
      $endPoint,
      $cookiefile;
  
      $params2 = [
          "action" => "login",
          "lgname" => $amLogin,
          "lgpassword" => $amPass,
          "lgtoken" => $logintoken,
          "format" => "json"
      ];
  
    $ch = curl_init();
  
      curl_setopt( $ch, CURLOPT_URL, $endPoint );
      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params2 ) );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
    curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
      $output = curl_exec( $ch );
    curl_close( $ch );
  
    return $output;
  }

function login($user, $pass) {  
    $result = loginRequest($user, $pass);
    $output = json_decode( $result, true );
    $token = $output['login']['token'];
    $result = loginRequest($user, $pass, $token);

    return $result;
}

function edit($editToken) {
    global
      $endPoint,
      $cookiefile;
  
      $params2 = [
          "action" => "edit",
          "title" => 'Utilisateur:TestApp',
          "format" => "json",
          "token" => $editToken,
          "text" => "Test",
          "summary" => "Test"
      ];

      $ch = curl_init();
  
      curl_setopt( $ch, CURLOPT_URL, $endPoint );
      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params2 ) );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
    curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
      $output = curl_exec( $ch );
    curl_close( $ch );
  
    return $output;
}

$action = $_POST['action'];

$input = json_decode(file_get_contents('php://input'), true);

if ($input['action'] != null)
    if ($input['action'] == 'login') {
        $user = $input['user'];
        $password = $input['password'];
        if ($user != null && $password != null) {
            $result = json_decode(login($user, $password), true);
            $output = [
                'result' => 'error'
            ];
            if ($result['login'] != null && $result['login']['result'] != null && $result['login']['result'] == 'Success') {
                $output = [
                    'result' => 'success'
                ];
            }
            print json_encode($output);
        }
    }
else
    print json_encode(['result' => 'test']);

/*
if ($input['action'] != null) {
    /*
    if ($input['action'] == 'login') {
        $user = $input['user'];
        $password = $input['password'];
        if ($user != null && $password != null) {
            $result = json_decode(login($user, $password), true);
            $output = [
                'result' => 'error'
            ];
            if ($result['login'] != null && $result['login']['result'] != null && $result['login']['result'] == 'Success') {
                $output = [
                    'result' => 'success'
                ];
            }
            print json_encode($output);
        }
        print json_encode(['result' => 'test']);
    } else
    if ($input['action'] == 'edit') {
    } else {
        print json_encode({});
    }
    *//*
    //print json_encode(['result' => 'test']);
    print json_encode({});
} else {
    print json_encode({});
}
*/