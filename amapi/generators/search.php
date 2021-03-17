<?php
/*****************************************************************************
 * search.php
 *
 * Recherche d'oeuvres par chaîne de caractères
 *****************************************************************************/

if (!class_exists('Search')) {

  require_once('includes/api.php');

  class Search {
    /**
     * Valide les paramètres
     *
     * @return {Object} Tableau contenant le résultat de la validation
     */
    public static function validateQuery() {
      $payload = [];

      $search = getRequestParameter('search');
      if (is_null($search) || $search == '')
        return [
          'success' => 0,
          'error' => [
            'code' => 'no_search',
            'info' => 'No value provided for parameter "search".',
            'status' => 400
          ]
        ];

      $payload = [
        'search' => $search
      ];

      return [
        'success' => 1,
        'payload' => $payload
      ];
    }

    protected static function doQuery($queryString) {
      $results = [];
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

    protected static function getArtworksByTitle($search) {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $searchLower = strtolower($search);
      $searchUpper = ucfirst($searchLower);

      $queryParameters = [
        '?Coordonnées' => 'coordinates',
        '?Auteur' => 'artist',
        'limit' => $limit
      ];

      $queryString = '[[Category:Notices d\'œuvre]] [[Titre::~*' . $searchLower . '*]] OR [[Category:Notices d\'œuvre]] [[Titre::~*' . $searchUpper . '*]]';
      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      return self::doQuery($queryString);
    }

    protected static function getArtworksByArtist($search) {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $searchLower = strtolower($search);
      $searchUpper = ucfirst($searchLower);

      $queryParameters = [
        '?Coordonnées' => 'coordinates',
        '?Auteur' => 'artist',
        'limit' => $limit
      ];

      $queryString = '[[Category:Notices d\'œuvre]] [[Auteur::~*' . $searchLower . '*]] OR [[Category:Notices d\'œuvre]] [[Auteur::~*' . $searchUpper . '*]]';
      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      return self::doQuery($queryString);
    }

    protected static function getArtworksByCity($search) {
      $offset = 0;
      $limit = 5000;
      $results = [];

      $searchLower = strtolower($search);
      $searchUpper = ucfirst($searchLower);

      $queryParameters = [
        '?Coordonnées' => 'coordinates',
        '?Auteur' => 'artist',
        'limit' => $limit
      ];

      $queryString = '[[Category:Notices d\'œuvre]] [[Ville::~*' . $searchLower . '*]] OR [[Category:Notices d\'œuvre]] [[Ville::~*' . $searchUpper . '*]]';
      foreach ($queryParameters as $key => $value) {
        $queryString .= '|' . $key . '=' . $value;
      }

      return self::doQuery($queryString);
    }

    protected static function mergeArtworks($artworksTitle, $artworksArtist, $artworksCity) {
      // Tableau de retour
      $artworks = $artworksTitle;

      // Articles déjà rencontrés
      $articles = [];

      for ($i = 0; $i < sizeof($artworksTitle); $i++) {
        array_push($articles, $artworksTitle[$i]->fulltext);
      }

      for ($i = 0; $i < sizeof($artworksArtist); $i++) {
        if (!in_array($artworksArtist[$i]->fulltext, $articles)) {
          array_push($artworks, $artworksArtist[$i]);
          array_push($articles, $artworksArtist[$i]->fulltext);
        }
      }

      for ($i = 0; $i < sizeof($artworksCity); $i++) {
        if (!in_array($artworksCity[$i]->fulltext, $articles)) {
          array_push($artworks, $artworksCity[$i]);
          array_push($articles, $artworksCity[$i]->fulltext);
        }
      }

      return $artworks;
    }

    /**
     * Retourne les résultats d'une recherche par chaîne de caractères
     *
     * @param {String} search Chaîne à chercher
     * @return {Object} oeuvres
     */
    public static function getData($payload) {
      $artworksTitle = self::getArtworksByTitle($payload['search']);
      $artworksArtist = self::getArtworksByArtist($payload['search']);
      $artworksCity = self::getArtworksByCity($payload['search']);

      $artworks = self::mergeArtworks($artworksTitle, $artworksArtist, $artworksCity);

      return $artworks;
    }

  }
}
