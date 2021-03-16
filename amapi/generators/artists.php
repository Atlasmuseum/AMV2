<?php
/*****************************************************************************
 * Artists.php
 *
 * Récupère la liste des artistes
 *****************************************************************************/

if (!class_exists('Artists')) {

  require_once('includes/api.php');

  class Artists {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $payload = [];

      return [
        'success' => 1,
        'payload' => $payload
      ];
    }

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

      $queryString = '[[Category:Artistes]]';
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

    protected static function getArtists() {
      $data = self::getDataAM();
      $results = [];
      for ($i = 0; $i < sizeof($data); $i++) {
        array_push($results, $data[$i]->fulltext);
      }
      return $results;
    }

    /**
     * Retourne la liste des artistes
     *
     * @param {*} $payload
     * @return {Object} Liste
     */
    public static function getData($payload) {
      $artists = [];

      $artists = self::getArtists();

      return $artists;
    }

  }
}
