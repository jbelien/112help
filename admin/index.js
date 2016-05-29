var map, vectorSource, circleSource, vectorLayer, circleLayer;

var baselayerBing       = new ol.layer.Tile({ source: new ol.source.BingMaps({ key: 'AqrncJ8kQb58T8fylEPY7nZHRcEMGqyL_9WUoTlMT_dufzDUbZPz9oKOWe8UmuVZ', imagerySet: 'Road' }) });
var baselayerBingAerial = new ol.layer.Tile({ source: new ol.source.BingMaps({ key: 'AqrncJ8kQb58T8fylEPY7nZHRcEMGqyL_9WUoTlMT_dufzDUbZPz9oKOWe8UmuVZ', imagerySet: 'Aerial', maxZoom: 19 }) });
var baselayerMapQuest   = new ol.layer.Tile({ source: new ol.source.MapQuest({ layer: 'osm' }) });
// http://cirb.brussels/fr/nos-solutions/urbis-solutions/urbis-tools
var baselayerUrbIS      = new ol.layer.Tile({ source: new ol.source.TileWMS({ url: 'http://geoserver.gis.irisnet.be/urbis/wms/', params: { LAYERS: 'urbisFR' }, attributions: [ '&copy; CIRB-CIBG' ] }) });
var baselayerUrbIS_P    = new ol.layer.Tile({ source: new ol.source.TileWMS({ url: 'http://geoserver.gis.irisnet.be/urbis/wms/', params: { LAYERS: 'urbis:ortho2014' }, attributions: [ '&copy; CIRB-CIBG' ] }) });
// https://www.agiv.be/producten/grb
// https://www.agiv.be/producten/orthofotomozaieken
var baselayerAGIV       = new ol.layer.Tile({ source: new ol.source.XYZ({ url: 'https://tile.informatievlaanderen.be/ws/raadpleegdiensten/wmts/?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=grb_bsk&STYLE=&FORMAT=image/png&tileMatrixSet=GoogleMapsVL&tileMatrix={z}&tileRow={y}&tileCol={x}', attributions: [ '&copy; AGIV' ] }) });
var baselayerAGIV_P     = new ol.layer.Tile({ source: new ol.source.XYZ({ url: 'https://tile.informatievlaanderen.be/ws/raadpleegdiensten/wmts/?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=omwrgbmrvl&STYLE=&FORMAT=image/png&tileMatrixSet=GoogleMapsVL&tileMatrix={z}&tileRow={y}&tileCol={x}', attributions: [ '&copy; AGIV' ] }) });

var baselayerSPW        = new ol.layer.Tile({ source: new ol.source.TileWMS({ url: 'https://geoservices.wallonie.be/arcgis/services/TOPOGRAPHIE/PICC_VDIFF/MapServer/WMSServer', params: { LAYERS: '0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26' }, attributions: [ '&copy; SPW' ] }) });
var baselayerSPW_P      = new ol.layer.Tile({ source: new ol.source.TileWMS({ url: 'https://geoservices.wallonie.be/arcgis/services/IMAGERIE/ORTHO_LAST/MapServer/WMSServer', params: { LAYERS: '0' }, attributions: [ '&copy; SPW' ] }) });

$(document).ready(function() {
  var scaleLineControl = new ol.control.ScaleLine();
  var attributionControl = new ol.control.Attribution({ collapsible: false });

  vectorSource = new ol.source.Vector({ format: new ol.format.GeoJSON() });
  circleSource = new ol.source.Vector();

  vectorLayer = new ol.layer.Vector({ style: styleFunction, source: vectorSource });
  circleLayer = new ol.layer.Vector({ style: styleCircleFunction, source: circleSource });

  map = new ol.Map({
    target: 'map',
    layers: [
      baselayerMapQuest,
      circleLayer,
      vectorLayer
    ],
    controls: ol.control.defaults({ attribution: false }).extend([ scaleLineControl, attributionControl ]),
    view: new ol.View({
      center: ol.proj.fromLonLat([4.341233, 50.855545]),
      zoom: 15
    })
  });

  map.on('click', function(event) {
    $('#list .alert112').removeClass('active');
    map.forEachFeatureAtPixel(event.pixel, function(feature) {
      var id = parseInt(feature.getId());
      $('#message-'+id).addClass('active');
    });
  });

  map.on('moveend', function(evt) {
    $('#list .alert112').hide();
    var extent = map.getView().calculateExtent(map.getSize());
    vectorSource.forEachFeatureIntersectingExtent(extent, function(feature) {
      var f = feature.getProperties(), id = feature.getId();
      $('#message-'+id).show();
    });
  });

  loadFunction(true);

  $('select[name=relative]').on('change', function() {
    $(this).parent('form').trigger('submit');
  });
  $('input[name=show]').on('change', function() {
    vectorLayer.setVisible(($(this).val() == 1));
  });
  $('select[name=baselayer]').on('change', function() {
    switch($(this).val()) {
      case 'bing'    : map.getLayers().setAt(0, baselayerBing      ); break;
      case 'bing_p'  : map.getLayers().setAt(0, baselayerBingAerial); break;
      case 'cirb'    : map.getLayers().setAt(0, baselayerUrbIS     ); break;
      case 'cirb_p'  : map.getLayers().setAt(0, baselayerUrbIS_P   ); break;
      case 'agiv'    : map.getLayers().setAt(0, baselayerAGIV      ); break;
      case 'agiv_p'  : map.getLayers().setAt(0, baselayerAGIV_P    ); break;
      case 'spw'     : map.getLayers().setAt(0, baselayerSPW       ); break;
      case 'spw_p'   : map.getLayers().setAt(0, baselayerSPW_P     ); break;
      case 'mapquest':
      default        : map.getLayers().setAt(0, baselayerMapQuest); break;
    }
  });

  window.setInterval(function() { loadFunction(false); }, 60000);
});

$(document).on('click', '.alert112', function() {
  var id = $(this).data('id');
  map.getView().fit(vectorSource.getFeatureById(id).getGeometry().getExtent(), map.getSize(), { maxZoom: 19 });
});

var loadFunction = function(fit) {
  vectorSource.clear(); circleSource.clear(); $('#list').empty();

  $.get('json.php', { relative: relative }, function(data) {
    var d = new Date();
    $('.navbar-text > time').attr('datetime', d.toString()).text(d.toLocaleString());

    var features = (new ol.format.GeoJSON()).readFeatures(data, { featureProjection: 'EPSG:3857' }); vectorSource.addFeatures(features);

    if (features.length > 0) {
      for (var i = 0; i < features.length; i++) {
        var p = features[i].getProperties(), c = features[i].getGeometry().getCoordinates();

        if (p.accuracy != -1) {
          var circle = new ol.Feature({ id: p.id, indanger: p.indanger, urgence: p.urgence, geometry: new ol.geom.Circle(c, parseFloat(p.accuracy)) }); circleSource.addFeature(circle);
        }

        if (p.urgence & 1) { var img = document.createElement('img'); $(img).attr({ src: '../img/admin/fire.svg', height: '50px' }) }
        else if (p.urgence & 2) { var img = document.createElement('img'); $(img).attr({ src: '../img/admin/route.svg', height: '50px' }) }
        else if (p.urgence & 4) { var img = document.createElement('img'); $(img).attr({ src: '../img/admin/health.svg', height: '50px' }) }
        else if (p.urgence & 8) { var img = document.createElement('img'); $(img).attr({ src: '../img/admin/violence.svg', height: '50px' }) }

        var div = $('#infos').clone().show().attr({ id: 'message-'+p.id }).data({ id: p.id }); if (p.indanger == 0) { div.addClass('dismiss'); }
        div.find('.icone').append(img);
        div.find('address').text(p.address);
        div.find('.heure').attr({ title: p.datetime });
        div.find('.heure > time').text(p.ago).attr({ datetime: p.datetime });
        div.find('.distance > span').text((p.accuracy == -1 ? 'manual' : p.accuracy+'m.'));
        div.find('.batterie > span').text((p.battery != null ? p.battery+'%' : ''));
        $('#list').append(div);
      }

      if (fit === true) map.getView().fit(circleSource.getExtent(), map.getSize(), { maxZoom: 15 });
    }
  });
}

var styleFunction = function(feature) {
  var p = feature.getProperties();

  if (p.indanger == 0) { var fillColor = 'rgba(0,128,0,0.3)'; }
  else if (p.urgence & 1) { var fillColor = '#ED1F24'; }
  else if (p.urgence & 2) { var fillColor = '#01A64F'; }
  else if (p.urgence & 4) { var fillColor = '#1CBBB4'; }
  else if (p.urgence & 8) { var fillColor = '#979797'; }
  else { var fillColor = '#808080'; }

  return new ol.style.Style({
    image: new ol.style.Circle({
      radius: 6,
      fill: new ol.style.Fill({ color: fillColor }),
      stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
    }),
    zIndex: (p.indanger == 1 ? 19 : 11)
  });
}
var styleCircleFunction = function(feature) {
  var p = feature.getProperties();

  if (p.indanger == 0) { var strokeColor = 'rgba(0,128,0,0.3)', strokeWidth = 1, fillColor = false; }
  else if (p.urgence & 1) { var strokeColor = '#ED1F24', strokeWidth = 1, fillColor = false; }
  else if (p.urgence & 2) { var strokeColor = '#01A64F', strokeWidth = 1, fillColor = false; }
  else if (p.urgence & 4) { var strokeColor = '#1CBBB4', strokeWidth = 1, fillColor = false; }
  else if (p.urgence & 5) { var strokeColor = '#979797', strokeWidth = 1, fillColor = false; }
  else { var strokeColor = '#808080', strokeWidth = 1, fillColor = false; }

  if (feature.getGeometry().getRadius() <= 1000) {
    var fillColor = ol.color.asArray(strokeColor);
    fillColor = fillColor.slice();
    fillColor[3] = 0.1;
  }

  return new ol.style.Style({
    stroke: new ol.style.Stroke({
      color: strokeColor,
      width: strokeWidth
    }),
    fill: (fillColor !== false ? new ol.style.Fill({ color: fillColor }) : false),
    zIndex: (p.indanger == 1 ? 9 : 1)
  });
}