<?php
/*****************************************************************************
 * route.php
 *
 * Détermination de la route
 *****************************************************************************/

require_once ('./includes/utils.php');
require_once ('./includes/config.php');
require_once ('./includes/response.php');

if (!class_exists('Router')) {

  class Router {

    protected static function getMap($response) {
      require_once ('generators/map.php');
      $validation = Map::validateQuery();
      if ($validation['success']) {
        $data = [];
        $origin = strtolower(getRequestParameter('origin'));
        if (is_null($origin)) 
          $data = Map::getMap();
        else {
          if ($origin == 'atlasmuseum')
            $data = Map::getMapAM();
          else
          if ($origin == 'wikidata')
            $data = Map::getMapWD();
        }
        if (sizeof($data) > 0) {
          $response->setValue('entities', $data);
          $response->setSuccess(true);
          $response->setStatusCode(200);
        } else {
          $response->setError('no_data', 'No data found for map.', 200);
        }
      } else {
        $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
      }
    }

    protected static function getCollection($response) {
      require_once ('generators/collection.php');
      $validation = Collection::validateQuery();
      if ($validation['success']) {
        $data = [];
        $collection = str_replace('_', ' ', urldecode(getRequestParameter('collection')));
        if (!is_null($collection))
          $data = Collection::getCollection($collection);
        if (sizeof($data) > 0) {
          $response->setValue('entities', $data);
          $response->setSuccess(true);
          $response->setStatusCode(200);
        } else {
          $response->setError('no_data', 'No data found for collection: ' . $collection, 200);
        }
      } else {
        $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
      }
    }

    protected static function getArtwork($response) {
      require_once ('generators/artwork.php');
      $validation = Artwork::validateQuery();
      if ($validation['success']) {
        $data = [];
        $article = str_replace('_', ' ', urldecode(getRequestParameter('article')));
        $redirect = getRequestParameter('redirect');
        if (!is_null($article))
          $data = Artwork::getArtwork($article, !is_null($redirect) && ($redirect === "1" || strtolower($redirect) === "true"));
        if (sizeof($data) > 0) {
          $response->setValue('entities', $data);
          $response->setSuccess(true);
          $response->setStatusCode(200);
        } else {
          $response->setError('no_data', 'No data found for artwork: ' . $article, 200);
        }
      } else {
        $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
      }
    }

    protected static function getArtist($response) {
      require_once ('generators/artist.php');
      $validation = Artist::validateQuery();
      if ($validation['success']) {
        $data = [];
        $article = str_replace('_', ' ', urldecode(getRequestParameter('article')));
        $redirect = getRequestParameter('redirect');
        if (!is_null($article))
          $data = Artist::getArtist($article, !is_null($redirect) && ($redirect === "1" || strtolower($redirect) === "true"));
        if (sizeof($data) > 0) {
          $response->setValue('entities', $data);
          $response->setSuccess(true);
          $response->setStatusCode(200);
        } else {
          $response->setError('no_data', 'No data found for artist: ' . $article, 200);
        }
      } else {
        $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
      }
    }

    /**
     * Retourne la réponse en fonction de la route
     *
     * @return {Response} la réponse
     */
    public static function getResponse() {
      $response = new Response();

      /**
       * Détermine l'action à mener
       */
      $action = getRequestParameter('action');

      if (is_null($action)) {
        // Pas de code d'action -> erreur
        $response->setError('no_action', 'No value for parameter "action".', 200);
      } else {
        // Détermination de la réponse en fonction du code d'action
        switch (strtolower($action)) {
          case 'amgetmap':
            // Carte
            self::getMap($response);
            break;
          
          case 'amgetcollection':
            // Collection
            self::getCollection($response);
            break;

          case 'amgetartwork':
            // Artwork
            self::getArtwork($response);
            break;

          case 'amgetartist':
            // Artist
            self::getArtist($response);
            break;

          default:
            // Action inconnue -> erreur
            $response->setError('unknown_action', 'Unrecognized value for parameter "action": ' . $action . '.', 400);
        }
      }

      return $response;
    }
  }

}

?>
