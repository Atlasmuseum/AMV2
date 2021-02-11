var artworks_map = [];
var clusterSource;

$(document).ready(function() {
  let data = null

  let li = document.getElementById('ca-edit')
  let page = window.location.href.replace(/^.*index\.php\?title=/, '').replace(/^.*\/wiki\//, '')

  if (li) {
    let ul = li.parentElement,
        newLi = document.createElement("li")

    newLi.setAttribute('id', 'ca-form_edit')
    newLi.classList.add('collapsible')
    newLi.classList.add('selected')

    newLi.innerHTML = '<span><a href="http://atlasmuseum.net/wiki/Spécial:WikidataEditCollection/'+page+'">Modifier avec formulaire</a></span></li>';

    ul.insertBefore(newLi, li)
  }

  $.getJSON('http://atlasmuseum.net/w/amapi/index.php?action=amgetcollection&props=artworks&collection=' + page)
    .then(function(result) {
      data = result.entities.data.artworks
      initMap(data)
    })
})

/**
 * Initialisation de la carte, après réception des données
 */
initMap = function (data) {
  $('#map-loader').hide()
  artworks_map = data
  // console.log(data)
  createMap(data)
  createList(data, 'collectionTable')
}

/**
 * Création d'une carte
 */
createMap = function(artworksData, divId = 'map') {
  const features = []
  let perenneCheckbox = false
  let ephemereCheckbox = false
  let detruiteCheckbox = false
  let verifierCheckbox = false
  let nonRealiseeCheckbox = false

  for (let key in artworksData) {
    if (artworksData[key].lat !== 0 || artworksData[key].lon !== 0) {
      features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.transform([artworksData[key].lon, artworksData[key].lat], "EPSG:4326", "EPSG:3857")),
        title: (artworksData[key].title !== null ? artworksData[key].title : 'Titre inconnu'),
        artist: artworksData[key].artists,
        id: artworksData[key].wikidata,
        nature: artworksData[key].nature,
        article: artworksData[key].article,
      }))
      if (!perenneCheckbox && artworksData[key].nature === 'pérenne')
        perenneCheckbox = true
      if (!ephemereCheckbox && artworksData[key].nature === 'éphémère')
        ephemereCheckbox = true
      if (!detruiteCheckbox && artworksData[key].nature === 'détruite')
        detruiteCheckbox = true
      if (!verifierCheckbox && artworksData[key].nature === 'à vérifier')
        verifierCheckbox = true
      if (!nonRealiseeCheckbox && artworksData[key].nature === 'non réalisée')
        nonRealiseeCheckbox = true
    }
  }

  const extent = features[0].getGeometry().getExtent().slice(0)
  features.forEach(function(feature){ ol.extent.extend(extent,feature.getGeometry().getExtent())})
    
  const raster = new ol.layer.Tile({
    source: new ol.source.OSM()
  })

  const source = new ol.source.Vector({
    features: features
  })

  clusterSource = new ol.source.Cluster({
    distance: 50,
    source: source
  })

  const clusters = createClusters(clusterSource)

  const popupContainer = document.getElementById(divId + "-popup")
  const popupContent = document.getElementById(divId + "-popup-content")
  const popupCloser = document.getElementById(divId + "-popup-closer")

  popupCloser.onclick = function() {
    overlay.setPosition(undefined)
    popupCloser.blur()
    return false
  }
  
  const overlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
    element: popupContainer,
    autoPan: true,
    autoPanAnimation: {
      duration: 250
    },
  }))

  const map = new ol.Map({
    layers: [raster, clusters],
    overlays: [overlay],
    target: divId,
  })

  map.getView().setMinZoom(1)
  map.getView().fit(extent)

  map.on("pointermove", function (e) {
    let newStyle = this.hasFeatureAtPixel(e.pixel) ? "pointer" : ""
    this.getTargetElement().style.cursor = newStyle
  })

  createPopup(map, overlay, popupContainer, popupContent, popupCloser)

  if (perenneCheckbox)
    $('#checkbox-perenne').removeAttr("disabled")
  if (ephemereCheckbox)
    $('#checkbox-ephemere').removeAttr("disabled")
  if (detruiteCheckbox)
    $('#checkbox-detruite').removeAttr("disabled")
  if (verifierCheckbox)
    $('#checkbox-verifier').removeAttr("disabled")
  if (nonRealiseeCheckbox)
    $('#checkbox-non-realisee').removeAttr("disabled")
}

createClusters = function(clusterSource) {
  const styleCache = {}

  return new ol.layer.Vector({
    source: clusterSource,
    style: function(feature) {
      const size = feature.get("features").length;
      let style = styleCache[size];
      if (!style || size==1) {
        if (size<5) {
          radius = 10
          color_in = [0, 140, 255]
          color_out = [0, 140, 255, 0.3]
        }
        else if (size<10) {
          radius = 12
          color_in = [255, 191, 0]
          color_out = [255, 191, 0, 0.3]
        }
        else if (size<20) {
          radius = 14
          color_in = [255, 0, 0]
          color_out = [255, 0, 0, 0.3]
        }
        else {
          radius = 15
          color_in = [255, 0, 237]
          color_out = [255, 0, 237, 0.3]
        }
        
        if (size>1)
          style = [new ol.style.Style({
            image: new ol.style.Circle({
              radius: radius+5,
              fill: new ol.style.Fill({ color: color_out })
            })
          }),new ol.style.Style({
            image: new ol.style.Circle({
              radius: radius,
              fill: new ol.style.Fill({ color: color_in })
            }),
            text: new ol.style.Text({
              text: size.toString(),
              font: 'bold 11px Arial, Verdana, Helvetica, sans-serif',
              fill: new ol.style.Fill({ color: "#000" })
            })
          })];
          else {
            let icon_src = "";
            switch (feature.get("features")[0].get("nature")) {
              case "pérenne":
                icon_src = "http://atlasmuseum.net/w/images/a/a0/Picto-gris.png";
                break;
              case "éphémère":
                icon_src = "http://atlasmuseum.net/w/images/4/49/Picto-jaune.png";
                break;
              case "détruite":
                icon_src = "http://atlasmuseum.net/w/images/a/a8/Picto-rouge.png";
                break;
              case "non réalisée":
                icon_src = "http://atlasmuseum.net/w/images/2/2d/Picto-blanc.png";
                break;
              case "à vérifier":
                icon_src = "http://atlasmuseum.net/w/images/9/90/Picto-bleu.png";
                break;
              default:
                icon_src = "http://atlasmuseum.net/w/images/d/dd/Picto-Wikidata.png";
            }
                
            style = new ol.style.Style({
              image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                anchor: [0.5, 46],
                anchorXUnits: "fraction",
                anchorYUnits: "pixels",
                src: icon_src
              }))
            });
          }
          styleCache[size] = style
      }
      return style
    }
  });
}

createPopup = function (map, overlay, container, content, closer) {
  map.on("click", function(evt) {
    let feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) { return feature })
    container.style.visibility = "visible"
    if (typeof feature === "undefined") {
      overlay.setPosition(undefined)
      closer.blur()
      return false
    }
    else if (feature.get("features").length == 1) {
      const features = feature.get("features")
      let article = features[0].get("article")
      let title = features[0].get("title")
      let artist = features[0].get("artist")
      const nature = features[0].get("nature")
      const id = features[0].get("id")

      const coordinate = evt.coordinate

      let link = ''
      if (article != '')
        link = article
      else
        link = "Spécial:Wikidata/" + id

      //let text = "<p><b><a href=\"" + link + "\">" + title + "</a></b></p><hr />"
      let text = "<p><b><a href=\"" + link + "\">" + title + "</a></b></p><div id=\"map-image-placeholder\"></div><hr />"

      text += '<table><tbody>'

      if (artist != "") {
        const artists = []
        for (let keyArtist in artist) {
          artists.push(artist[keyArtist].name)
        }
        text += "<tr><th>Auteur : </th><td>"+artists.join(', ')+"</td></tr>"
      }
      if (nature != "" && nature != "wikidata")
        text += "<tr><th>Nature : </th><td>"+nature+"</td></tr>"

      text += '</tbody></table>'

      content.innerHTML = text

      loadImage(article ? article : id, nature === "wikidata")

      overlay.setPosition(coordinate)
    }
  });
}

loadImage = function(article, wikidata) {
  let getArtworkUrl = 'http://atlasmuseum.net/w/amapi/index.php?action=amgetartwork&article=' + encodeURIComponent(article.replace('http://atlasmuseum.net/wiki/', ''))
  if (wikidata) {
    getArtworkUrl += '&origin=wikidata'
  }
  $.getJSON(getArtworkUrl)
    .then(function(result) {
      if (result && result.entities && result.entities.data && result.entities.data.image_principale) {
        const imageData = result.entities.data.image_principale
        const getImageUrl = 'http://atlasmuseum.net/w/amapi/index.php?action=amgetimage&image=' + encodeURIComponent(imageData.value[0].value) + '&width=200&origin=' + imageData.value[0].origin
        $.getJSON(getImageUrl)
          .then(function(resultImage) {
            const replaceImg = '<img src="' + resultImage.entities.thumbnail + '" />'
            document.getElementById('map-image-placeholder').innerHTML = replaceImg
          })
      } else {
        const replaceImg = '<img src="http://atlasmuseum.net/w/images/thumb/5/5f/Image-manquante.jpg/200px-Image-manquante.jpg" />'
        document.getElementById('map-image-placeholder').innerHTML = replaceImg
      }
    })
}

changeMarkers = function() {
  clusterSource.getSource().clear()
  features = Array()
  for (var key in artworks_map) {
    if ((artworks_map[key].nature == "pérenne" && $("#checkbox-perenne").is(":checked")) ||
        (artworks_map[key].nature == "éphémère" && $("#checkbox-ephemere").is(":checked")) ||
        (artworks_map[key].nature == "détruite" && $("#checkbox-detruite").is(":checked")) ||
        (artworks_map[key].nature == "non réalisée" && $("#checkbox-non-realisee").is(":checked")) ||
        (artworks_map[key].nature == "à vérifier" && $("#checkbox-verifier").is(":checked")) ||
        (artworks_map[key].nature == "wikidata" && $("#checkbox-wikidata").is(":checked")))
      features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.transform([artworks_map[key].lon, artworks_map[key].lat], "EPSG:4326", "EPSG:3857")),
        title: artworks_map[key].title,
        artist: artworks_map[key].artist,
        id: artworks_map[key].wikidata,
        nature: artworks_map[key].nature,
        article: artworks_map[key].article,
      }))
  }

  clusterSource.getSource().addFeatures(features)
}

/**
 * Création de la liste
 */
createList = function(artworksData, tableId = 'collectionTable') {
  let even = true

  for (let key in artworksData) {
    let text = '<tr class="row-' + (even ? 'even' : 'odd') + '">'
    even = !even

    text += '<td class="Titre-de-l\'œuvre smwtype_txt">'
    text += '<a href="' + artworksData[key].article + '">'
    text += artworksData[key].title !== null ? artworksData[key].title : 'Titre inconnu'
    text += '</a>'
    text += '</td>'

    text += '<td class="Artiste smwtype_wpg">'
    const artists = []
    for (let keyArtist in artworksData[key].artists) {
      if (artworksData[key].artists[keyArtist].name !== null)
        artists.push('<a href="' + artworksData[key].artists[keyArtist].url + '">' + artworksData[key].artists[keyArtist].name + '</a>')
      else
        artists.push('Auteur inconnu')
    }
    text += artists.join(', ')
    text += '</td>'

    text += '<td class="Date smwtype_dat">'
    text += artworksData[key].date
    text += '</td>'

    text += '</tr>'

    $('#' + tableId).find('tbody').append(text)
  }
}
