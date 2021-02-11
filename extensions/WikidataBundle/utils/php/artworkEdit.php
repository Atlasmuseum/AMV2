<?php

require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'artworkGetData.php');
require_once(ATLASMUSEUM_UTILS_PATH_PHP . 'constants.php');

class ArtworkEditTest {
  public static function renderWikidata($entity) {
    $wikidataID = !is_null($entity->data->wikidata) ? $entity->data->wikidata->value[0] : '';
    $wikidataOrigin = ($entity->origin === 'wikidata');
    ?>
      <tr>
        <th>Wikidata</th>
        <td><span class="inputSpan"><input id="input_label" class="createboxInput" size="70" value="<?php print $wikidataID; ?>" name="Edit[wikidata]">
        <?php
          if (!$wikidataOrigin) {
            ?>
              <br /><br /><button id="amImportButton" onclick="importWikidata();">Importer</button>
            <?php
          }
        ?>
        </span></td>
      </tr>
    <?php
  }

  public static function renderLabel($data) {
    $label = !is_null($data) ? $data->value[0] : '';
    ?>
      <tr>
        <th>Titre<span class="mandatory">*</span></th>
        <td> <span class="inputSpan mandatoryFieldSpan"><input id="input_2" tabindex="2" class="createboxInput mandatoryField" size="70" value="<?php print $label; ?>" name="Edit[titre]"></span></td>
      </tr>
    <?php
  }

  public static function renderCoordinates($data) {
    $latitude = 0;
    $longitude = 0;
    if (!is_null($data)) {
      $latitude = $data->value[0]->lat;
      $longitude = $data->value[0]->lon;
    }
    ?>
      <tr>
        <th>Coordonnées<span class="mandatory">*</span></th>
        <td>
          <p>
            <span class="inputSpan mandatoryFieldSpan"><input class="createboxInput mandatoryField" id="coordinates_input" size="40" value="<?php print $latitude . ', ' . $longitude; ?>" name="Edit[site_coordonnees]"></span>
            <button onclick="change_map_input()">Mise à jour de la carte</button>
          </p>
          <p>
            <span class="inputSpan"><input class="createboxInput" id="address_input" size="40" value="" name=""></span>
            <button onclick="change_map_address()">Estimer les coordonnées</button>
          </p>
          <div id="map" data-lat="<?php print $latitude; ?>" data-lon="<?php print $longitude; ?>" style="height:350px"></div>
        </td>
      </tr>
    <?php
  }

  public static function renderNature($data) {
    $nature = !is_null($data) ? $data->value[0] : 'pérenne';
    $values = ['pérenne', 'éphémère', 'détruite', 'non réalisée', 'à vérifier'];
    ?>
      <tr>
        <th>Nature</th>
        <td>
          <span class="radioButtonSpan mandatoryFieldSpan">
            <?php
              for ($i = 0; $i < sizeof($values); $i++) {
                print '<label class="radioButtonItem"><input name="Edit[nature]" type="radio" value="' . $values[$i] . '"';
                if ($nature === $values[$i])
                  print ' checked="checked"';
                print '>' . $values[$i] . '</label>';
              }
            ?>
          </span>
        </td>
      </tr>
    <?php
  }
  
  public static function renderText($data, $key, $title, $mandatory=false) {
    $text = '';
    if (!is_null($data))
      $text = $data->value[0];
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>_container">
          <input type="text" id="input_<?php print $key; ?>" value="<?php print $text; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" size="45">
        </td>
      </tr>
    <?
  }

  public static function renderMainImage($data, $key, $title, $mandatory=false) {
    $imageFile = '';
    $imageOrigin = 'atlasmuseum';
    $imageThumb = '';
    if (!is_null($data)) {
      $imageFile = $data->value[0]->value;
      $imageOrigin = $data->value[0]->origin;

      // Récupération des données de l'œuvre
      $parameters = [
        'action' => 'amgetimage',
        'image' => $imageFile,
        'origin' => $imageOrigin,
        'width' => 200
      ];
      $imageData = API::call_api($parameters, 'am');

      if ($imageData->success === 1)
        $imageThumb = $imageData->entities->thumbnail;
    }
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>_cell">
          <input type="text" id="input_<?php print $key; ?>" value="<?php print str_replace('"', '&quot;', $imageFile); ?>" name="Edit[<?php print $key; ?>]" class="createboxInput createboxInputMainImage" size="45">
          <a data-fancybox data-type="iframe" data-src="<?php print BASE_MAIN; ?>index.php?title=Sp%C3%A9cial:UploadWindow&amp;pfInputID=input_<?php print $key; ?>" href="javascript:;">Importer un fichier</a>
          <br />
          <input id="input_checkbox_<?php print $key; ?>" name="Edit[<?php print $key; ?>_origin]" type="checkbox" class="createboxInput" <?php if ($imageOrigin === 'commons') print 'checked'; ?>><i>Cette image provient de Wikimedia Commons</i>
          <?php
            if ($imageThumb != '') {
            ?>
              <div id="input_<?php print $key; ?>_thumb"  class="image_thumb">
                <img src="<?php print $imageThumb; ?>" style="max-width: 200px; height: auto;" />
              </div>
            <?php
            }
          ?>
          <div class="image_disclaimer">
            Avant d'importer une image, assurez vous que vous avez les droits suffisants pour le faire (œuvres originales dont vous êtes l'auteur, œuvres dans le domaine public, œuvres sous licence libre). Veuillez consulter l'aide sur les droits d'auteur.<br />
            Si vous n'avez pas les droits sur l'image ou si avez un doute, laissez le nom de l'image : image-manquante.jpg dans le zone de saisie "Image principale".
          </div>
        </td>
      </tr>
    <?
  }

  public static function renderImageList($data, $key, $title, $mandatory=false) {
    ?>
    <div class="multipleTemplateWrapper">
	    <div class="multipleTemplateList ui-sortable" id="input_<?php print $key; ?>_container">
      <?php
        if (!is_null($data))
          for ($i = 0; $i < sizeof($data->value); $i++) {  
          ?>
          <div class="multipleTemplateInstance multipleTemplate" id="input_<?php print $key; ?>_instance_<?php print $i; ?>">
            <table>
              <tbody>
                <tr>
                  <td> 
                    <table>
                      <tbody>
                        <tr>
                          <td style="width:140px;"><b>Importer une image&nbsp;:</b></td>
                          <td>
                            <span class="inputSpan">
                              <input id="input_<?php print $key; ?>_<?php print $i; ?>" class="createboxInput" size="35" value="<?php print $data->value[$i]->value; ?>" name="Edit[<?php print $key; ?>][<?php print $i; ?>]" type="text">
                              <a data-fancybox data-type="iframe" data-src="<?php print BASE_MAIN; ?>index.php?title=Sp%C3%A9cial:UploadWindow&amp;pfInputID=input_<?php print $key; ?>_<?php print $i; ?>" href="javascript:;">Importer un fichier</a>
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                  <?php
                  /*
                  <td><a class="addAboveButton" title="Ajouter une autre instance au-dessus de celle-ci"><img src="/w/extensions/SemanticForms/skins/SF_add_above.png" class="multipleTemplateButton"></a></td>
                  */
                  ?>
			            <td><button class="removeButton" title="Enlever cette instance" onclick="remove_image_line('<?php print $key; ?>', <?php print $i; ?>)"><img src="/w/extensions/SemanticForms/skins/SF_remove.png" class="multipleTemplateButton"></button></td>
                  <?php
                  /*
			            <td class="instanceRearranger"><img src="/w/extensions/SemanticForms/skins/rearranger.png" class="rearrangerImage"></td>
                  */
                  ?>
                </tr>
              </tbody>
            </table>
          </div>
          <?php
        }
      ?>
      </div>
      <p><button class="multipleTemplateAdder" onclick="add_image_line('<?php print $key; ?>');">Importer d'autres images</button></p>
    </div>
    <?php
  }

  public static function renderTextarea($data, $key, $title, $mandatory=false) {
    $text = '';
    if (!is_null($data)) {
      $text = str_replace('\\n', "\n", $data->value[0]);
    }
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <textarea id="input_<?php print $key; ?>" name="Edit[<?php print $key; ?>]" class="createboxInput" rows="5" cols="40" style="width: 100%"><?php print $text; ?></textarea>
        </td>
      </tr>
    <?
  }

  public static function renderItem($data, $key, $title, $mandatory=false) {
    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <?php
            if (!is_null($data)) {
              foreach ($data->value as $index => $value) {
              ?>
                <div id="input_<?php print $key; ?>_wrapper_<?php print $index; ?>" class="inputSpan<?php if ($mandatory) print ' mandatoryFieldSpan'; ?> autocomplete">
                  <input id="input_<?php print $key; ?>_<?php print $index; ?>" class="createboxInput<?php if ($mandatory) print ' mandatoryField'; ?>" size="60" value="<?php print $value->label; ?>" name="Edit[<?php print $key; ?>][<?php print $index; ?>]">
                  <input type="hidden" id="input_<?php print $key; ?>_id_<?php print $index; ?>" name="Edit[<?php print $key; ?>][id][<?php print $index; ?>]" value="<?php print $value->article; ?>">
                  <span class="edit_item_button" title="Supprimer cette ligne" onclick="removeLine('input_<?php print $key; ?>_wrapper_<?php print $index; ?>');">
                    [&nbsp;x&nbsp;]
                  </span>
                </div>
              <?php
              }
            }
          ?>
          <div class="edit_item_button add_button" title="Ajouter une ligne" onclick="addLine('input_<?php print $key; ?>', '<?php print $key; ?>', '<?php print $key; ?>', <?php print $mandatory; ?>);">
            [&nbsp;+&nbsp;]
          </div>
        </td>
      </tr>
    <?
  }

  public static function renderItemCheckboxes($data, $key, $title, $list, $mandatory=false) {
    $d = [];

    if (!is_null($data)) {
      for ($i = 0; $i < sizeof($data->value); $i++) {
        if ($data->type === 'item') {
          array_push($d, $data->value[$i]->article);
          if ($data->value[$i]->label != $data->value[$i]->article)
            array_push($d, $data->value[$i]->label);
        } else
        if ($data->type === 'text') {
          array_push($d, $data->value[$i]);
        }
      }
    }

    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <div class="checkboxes3columns">
            <span class="checkboxesSpan">
              <?php
                for ($i=0; $i<sizeof($list); $i++) {
                  $checked = (in_array($list[$i]['label'], $d) || ($list[$i]['id'] != '' && in_array($list[$i]['id'], $d)));
                  ?>
                    <label class="checkboxLabel">
                      <input id="input_<?php print $key . '_' . $i; ?>" class="createboxInput" type="checkbox" value="<?php if ($list[$i]['id'] != '') print $list[$i]['id']; else print $list[$i]['label']; ?>" name=Edit[<?php print $key; ?>][<?php print $i; ?>] <?php if ($checked) print 'checked'; ?>> <?php print $list[$i]['label']; ?>
                    </label>
                  <?php
                }
              ?>
            </span>
          </div>
        </td>
      </tr>
    <?
  }

  public static function renderItemRadio($data, $key, $title, $list, $mandatory=false) {
    $d = [];

    if (!is_null($data)) {
      for ($i = 0; $i < sizeof($data->value); $i++) {
        array_push($d, $data->value[$i]->article);
        if ($data->value[$i]->label != $data->value[$i]->article)
          array_push($d, $data->value[$i]->label);
      }
    }

    ?>
      <tr>
        <th><?php print $title; ?><?php if ($mandatory) print ' <span class="mandatory">*</span>'; ?></th>
        <td id="input_<?php print $key; ?>">
          <div class="checkboxes1column">
            <span class="radioButtonSpan">
              <?php
                for ($i=0; $i<sizeof($list); $i++) {
                  $checked = (in_array($list[$i]['label'], $d) || ($list[$i]['id'] != '' && in_array($list[$i]['id'], $d)));
              ?>
                <label class="radioButtonItem">
                  <input id="input_<?php print $key . '_' . $i; ?>" class="createboxInput" type="radio" value="<?php if ($list[$i]['id'] != '') print $list[$i]['id']; else print $list[$i]['label']; ?>" name=Edit[<?php print $key; ?>] <?php if ($checked) print 'checked'; ?>> <?php print $list[$i]['label']; ?>
                </label>
              <?php
                }
              ?>
            </span>
          </div>
        </td>
      </tr>
    <?
  }

  /**
   * En-tête
   */
  protected static function renderHeader($entity) {
    // Nom de l'article en cours (vide si création)
    $article = '';
    if (!is_null($entity)) {
      // Si origine atlasmuseum, le nom de l'article est passé dans l'entité
      $article = $entity->article;

      // Si origine Wikidata, il faut construire le nom à partir du titre et des auteurs
      if ($entity->origin === 'wikidata') {
        $artists = [];
        if (!is_null($entity->data->artiste))
          for ($i = 0; $i < sizeof($entity->data->artiste->value); $i++)
            array_push($artists, $entity->data->artiste->value[$i]->label);
        $article = $entity->title . ' (' . (sizeof($artists > 0) ? implode(', ', $artists) : 'artiste inconnu') . ')';
      }
    }

    ?>
    <form id="edit_form" onsubmit="return false;">
      <input type="hidden" id="article" name="article" value="<?php print $article; ?>">
      <div class="jquery-large headertabs">
      <div>
          <ul class="edit-tabs">
            <li class="edit_tab tab_selected" onclick="showTab(this, 'infos_essentielles');">Infos essentielles</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'description');">Description</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'production');">Production</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'site');">Site</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'acteurs');">Acteurs</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'conservation');">Conservation</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'sources');">Sources</li>
            <li class="edit_tab tab_unselected" onclick="showTab(this, 'photos');">Photos</li>
          </ul>
        </div>
    <?php
  }

  /**
   * Pied de page
   */
  protected static function renderFooter() {
    ?>
        <div class="edit_publish">
          <input type="button" value="Publier" name="wpSave" onclick="publish();">
        </div>
      </form>

      <div style="display:none">
        <form id="editform" name="editform" method="post" action="" enctype="multipart/form-data">
          <div id="antispam-container" style="display: none;"><input type="text" name="wpAntispam" id="wpAntispam" value="" /></div>
          <input type="hidden" name="editingStatsId" id="editingStatsId" value="" />
          <input type='hidden' value="" name="wpSection"/>
          <input type='hidden' value="" name="wpStarttime" />
          <input type='hidden' value="" name="wpEdittime" />
          <input type='hidden' value="" name="wpScrolltop" id="wpScrolltop" />
          <input type="hidden" value="" name="wpAutoSummary"/>
          <input type="hidden" value="" name="oldid"/>
          <input type="hidden" value="30307" name="parentRevId"/>
          <input type="hidden" value="text/x-wiki" name="format"/>
          <input type="hidden" value="wikitext" name="model"/>
          <input type="hidden" value=<?php print Api::get_token(); ?> name="wpEditToken"/>
          <textarea tabindex="1" accesskey="," id="wpTextbox1" cols="80" rows="25" style="" lang="fr" dir="ltr" name="wpTextbox1"></textarea>
        </form>
      </div>

      <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>jquery-ui.min.js"></script>
      <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
      <script type="text/javascript" src="<?php print OPEN_LAYER_JS; ?>"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>autocomplete.js"></script>
      <script type="text/javascript" src="<?php print ATLASMUSEUM_UTILS_FULL_PATH_JS; ?>artworkEdit.js"></script>
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>edit.css">
      <link rel="stylesheet" href="<?php print ATLASMUSEUM_UTILS_FULL_PATH_CSS; ?>autocomplete.css">
      <link rel="stylesheet" href="<?php print OPEN_LAYER_CSS; ?>" type="text/css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    <?php
  }

  protected static function renderOpenBlock($blockName) {
    // $blockId = str_replace('%', '.', urlencode(str_replace(' ', '_', strtolower($blockName))));
    ?>
      <h2><span class="mw-headline"><?php print $blockName; ?></span></h2>
      <table class="formtable">
        <tbody>
    <?php
  }

  protected static function renderCloseBlock() {
    ?>
        </tbody>
      </table>
    <?php
  }

  /**
   * Infos principales
   */
  protected static function renderMainInfos($entity) {
    print '<div id="infos_essentielles" class="edit_section section_selected">';

    self::renderOpenBlock('Wikidata');
    self::renderWikidata($entity);
    self::renderCloseBlock();

    self::renderOpenBlock('Titre et coordonnées');
    self::renderLabel($entity->data->titre);
    self::renderCoordinates($entity->data->site_coordonnees);
    self::renderCloseBlock();

    self::renderOpenBlock('Artiste');
    self::renderItem($entity->data->artiste, 'artiste', 'Artiste', true);
    self::renderCloseBlock();

    self::renderOpenBlock('Nature');
    self::renderNature($entity->data->nature);
    self::renderCloseBlock();

    self::renderOpenBlock('Image principale');
    self::renderMainImage($entity->data->image_principale, 'image_principale', 'Image principale', false);
    self::renderCloseBlock();

    print '</div>';
  }

  protected static function renderDescription($entity) {
    print '<div id="description" class="edit_section section_unselected">';

    self::renderOpenBlock('Description de l\'œuvre');
    self::renderText($entity->data->{'sous-titre'}, 'sous-titre', 'Sous-titre');
    self::renderTextarea($entity->data->description, 'description', 'Description');
    self::renderItemCheckboxes($entity->data->type_art, 'type_art', 'Domaine(s)', Constants::get_domains());
    self::renderText($entity->data->precision_type_art, 'precision_type_art', 'Précision sur le domaine');
    self::renderItemCheckboxes($entity->data->couleur, 'couleur', 'Couleur(s)', Constants::get_colors());
    self::renderText($entity->data->precision_couleur, 'precision_couleur', 'Précision sur les couleurs');
    self::renderItemCheckboxes($entity->data->materiaux, 'materiaux', 'Matériau(x)', Constants::get_materials());
    self::renderText($entity->data->precision_materiaux, 'precision_materiaux', 'Précision sur les matériaux');
    self::renderText($entity->data->techniques, 'techniques', 'Techniques');
    self::renderText($entity->data->hauteur, 'hauteur', 'Hauteur (m)');
    self::renderText($entity->data->profondeur, 'profondeur', 'Profondeur (m)');
    self::renderText($entity->data->largeur, 'largeur', 'Largeur (m)');
    self::renderText($entity->data->diametre, 'diametre', 'Diamètre (m)');
    self::renderText($entity->data->surface, 'surface', 'Surface (m²)');
    self::renderText($entity->data->precision_dimensions, 'precision_dimensions', 'Précision sur les dimensions');
    self::renderText($entity->data->symbole, 'symbole', 'Références');
    self::renderItem($entity->data->forme, 'forme', 'Sujet représenté');
    self::renderText($entity->data->mot_cle, 'mot_cle', 'Mots clés');
    self::renderText($entity->data->influences, 'influences', 'Influences');
    self::renderText($entity->data->a_influence, 'a_influence', 'À influencé');
    self::renderTextarea($entity->data->notice_augmentee, 'notice_augmentee', 'Notice augmentée');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Production
   */
  protected static function renderProduction($entity) {
    print '<div id="production" class="edit_section section_unselected">';

    self::renderOpenBlock('Production de l\'œuvre');
    self::renderText($entity->data->inauguration, 'inauguration', 'Date d\'inauguration');
    self::renderText($entity->data->restauration, 'restauration', 'Date de restauration');
    self::renderText($entity->data->fin, 'fin', 'Date de fin');
    self::renderText($entity->data->precision_date, 'precision_date', 'Précision sur les dates');
    self::renderItem($entity->data->programme, 'programme', 'Procédure');
    self::renderText($entity->data->numero_inventaire, 'numero_inventaire', 'Numéro d\'inventaire');
    self::renderText($entity->data->contexte_production, 'contexte_production', 'Contexte de production');
    self::renderText($entity->data->periode_art, 'periode_art', 'Période(s)');
    self::renderItemCheckboxes($entity->data->mouvement_artistes, 'mouvement_artistes', 'Mouvement(s)', Constants::get_movements());
    self::renderText($entity->data->precision_mouvement_artistes, 'precision_mouvement_artistes', 'Précision sur le mouvement');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Site
   */
  protected static function renderSite($entity) {
    print '<div id="site" class="edit_section section_unselected">';

    self::renderOpenBlock('Site');
    self::renderItem($entity->data->site_nom, 'site_nom', 'Nom du site');
    self::renderTextarea($entity->data->site_details, 'site_details', 'Détails sur le site');
    self::renderText($entity->data->site_lieu_dit, 'site_lieu_dit', 'Lieu-dit');
    self::renderText($entity->data->site_adresse, 'site_adresse', 'Adresse');
    self::renderText($entity->data->site_code_postal, 'site_code_postal', 'Code postal');
    self::renderItem($entity->data->site_ville, 'site_ville', 'Ville, localité...');
    self::renderText($entity->data->site_departement, 'site_departement', 'Département, comté, district...');
    self::renderText($entity->data->site_region, 'site_region', 'Région, province...');
    self::renderItem($entity->data->site_pays, 'site_pays', 'Pays');
    self::renderTextarea($entity->data->site_acces, 'site_acces', 'Accès');
    self::renderTextarea($entity->data->site_visibilite, 'site_visibilite', 'Visibilité');
    self::renderItemRadio($entity->data->site_pmr, 'site_pmr', 'Accessibilité PMR', Constants::get_pmr());
    self::renderTextarea($entity->data->site_urls, 'site_urls', 'URLs');
    self::renderTextarea($entity->data->site_pois, 'site_pois', 'Points d\'intérêt');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Acteurs
   */
  protected static function renderActors($entity) {
    print '<div id="acteurs" class="edit_section section_unselected">';

    self::renderOpenBlock('Acteurs');
    self::renderItem($entity->data->commissaires, 'commissaires', 'Commissaires');
    self::renderItem($entity->data->commanditaires, 'commanditaires', 'Commanditaires');
    self::renderText($entity->data->partenaires_publics, 'partenaires_publics', 'Partenaire(s) public(s)');
    self::renderText($entity->data->partenaires_prives, 'partenaires_prives', 'Partenaire(s) privé(s)');
    self::renderText($entity->data->collaborateurs, 'collaborateurs', 'Collaborateur(s)');
    self::renderText($entity->data->maitrise_oeuvre, 'maitrise_oeuvre', 'Maîtrise d\'œuvre');
    self::renderText($entity->data->maitrise_oeuvre_deleguee, 'maitrise_oeuvre_deleguee', 'Maîtrise d\'œuvre déléguée');
    self::renderText($entity->data->maitrise_ouvrage, 'maitrise_ouvrage', 'Maîtrise d\'ouvrage');
    self::renderText($entity->data->maitrise_ouvrage_deleguee, 'maitrise_ouvrage_deleguee', 'Maîtrise d\'ouvrage déléguée');
    self::renderText($entity->data->proprietaire, 'proprietaire', 'Propriétaire');
    self::renderText($entity->data->architecte, 'architecte', 'Architecte');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Conservation
   */
  protected static function renderConservation($entity) {
    print '<div id="conservation" class="edit_section section_unselected">';

    self::renderOpenBlock('État de conservation');
    self::renderItemCheckboxes($entity->data->conservation, 'conservation', 'État de conservation', Constants::get_conservation());
    self::renderItemCheckboxes($entity->data->precision_etat_conservation, 'precision_etat_conservation', 'Précisions sur l\'état de conservation', Constants::get_conservation_precision());
    self::renderTextarea($entity->data->autre_precision_etat_conservation, 'autre_precision_etat_conservation', 'Autres précisions sur l\'état de conservation');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Sources
   */
  protected static function renderSources($entity) {
    print '<div id="sources" class="edit_section section_unselected">';

    self::renderOpenBlock('Sources');
    self::renderTextarea($entity->data->source, 'source', 'Source');
    self::renderCloseBlock();

    print '</div>';
  }

  /**
   * Photos
   */
  protected static function renderPhotos($entity) {
    print '<div id="photos" class="edit_section section_unselected">';

    print '<h2><span class="mw-headline"> Construction / installation / Montage </span></h2>';
    self::renderImageList($entity->data->image_galerie_construction, 'image_galerie_construction', 'Construction');

    print '<h2><span class="mw-headline"> Autres prises de vue </span></h2>';
    self::renderImageList($entity->data->image_galerie_autre, 'image_galerie_autre', 'Autres');

    print '</div>';
  }

  /**
   * Affiche le formulaire d'édition d'une œuvre
   */
  public static function renderEntityEdit($entity) {    
    ob_start();

    // En-tête
    self::renderHeader($entity);

    // Infos essentielles
    self::renderMainInfos($entity);

    // Description
    self::renderDescription($entity);

    // Production
    self::renderProduction($entity);

    // Site
    self::renderSite($entity);

    // Acteurs
    self::renderActors($entity);

    // Conservation
    self::renderConservation($entity);

    // Sources
    self::renderSources($entity);

    // Photos
    self::renderPhotos($entity);

    // Pied de page
    self::renderFooter();

    $contents = ob_get_contents();
    ob_end_clean();

    return $contents;
  }

  /**
   * Rendu si erreur
   */
  protected static function renderError() {
    return '<div>Problème lors de la récupération des données... Veuillez recharger la page.</div>';
  }

  /**
   * Rendu d'édition d'une œuvre
   */
  public static function renderEdit($id = null) {
    $title = 'Ajouter une œuvre';

    if (!is_null($id)) {
      // Récupération des données de l'œuvre
      $parameters = [
        'action' => 'amgetartwork',
        'article' => $id
      ];
      $data = API::call_api($parameters, 'am');

      if ($data->success === 1) {
        // Œuvre ok
        if ($data->entities->origin === 'wikidata')
          $title = 'Importer : ' . $data->entities->title;
        else
          $title = 'Modifier : ' . $data->entities->title;
        $content = self::renderEntityEdit($data->entities);
      } else {
        // Problème de données
        $content = self::renderError();
      }
    } else {
      $data = (object)[
        'article' => '',
        'title' => '',
        'origin' => 'atlasmuseum',
        'data' => (object)[]
      ];
      $content = self::renderEntityEdit($data);
    }

    return [
      'title' => $title,
      'content' => preg_replace("/\r|\n/", "", $content),
    ];
  }

}
