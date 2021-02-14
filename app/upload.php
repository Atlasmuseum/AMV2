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

function curlFile($file) {
  return new CURLFile( realpath( $file["tmp_name"] ), $file["type"], $file["name"] );
}

function upload($file, $token) {
  global
    $endPoint,
    $cookiefile;
  
  $params = [
    "action" => "upload",
    "filename" => $file['name'],
    "ignorewarnings" => "1",
    "token" => $token,
    "format" => "json",
    //"file" => curlFile($file)
    "file" => file_get_contents($file['tmp_name'])
  ];
  var_dump($params);
  
  $ch = curl_init();
  
  curl_setopt( $ch, CURLOPT_URL, $endPoint );
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'content-type: multipart/form-data' ));
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookiefile );
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookiefile );
  
  $output = curl_exec( $ch );
  curl_close( $ch );
  
  return $output;
}

function uploadFile($localfilename, $filename, $token) {
  global
    $endPoint,
    $cookiefile;
	
	$handle = fopen($localfilename, "rb");
	$file_body = fread($handle, filesize($localfilename));
	fclose($handle);	
		
	$destination = $endPoint;
	$eol = "\r\n";
	$data = '';
	$header = ''; 
	$mime_boundary=md5(time());
	
	$params = array ('action'=>'upload',
					'filename'=>$filename,
					//'ignorewarnings'=>'yes', //if uncommented will ignore warnings and will overwrite existing files. Think twice.
					'token'=>$token,
					'format'=>'json');	
	//parameters 	
	foreach ($params as $key=>$value){
			$data .= '--' . $mime_boundary . $eol;
			$data .= 'Content-Disposition: form-data; name="' . $key . '"' . $eol;
			$data .= 'Content-Type: text/plain; charset=UTF-8' .  $eol;
			$data .= 'Content-Transfer-Encoding: 8bit' .  $eol . $eol;
			$data .= $value . $eol;
		}
	
	//file
	$data .= '--' . $mime_boundary . $eol;
	$data .= 'Content-Disposition: form-data; name="file"; filename="'.$filename.'"' . $eol; //Filename here
	$data .= 'Content-Type: application/octet-stream; charset=UTF-8' . $eol;
	$data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
	$data .= $file_body . $eol;
	$data .= "--" . $mime_boundary . "--" . $eol . $eol; // finish with two eol's
	
	//headers
	$header .= 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)' . $eol;
	$header .= 'Content-Type: multipart/form-data; boundary='.$mime_boundary . $eol;
	$header .= 'Host: hy.wikisource.org'. $eol;
	$header .= 'Cookie: '. $cookiefile . $eol;
	$header .= 'Content-Length: ' . strlen($data) . $eol;
	$header .= 'Connection: Keep-Alive';
	
	$params = array('http' => array(
					  'method' => 'POST',
					  'header' => $header,					  
					  'content' => $data					  
				   ));
	 
	$ctx = stream_context_create($params);
	var_dump($params);
	$response = @file_get_contents($destination, FILE_TEXT, $ctx);

	return $response;
}

function uploadByUrl($file, $token) {
	global
        $endPoint,
        $cookiefile;

  $current = file_get_contents($file['tmp_name']);
  file_put_contents('pictures/' . $file['name'], $current);

	$params = [
		"action" => "upload",
		"filename" => $file['name'],
		"url" => "https://atlasmuseum.net/w/app/pictures/" . $file['name'],
		"ignorewarnings" => "1",
		"token" => $token,
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

$endResult = 'error';

ob_start();

var_dump($_POST);
var_dump($_FILES);

if ($_POST['name'] != null && $_POST['user'] != null && $_POST['password'] != null && $_FILES != null && $_FILES['file'] != null) {
  $user = $_POST['user'];
  $password = $_POST['password'];
  $result = json_decode(login($user, $password), true);

  if ($result['login'] != null && $result['login']['result'] != null && $result['login']['result'] == 'Success') {

    $token = getCSRFToken();
    var_dump($token);
    $uploadResult = uploadByUrl($_FILES['file'], $token);
    var_dump($uploadResult);

    $endResult = 'success';
  } else {
    $endResult = 'Login error';
  }
}

$log = ob_get_contents();
file_put_contents('log.txt', $log);
ob_end_clean();

print json_encode(['result' => $endResult]);
