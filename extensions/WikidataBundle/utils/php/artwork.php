<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'api.php');

class Artwork {
  /**
   * En-tête (titre de l'œuvre si provenant de Wikidata, bouton d'import / export)
   */
  protected static function renderHeader($entity) {
    if ($entity->origin === 'wikidata') {
      // Mise à jour du titre de la page avec le titre de l'œuvre, si elle existe
      if (!is_null($entity) && !is_null($entity->data) && !is_null($entity->data->titre)) {
        $artworkTitle = $entity->data->titre->value[0];
        ?>
          <script>document.getElementById('firstHeading').getElementsByTagName('span')[0].textContent = "<?php print $artworkTitle; ?>"</script>
        <?php
      }

      // Affichage "Importer cette œuvre..."
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:EditArtwork/<?php print $entity->article; ?>">
          <img src="http://atlasmuseum.net/w/skins/AtlasMuseum/resources/images/hmodify.png" />Importer cette œuvre dans atlasmuseum
        </a>
      </div>
      <?php
    } else {
      // Affichage "Exporter cette œuvre..."
      ?>
      <div class="import">
        <a href="<?php print ATLASMUSEUM_PATH; ?>Spécial:WikidataExport/<?php print $entity->article; ?>">
          <img src="http://atlasmuseum.net/w/skins/AtlasMuseum/resources/images/hmodify.png" />Exporter cette œuvre sur Wikidata
        </a>
      </div>
      <?php
    }
  }

  /**
   * Pied de page (inclusion des fichiers .js et .css)
   */
  protected static function renderFooter() {
    ?>
      <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
      <script type="text/javascript" src="<?php print OPEN_LAYER_JS; ?>"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artwork.js"></script>
      <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>artwork.css">
    <?php
  }

  /**
   * Écriture de l'image principale
   */
  protected static function renderMainImage($image) {
    print '<div class="topImgCtnr"><div class="thumb tright"><div id="mainImage" class="thumbinner';
    if(!is_null($image))
      print ' loading';
    print '">';
    if (!is_null($image)) {
      $imageValue = str_replace('"', '&quot;', $image->value[0]->value);
      
      print '<div class="image-loader" data-origin="' . $image->value[0]->origin . '" data-value="' . $imageValue . '" data-width="420" data-legend="true"><div class="loader loader-big"><span></span><span></span><span></span><span></span></div></div>';
    } else {
      print '<a href="' . MISSING_IMAGE_LINK . '" class="image"><img alt="" src="' . MISSING_IMAGE_FILE . '" class="thumbimage" srcset="" /></a>';
    }
          
    print '</div></div></div>';
  }

  /**
   * Écriture de la carte
   */
  protected static function renderCoordinates($coordinates) {
    if (!is_null($coordinates)) {
      $lat = $coordinates->value[0]->lat;
      $lon = $coordinates->value[0]->lon;
      if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        // Si les coordonnées tombent en dehors de la carte, on les initialise à 0
        $lat = 0;
        $lon = 0;
      }
      ?>
      <div class="topImgCtnr floatright">
        <div id="map" data-lat="<?php print $lat; ?>" data-lon="<?php print $lon; ?>"></div>
      </div>
      <?php
    } else {

    }
  }

  /**
   * Notice augmentée
   */
  protected static function renderEnhancedDescription($text) {
    if (!is_null($text)) {
      ?>
        <div class="noticePlus noticePlusExpanded">
          <h2 onclick="toggleNoticePlus(this)"> <span class="mw-headline" id="Notice.2B"> Notice+ </span></h2>
          <div>
            <?php print API::convert_to_wiki_text($text->value[0]); ?>
          </div>
        </div>
      <?php
    }
  }

  /**
   * Ligne standard
   */
  protected static function renderLine($titleSingular, $titlePlural, $data, $twoLines = false) {
    if (!is_null($data)) {
      $text = [];
      for ($i=0; $i < sizeof($data->value) ; $i++) {
        if ($data->type === 'text' || $data->type === 'date')
          array_push($text, $data->value[$i]);
        else
        if ($data->type === 'item')
          array_push($text, $data->value[$i]->label);
        else
        if ($data->type === 'coordinates') {
          $lat = $data->value[$i]->lat;
          $lon = $data->value[$i]->lon;
          $ns = ($lat >= 0 ? 'N' : 'S');
          $ew = ($lon >= 0 ? 'E' : 'O');
          $lat = abs($lat);
          $lon = abs($lon);

          $coords .= floor($lat) . '° ';
          $lat = ($lat - floor($lat)) * 60;
          $coords .= ($lat < 10 ? '0' : '') . floor($lat) . '′ ';
          $lat = ($lat - floor($lat)) * 60;
          $coords .= ($lat < 10 ? '0' : '') . round($lat) . '″ ' . $ns . '<br />';

          $coords .= floor($lon) . '° ';
          $lon = ($lon - floor($lon)) * 60;
          $coords .= ($lon < 10 ? '0' : '') . floor($lon) . '′ ';
          $lon = ($lon - floor($lon)) * 60;
          $coords .= ($lon < 10 ? '0' : '') . round($lon) . '″ ' . $ew;

          array_push($text, $coords);
        }
      }

      print '<tr>';
      print ($twoLines ? '<td colspan="2"><b>' : '<th>');
      print (sizeof($text) > 1 ? $titleSingular : $titleSingular);
      print ($twoLines ? '</b><br />' : '</th><td>');
      print implode(', ', $text);
      print '</td></tr>';
    }
  }

  /**
   * Ligne wiki text
   */
  protected static function renderLineWikiText($titleSingular, $titlePlural, $data, $twoLines = false) {
    if (!is_null($data)) {
      $text = [];
      for ($i=0; $i < sizeof($data->value) ; $i++) {
        array_push($text, API::convert_to_wiki_text($data->value[$i]));
      }

      print '<tr>';
      print ($twoLines ? '<td colspan="2"><b>' : '<th>');
      print (sizeof($text) > 1 ? $titleSingular : $titleSingular);
      print ($twoLines ? '</b><br />' : '</th><td>');
      print implode(', ', $text);
      print '</td></tr>';
    }
  }

  protected static function renderArtists($artists) {
    if (!is_null($artists)) {
      for ($i = 0; $i < sizeof($artists->value); $i++) {
        $name = $artists->value[$i]->label;
        $origin = $artists->value[$i]->origin;
        $article = $artists->value[$i]->article;
        $link = ($origin === 'wikidata' ? 'Spécial:WikidataArtist/' . $article : $article);

        print '<div class="artist" data-origin="' . $origin . '" data-article="' . $article . '">';
        print '<h3><span class="mw-headline">';
        print '<a href="' . ATLASMUSEUM_PATH . $link . '" title="">' . $name . '</a>';
        print '</span></h3><div class="loader loader-artist"><span></span><span></span><span></span><span></span></div></div>';
      }
    }
  }

  protected static function renderSource($source) {
    if (!is_null($source)) {
      print '<div class="mapCtnr"><b>Sources :</b><br />';
      for ($i=0; $i < sizeof($source->value) ; $i++)
        print API::convert_to_wiki_text($source->value[$i]);
      print '</div>';
    }
  }

  protected static function renderWikidataLink($q) {
    if (!is_null($q)) {
      print '<div class="wikidataLink"><a href="https://www.wikidata.org/wiki/' . $q->value[0] . '" target="_blank"><img src="http://atlasmuseum.net/w/skins/AtlasMuseum/resources/hwikidata.png" /><span>Voir cette œuvre sur Wikidata</span></a></div>';
    }
  }

  protected static function renderGalerie($title, $gallery) {
    if (!is_null($gallery)) {
      ?>
      <div class="atmslideshowCtnr">
        <div class="atmslideshowHead" onclick="toggleFold(this)"><h3><?php print $title; ?></h3></div>
        <ul class="atmslideshowContent" style="display:none;">
        <?php
          for($i = 0; $i < sizeof($gallery->value); $i++) {
            ?><li>
              <div class="thumb tright">
                <div class="thumbinner loading">
                  <div class="image-loader" data-origin="<?php print $gallery->value[$i]->origin; ?>" data-value="<?php print $gallery->value[$i]->value; ?>">
                    <div class="loader">
                      <span></span>
                      <span></span>
                      <span></span>
                      <span></span>
                    </div>
                  </div>
                </div>
              </div>
            </li><?php
          }
        ?>
        </ul>
      </div>
      <?php
    }
  }

  protected static function renderOtherArtworks($article, $wikidata, $artists) {
    if ($artists) {
      $ids = [];
      for ($i = 0; $i < sizeof($artists->value); $i++) {
        array_push($ids, str_replace('"', '&quot;', $artists->value[$i]->article));
      }
      $article = str_replace('"', '&quot;', $article);
      if (!is_null($wikidata))
        $article .= '|' . $wikidata->value[0];
      print '<div id="autres_oeuvres" data-exclude="' . $article . '" data-artists="' . implode('|', $ids) . '"></div>';
    }
  }

  protected static function renderCloseSites($wikidata, $coordinates) {
    if (!is_null($coordinates)) {
      $lat = $coordinates->value[0]->lat;
      $lon = $coordinates->value[0]->lon;
      if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        // Si les coordonnées tombent en dehors de la carte, on les initialise à 0
        $lat = 0;
        $lon = 0;
      }
      $exclude = (is_null($wikidata) ? '' : $wikidata->value[0]);
      print '<div id="sites_proches" data-exclude="' . $exclude . '" data-latitude="' . $lat . '" data-longitude="' . $lon . '"></div>';
    }
  }

  protected static function renderCloseArtworks($article, $wikidata, $coordinates) {
    if (!is_null($coordinates)) {
      $lat = $coordinates->value[0]->lat;
      $lon = $coordinates->value[0]->lon;
      if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        // Si les coordonnées tombent en dehors de la carte, on les initialise à 0
        $lat = 0;
        $lon = 0;
      }
      $article = str_replace('"', '&quot;', $article);
      if (!is_null($wikidata))
        $article .= '|' . $wikidata->value[0];
      print '<div id="oeuvres_proches" data-exclude="' . $article . '" data-latitude="' . $lat . '" data-longitude="' . $lon . '"></div>';
    }
  }

  /**
   * Écriture d'une œuvre
   */
  protected static function renderEntity($entity) {
    ob_start();
    // En-tête
    self::renderHeader($entity);

    // Ouverture du bloc œuvre principal
    print '<div class="dalm">';

    // Ouverture de bloc image + carte
    print '<div class="topCtnr">';
    // Image principale
    self::renderMainImage($entity->data->image_principale);
    // Carte
    self::renderCoordinates($entity->data->site_coordonnees);
    // Fermeture du bloc image + carte
    print '</div>';

    // Notice augmentée
    self::renderEnhancedDescription($entity->data->notice_augmentee);

    // Ouverture du bloc contenu principal
    print '<div class="ibCtnr">';

    // Ouverture du bloc "Œuvre"
    print '<div class="ibOeuvre">';
    print '<h2';
    if (!is_null($entity->data->notice_augmentee)) print ' onclick="toggleNoticePlusHeader(this)"';
    print '><span class="mw-headline" id=".C5.92uvre"> Œuvre </span></h2>';
    print '<table class="wikitable" style="table">';

    self::renderLine('Titre', 'Titre', $entity->data->titre);
    self::renderLine('Sous-titre', 'Sous-titres', $entity->data->sous_titre);
    self::renderLine('Description', 'Descriptions', $entity->data->description, true);
    self::renderLine('Date', 'Dates', $entity->data->inauguration);
    self::renderLine('Date de restauration', 'Dates de restauration', $entity->data->restauration);
    self::renderLine('Date de fin', 'Dates de fin', $entity->data->fin);
    self::renderLine('Précision sur les dates', 'Précisions sur les dates', $entity->data->precision_date);
    self::renderLine('Nature', 'Natures', $entity->data->nature);
    self::renderLine('Programme', 'Programmes', $entity->data->programme);
    self::renderLine('Numéro d\'inventaire', 'Numéros d\'inventaire', $entity->data->numero_inventaire);
    self::renderLine('Contexte de production', 'Contextes de production', $entity->data->contexte_production, true);
    self::renderLine('État de conservation', 'États de conservation', $entity->data->conservation);
    self::renderLine('Précision sur l\'état de conservation', 'Précisions sur l\'état de conservation', $entity->data->precision_etat_conservation);
    self::renderLine('Autres précisions sur l\'état de conservation', 'Autres précisions sur l\'état de conservation', $entity->data->autre_precision_etat_conservation);
    self::renderLine('Période', 'Période', $entity->data->periode_art);
    self::renderLine('Mouvement', 'Mouvements', $entity->data->mouvement_artistes);
    self::renderLine('Précision sur le mouvement', 'Précisions sur le mouvement', $entity->data->precision_mouvement_artistes);
    self::renderLine('Domaine', 'Domaines', $entity->data->type_art);
    self::renderLine('Précision sur le domaine', 'Précisions sur le domaine', $entity->data->precision_type_art);
    self::renderLine('Couleur', 'Couleurs', $entity->data->couleur);
    self::renderLine('Précision sur les couleurs', 'Précisions sur les couleurs', $entity->data->precision_couleur);
    self::renderLine('Matériau', 'Matériaux', $entity->data->materiaux);
    self::renderLine('Précision sur les matériaux', 'Précisions sur les matériaux', $entity->data->precision_materiaux);
    self::renderLine('Techniques', 'Techniques', $entity->data->techniques);
    self::renderLine('Hauteur (m)', 'Hauteur (m)', $entity->data->hauteur);
    self::renderLine('Profondeur (m)', 'Profondeur (m)', $entity->data->longueur);
    self::renderLine('Largeur (m)', 'Largeur (m)', $entity->data->largeur);
    self::renderLine('Diamètre (m)', 'Diamètre (m)', $entity->data->diametre);
    self::renderLine('Surface (m²)', 'Surface (m²)', $entity->data->surface);
    self::renderLine('Précision sur les dimensions', 'Précisions sur les dimensions', $entity->data->precision_dimensions);
    self::renderLine('Référence', 'Références', $entity->data->symbole);
    self::renderLine('Sujet représenté', 'Sujets représentés', $entity->data->forme);
    self::renderLine('Mots clés', 'Mots clés', $entity->data->mot_cle);
    self::renderLine('Influences', 'Influences', $entity->data->influences);
    self::renderLine('À influencé', 'À influencé', $entity->data->a_influence);
    self::renderLine('Commanditaire', 'Commanditaires', $entity->data->commanditaires);
    self::renderLine('Commissaire', 'Commissaires', $entity->data->commissaires);
    self::renderLine('Partenaires publics', 'Partenaires publics', $entity->data->partenaires_publics);
    self::renderLine('Partenaires privés', 'Partenaires privés', $entity->data->partenaires_prives);
    self::renderLine('Collaborateurs', 'Collaborateurs', $entity->data->collaborateurs);
    self::renderLine('Maîtrise d\'œuvre', 'Maîtrise d\'œuvre', $entity->data->maitrise_oeuvre);
    self::renderLine('Maîtrise d\'œuvre déléguée', 'Maîtrise d\'œuvre déléguée', $entity->data->maitrise_oeuvre_deleguee);
    self::renderLine('Maîtrise d\'ouvrage', 'Maîtrise d\'ouvrage', $entity->data->maitrise_ouvrage);
    self::renderLine('Maîtrise d\'ouvrage déléguée', 'Maîtrise d\'ouvrage déléguée', $entity->data->maitrise_ouvrage_deleguee);
    self::renderLine('Propriétaire', 'Propriétaire', $entity->data->proprietaire);
    self::renderLine('Architecte', 'Architecte', $entity->data->architecte);

    // Fermeture du bloc "Œuvre"
    print '</table>';
    print '</div>';

    // Ouverture du bloc "Site"
    print '<div class="ibSite">';
    print '<h2';
    if (!is_null($entity->data->notice_augmentee)) print ' onclick="toggleNoticePlusHeader(this)"';
    print '><span class="mw-headline" id="Site"> Site </span></h2>';
    print '<table class="wikitable" style="table">';

    self::renderLine('Lieu', 'Lieux', $entity->data->site_nom);
    self::renderLine('Lieu-dit', 'Lieux-dits', $entity->data->site_lieu_dit);
    self::renderLine('Adresse', 'Adresses', $entity->data->site_adresse);
    self::renderLine('Code postal', 'Codes postaux', $entity->data->site_code_postal);
    self::renderLine('Ville', 'Villes', $entity->data->site_ville);
    self::renderLine('Département', 'Départements', $entity->data->site_departement);
    self::renderLine('Région', 'Région', $entity->data->site_region);
    self::renderLine('Pays', 'Pays', $entity->data->site_pays);
    self::renderLine('Détails sur le site', 'Détails sur le site', $entity->data->site_details, true);
    self::renderLine('Accès', 'Accès', $entity->data->site_acces, true);
    self::renderLine('Visibilité', 'Visibilité', $entity->data->site_visibilite, true);
    self::renderLine('PMR', 'PMR', $entity->data->site_pmr);
    self::renderLineWikiText('URLs', 'URLs', $entity->data->site_urls);
    self::renderLine('Points d\'intérêt', 'Points d\'intérêt', $entity->data->site_pois, true);
    self::renderLine('Latitude/Longitude', 'Latitude/Longitude', $entity->data->site_coordonnees);

    // Fermeture du bloc "Site"
    print '</table>';
    print '</div>';

    // Ouverture du bloc "Artiste"
    print '<div class="ibArtiste">';
    print '<h2';
    if (!is_null($entity->data->notice_augmentee)) print ' onclick="toggleNoticePlusHeader(this)"';
    print '><span class="mw-headline" id="Artiste">Artiste';
    if (!is_null($entity->data->artiste) && sizeof($entity->data->artiste->value) > 1)
      print 's';
    print '</span></h2>';
    print '<table class="wikitable" style="table">';

    self::renderArtists($entity->data->artiste);

    // Fermeture du bloc "Site"
    print '</table>';
    print '</div>';

    // Fermeture du bloc contenu principal
    print '</div>';

    print '<div class="clearfix"></div>';

    self::renderSource($entity->data->source);
    self::renderWikidataLink($entity->data->wikidata);

    // Bloc 'Atlas
    print '<div class="atlasCtnr">';
    print '<h2> <span class="mw-headline" id="ATLAS"> ATLAS </span></h2>';
    self::renderGalerie('Construction / installation / Montage', $entity->data->image_galerie_construction);
    self::renderGalerie('Autres prises de vues', $entity->data->image_galerie_autre);
    self::renderOtherArtworks($entity->article, $entity->data->wikidata, $entity->data->artiste);
    self::renderCloseSites($entity->data->wikidata, $entity->data->site_coordonnees);
    self::renderCloseArtworks($entity->article, $entity->data->wikidata, $entity->data->site_coordonnees);
    print '<div id="atlas_loader" class="loader"><span></span><span></span><span></span><span></span></div>';
    print '</div>';

    // Fermeture du bloc œuvre principal
    print '</div>';

    // Pied de page
    self::renderFooter();

    $contents = ob_get_contents();
    ob_end_clean();

    return $contents;
  }

  /**
   * Écriture si erreur
   */
  protected static function renderError($article) {
    $link = 'http://atlasmuseum.net/w/index.php?title=' . urlencode($article) . '&action=purge';
    /*
    var_dump($article);
    var_dump($link);
    exit;
    */

    $text = '<div style="margin-bottom: 20px;">Erreur lors de la récupération des données...</div>';
    $text .= '<div><button onclick="window.location.href=\'' . $link . '\';">Recharger la page</button></div>';

    return $text;
  }

  /**
   * Rendu d'une œuvre
   */
  public static function renderArtwork($article) {
    if (is_null($article))
      return '';

    // Récupération des données de l'œuvre
    $parameters = [
      'action' => 'amgetartwork',
      'article' => $article
    ];
    $data = API::call_api($parameters, 'am');

    if ($data->success === 1) {
      // Œuvre ok
      $contents = self::renderEntity($data->entities);
    } else {
      // Problème de données
      $contents = self::renderError($article);
    }

    return preg_replace("/\r|\n/", "", $contents);
  }

}
