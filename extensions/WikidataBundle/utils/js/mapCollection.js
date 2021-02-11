var mapData = [];
var clusterSource;

document.addEventListener("DOMContentLoaded", function(event) {
  mapData = JSON.parse($('#mapData').attr("data-artworks"))

  features = [];

  for (var key in mapData) 
    if (mapData[key].latitude && mapData[key].longitude)
      features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.transform([mapData[key].longitude, mapData[key].latitude], "EPSG:4326", "EPSG:3857")),
        title: mapData[key].title,
        //artist: mapData[key].creator.join(", "),
        artist: '',
        type: mapData[key].nature,
        date: mapData[key].date,
        article: mapData[key].article,
        image: mapData[key].image
      }));

  var extent = features[0].getGeometry().getExtent().slice(0);
  features.forEach(function(feature){ ol.extent.extend(extent,feature.getGeometry().getExtent())});
  
  var raster = new ol.layer.Tile({
    source: new ol.source.OSM()
  });
  var source = new ol.source.Vector({
    features: features
  });

  clusterSource = new ol.source.Cluster({
    distance: 50,
    source: source
  });
  var styleCache = {};
  var clusters = new ol.layer.Vector({
    source: clusterSource,
    style: function(feature) {
      var size = feature.get("features").length;
      var style = styleCache[size];
      if (!style || size==1) {
        if (size<5) {
          radius = 10;
          color_in = [0, 140, 255]
          color_out = [0, 140, 255, 0.3]
        }
        else if (size<10) {
          radius = 12;
          color_in = [255, 191, 0]
          color_out = [255, 191, 0, 0.3]
        }
        else if (size<20) {
          radius = 14;
          color_in = [255, 0, 0]
          color_out = [255, 0, 0, 0.3]
        }
        else {
          radius = 15;
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
            var icon_src = "";
            switch (feature.get("features")[0].get("type")) {
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
          styleCache[size] = style;
      }
      return style;
    }
  });
  
  var container = document.getElementById("map-popup");
  var content = document.getElementById("map-popup-content");
  var closer = document.getElementById("map-popup-closer");

  closer.onclick = function() {
    overlay.setPosition(undefined);
    closer.blur();
    return false;
  };

  var overlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
    element: container,
    autoPan: true,
    autoPanAnimation: {
      duration: 250
    }
  }));

  var map = new ol.Map({
    layers: [raster, clusters],
    overlays: [overlay],
    target: "map"
  });
  map.getView().setMinZoom(1);
  map.getView().fit(extent);

  map.on("pointermove", function (e) {
    /*let newStyle = this.hasFeatureAtPixel(e.pixel) ? "pointer" : "";
    if (newStyle !== cursorStyle) {
        this.getTargetElement().style.cursor = cursorStyle = newStyle;
    }*/
    let newStyle = this.hasFeatureAtPixel(e.pixel) ? "pointer" : "";
    this.getTargetElement().style.cursor = newStyle;
});

  map.on("click", function(evt) {
    var feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) { return feature; });
    container.style.visibility = "visible";
    if (typeof feature === "undefined") {
      overlay.setPosition(undefined);
      closer.blur();
      return false;
    }
    else if (feature.get("features").length == 1) {
      var features = feature.get("features");
      article = features[0].get("article");
      title = features[0].get("title");
      image = features[0].get("image");
      artist = features[0].get("artist");
      date = "";
      nature = features[0].get("type");
      id = features[0].get("id");
      type= features[0].get("type");

      var coordinate = evt.coordinate;
      var hdms = ol.coordinate.toStringHDMS(ol.proj.transform( coordinate, "EPSG:3857", "EPSG:4326"));

      var link = '';
      if (article != '')
        link = article
      else
        link = "Spécial:Wikidata/"+id

      var text = "<p><b><a href=\"http://atlasmuseum.net/wiki/"+link+"\">"+title+"</a></b></p><hr />";

      /*if (image != "")
        text += "<div class=\"map_infowindow_image\"><a href=\"index.php?action=artwork&q="+id+"&origin="+((type == "Wikidata")?"wikidata":"atlasmuseum")+"\"><img src=\""+image+"\" /></a></div>";*/

      text += '<table><tbody>'

      if (artist != "")
        text += "<tr><th>Auteur : </th><td>"+artist+"</td></tr>";
      if (date != "")
        text += "<tr><th>Date : </th><td>"+date+"</td></tr>";
      if (nature != "" && nature != "wikidata")
        text += "<tr><th>Nature : </th><td>"+nature+"</td></tr>";

      text += '</tbody></table>'

      $("#map-popup-content").html(text);

      loadImage(article ? article : id, nature === "wikidata")

      overlay.setPosition(coordinate);
    }
  });
});

loadImage = function(article, wikidata) {
  let getArtworkUrl = 'http://atlasmuseum.net/w/amapi/index.php?action=amgetartwork&article=' + encodeURIComponent(article)
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
  clusterSource.getSource().clear();
  features = Array();
  for (var key in mapData) {
    if ((mapData[key].nature == "pérenne" && $("#checkbox-perenne").is(":checked")) ||
        (mapData[key].nature == "éphémère" && $("#checkbox-ephemere").is(":checked")) ||
        (mapData[key].nature == "détruite" && $("#checkbox-detruite").is(":checked")) ||
        (mapData[key].nature == "non réalisée" && $("#checkbox-non-realisee").is(":checked")) ||
        (mapData[key].nature == "à vérifier" && $("#checkbox-verifier").is(":checked")) ||
        (mapData[key].nature == "wikidata" && $("#checkbox-wikidata").is(":checked")))
      features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.transform([mapData[key].longitude, mapData[key].latitude], "EPSG:4326", "EPSG:3857")),
        title: mapData[key].title,
        artist: mapData[key].creator.join(", "),
        id: mapData[key].wikidata,
        date: mapData[key].date,
        type: mapData[key].nature,
        article: mapData[key].article,
        image: mapData[key].image
      }));
  }

  clusterSource.getSource().addFeatures(features);
}