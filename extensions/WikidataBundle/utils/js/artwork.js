var atlasLoadingCount = 3

$(document).ready(function() {
  editWithForm()
  loadMap()
  loadArtists()
  loadImages()
  loadOtherArtworks()
  loadCloseSites()
  loadCloseArtworks()
})

/**
 * Ajout du bouton "Modifier avec formulaire"
 */
function editWithForm() {
  var li = document.getElementById('ca-edit')

  if (li) {
    var ul = li.parentElement,
        newLi = document.createElement("li"),
        page = window.location.href.replace(/^.*index\.php\?title=/, '').replace(/^.*wiki\//, '');

    newLi.setAttribute('id', 'ca-form_edit')
    newLi.classList.add('collapsible')
    newLi.classList.add('selected')

    newLi.innerHTML = '<span><a href="http://atlasmuseum.net/wiki/Spécial:EditArtwork/' + page + '">Modifier avec formulaire</a></span></li>';

    ul.insertBefore(newLi, li)
  }
}

/**
 * Initialisation de la carte
 */
function loadMap() {
  const mapDiv = $('#map')
  let lat = 0
  let lon = 0

  // Récupération latitude/longitude passées dans la div d'id "map"
  if (mapDiv) {
    if (mapDiv.data('lat'))
      lat = mapDiv.data('lat')
    if (mapDiv.data('lon'))
      lon = mapDiv.data('lon')
  }

  // Création de la carte
  marker = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.transform([lon, lat], "EPSG:4326", "EPSG:3857")) })
  const extent = marker.getGeometry().getExtent().slice(0)
  const raster = new ol.layer.Tile({ source: new ol.source.OSM() })
  const vectorSource = new ol.source.Vector({ features: [marker] })
  const iconStyle = new ol.style.Style({
    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
      anchor: [0.5, 46],
      anchorXUnits: 'fraction',
      anchorYUnits: 'pixels',
      opacity: 0.75,
      src: 'http://atlasmuseum.net/w/images/a/a0/Picto-gris.png'
    }))
  })
  const vectorLayer = new ol.layer.Vector({
    source: vectorSource,
    style: iconStyle
  })
  const map = new ol.Map({
    layers: [raster, vectorLayer],
    target: "map"
  })
  map.getView().fit(extent)
  map.getView().setZoom(15)
}

toggleFold = function(e) {
  parent = e.parentElement;
  for (var i = 0; i < parent.childNodes.length; i++)
    if (parent.childNodes[i].classList && parent.childNodes[i].classList.contains('atmslideshowContent')) {
      if (parent.childNodes[i].style.display == 'none') {
        parent.childNodes[i].style.display = 'block'
        e.classList.add('atmslideshowHeadExt');
      } else {
        parent.childNodes[i].style.display = 'none'
        e.classList.remove('atmslideshowHeadExt');
      }
    }
}

toggleNoticePlus = function(e) {
  parent = e.parentElement;
  if (parent.classList.contains('noticePlusExpanded'))
    parent.classList.remove('noticePlusExpanded')
  else
    parent.classList.add('noticePlusExpanded')
}

toggleNoticePlusHeader = function(e) {
  parent = e.parentElement
  folded = e.classList.contains('ibCtnrDisplayed')
  for (var i = 0; i < parent.childNodes.length; i++)
    if (parent.childNodes[i].style && parent.childNodes[i] != e) {
      if (folded)
        parent.childNodes[i].style.display = 'table'
      else
        parent.childNodes[i].style.display = 'none'
    }

  if (folded)
    e.classList.remove('ibCtnrDisplayed')
  else
    e.classList.add('ibCtnrDisplayed')
}

/**
 * Chargement des artistes
 */
function loadArtists() {
  $('.artist').each((index, artistDiv) => {
    const article = $(artistDiv).data('article')
    if (article) {
      const params = {
        action: 'amgetartist',
        article,
        redirect: 'true',
      }
      
      $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(params))
        .then(data => {
          // Supprime le loader
          $(artistDiv).find('.loader').remove()
          if (data.success === 1) {
            // L'id vient de WD mais l'artiste existe sur AM ? Changer le lien
            if (data.entities.origin === 'atlasmuseum') {
              $(artistDiv).find('a').attr('href', 'http://atlasmuseum.net/wiki/' + data.entities.article)
            }

            // Image
            if (data.entities.data.thumbnail) {
              $(artistDiv).append('<p style="text-align:center"><a href="' + data.entities.data.thumbnail.value[0].url + '" class="image"><img alt="" src="' + data.entities.data.thumbnail.value[0].thumbnail + '" style="width:auto;max-width224px;max-height:149px"></a></p>')
            } else {
              $(artistDiv).append('<p style="text-align:center"><a href="http://atlasmuseum.net/wiki/Fichier:Image-manquante.jpg" class="image"><img alt="" src="http://atlasmuseum.net/w/images/5/5f/Image-manquante.jpg" style="width:auto;max-width224px;max-height:149px"></a></p>')
            }

            let table = '<table class="wikitable"><tbody>'
            if (data.entities.data.abstract) {
              table += '<td colspan="2"><br />' + data.entities.data.abstract.value[0] +'</td></tr>'
            }
            if (data.entities.data.birthplace) {
              table += '<th>Lieu de naissance</th><td>' + data.entities.data.birthplace.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            if (data.entities.data.dateofbirth) {
              table += '<th>Date de naissance</th><td>' + data.entities.data.dateofbirth.value.join(', ') +'</td></tr>'
            }
            if (data.entities.data.deathplace) {
              table += '<th>Lieu de décès</th><td>' + data.entities.data.deathplace.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            if (data.entities.data.deathdate) {
              table += '<th>Date de décès</th><td>' + data.entities.data.deathdate.value.join(', ') +'</td></tr>'
            }
            if (data.entities.data.nationality) {
              table += '<th>Nationalité</th><td>' + data.entities.data.nationality.value.map(item => item.label).join(', ') +'</td></tr>'
            }
            table += '</tbody></table>'
            $(artistDiv).append(table)
          } else {
            // Artiste pas trouvé
            if (article.match(/^[qQ][0-9]+/)) {
              // Il s'agit d'une entité Wikidata inexistante : on supprime la ligne
              $(artistDiv).remove()
            } else {
              // Il s'agit d'un article atlasmuseum inexistant : lien vers page de création
              $(artistDiv).find('a').attr('href', 'http://atlasmuseum.net/wiki/Spécial:EditArtist/' + article)
              // Ajout de l'image manquante générique
              $(artistDiv).append('<p style="text-align:center"><a href="http://atlasmuseum.net/wiki/Fichier:Image-manquante.jpg" class="image"><img alt="" src="http://atlasmuseum.net/w/images/5/5f/Image-manquante.jpg" style="width:auto;max-width224px;max-height:149px"></a></p>')
            }
          }
        })
    }
});
}

/**
 * Chargement des images
 */
function loadImages() {
  $('.image-loader').each((index, imageDiv) => {
    const origin = $(imageDiv).data('origin')
    const value = $(imageDiv).data('value')
    const width = $(imageDiv).data('width') ? $(imageDiv).data('width') : 192
    const legend = ($(imageDiv).data('legend') && $(imageDiv).data('legend') === true)
    console.log('Loading image', value)
    const params = {
      action: 'amgetimage',
      image: value,
      origin,
      width,
    }
    if (legend)
      params['legend'] = true

    $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(params))
      .then(data => {
        replaceLoader($(imageDiv), data.entities.url, data.entities.thumbnail, legend ? data.entities.legend : null)
      })
  })
}

function replaceLoader(imageDiv, url, thumbnail, legend = null) {
  imageDiv.find('.loader').remove()
  imageDiv.parent().removeClass('loading')

  // Rajoute l'image
  let text = ''
  if (url !== '')
    text = '<a href="' + url  + '" class="image"><img alt="" src="' + thumbnail  + '" class="thumbimage" srcset="" /></a>'
  else
    text = '<img alt="" src="' + thumbnail  + '" class="thumbimage" srcset="" />'

  // Rajoute la légende au besoin
  if (legend)
    text += '<div class="thumbcaption" style="text-align: center">' + legend  + '</div>'
  
  imageDiv.parent().append(text)

  // Supprime l'élément initial
  imageDiv.remove()
}

function loadOtherArtworks() {
  const mainDiv = $('#autres_oeuvres')
  if (mainDiv) {
    const artists = mainDiv.data('artists')
    const exclude = mainDiv.data('exclude')
    const params = {
      action: 'amgetartworksbyartists',
      artists,
      exclude,
    }

    $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(params))
      .then(data => {
        if (data.success === 1) {
          mainDiv.addClass('atmslideshowCtnr')
          let headerTitle = ''

          if (data.entities.length > 1)
            headerTitle = 'Autres œuvres'
          else
            headerTitle = 'Autre œuvre'
          if (artists.includes('|'))
            headerTitle += ' des artistes '
          else
            headerTitle += ' de l\'artiste '
          headerTitle += 'dans l\'espace public'

          text = '<div class="atmslideshowHead" onclick="toggleFold(this)"><h3>' + headerTitle + '</h3></div><ul class="atmslideshowContent" style="display:none;">'
          for (let i = 0; i < data.entities.length ; i++) {
            let link = encodeURIComponent(data.entities[i].article)
            if (data.entities[i].origin === 'wikidata')
              link = 'Spécial:Wikidata/' + link
            link = 'http://atlasmuseum.net/wiki/' + link

            text += '<li><div class="thumb tright"><a class="image" href="' + link + '"><div class="thumbinner">'

            if (data.entities[i].image) {
              text += '<div class="loading"><div class="image-loader" id="image-loader-other-' + i + '"><div class="loader loader-gallery"><span></span><span></span><span></span><span></span></div></div></div>'

              const imgParams = {
                action: 'amgetimage',
                image: data.entities[i].image.file,
                origin: data.entities[i].image.origin,
                width: 192,
              }
              $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(imgParams))
                .then(data => {
                  replaceLoader($('#image-loader-other-' + i), '', data.entities.thumbnail)
                })

            } else {
              text += '<div><img alt="" src="http://atlasmuseum.net/w/images/5/5f/Image-manquante.jpg" /></div>'
            }

          text += '<br />' + data.entities[i].titre + '</div></a></div></li>'
          }
          text += '</ul></div>'

          $(mainDiv).append(text)
        }

        atlasLoadingCount--
        if (atlasLoadingCount <= 0)
          closeAtlasLoader()
      })
  }
}

/**
 * Chargement des sites proches
 */
function loadCloseSites() {
  const mainDiv = $('#sites_proches')
  if (mainDiv) {
    const latitude = mainDiv.data('latitude')
    const longitude = mainDiv.data('longitude')
    const exclude = mainDiv.data('exclude')
    const params = {
      action: 'amgetclosesites',
      latitude,
      longitude,
      distance: 10,
      exclude,
    }

    $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(params))
      .then(data => {
        if (data.success === 1) {
          mainDiv.addClass('atmslideshowCtnr')
          text = '<div class="atmslideshowHead" onclick="toggleFold(this)"><h3>Sites proches</h3></div><ul class="atmslideshowContent" style="display:none;">'
          for (let i = 0; i < data.entities.length ; i++) {
            let link = encodeURIComponent(data.entities[i].wikidata)
            link = 'http://www.wikidata.org/wiki/' + link

            text += '<li><div class="thumb tright"><a class="image" href="' + link + '"><div class="thumbinner">'

            if (data.entities[i].image) {
              text += '<div class="loading"><div class="image-loader" id="image-loader-site-' + i + '"><div class="loader loader-gallery"><span></span><span></span><span></span><span></span></div></div></div>'

              const imgParams = {
                action: 'amgetimage',
                image: data.entities[i].image.file,
                origin: 'commons',
                width: 192,
              }
              $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(imgParams))
                .then(data => {
                  replaceLoader($('#image-loader-site-' + i), '', data.entities.thumbnail)
                })

            } else {
              text += '<div><img alt="" src="http://atlasmuseum.net/w/images/5/5f/Image-manquante.jpg" /></div>'
            }

          text += '<br />' + data.entities[i].label + '</div></a></div></li>'
          }
          text += '</ul></div>'

          $(mainDiv).append(text)
        }

        atlasLoadingCount--
        if (atlasLoadingCount <= 0)
          closeAtlasLoader()
      })
  }
}

/**
 * Chargement des œuvres proches
 */
function loadCloseArtworks() {
  const mainDiv = $('#oeuvres_proches')
  if (mainDiv) {
    const latitude = mainDiv.data('latitude')
    const longitude = mainDiv.data('longitude')
    const exclude = mainDiv.data('exclude')

    const params = {
      action: 'amgetcloseartworks',
      latitude,
      longitude,
      distance: 10,
      exclude,
    }

    $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(params))
      .then(data => {
        if (data.success === 1) {
          mainDiv.addClass('atmslideshowCtnr')
          text = '<div class="atmslideshowHead" onclick="toggleFold(this)"><h3>Œuvres proches</h3></div><ul class="atmslideshowContent" style="display:none;">'
          for (let i = 0; i < data.entities.length ; i++) {
            let link = encodeURIComponent(data.entities[i].article)
            if (data.entities[i].origin === 'wikidata')
              link = 'Spécial:Wikidata/' + link
            link = 'http://atlasmuseum.net/wiki/' + link

            text += '<li><div class="thumb tright"><a class="image" href="' + link + '"><div class="thumbinner">'

            if (data.entities[i].image) {
              text += '<div class="loading"><div class="image-loader" id="image-loader-close-' + i + '"><div class="loader loader-gallery"><span></span><span></span><span></span><span></span></div></div></div>'

              const imgParams = {
                action: 'amgetimage',
                image: data.entities[i].image.file,
                origin: data.entities[i].image.origin,
                width: 192,
              }
              $.getJSON('http://atlasmuseum.net/w/amapi/index.php?' + $.param(imgParams))
                .then(data => {
                  replaceLoader($('#image-loader-close-' + i), '', data.entities.thumbnail)
                })

            } else {
              text += '<div><img alt="" src="http://atlasmuseum.net/w/images/5/5f/Image-manquante.jpg" /></div>'
            }

          text += '<br />' + data.entities[i].titre + '</div></a></div></li>'
          }
          text += '</ul></div>'

          $(mainDiv).append(text)
        }

        atlasLoadingCount--
        if (atlasLoadingCount <= 0)
          closeAtlasLoader()
      })

  }
}

function closeAtlasLoader() {
  $('#atlas_loader').remove()
}
