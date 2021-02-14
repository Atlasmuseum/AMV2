<?php

header('Content-Type: text/html; charset=utf-8');

define('LOG_FILE_NAME', 'app.log');
define('SCRIPT_URL', 'http://atlasmuseum.net/w/app/');
define('API_URL', 'http://atlasmuseum.net/wiki/api.php');

$correspondances = array(
	'titre' => 'titre',
	'artiste' => 'artiste',
	'nature' => 'nature',
	'description' => 'description',
	'couleur' => 'couleur',
	'materiaux' => 'materiaux',
	'nomsite' => 'Site nom',
	'detailsite' => 'Site details',
	'inauguration' => 'inauguration',
	'etat' => 'conservation',
	'petat' => 'precision_etat_conservation',
	'pmr' => 'Site pmr',
	'latitude' => 'latitude',
	'longitude' => 'longitude',
	'photo' => 'photo'
);

function diacritics($string) {
	$string = str_replace("\u00c0", "À", $string);
	$string = str_replace("\u00c1", "Á", $string);
	$string = str_replace("\u00c2", "Â", $string);
	$string = str_replace("\u00c3", "Ã", $string);
	$string = str_replace("\u00c4", "Ä", $string);
	$string = str_replace("\u00c5", "Å", $string);
	$string = str_replace("\u00c6", "Æ", $string);
	$string = str_replace("\u00c7", "Ç", $string);
	$string = str_replace("\u00c8", "È", $string);
	$string = str_replace("\u00c9", "É", $string);
	$string = str_replace("\u00ca", "Ê", $string);
	$string = str_replace("\u00cb", "Ë", $string);
	$string = str_replace("\u00cc", "Ì", $string);
	$string = str_replace("\u00cd", "Í", $string);
	$string = str_replace("\u00ce", "Î", $string);
	$string = str_replace("\u00cf", "Ï", $string);
	$string = str_replace("\u00d1", "Ñ", $string);
	$string = str_replace("\u00d2", "Ò", $string);
	$string = str_replace("\u00d3", "Ó", $string);
	$string = str_replace("\u00d4", "Ô", $string);
	$string = str_replace("\u00d5", "Õ", $string);
	$string = str_replace("\u00d6", "Ö", $string);
	$string = str_replace("\u00d8", "Ø", $string);
	$string = str_replace("\u00d9", "Ù", $string);
	$string = str_replace("\u00da", "Ú", $string);
	$string = str_replace("\u00db", "Û", $string);
	$string = str_replace("\u00dc", "Ü", $string);
	$string = str_replace("\u00dd", "Ý", $string);

	$string = str_replace("\u00df", "ß", $string);
	$string = str_replace("\u00e0", "à", $string);
	$string = str_replace("\u00e1", "á", $string);
	$string = str_replace("\u00e2", "â", $string);
	$string = str_replace("\u00e3", "ã", $string);
	$string = str_replace("\u00e4", "ä", $string);
	$string = str_replace("\u00e5", "å", $string);
	$string = str_replace("\u00e6", "æ", $string);
	$string = str_replace("\u00e7", "ç", $string);
	$string = str_replace("\u00e8", "è", $string);
	$string = str_replace("\u00e9", "é", $string);
	$string = str_replace("\u00ea", "ê", $string);
	$string = str_replace("\u00eb", "ë", $string);
	$string = str_replace("\u00ec", "ì", $string);
	$string = str_replace("\u00ed", "í", $string);
	$string = str_replace("\u00ee", "î", $string);
	$string = str_replace("\u00ef", "ï", $string);
	$string = str_replace("\u00f0", "ð", $string);
	$string = str_replace("\u00f1", "ñ", $string);
	$string = str_replace("\u00f2", "ò", $string);
	$string = str_replace("\u00f3", "ó", $string);
	$string = str_replace("\u00f4", "ô", $string);
	$string = str_replace("\u00f5", "õ", $string);
	$string = str_replace("\u00f6", "ö", $string);
	$string = str_replace("\u00f8", "ø", $string);
	$string = str_replace("\u00f9", "ù", $string);
	$string = str_replace("\u00fa", "ú", $string);
	$string = str_replace("\u00fb", "û", $string);
	$string = str_replace("\u00fc", "ü", $string);
	$string = str_replace("\u00fd", "ý", $string);
	$string = str_replace("\u00ff", "ÿ", $string);
	
	return $string;
}

function api_action($data) {
	$data['format'] = 'json';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, API_URL);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
     
	curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
	$output = curl_exec($ch);
	curl_close ($ch);

	return json_decode($output, TRUE);
}

function attempt_login($user, $pass, $token='') {
	return api_action ( array(
		'action'     => 'login',
		'lgname'     => $user,
		'lgpassword' => $pass,
		'lgtoken'    => $token
	));
}

function login($user, $pass) {
	
	$data = attempt_login($user, $pass);
	$result = $data['login']['result'];

	if ($result == 'NeedToken') {
		$token = $data['login']['token'];
		$data = attempt_login($user, $pass, $token);
		$result = $data['login']['result'];
		$token  = $data['login']['lgtoken'];
	}

}

/**
 * add_picture
 **/
function add_picture($id, $page_title, $content_original, $photo) {
	//-- élimine tous les sauts de lignes du contenu de la page
	$content = str_replace(array("\r", "\n"), '', $content_original);

	$image_principale = '';
	$images_secondaires = array();

	if (stripos($content, 'image_principale') !== false) {
		//-- il existe déjà un champ "image_principale" : en extraire la valeur
		$image_principale = preg_replace('/^.*\|[\s]*image_principale[\s]*=[\s]*/i', '', $content);
		$image_principale = preg_replace('/[\s]*\|.*$/', '', $image_principale);
	}
	
	if ($image_principale == $photo)
		//-- l'image principale correspond déjà à la photo qu'on veut rajouter : fin de la procédure
		return false;

	if ($image_principale == '' || $image_principale == 'image-manquante.jpg') {
		//-- l'image principale actuelle est vide ou manquante : la remplacer
		return true;
	} else {
		//-- dans le cas contraire : ajouter l'image en fin de notice avec le modèle {{UploadImageGalerieAutre}}

		$galerie = array();

		if (stripos($content, 'UploadImageGalerieAutre') !== false) {
			//-- extrait les modèles {{UploadImageGalerieAutre}} existants
			$galerie = preg_split('/\{\{[\s]*UploadImageGalerieAutre[\s]*\|[\s]*/i', $content);
			array_shift($galerie);
		}
		//-- supprime les caractères de fin de modèle pour chaque entrée
		foreach($galerie as &$g)
			$g = preg_replace('/[\s]*\}\}.*$/', '', $g);
		
		
		if (!in_array($photo, $galerie)) {
			//-- l'image qu'on veut ajouter n'est pas dans la galerie des autres images actuelles : l'ajouter
			
			//-- récupère un token d'édition
			$data = api_action(array(
				'action'	=> 'tokens',
				'type'	=> 'edit'
			));
			$token = $data['tokens']['edittoken'];
			
			//-- édition
			$data = api_action(array(
				'action'	=> 'edit',
				'title'	=> $page_title,
				'text'	=> $content_original . "\n{{UploadImageGalerieAutre|" . $photo . "}}",
				'token'	=> $token
			));
		}
			
	}
	
	return false;
} //-- add_picture

print '<pre>';	
	
	
$xml = simplexml_load_string(file_get_contents('http://atlasmuseum.net/w/app/file.xml'));
$json = json_encode($xml);
$array = json_decode($json, TRUE);
	
login('TestAppli', 'Janvier16');

$idsEdit = array();

if (isset($array['contribution']['@attributes'])) {
	$modified_parameter = $array['contribution']['modification']['@attributes']['value'];
	$modified_value = $array['contribution'][$modified_parameter]['@attributes']['value'];
	$id = $array['contribution']['id']['@attributes']['value'];
	//$id = 3673;
	if (!isset($idsEdit[$id]))
		$idsEdit[$id] = array();
	$idsEdit[$id][$correspondances[$modified_parameter]] = $modified_value;
} else {
	foreach($array['contribution'] as $contribution) {
		$modified_parameter = $contribution['modification']['@attributes']['value'];
		$modified_value = $contribution[$modified_parameter]['@attributes']['value'];
		//$id = $contribution['id']['@attributes']['value'];
		$id = 3673;
		if (!isset($idsEdit[$id]))
			$idsEdit[$id] = array();
		$idsEdit[$id][$correspondances[$modified_parameter]] = $modified_value;
	}
}

foreach($idsEdit as $key=>$id) {
	if (isset($id['latitude']) && isset($id['longitude']))
		$id['Site coordonnees'] = $id['latitude'] . ", " . $id['longitude'];
	unset($id[$key]['latitude']);
	unset($id[$key]['longitude']);
	
	$data = api_action(array(
		'action'	=> 'query',
		'prop'	=> 'revisions',
		'pageids'	=> $key,
		'rvprop' => 'content'
	));
	$content = $data['query']['pages'][$key]['revisions'][0]['*'];

	$page_title = $data['query']['pages'][$key]['title'];
	//$page_title = "Utilisateur:François";
	if (!isset($id['Site coordonnees'])) {
		$coords = str_replace(array("\r", "\n"), '', $content);
		$coords = preg_replace('/\s+/', '', $coords);
		if (stripos($coords, '|sitecoordonnees') !== false) {
			$coords = preg_replace('/^.*\|sitecoordonnees=/i', '', $coords);
			$coords = preg_replace('/\|.*$/', '', $coords);
			//$coords = preg_replace('/,/', ', ', $coords);
			$id['Site coordonnees'] = $coords;
		}
	}

	$edit = array(
		'action'	=> 'sfautoedit',
		'target'	=> $page_title,
		'form'    => 'Notice d\'œuvre'
	);
	
	foreach ($id as $param=>$value)
		if ($param != 'photo')
			$edit['Notice d\'œuvre['.$param.']'] = $value;
		else {
			if (add_picture($key, $page_title, $content, $value))
				$edit['Notice d\'œuvre[image_principale]'] = $value;
		}

	if (count($edit) > 3)
		//-- plus de 3 entrées dans le tableau $edit = au moins un champ à éditer
		$data = api_action($edit);
	var_dump($edit);
	
}
	
print '</pre>';	
?>


