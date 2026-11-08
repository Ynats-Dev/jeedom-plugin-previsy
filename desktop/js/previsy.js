
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */

// Sur la partie Debug
$('#bt_previsyDebug').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Debug}}"});
    $('#md_modal').load('index.php?v=d&plugin=previsy&modal=debug').dialog('open');
});

/*
 * ---------------------------------------------------------------------------------------------
 * Alertes personnalisées (onglet "Mes alertes")
 * Les règles sont stockées dans eqLogic.configuration.alertesPerso (tableau) via les points
 * d'entrée printEqLogic / saveEqLogic appelés par plugin.template.
 * ---------------------------------------------------------------------------------------------
 */

var PREVISY_DIRECTIONS = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];
var PREVISY_BORNES = [
    {min: 'ventMin', max: 'ventMax', label: '{{Vent moyen}}', unite: 'km/h'},
    {min: 'rafaleMin', max: 'rafaleMax', label: '{{Rafales}}', unite: 'km/h'},
    {min: 'tempMin', max: 'tempMax', label: '{{Température}}', unite: '°C'},
    {min: 'ressentieMin', max: 'ressentieMax', label: '{{Température ressentie}}', unite: '°C'},
    {min: 'humiditeMin', max: 'humiditeMax', label: '{{Humidité}}', unite: '%'},
    {min: 'pressionMin', max: 'pressionMax', label: '{{Pression}}', unite: 'hPa'},
    {min: 'heureMin', max: 'heureMax', label: '{{Plage horaire}}', unite: 'h (0-23)'}
];

function previsyModifie() {
    if (typeof jeeFrontEnd !== 'undefined') {
        jeeFrontEnd.modifyWithoutSave = true;
    } else {
        modifyWithoutSave = true;
    }
}

function previsyPrecipitationSelect(_key, _label) {
    return '<div class="form-group">'
            + '<label class="col-sm-3 control-label">' + _label + '</label>'
            + '<div class="col-sm-3"><select class="form-control input-sm previsyRegleAttr" data-key="' + _key + '">'
            + '<option value="">{{Indifférent}}</option>'
            + '<option value="oui">{{Oui}}</option>'
            + '<option value="non">{{Non}}</option>'
            + '</select></div></div>';
}

function previsyAddRegle(_regle) {
    var regle = _regle || {};
    if (!isset(regle.id) || regle.id === '') {
        var max = 0;
        $('#div_previsyRegles .previsyRegle').each(function () {
            max = Math.max(max, parseInt($(this).attr('data-id')) || 0);
        });
        regle.id = max + 1;
        regle.actif = 1;
        regle.widget = 1;
        regle.dureeMin = 1;
    }

    var html = '<div class="panel panel-default previsyRegle" data-id="' + parseInt(regle.id) + '">';
    html += '<div class="panel-heading">';
    html += '<i class="fas fa-bell"></i> <input class="form-control input-sm previsyRegleAttr" data-key="nom" style="display:inline-block; width:300px;" placeholder="{{Nom de l\'alerte (ex. Voile)}}"/>';
    html += ' <label class="checkbox-inline"><input type="checkbox" class="previsyRegleAttr" data-key="actif"/>{{Active}}</label>';
    html += ' <label class="checkbox-inline"><input type="checkbox" class="previsyRegleAttr" data-key="widget"/>{{Afficher dans le widget}}</label>';
    html += '<a class="btn btn-danger btn-xs pull-right bt_previsyRemoveRegle"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>';
    html += '</div>';
    html += '<div class="panel-body"><form class="form-horizontal">';

    html += previsyPrecipitationSelect('pluie', '{{Pluie}}');
    html += previsyPrecipitationSelect('neige', '{{Neige}}');

    html += '<div class="form-group"><label class="col-sm-3 control-label">{{Direction du vent (d\'où il vient)}}</label><div class="col-sm-9">';
    for (var i in PREVISY_DIRECTIONS) {
        html += '<label class="checkbox-inline"><input type="checkbox" class="previsyRegleDirection" value="' + PREVISY_DIRECTIONS[i] + '"/>' + PREVISY_DIRECTIONS[i] + '</label>';
    }
    html += ' <span style="opacity:0.6;">({{aucune case = toutes directions}})</span></div></div>';

    for (var b in PREVISY_BORNES) {
        var borne = PREVISY_BORNES[b];
        html += '<div class="form-group"><label class="col-sm-3 control-label">' + borne.label + '</label>';
        html += '<div class="col-sm-2"><input class="form-control input-sm previsyRegleAttr" data-key="' + borne.min + '" placeholder="{{min}}"/></div>';
        html += '<div class="col-sm-2"><input class="form-control input-sm previsyRegleAttr" data-key="' + borne.max + '" placeholder="{{max}}"/></div>';
        html += '<div class="col-sm-2" style="padding-top:5px;">' + borne.unite + '</div></div>';
    }

    html += '<div class="form-group"><label class="col-sm-3 control-label">{{Durée minimale}}</label>';
    html += '<div class="col-sm-2"><input class="form-control input-sm previsyRegleAttr" data-key="dureeMin" placeholder="1"/></div>';
    html += '<div class="col-sm-4" style="padding-top:5px;">{{heure(s) consécutive(s)}}</div></div>';

    html += '<div class="form-group"><label class="col-sm-3 control-label">{{Message}}</label>';
    html += '<div class="col-sm-6"><input class="form-control input-sm previsyRegleAttr" data-key="message" placeholder="{{Texte ajouté à la commande Texte (optionnel)}}"/></div></div>';

    html += '</form></div></div>';

    var card = $(html);
    // Valeurs affectées après coup : pas d'injection HTML possible via les saisies
    card.find('.previsyRegleAttr').each(function () {
        var key = $(this).attr('data-key');
        var valeur = isset(regle[key]) ? regle[key] : '';
        if ($(this).attr('type') === 'checkbox') {
            $(this).prop('checked', valeur == 1);
        } else {
            $(this).val(valeur);
        }
    });
    if (Array.isArray(regle.directions)) {
        card.find('.previsyRegleDirection').each(function () {
            $(this).prop('checked', regle.directions.indexOf($(this).val()) !== -1);
        });
    }
    $('#div_previsyRegles').append(card);
}

function printEqLogic(_eqLogic) {
    setTimeout(function () {
        // Équipement qui n'a jamais enregistré ce réglage : on affiche la valeur utilisée par défaut (jaune)
        var niveau = $('.eqLogicAttr[data-l1key=configuration][data-l2key=vigilanceNiveauMin]');
        if (!niveau.val()) {
            niveau.val('2');
        }
        previsyChargerZones(); // après que Jeedom a rempli le champ Pays
    }, 100);
    $('#div_previsyRegles').empty();
    var regles = (_eqLogic && _eqLogic.configuration) ? _eqLogic.configuration.alertesPerso : null;
    if (typeof regles === 'string') {
        try { regles = JSON.parse(regles); } catch (e) { regles = null; }
    }
    if (Array.isArray(regles)) {
        for (var i in regles) {
            previsyAddRegle(regles[i]);
        }
    }
}

function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    var regles = [];
    $('#div_previsyRegles .previsyRegle').each(function () {
        var regle = {id: parseInt($(this).attr('data-id')), directions: []};
        $(this).find('.previsyRegleAttr').each(function () {
            regle[$(this).attr('data-key')] = ($(this).attr('type') === 'checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();
        });
        $(this).find('.previsyRegleDirection:checked').each(function () {
            regle.directions.push($(this).val());
        });
        regles.push(regle);
    });
    _eqLogic.configuration.alertesPerso = regles;
    return _eqLogic;
}

$('#bt_previsyAddRegle').off('click').on('click', function () {
    previsyAddRegle();
    previsyModifie();
});

$('#div_previsyRegles').off('click', '.bt_previsyRemoveRegle').on('click', '.bt_previsyRemoveRegle', function () {
    $(this).closest('.previsyRegle').remove();
    previsyModifie();
});

$('#div_previsyRegles').off('change', 'input, select').on('change', 'input, select', function () {
    previsyModifie();
});

/*
 * ---------------------------------------------------------------------------------------------
 * Vigilance : suggestions de zones pour le pays choisi (zones présentes dans le flux MeteoAlarm)
 * ---------------------------------------------------------------------------------------------
 */
function previsyChargerZones() {
    var pays = $('#previsyVigilancePays').val();
    var datalist = $('#previsyVigilanceZones');
    var info = $('#previsyVigilanceZonesInfo');
    datalist.empty();
    info.text('');
    if (!pays) {
        return;
    }
    $.ajax({
        type: 'POST',
        url: 'plugins/previsy/core/ajax/previsy.ajax.php',
        data: {action: 'getVigilanceZones', pays: pays},
        dataType: 'json',
        global: false,
        success: function (data) {
            if (data.state != 'ok' || !Array.isArray(data.result)) {
                return;
            }
            for (var i in data.result) {
                datalist.append($('<option>').attr('value', data.result[i]));
            }
            if (data.result.length == 0) {
                info.text('{{Aucune zone dans le flux actuel : saisissez le nom de la région tel qu\'il apparaît sur meteoalarm.org.}}');
            } else {
                info.text(data.result.length + ' {{zone(s) suggérée(s) (zones ayant eu une vigilance récemment).}}');
            }
        }
    });
}

$('#previsyVigilancePays').off('change.previsy').on('change.previsy', previsyChargerZones);
