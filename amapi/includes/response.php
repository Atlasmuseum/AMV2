<?php
/*****************************************************************************
 * response.php
 *
 * Réponse à l'API
 *****************************************************************************/

 if (!class_exists('Response')) {

  class Response {
    protected $statusCode = 200;
    protected $response = [
      'success' => 1
    ];

    /**
     * Constructeur
     */
    function __construct() {
    }

    /**
     * Ajoute une valeur à la réponse
     */
    public function setValue($key, $value) {
      $this->response[$key] = $value;
    }

    /**
     * Définit si la réponse est un succès ou non
     *
     * @param {bool} $success - true si succès, false sinon
     */
    public function setSuccess($success) {
      $this->setValue('success', $success ? 1 : 0);
    }

    /**
     * Met à jour le code statut HTML de la réponse
     *
     * @param {integer} $statusCode - Code statut
     */
    public function setStatusCode($statusCode = 200) {
      $this->statusCode = $statusCode;
    }

    /**
     * Définit une réponse en erreur
     *
     * @param {string} $code - Code erreur
     * @param {string} $info - Description de l'erreur
     */
    public function setError($code = '', $info = '', $statusCode = null) {
      $this->setValue('error', [
        'code' => $code,
        'info' => $info
      ]);
      $this->setSuccess(false);
      $this->setStatusCode($statusCode);
    }

    /**
     * Retourne la réponse en cours sous forme d'un chaîne encodant un JSON
     *
     * @return {string} La valeur de la réponse
     */
    public function getResponseJSON() {
      // return json_encode($this->response, JSON_FORCE_OBJECT);
      return json_encode($this->response);
    }

    /**
     * Envoie un code statut HTML et écrit la réponse
     */
    public function sendResponse() {
      if (!is_null($this->response['entities']['compress'])) {
        print $this->response['entities']['data'];
      } else {
        header("Content-Type: application/json; charset=UTF-8");
        if (!is_null($this->statusCode) && is_int($this->statusCode)) {
          http_response_code($this->statusCode);
        }
        print $this->getResponseJSON();
      }
    }
  }

}

?>
