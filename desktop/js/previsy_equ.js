
function previsyNormalizer(str) {
  var accents    = "ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž' ";
  var accentsOut = "aaaaaaaaaaaaaooooooooooooeeeeeeeeeccdiiiiiiiiuuuuuuuunnssyyyzz--";
  str = str.split('');
  var strLen = str.length;
  var i, x;
  for (i = 0; i < strLen; i++) {
    if ((x = accents.indexOf(str[i])) != -1) {
      str[i] = accentsOut[x];
    }
  }
  var final = str.join('').toLowerCase();
  
  // Lien de test uniquement pour prevision-meteo.ch (Open-Meteo trouve la ville par géocodage)
  var previsyApi = (typeof previsyApiGlobale !== 'undefined' && previsyApiGlobale !== '') ? previsyApiGlobale : $('#previsyApi').val();
  if(final !== '' && previsyApi === 'prevision_meteo_ch'){
      $( "#previsyLinkVille" ).replaceWith('<a class="btn btn-sm btn-default" id="previsyLinkVille" href="https://www.prevision-meteo.ch/meteo/localite/'+final+'" target="_blank">{{Testez la ville de }}<b>'+final+'</b> {{sur}} prevision-meteo.ch</a>');
  } else {
      $( "#previsyLinkVille" ).replaceWith('<span id="previsyLinkVille"></span>');
  }
 
  return final;
}

function previsyInArray(name){
    if(name.match(/widget/)){ return 1; }
    else if(name.match(/dans_heure/)){ return 1; }
    else if(name.match(/Ville/)){ return 1; }
    else if(name.match(/Latitude/)){ return 1; }
    else if(name.match(/Longitude/)){ return 1; }
    else if(name.match(/type/)){ return 1; }
    else if(name.match(/LastUpDate/)){ return 1; }
    else if(name.match(/date_end/)){ return 1; }
    else if(name.match(/date_start/)){ return 1; }
    else if(name.match(/duree/)){ return 1; }
    else if(name.match(/txt_full/)){ return 1; }
    else { return 0; }
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    
    var previsyText = init(_cmd.name);
    
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
    tr += '</td>';
    tr += '<td>';
    if(!isset(_cmd.type) || _cmd.type == 'info' && !previsyText.match(/Ville/) && !previsyText.match(/widget/)){
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id) && !previsyText.match(/widget/)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i>{{Tester}}</a>';
    }
    if (_cmd.type != 'action' && previsyInArray(previsyText) == 0) {
        tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    }
    tr += '</td>';
    tr += '</tr>';
    
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
