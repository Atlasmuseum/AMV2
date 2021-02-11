<?php
/*****************************************************************************
 * map.php
 *
 * Récupère les données en vue d'un affichage sur la carte
 *****************************************************************************/

if (!class_exists('Map')) {

  require_once('includes/api.php');
  require_once('includes/config.php');

  class Map {

    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $update = getRequestParameter('update');
      $origin = getRequestParameter('origin');
      $nature = getRequestParameter('nature');
      $compress = getRequestParameter('compress');

      if (!is_null($origin))
        $origin = explode('|', $origin);
      else
        $origin = [];

      if (!is_null($nature))
        $nature = explode('|', $nature);
      else
        $nature = [];

      if (!is_null($compress))
        $compress = (strtolower($compress) == 'true');
      else
        $compress = false;

        return [
        'success' => 1,
        'payload' => [
          'update' => $update,
          'origin' => $origin,
          'nature' => $nature,
          'compress' => $compress
        ]
      ];
    }

    /**
     * Retourne les notices d'œuvres présentes sur atlasmuseum
     *
     * @return {Object} Œuvres sur atlasmuseum
     */
    protected static function getDataAM() {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $queryParameters = [
        '?Coordonnées' => 'coordinates',
        '?Nature' => 'nature',
        '?Wikidata' => 'wikidata',
        'limit' => $limit
      ];

      $queryString = '[[Category:Notices d\'œuvre]]';
      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      $continue = false;
      do {
        // Requête à l'API
        $parameters = [
          'action' => 'ask',
          'query' => $queryString . '|offset=' . $offset
        ];
        $tmpData = API::callApi($parameters, 'atlasmuseum');
        
        if (!is_null($tmpData)) {
          // Doit-on continuer la query avec un offset ?
          if (property_exists($tmpData, 'query-continue-offset')) {
            $continue = true;
            $offset += $limit;
          } else
            $continue = false;

          // Notices
          if (property_exists($tmpData, 'query') && property_exists($tmpData->query, 'results'))
            $results = array_merge($results, $tmpData->query->results);
        } else {
          $continue = false;
        }
      } while ($continue && $offset < 4000);

      return $results;
    }

    /**
     * Convertit les données brutes d'atlasmuseum en données utilisables
     *
     * @param {Object} $inputData - Liste des œuvres à convertir
     * @return {Array} Tableau des œuvres
     */
    public static function convertDataAM($inputData) {
      $outputData = [];

      for ($i=0; $i < sizeof($inputData); $i++) {
        $artwork = [
          'article' => '',
          'title' => '',
          'artist' => '',
          'lat' => 0,
          'lon' => 0,
          'nature' => 'pérenne',
          'wikidata' => ''
        ];

        // Titre, artiste
        if (property_exists($inputData[$i], 'fulltext')) {
          $artwork['article'] = $inputData[$i]->fulltext;
          $artwork['title'] = preg_replace('/ \([^\)]+\)$/', '', $inputData[$i]->fulltext);
          $artwork['artist'] = preg_replace('/^.* \(([^\)]+)\)$/', '$1', $inputData[$i]->fulltext);
        }

        if (property_exists($inputData[$i], 'printouts')) {
          // Coordonnées
          if (property_exists($inputData[$i]->printouts[0], '0') && property_exists($inputData[$i]->printouts[0]->{0}, 'lat')) {
            $artwork['lat'] = $inputData[$i]->printouts[0]->{0}->lat;
            $artwork['lon'] = $inputData[$i]->printouts[0]->{0}->lon;
          }

          // Nature
          if (property_exists($inputData[$i]->printouts[1], '0'))
            $artwork['nature'] = $inputData[$i]->printouts[1]->{0};
          
          // Wikidata
          if (property_exists($inputData[$i]->printouts[2], '0'))
            $artwork['wikidata'] = $inputData[$i]->printouts[2]->{0};
        }
        
        // Si les coordonnées ne sont pas nulles, on ajoute l'œuvre
        if ($artwork['lat'] != 0 && $artwork['lon'] != 0)
          array_push($outputData, $artwork);
      }

      return $outputData;
    }

    /**
     * Récupère un tableau avec tous les identifiants Wikidata déjà sur atlasmuseum
     *
     * @param {Object} $inputData - Liste des œuvres
     * @return {Array} Tableau des identifiants
     */
    public static function getWikidataIdsAM($inputData) {
      $wikidataIds = [];
      for ($i=0; $i < sizeof($inputData); $i++)
        if ($inputData[$i]['wikidata'] != '' && preg_match('/^[qQ][0-9]+$/', $inputData[$i]['wikidata']))
          array_push($wikidataIds, intval(str_ireplace('Q', '',$inputData[$i]['wikidata'])));
      
      sort($wikidataIds);

      return $wikidataIds;
    }

    /**
     * Retourne les notices d'œuvres présentes sur Wikidata
     *
     * @param {Array} $wikidataIdsAM - Identifiants Wikidata existants sur atlasmuseum
     * @return {Object} Œuvres sur Wikidata
     */
    protected static function getDataWD($wikidataIdsAM = []) {
      $outputData = [];
      /*
      $query =
        'SELECT DISTINCT ?q ?coords WHERE {' .
        '  ?q wdt:P136/wdt:P279* ?genre ;' .
        '     wdt:P625 ?coords . ' .
        '  VALUES ?genre { wd:Q557141 wd:Q219423 wd:Q17516 wd:Q326478 wd:Q2740415 }' .
        '}';*/
      $query =
        'SELECT DISTINCT ?q ?qLabel ?coords ?creatorLabel WHERE {' .
        '  ?q wdt:P136/wdt:P279* ?genre ;' .
        '     wdt:P625 ?coords .' .
        // '  VALUES ?genre { wd:Q557141 wd:Q219423 wd:Q17516 wd:Q326478 wd:Q2740415 }' .
        '  VALUES ?genre { wd:Q557141 }' .
        '  OPTIONAL { ?q wdt:P170 ?creator }' .
        '  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" . }' .
        '}';

      $queryResult = Api::Sparql($query);

      if (!is_null($queryResult)) {
        foreach ($queryResult->results->bindings as $entity) {
          $id = str_replace(WIKIDATA_ENTITY, '', $entity->q->value);

          if (!in_array(intval(substr($id, 1)),$wikidataIdsAM)) {
            $artwork = [
              'article' => '',
              'title' => $entity->qLabel->value,
              'artist' => '',
              'lat' => 0,
              'lon' => 0,
              'nature' => 'wikidata',
              'wikidata' => $id
            ];

            $coords = explode(' ', str_replace(')', '', str_replace('Point(', '', $entity->coords->value)));
            $artwork['lat'] = floatval($coords[1]);
            $artwork['lon'] = floatval($coords[0]);

            if (property_exists($entity, 'creatorLabel'))
              $artwork['artist'] = $entity->creatorLabel->value;

            array_push($outputData, $artwork);
          }
        }
      }

      usort($outputData, function ($a, $b) {
        $id1 = intval(substr($a['wikidata'], 1));
        $id2 = intval(substr($b['wikidata'], 1));
        return $id1 - $id2;
      });

      return $outputData;
    }

    public static function getMapAM() {
      return self::convertDataAM(self::getDataAM());
    }

    public static function getMapWD() {
      return self::getDataWD();
    }

    /**
     * Get data from database
     * 
     */
    protected static function getMap($nature) {
      $data = [];

      $mysqli = new mysqli(DB_SERVER, DB_NAME, DB_PASSWORD, DB_USER);
      $mysqli->set_charset("utf8");

      $query = 'SELECT * FROM ' . DATABASE_MAP;

      if (sizeof($nature) > 0) {
        $queryValues = [];
        for ($i = 0; $i < sizeof($nature); $i++)
          array_push($queryValues, '"' . str_replace('"', '\"', $nature[$i]) . '"');
        $query .= ' WHERE nature IN(' . implode(', ', $queryValues) . ')';
      }
      $query .= ';';
      $result = $mysqli->query($query);

      while ($row = $result->fetch_assoc()) {
        $artwork = [
          'article' => $row['article'],
          'title' => $row['title'],
          'artist' => $row['artist'],
          'lat' => floatval($row['lat']),
          'lon' => floatval($row['lon']),
          'nature' => $row['nature'],
          'wikidata' => $row['wikidata']
        ];

        array_push($data, $artwork);
      }

      return $data;
    }

    protected static function compress($unc) {
      $i;$c;$wc;
      $w = "";
      $dictionary = array();
      $result = array();
      $dictSize = 256;
      for ($i = 0; $i < 256; $i += 1) {
          $dictionary[chr($i)] = $i;
      }
      for ($i = 0; $i < strlen($unc); $i++) {
          $c = $unc[$i];
          $wc = $w.$c;
          if (array_key_exists($w.$c, $dictionary)) {
              $w = $w.$c;
          } else {
              array_push($result,$dictionary[$w]);
              $dictionary[$wc] = $dictSize++;
              $w = (string)$c;
          }
      }
      if ($w !== "") {
          array_push($result,$dictionary[$w]);
      }
      return implode(",",$result);
    }

    /**
     * Récupère les données en vue de leur affichage sur une carte
     *
     * @return {Object} Tableau contenant les données
     */
    public static function getData($payload) {
      if (array_key_exists('update', $payload) && $payload['update']) {
        $dataAM = [];
        $dataWD = [];
        if (sizeof($payload['origin']) == 0 || in_array('atlasmuseum', $payload['origin']))
          $dataAM = self::getMapAM();
        if (sizeof($payload['origin']) == 0 || in_array('wikidata', $payload['origin']))
          $dataWD = self::getMapwD();
        return array_merge($dataAM, $dataWD);
      } else {
        $data = self::getMap($payload['nature']);
        if ($payload['compress']) {
          return [
            'compress' => true,
            'data' => gzcompress(json_encode($data))
          ];
        }
        return $data;
      }
    }
  }

}
