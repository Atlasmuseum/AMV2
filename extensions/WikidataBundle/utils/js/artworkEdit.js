showTab = function(tab, section) {
  var list = document.getElementsByClassName('edit_section');
  for (var i=0; i<list.length; i++) {
    list[i].classList.remove('section_selected');
    list[i].classList.add('section_unselected');
  }
  document.getElementById(section).classList.remove('section_unselected');
  document.getElementById(section).classList.add('section_selected');

  list = document.getElementsByClassName('edit_tab');
  for (var i=0; i<list.length; i++) {
    list[i].classList.remove('tab_selected');
    list[i].classList.add('tab_unselected');
  }
  tab.classList.remove('tab_unselected');
  tab.classList.add('tab_selected');
}

addLine = function(id, property, key, mandatory, wikidataId="", wikidataLabel="") {
  var td = document.getElementById(id),
      divs = document.querySelectorAll('#' + id + ' .inputSpan'),
      newLine = document.createElement("div"),
      add = document.querySelectorAll('#' + id + '> .add_button')[0]

  if (divs.length>0)
    n = parseInt(divs[divs.length-1].id.replace(id+'_wrapper_', ''))+1
  else
    n = 0;

  newLine.classList.add('inputSpan')
  newLine.classList.add('autocomplete')
  if (mandatory)
    newLine.classList.add('mandatoryFieldSpan')

  newLine.setAttribute('id', id+'_wrapper_'+n)

  newLine.innerHTML = '<input id="'+id+'_'+n+'" class="createboxInput'+(mandatory ? ' mandatoryField' : '')+'" size="60" value="' + wikidataLabel + '" name="Edit['+key+']['+n+']"><input type="hidden" id="'+id+'_id_'+n+'" name="Edit['+key+'][id]['+n+']" value="' + wikidataId + '"></input> <span class="edit_item_button" title="Supprimer cette ligne" onclick="removeLine(\''+id+'_wrapper_'+n+'\')">[&nbsp;x&nbsp;]</span>';
  
  td.insertBefore(newLine, add)
  autocomplete(document.getElementById(id+'_'+n))
}

removeLine = function(id) {
  document.getElementById(id).remove()
}

getLabel = function(id, callback) {
  const url = 'https://www.wikidata.org/w/api.php'
  const params = {
    action: 'wbgetentities',
    ids: id,
    languages: 'fr',
    props: 'labels',
    format: 'json',
    origin: '*',
  }
  $.getJSON(url, params, function(res) {
    if (res && res.entities && res.entities[id] && res.entities[id].labels && res.entities[id].labels.fr)
      callback(res.entities[id].labels.fr.value)
    else
      callback(id)
  })
}

getImageAm = function(image, width, callback) {
  const url = 'http://publicartmuseum.net/w/api.php'
  const params = {
    action: 'query',
    prop: 'imageinfo',
    iiprop: 'url',
    iiurlwidth: width,
    titles: 'File:' + image,
    format: 'json',
  }
  $.getJSON(url, params, function(res) {
    if (res && res.query && res.query.pages) {
      const keys = Object.keys(res.query.pages)
      callback(res.query.pages[keys[0]].imageinfo[0].thumburl)
    } else {
      callback('')
    }
  })
}

importWikidataClaim = function(claim, property) {
  for (let i = 0; i < claim.length; i++) {
    let id = claim[i].mainsnak.datavalue.value.id
    getLabel(id, function(label) {
      let j = 0
      let found = false
      while (document.getElementById('input_' + property + '_' + j)) {
        const elementLabel = document.getElementById('input_' + property + '_' + j).value
        const elementId = document.getElementById('input_' + property + '_id_' + j).value
        if (elementId === id) {
          found = true
          break
        }
        if (elementLabel === label) {
          found = true
          document.getElementById('input_' + property + '_id_' + j).value = id
          break
        }
        j++
      }
      if (!found) {
        addLine('input_' + property, property, property, true, id, label)
      }
    })
  }
}

importWikidataImage = function(claim, property) {
  if (claim[0]) {
    document.getElementById('input_' + property).value = claim[0].mainsnak.datavalue.value
    document.getElementById('input_checkbox_' + property).setAttribute('checked', 'true')
  }
}

importWikidata = function() {
  let wikidataId = document.getElementById('input_label').value
  let pattern = /^[qQ][0-9]+$/
  if (pattern.test(wikidataId)) {
    const url = 'https://www.wikidata.org/w/api.php'
    const params = {
      action: 'wbgetentities',
      ids: wikidataId,
      languages: 'fr',
      props: 'labels|claims',
      format: 'json',
      origin: '*',
    }
    $.getJSON(url, params, function(res) {
      if (res.entities && res.entities[wikidataId]) {
        const labels = res.entities[wikidataId].labels
        const claims = res.entities[wikidataId].claims
        if (labels && labels.fr) {
          // Label
          if (document.getElementById('input_2').value === '') {
            document.getElementById('input_2').value = labels.fr.value
          }
        }
        if (claims) {
          // Coordonnées
          if (claims.P625) {
            if (document.getElementById('coordinates_input').value === '' || document.getElementById('coordinates_input').value === '0, 0') {
              latitude = claims.P625[0].mainsnak.datavalue.value.latitude
              longitude = claims.P625[0].mainsnak.datavalue.value.longitude
              document.getElementById('coordinates_input').value = latitude + ', ' + longitude
            }
          }

          // Créateurs
          if (claims.P170) {
            importWikidataClaim(claims.P170, 'artiste')
          }

          // Localisation
          if (claims.P131) {
            importWikidataClaim(claims.P131, 'site_ville')
          }

          // Pays
          if (claims.P17) {
            importWikidataClaim(claims.P17, 'site_pays')
          }

          // Site
          if (claims.P276) {
            importWikidataClaim(claims.P276, 'site_nom')
          }

          // Commanditaires
          if (claims.P88) {
            importWikidataClaim(claims.P88, 'commanditaires')
          }

          // Commissaires
          if (claims.P1640) {
            importWikidataClaim(claims.P1640, 'commissaires')
          }

          // Image
          if (claims.P18) {
            importWikidataImage(claims.P18, 'image_principale')
          }
        }
      }
    })
  }
}

publish = function() {
  var form_data = $('#edit_form').serializeArray(),
      data = {},
      article = ''

  // console.log(form_data);

  for (var i=0; i<form_data.length; i++)
    if (form_data[i].name == 'article') {
      article = form_data[i].value
    } else {
      params = form_data[i].name.replace(/Edit\[(.*)\]$/, '$1').split('][');
      const eq = params[0]

      if(params.length == 1) {
        data[eq] = form_data[i].value;
      } else
      if(params.length == 2) {
        let index = params[1]
        if (!data[eq])
          data[eq] = [];
        if (!data[eq][index])
          data[eq][index] = {'label':'','id':''};
        data[eq][index]['label'] = form_data[i].value;
      } else
      if(params.length == 3) {
        let index = params[2]
        if (!data[eq])
          data[eq] = [];
        if (!data[eq][index]) 
          data[eq][index] = {'label':'','id':''};
        data[eq][index][params[1]] = form_data[i].value;
      }
    }

  if (data['image_principale_origin'] && data['image_principale_origin'] === 'on') {
    if (data['image_principale']) {
      data['image_principale'] = 'Commons:' + data['image_principale']
    }
    delete data['image_principale_origin']
  }

  // console.log(data)

  var text = '{{Notice d\'œuvre\n';

  for (var key in data) {
    if (Array.isArray(data[key])) {
      var r = [];
      for (var i in data[key]) {
        if (data[key][i].id != '')
          r.push(data[key][i].id)
        else
        if (data[key][i].label != '')
          //r.push(data[key][i].label.replace(/\"/g, '&quot;').replace(/\n/g, '\\n').replace(/\r/g, '').replace(/</g, '&lt;').replace(/>/g, '&gt;'))
          r.push(data[key][i].label
            .replace(/\"/g, '&quot;')
            .replace(/\n/g, '\\n')
            .replace(/\r/g, '')
            .replace(/<br[\s]*\/[\s]*>/gi, '\\n')
          )
      }
      text += '|' + key + '=' + r.join(';') + '\n';
    } else {
      if (data[key] != '')
        text += '|' + key + '=' + data[key]
          .replace(/\"/g, '&quot;')
          .replace(/\n/g, '\\n')
          .replace(/\r/g, '')
          .replace(/<br[\s]*\/[\s]*>/gi, '\\n')
          + '\n';
    }
  }

  text += '}}\n'

  // console.log(text)

  //console.log($('#real_edit_form').serializeArray());

  //-- Envoi des données
  document.getElementById('wpTextbox1').value = text;
  if (article != '') {
    document.getElementById('editform').action = '/w/index.php?title=' + encodeURIComponent(article) + '&action=submit';
    document.getElementById("editform").submit();
  } else {
    article = createArticleName(data)
    document.getElementById('editform').action = '/w/index.php?title=' + encodeURIComponent(article) + '&action=submit';
    document.getElementById("editform").submit();
  }
}

createArticleName = function(data) {
  const artist = []
  for (let i in data.artiste)
    artist.push(data.artiste[i].label)

  article = ''
  article_title = data.titre != '' ? data.titre : 'Titre inconnu'
  article_artist = artist.length > 0 ? artist.join(', ') : 'artiste inconnu'

  if (article_title === 'Titre inconnu' && article_artist === 'artiste inconnu') {
    const today = new Date()
    let hours = today.getHours()
    if (hours < 10)
      hours = '0' + hours
    let minutes = today.getMinutes()
    if (minutes < 10)
      minutes = '0' + minutes
    let dd = today.getDate()
    let mm = today.getMonth() + 1
    const yyyy = today.getFullYear()
    if (dd < 10)
      dd = '0' + dd
    if (mm < 10)
      mm = '0' + mm
    const date_string = dd + '/' + mm + '/' + yyyy + ' ' + hours + ':' + minutes
    article = article_title + ' (' + article_artist + ', ' + date_string + ')'
  } else
    article = article_title + ' (' + article_artist + ')'

  return article
}

change_map_input = function() {
  coords = document.getElementById('coordinates_input').value.split(/[\s]*,[\s]*/)

  if (coords.length == 2) {
    var latitude = parseFloat(coords[0]),
        longitude = parseFloat(coords[1]);

    if (!isNaN(latitude) && !isNaN(longitude) && latitude>=-90 && latitude<=90 && longitude>=-180 && longitude <=180) {
      
      var new_location = ol.proj.transform([longitude, latitude], "EPSG:4326", "EPSG:3857")
      marker.getGeometry().setCoordinates(new_location);
      map.getView().setCenter(new_location);
      reverse_geocoding({
        lat: latitude,
        lon: longitude,
      })
    }
  }
}

change_map_address = function() {
  address = document.getElementById('address_input').value
  if (address != "") {
    var params = {
      "address": address,
      // "key": "AIzaSyBlETt3Lsnmn6Rz7eE42Fwtci0ZU6UUBkU"
      "key": "AIzaSyDdtlAVCL9s3s5SLxPIa58ueeXQIsKv-UU"
    }
    
    $.getJSON('https://maps.google.com/maps/api/geocode/json', params).then(function(res) {
    
      if (res.results.length > 0) {
        var new_location = ol.proj.transform([res.results[0].geometry.location.lng, res.results[0].geometry.location.lat], "EPSG:4326", "EPSG:3857")
        marker.getGeometry().setCoordinates(new_location);
        map.getView().setCenter(new_location);

        document.getElementById('coordinates_input').value = res.results[0].geometry.location.lat + ", " + res.results[0].geometry.location.lng
        reverse_geocoding({
          lat: res.results[0].geometry.location.lat,
          lon: res.results[0].geometry.location.lng,
        })
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", function(event) {
  coords = document.getElementById('coordinates_input').value.split(/[\s]*,[\s]*/)

  marker = new ol.Feature({
    geometry: new ol.geom.Point(ol.proj.transform([parseFloat(coords[1]), parseFloat(coords[0])], "EPSG:4326", "EPSG:3857"))
  });
  var extent = marker.getGeometry().getExtent().slice(0);
  var raster = new ol.layer.Tile({
    source: new ol.source.OSM()
  });
  var vectorSource = new ol.source.Vector({
    features: [marker]
  });
  var iconStyle = new ol.style.Style({
    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
      anchor: [0.5, 46],
      anchorXUnits: 'fraction',
      anchorYUnits: 'pixels',
      opacity: 0.75,
      src: 'http://publicartmuseum.net/w/images/a/a0/Picto-gris.png'
    }))
  });
  var vectorLayer = new ol.layer.Vector({
    source: vectorSource,
    style: iconStyle
  });
  map = new ol.Map({
    layers: [raster, vectorLayer],
    target: "map"
  });
  map.getView().fit(extent);
  map.getView().setZoom(15);

  map.on('click', function(evt) {
    map.getView().setCenter(evt.coordinate);
    var coordinates = ol.proj.transform(evt.coordinate, 'EPSG:3857', 'EPSG:4326');
    marker.getGeometry().setCoordinates(evt.coordinate);

    document.getElementById('coordinates_input').value = coordinates[1] + ", " + coordinates[0];
    reverse_geocoding({
      lat: coordinates[0],
      lon: coordinates[1],
    })
  });
});

change_image_thumb = function(inputId) {
  const input = document.getElementById(inputId);
  if (input) {
    const thumb = document.getElementById(inputId + '_thumb');
    if (thumb) {
      const imageName = input.value
      getImageAm(imageName, 200, function(imageUrl) {
        if (imageUrl != '')
          thumb.getElementsByTagName('img')[0].src = imageUrl
      })
    }
  }
}

add_image_line = function(property) {
  let container = document.getElementById('input_' + property + '_container');
  let n = container.childElementCount;
  // let html = '<div class="multipleTemplateInstance multipleTemplate" id="input_' + property + '_instance_' + n + '"><table><tbody><tr><td> <table><tbody><tr><td style="width:140px;"><b>Importer une image&nbsp;:</b></td><td><span class="inputSpan"><input id="input_' + property + '_' + n + '" class="createboxInput" size="35" value="" name="Edit[' + property + '][' + n + ']" type="text">\n<a data-fancybox data-type="iframe" data-src="http://publicartmuseum.net/w/index.php?title=Sp%C3%A9cial:UploadWindow&amp;pfInputID=input_' + property + '_' + n + '" href="javascript:;">Importer un fichier</a></span></td></tr></tbody></table></td><td><a class="addAboveButton" title="Ajouter une autre instance au-dessus de celle-ci"><img src="/w/extensions/SemanticForms/skins/SF_add_above.png" class="multipleTemplateButton"></a></td><td><button class="removeButton" title="Enlever cette instance" onclick="remove_image_line(\'' + property + '\', ' + n + ')"><img src="/w/extensions/SemanticForms/skins/SF_remove.png" class="multipleTemplateButton"></button></td><td class="instanceRearranger"><img src="/w/extensions/SemanticForms/skins/rearranger.png" class="rearrangerImage"></td></tr></tbody></table></div>';
  let html = '<div class="multipleTemplateInstance multipleTemplate" id="input_' + property + '_instance_' + n + '"><table><tbody><tr><td> <table><tbody><tr><td style="width:140px;"><b>Importer une image&nbsp;:</b></td><td><span class="inputSpan"><input id="input_' + property + '_' + n + '" class="createboxInput" size="35" value="" name="Edit[' + property + '][' + n + ']" type="text">\n<a data-fancybox data-type="iframe" data-src="http://publicartmuseum.net/w/index.php?title=Sp%C3%A9cial:UploadWindow&amp;pfInputID=input_' + property + '_' + n + '" href="javascript:;">Importer un fichier</a></span></td></tr></tbody></table></td><td><button class="removeButton" title="Enlever cette instance" onclick="remove_image_line(\'' + property + '\', ' + n + ')"><img src="/w/extensions/SemanticForms/skins/SF_remove.png" class="multipleTemplateButton"></button></td></tr></tbody></table></div>';
  let e = document.createElement('div');
  e.innerHTML = html;
  while(e.firstChild) {
    container.appendChild(e.firstChild);
  }
}

remove_image_line = function(property, index) {
  let instance = document.getElementById('input_' + property + '_instance_' + index);
  instance.remove();
}

reverse_geocoding = function(coordinates) {
  //-- check if reverse geocoding car be used (i.e. adequate form fields are empty)
  const geocoding_ok = $('#input_site_region').val() !== undefined && 
    $('#input_site_departement').val() !== undefined && 
    $('#input_site_ville_wrapper_0').length === 0 &&
    $('#input_site_pays_wrapper_0').length === 0
console.log(coordinates)
console.log(geocoding_ok)
  if (geocoding_ok) {
    //-- call gmaps.php
    $.getJSON('http://publicartmuseum.net/w/amapi/gmaps.php?latitude='+coordinates.lat+'&longitude='+coordinates.lon+'&username=atlasmuseum', function(response) {
      $.each(response, function(key, value) {
        switch (key) {
          case 'country':
            country = value
            break
          case 'administrative_area_level_1':
            adm1 = value
            break
          case 'administrative_area_level_2':
            adm2 = value
            break
          case 'locality':
            locality = value
            break
        }
      });
      
      // update form fields if needed
      if (country) {
        addLine('input_site_pays', 'site_pays', 'site_pays')
        $('#input_site_pays_0').val(country)
      }
      if (adm1)
        $('#input_site_region').val(adm1)
      if (adm2)
        $('#input_site_departement').val(adm2)
      if (locality) {
        addLine('input_site_ville', 'site_ville', 'site_ville')
        $('#input_site_ville_0').val(locality)
      }
    });
  }
}