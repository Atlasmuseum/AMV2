<?php

header('Content-Type: text/html; charset=utf-8');

define('SCRIPT_URL', 'http://atlasmuseum.net/w/app/');
define('API_URL', 'http://atlasmuseum.net/wiki/api.php');
define('BASE_URL', 'http://atlasmuseum.net/wiki/index.php?title=');
define('PAGE_LIMIT', 10000);
define('PAGE_OFFSET', 0);

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

function getCategoryMembers($category) {
	
	$continue = '';
	
	$pageids = array();
	
	do {

		$data = api_action(array(
			'action'	=> 'query',
			'list' => categorymembers,
			'cmlimit' => 500,
			'cmnamespace' => 0,
			'cmtype' => 'page',
			'cmpageid' => $category,
			'cmprop' => 'ids|title',
			'cmcontinue' => $continue
		));
		
		if (isset($data['query-continue']))
			$continue = $data['query-continue']['categorymembers']['cmcontinue']; 
		else
			$continue = '';
		
		foreach ($data['query']['categorymembers'] as $page)
			array_push($pageids, $page['pageid']);
		
	} while($continue != '');
	
	return $pageids;
	
}

function getContent($pageids) {
	
	$sliceIndex = PAGE_OFFSET;
	$content = array();
	
	do {
		$limit = min(50, PAGE_OFFSET+PAGE_LIMIT-$sliceIndex);
		$ids = implode('|',array_slice($pageids, $sliceIndex, $limit));
		
		$data = api_action(array(
			'action'	=> 'query',
			'prop' => 'revisions',
			'pageids' => $ids,
			'rvprop' => 'content'
		));
		
		//foreach ($l_result->{'query'}->{'pages'} as $l_page)
			//array_push($l_imageInfos, $l_page);
			
		//var_dump($data);	
		
		$sliceIndex += 50;
		
		$content = array_merge($content, $data['query']['pages']);
		
	} while ($sliceIndex < PAGE_OFFSET+PAGE_LIMIT && $sliceIndex < count($pageids));
	
	return $content;

}

function processContent($content) {
	
	print '<html><body><pre>';

	foreach ($content as $page) {

		$pageid = $page['pageid'];
		print '   obj = new JSONObject("{\"id\":\"' . $pageid . '\"';
		
		$url = BASE_URL . rawurlencode ($page['title']);
		print ',\"url\":\"'.$url.'\"';

		$text = $page['revisions'][0]['*'];
		$text = preg_replace('/\r/', ' ', $text);
		$text = preg_replace('/\n/', ' ', $text);
		$text = preg_replace('/\t/', ' ', $text);
		$text = preg_replace('/[ ]+/', ' ', $text);
		$text = preg_replace('/^.*\{\{Notice d\'œuvre[ ]+/', '', $text);
		
		$data = preg_split('/[ ]*\|([^=]*)=/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		for ($i=1; $i<count($data); $i+=2) {
			
			$data[$i] = preg_replace('/ /', '', $data[$i]);
			$data[$i+1] = preg_replace('/\"/', '\\\"', $data[$i+1]);
			$data[$i+1] = preg_replace('/<[^>]*>/', '', $data[$i+1]);
			
			if ($i == count($data)-2)
				$data[$i+1] = preg_replace('/\}\}.*$/', '', $data[$i+1]);
				
			$data[$i+1] = preg_replace('/[\s]*$/', '', $data[$i+1]);
			$data[$i+1] = preg_replace('/\"/', '\\\"', $data[$i+1]);

			if ($data[$i] != 'description' && $data[$i] != 'noticeaugmentée') {
				if ($data[$i] == 'Sitecoordonnees') {
					$latlng = preg_split('/, /', $data[$i+1]);
					print ',\"latitude\":\"' . $latlng[0]. '\"';
					print ',\"longitude\":\"' . $latlng[1]. '\"';
				} else
					print ',\"' . $data[$i] . '\":\"' . $data[$i+1]. '\"';
			}
		}
		
		print '}");'."\n";
		print "    data.put(obj); nbentries++;\n";

	}
	
	print '</pre></body></html>';

}

$pageids = getCategoryMembers(3288);

$content = getContent($pageids);

processContent($content);

?>
