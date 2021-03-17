<?php
/*****************************************************************************
 * route.php
 *
 * Détermination de la route
 *****************************************************************************/

require_once ('./includes/utils.php');
require_once ('./includes/config.php');
require_once ('./includes/response.php');

$actions = [
  'amgetmap' => [
    'include' => 'map.php',
    'class' => Map::class,
    'noData' => 'No data found for map.'
  ],
  'amgetcollection' => [
    'include' => 'collection.php',
    'class' => Collection::class,
    'noData' => 'No data found for collection.'
  ],
  'amgetartwork' => [
    'include' => 'artwork.php',
    'class' => Artwork::class,
    'noData' => 'No data found for artwork.'
  ],
  'amgetartists' => [
    'include' => 'artists.php',
    'class' => Artists::class,
    'noData' => 'No data found for artists.'
  ],
  'amgetartist' => [
    'include' => 'artist.php',
    'class' => Artist::class,
    'noData' => 'No data found for artist.'
  ],
  'amgetimage' => [
    'include' => 'image.php',
    'class' => Image::class,
    'noData' => 'No data found for image.'
  ],
  'amgetcloseartworks' => [
    'include' => 'closeArtworks.php',
    'class' => CloseArtworks::class,
    'noData' => 'No data found.'
  ],
  'amgetclosesites' => [
    'include' => 'closeSites.php',
    'class' => CloseSites::class,
    'noData' => 'No data found.'
  ],
  'amgetartworksbyartists' => [
    'include' => 'artworksByArtists.php',
    'class' => ArtworksByArtists::class,
    'noData' => 'No data found.'
  ],
  'amgetsearch' => [
    'include' => 'search.php',
    'class' => Search::class,
    'noData' => 'No data found.'
  ],
];

if (!class_exists('Router')) {

  class Router {
    /**
     * Génère une action
     *
     * @param {Object} $response
     * @param {Object} $actionData
     */
    protected static function action($response, $actionData) {
      // Inclusion du fichier de la classe désirée
      require_once ('generators/' . $actionData['include']);
      // Validation des paramètres entrés dans l'url
      $validation = $actionData['class']::validateQuery();
      if ($validation['success']) {
        // paramètres ok : on récupère les données
        $data = $actionData['class']::getData($validation['payload']);
        if (sizeof($data) > 0) {
          // Au moins une donnée : on les retourne
          $response->setValue('entities', $data);
          $response->setSuccess(true);
          $response->setStatusCode(200);
        } else {
          // Aucune donnée : message d'erreur spécifique
          $response->setError('no_data', $actionData['noData'], 200);
        }
      } else {
        // Paramètres pas ok
        $response->setError($validation['error']['code'], $validation['error']['info'], $validation['error']['status']);
      }
    }

    /**
     * Retourne la réponse en fonction de la route
     *
     * @return {Response} la réponse
     */
    public static function getResponse() {
      global $actions;

      $response = new Response();

      /**
       * Détermine l'action à mener
       */
      $action = getRequestParameter('action');

      if (is_null($action)) {
        // Pas de code d'action -> erreur
        $response->setError('no_action', 'No value for parameter "action".', 200);
      } else {
        $action = strtolower($action);
        // Détermination de la réponse en fonction du code d'action
        if (array_key_exists($action, $actions)) {
          self::action($response, $actions[$action]);
        } else
          $response->setError('unknown_action', 'Unrecognized value for parameter "action": ' . $action . '.', 400);
      }

      return $response;
    }
  }

}

?>
