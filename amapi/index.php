<?php
/*****************************************************************************
 * index.php
 *
 * Fichier principal
 *****************************************************************************/

/*
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: PUT, POST, PATCH, DELETE, GET");
*/
//header("Content-Type: application/json; charset=UTF-8");

require_once ('./includes/router.php');

// On récupère la réponse
$response = Router::getResponse();

// Affichage de la réponse
$response->sendResponse();

?>