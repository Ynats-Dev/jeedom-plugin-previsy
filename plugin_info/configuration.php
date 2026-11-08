<?php
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<div style="width: 100%; margin: 15px 0; display:none;" id="div_alert_previsy" class="jqAlert alert-warning">
    <span href="#" class="btn_closeAlert pull-right cursor" style="position : relative;top:-2px; left : 30px;color : grey">×</span>
    <span class="displayError"></span>
</div>

<form class="form-horizontal">
    
        <div class="form-group">
            <div class="col-lg-2" style="right:15px; position: absolute;">
                <select onchange="previsy_mode_plugin()" class="configKey form-control" data-l1key="mode_plugin" id="previsy_mode">
                    <option value="normal">{{Mode normal}}</option>
                    <option value="advanced">{{Mode avancé}}</option>
                    <option value="debug">{{Mode debug}}</option>
                </select>
            </div>
        </div>
    
    <fieldset>
        <br />
        <div class="form-group">
            <label class="col-lg-4 control-label">{{API météo}}
                <sup><i class="fas fa-question-circle tooltips" title="{{L'API utilisée par tous les équipements. Open-Meteo (recommandé) couvre le monde entier ; prevision-meteo.ch couvre la France, la Belgique et la Suisse. « Choix par équipement » laisse chaque équipement choisir son API.}}"></i></sup>
            </label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="api_mode">
                    <?php
                    require_once __DIR__ . '/../core/class/bridges/previsy.bridge.php';
                    foreach (previsy_bridge::getList() as $apiId => $apiClass) {
                        echo '<option value="' . $apiId . '">' . $apiClass::getLabel() . '</option>';
                    }
                    ?>
                    <option value="equipement">{{Choix par équipement}}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Repli automatique sur l'autre API}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Si l'API choisie ne répond pas (panne, saturation, quota), Previsy utilise l'autre API. Un warning est écrit dans les logs et un message est ajouté au centre de messages. L'API choisie est retentée à chaque mise à jour.}}"></i></sup>
            </label>
            <div class="col-lg-2">
                <input type="checkbox" class="configKey" data-l1key="api_repli" />
            </div>
        </div>
        <br />
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nombre d'alerte en prévision à afficher}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" id="previsy_select" onchange="previsy_cpt()" data-l1key="nb_alerte">
                    <option value="1">1 {{alerte}}</option>
                    <option value="2">2 {{alertes}}</option>
                    <option value="3">3 {{alertes}}</option>
                    <option value="4">4 {{alertes}}</option>
                    <option value="5">5 {{alertes}}</option>
                </select>
            </div>
        </div>
        <br />
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Température}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="type_degre">
                    <option value="°C">{{Degrés Celsius (°C)}}</option>
                    <option value="°F">{{Degrés Fahrenheit (°F)}}</option>
                </select>
            </div>
        </div>
       
<?php 

    function addTrConfig($_datal1key, $_name) {
        echo '<tr style="background-color:transparent!important;">
            <td style="padding:2px;"><input type="checkbox" onclick="previsy_cpt()" class="configKey form-control previsy_checkbox" style="top:0" data-l1key="' . $_datal1key . '" /></td>
            <td style="padding:2px;">' . $_name . '</td>
            </tr>';
    }
?>
        <div class="form-group" id="show_commandes_plus" style="display:none;">
            <br />
            <label class="col-lg-4 control-label">{{Commandes à afficher (en option pour vos scénarios)}}</label>
            <div class="col-lg-5">
                 <table>
                    <?php
                        addTrConfig("show_mm_min", __("Commandes mm_min : Pluviométrie minimale",  __FILE__));
                        addTrConfig("show_mm_max", __("Commandes mm_max : Pluviométrie maximale",  __FILE__));
                        addTrConfig("show_mm_moyenne", __("Commandes mm_moyenne : Pluviométrie moyenne",  __FILE__));
                        addTrConfig("show_mm_total", __("Commandes mm_total : Pluviométrie totale",  __FILE__));
                        addTrConfig("show_temp_min", __("Commandes temp_min : Température minimale",  __FILE__));
                        addTrConfig("show_temp_max", __("Commandes temp_max : Température maximale",  __FILE__));
                        addTrConfig("show_temp_moyenne", __("Commandes temp_moyenne : Température moyenne",  __FILE__));
                        addTrConfig("show_humidite_min", __("Commandes humidite_min : Humidité moinimale",  __FILE__));
                        addTrConfig("show_humidite_max", __("Commandes humidite_max : Humidité maximale",  __FILE__));
                        addTrConfig("show_humidite_moyenne", __("Commandes humidite_moyenne : Humidité moyenne",  __FILE__));  
                        addTrConfig("show_vent_min", __("Commandes vent_min : Vent minimal",  __FILE__));  
                        addTrConfig("show_vent_max", __("Commandes vent_max : Vent maximal",  __FILE__));  
                        addTrConfig("show_vent_moyenne", __("Commandes vent_moyenne : Moyenne du vent",  __FILE__));  
                        addTrConfig("show_vent_nom", __("Commandes vent_nom : Vent nominal",  __FILE__));  
                        addTrConfig("show_rafale_min", __("Commandes rafale_min : Rafale minimum",  __FILE__));  
                        addTrConfig("show_rafale_max", __("Commandes rafale_max : Rafale maximale",  __FILE__));  
                        addTrConfig("show_rafale_moyenne", __("Commandes rafale_moyenne : Rafale moyenne",  __FILE__));  
                        addTrConfig("show_txt_start", __("Commandes txt_start : Début texte prédictif",  __FILE__));  
                        addTrConfig("show_txt_mm", __("Commandes txt_mm : Texte prédictif pluviométrie",  __FILE__));  
                        addTrConfig("show_txt_humidite", __("Commandes txt_humidite : Texte prédictif humidité",  __FILE__));
                        addTrConfig("show_txt_temperature", __("Commandes txt_temperature : Texte prédictif température",  __FILE__));
                        addTrConfig("show_txt_vent", __("Commandes txt_vent : Texte prédictif vent",  __FILE__));
                        addTrConfig("show_condition_max", __("Commandes condition_max : Condition maximale",  __FILE__));
                    ?>
                </table>
            </div>
        </div>
    <br />
  </fieldset>
    
</form>

<?php include_file('desktop', 'previsy_configuration', 'js', 'previsy'); ?>
