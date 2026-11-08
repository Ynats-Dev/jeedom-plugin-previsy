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

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# Documentation : https://www.prevision-meteo.ch/uploads/pdf/recuperation-donnees-meteo.pdf
# API : https://www.prevision-meteo.ch/services/json/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* * ***************************Includes********************************* */

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/bridges/previsy.bridge.php';
require_once __DIR__ . '/previsy.alertes.php';
require_once __DIR__ . '/previsy.vigilance.php';

class previsy extends eqLogic {
    /*     * *************************Attributs****************************** */

    public static $_widgetPossibility = array('custom' => true);
    public static $_jsonTampon = __DIR__ . "/../../../../plugins/previsy/data/json/"; // Dossier des Json en Tampon
    public static $_urlApi = "https://www.prevision-meteo.ch/services/json/"; // Conservé pour compatibilité : voir core/class/bridges/
    public static $_icons = "plugins/previsy/desktop/icons/"; // Dossier des iconespermettant de construire des phrases en différentes langues
    public static $_prevHeure = 72; // Heure max de récupération des données dans le Json
    public static $_maxAlerte = 5; // Nombre max d'alerte ou widget à afficher
    public static $_timeSynchro = 3600 * 2; // Synchro avec le site distant tous les X secondes
    public static $_timeRefreshForce = 300; // Rafraichir manuel : rechargement depuis l'API au plus toutes les X secondes

    /*     * **************************Configuration************************* */

    public static function getConfigFormatDegres() {
        return config::byKey('type_degre', 'previsy', "°C");
    }

    public static function getConfigNbAlerte() {
        return config::byKey('nb_alerte', 'previsy', 0);
    }
    
    public static function getConfigMode() {
        return config::byKey('mode_plugin', 'previsy', 'normal');
    }

    public static function getConfigShowCommandes() {
        $return["show_mm_min"] = config::byKey('show_mm_min', 'previsy', '0');
        $return["show_mm_max"] = config::byKey('show_mm_max', 'previsy', '0');
        $return["show_mm_moyenne"] = config::byKey('show_mm_moyenne', 'previsy', '0');
        $return["show_mm_total"] = config::byKey('show_mm_total', 'previsy', '0');
        $return["show_temp_min"] = config::byKey('show_temp_min', 'previsy', '0');
        $return["show_temp_max"] = config::byKey('show_temp_max', 'previsy', '0');
        $return["show_temp_moyenne"] = config::byKey('show_temp_moyenne', 'previsy', '0');
        $return["show_humidite_min"] = config::byKey('show_humidite_min', 'previsy', '0');
        $return["show_humidite_max"] = config::byKey('show_humidite_max', 'previsy', '0');
        $return["show_humidite_moyenne"] = config::byKey('show_humidite_moyenne', 'previsy', '0');
        $return["show_vent_min"] = config::byKey('show_vent_min', 'previsy', '0');
        $return["show_vent_max"] = config::byKey('show_vent_max', 'previsy', '0');
        $return["show_vent_moyenne"] = config::byKey('show_vent_moyenne', 'previsy', '0');
        $return["show_vent_nom"] = config::byKey('show_vent_nom', 'previsy', '0');
        $return["show_rafale_min"] = config::byKey('show_rafale_min', 'previsy', '0');
        $return["show_rafale_max"] = config::byKey('show_rafale_max', 'previsy', '0');
        $return["show_rafale_moyenne"] = config::byKey('show_rafale_moyenne', 'previsy', '0');
        $return["show_txt_start"] = config::byKey('show_txt_start', 'previsy', '0');
        $return["show_txt_mm"] = config::byKey('show_txt_mm', 'previsy', '0');
        $return["show_txt_humidite"] = config::byKey('show_txt_humidite', 'previsy', '0');
        $return["show_txt_temperature"] = config::byKey('show_txt_temperature', 'previsy', '0');
        $return["show_txt_vent"] = config::byKey('show_txt_vent', 'previsy', '0');
        $return["show_condition_max"] = config::byKey('show_condition_max', 'previsy', '0');
        return $return;
    }

    /*     * ***********************Methode static*************************** */

    public function UpdateDatas($_previsy) {
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('UpdateDatas :. ', __FILE__) . __('Lancement ', __FILE__) . '#ID# ' . $_previsy->getId());

        $_previsy->clearDatas($_previsy);

        $info = $_previsy->get($_previsy->getId());

        if (!is_array($info)) {
            log::add('previsy', 'debug', __('UpdateDatas :. ', __FILE__) . __('Aucune donnée (équipement désactivé ?) #ID# ', __FILE__) . $_previsy->getId());
            return;
        }

        log::add('previsy', 'debug', __('UpdateDatas :. ', __FILE__) . __('Lancement des mises à jour des données de #ID# ', __FILE__) . $_previsy->getId());

        $_previsy->checkAndUpdateCmd('ville', $info["GLOBAL"]["VILLE"]);
        $_previsy->checkAndUpdateCmd('latitude', $info["GLOBAL"]["LATITUDE"]);
        $_previsy->checkAndUpdateCmd('longitude', $info["GLOBAL"]["LONGITUDE"]);
        $_previsy->checkAndUpdateCmd('last_update', $info["GLOBAL"]["LAST_SYNCHRO"]);
        $_previsy->checkAndUpdateCmd('type_degre', self::getConfigFormatDegres());

        $showCommande = self::getConfigShowCommandes();

        $cpt = 0;

        if(!empty($info["ALERTES"]["GROUP"])){
            foreach ($info["ALERTES"]["GROUP"] as $value_alerte) {
            //$idCmd = array();
            $cpt++;

            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_dans_heure', $value_alerte["DANS_HEURE"]);
            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_type', $value_alerte["TYPE"]);
            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_date_start', $value_alerte["START"]);
            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_date_end', $value_alerte["END"]);

            if ($showCommande["show_condition_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_condition_max', $value_alerte["CONDITION_MAX"]);
            }
            if ($showCommande["show_mm_min"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_mm_min', $value_alerte["MM"]["MIN"]);
            }
            if ($showCommande["show_mm_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_mm_max', $value_alerte["MM"]["MAX"]);
            }
            if ($showCommande["show_mm_moyenne"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_mm_moyenne', $value_alerte["MM"]["MOY"]);
            }
            if ($showCommande["show_mm_total"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_mm_total', $value_alerte["MM"]["TOTAL"]);
            }
            if ($showCommande["show_temp_min"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_temp_min', $value_alerte["TEMPERATURE"]["MIN"]);
            }
            if ($showCommande["show_temp_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_temp_max', $value_alerte["TEMPERATURE"]["MAX"]);
            }
            if ($showCommande["show_temp_moyenne"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_temp_moyenne', $value_alerte["TEMPERATURE"]["MOY"]);
            }
            if ($showCommande["show_humidite_min"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_humidite_min', $value_alerte["HUMIDITE"]["MIN"]);
            }
            if ($showCommande["show_humidite_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_humidite_max', $value_alerte["HUMIDITE"]["MAX"]);
            }
            if ($showCommande["show_humidite_moyenne"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_humidite_moyenne', $value_alerte["HUMIDITE"]["MOY"]);
            }
            if ($showCommande["show_vent_min"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_vent_min', $value_alerte["VENT_VITESSE"]["MIN"]);
            }
            if ($showCommande["show_vent_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_vent_max', $value_alerte["VENT_VITESSE"]["MAX"]);
            }
            if ($showCommande["show_vent_moyenne"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_vent_moyenne', $value_alerte["VENT_VITESSE"]["MOY"]);
            }
            if ($showCommande["show_vent_nom"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_vent_nom', $value_alerte["VENT_NOM"]);
            }
            if ($showCommande["show_rafale_min"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_rafale_min', $value_alerte["VENT_RAFALES"]["MIN"]);
            }
            if ($showCommande["show_rafale_max"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_rafale_max', $value_alerte["VENT_RAFALES"]["MAX"]);
            }
            if ($showCommande["show_rafale_moyenne"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_rafale_moyenne', $value_alerte["VENT_RAFALES"]["MOY"]);
            }

            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_duree', $value_alerte["DUREE_HEURE"]);
            $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_full', $value_alerte["TXT"]["FULL"]);

            if ($showCommande["show_txt_start"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_start', $value_alerte["TXT"]["START"]);
            }
            if ($showCommande["show_txt_mm"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_mm', $value_alerte["TXT"]["MM"]);
            }
            if ($showCommande["show_txt_temperature"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_temperature', $value_alerte["TXT"]["TEMPERATURE"]);
            }
            if ($showCommande["show_txt_humidite"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_humidite', $value_alerte["TXT"]["HUMIDITE"]);
            }
            if ($showCommande["show_txt_vent"] == 1) {
                $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_txt_vent', $value_alerte["TXT"]["VENT"]);
            }
            
        $idCmd = $_previsy->getIdEtNameCmd(self::printZeroDizaine($cpt), array('widget', 'txt_full'));
        $_previsy->checkAndUpdateCmd('alerte_' . self::printZeroDizaine($cpt) . '_widget', self::getWidget($value_alerte, $idCmd));   
            
        }
        }
            
        if (!empty($info["ERROR"])) {
            $_previsy->checkAndUpdateCmd('alerte_01_widget', self::getWidgetError($info["GLOBAL"]["VILLE"] ?? '', previsy_bridge::getLabel(previsy_bridge::getApi($_previsy))));
        } elseif ($cpt == 0) {
            $_previsy->checkAndUpdateCmd('alerte_01_widget', self::getWidgetNull());
        }

        // Alertes personnalisées et vigilance nationale (indépendantes des alertes standards)
        $json = self::getJsonTampon($_previsy->getId());
        $json = previsy_bridge::hasError($json["datas"]) ? NULL : $json["datas"];

        // API ayant réellement fourni les données (principale ou repli)
        $apiPrincipale = previsy_bridge::getApi($_previsy);
        $apiDonnees = (is_object($json) AND isset($json->previsy->api)) ? $json->previsy->api : $apiPrincipale;
        $repli = (is_object($json) AND !empty($json->previsy->repli));
        $_previsy->checkAndUpdateCmd('api_utilisee', previsy_bridge::getLabel($apiDonnees) . ($repli ? ' (' . __('repli', __FILE__) . ')' : ''));
        $_previsy->setCache('widget_source', $repli ? "<div style='font-size:0.8em; opacity:0.8; margin:0 5px 5px 5px;'><i class='fas fa-exclamation-circle'></i> "
                . previsy_bridge::getLabel($apiPrincipale) . ' ' . __('indisponible : données', __FILE__) . ' ' . previsy_bridge::getLabel($apiDonnees) . "</div>" : '');
        $perso = previsy_alertes::update($_previsy, $json);
        $vigilance = previsy_vigilance::update($_previsy, $json);
        $_previsy->setCache('widget_perso', previsy_alertes::getWidget($perso));
        $_previsy->setCache('widget_vigilance', previsy_vigilance::getWidget($vigilance));

        $_previsy->refreshWidget();

        log::add('previsy', 'debug', __('UpdateDatas :. ', __FILE__) . __('Fin des mises à jour des données de #ID# ', __FILE__) . $_previsy->getId());
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
    }

    /*     * *********************Méthodes d'instance************************* */

    public static function cronHourly() {
        $eqLogics = eqLogic::byType('previsy');
        foreach ($eqLogics as $previsy) {
            if ($previsy->getIsEnable() == 1) {
                $previsy->updateJsonDatas($previsy->getId());
                log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
                log::add('previsy', 'debug', __('cronHourly :. ', __FILE__) . __('Lancement pour #ID# ', __FILE__) . $previsy->getId());
            }
        }
    }

    /*     * *********************Méthodes d'instance************************* */

    public static function createCmd($_array = NULL, $_this = NULL) {
        $info = $_this->getCmd(null, $_array["LogicalId"]);
        if (!is_object($info)) {
            $info = new previsyCmd();
            $info->setName(__($_array["Name"], __FILE__));
        }
        $info->setLogicalId($_array["LogicalId"]);
        $info->setEqLogic_id($_this->getId());
        $info->setIsHistorized($_array["Historized"]);
        $info->setIsVisible($_array["Visible"]);
        $info->setType($_array["Type"]);
        $info->setSubType($_array["SubType"]);
        if (!empty($_array["Unite"])) {
            $info->setUnite($_array["Unite"]);
        }
        $info->save();
    }
    
    public static function removeCmd($_LogicalId, $_this = NULL){
        $info = $_this->getCmd(null, $_LogicalId);
        if (is_object($info)) {
            $info->remove();
        }
    }

    public static function printZeroDizaine($_int) {
        if ($_int < 10) {
            return "0" . $_int;
        } else {
            return $_int;
        }
    }

    public function postSave() { 
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('postSave :. ', __FILE__) . __('Début de la création ou Mise à jour des commandes #ID# ', __FILE__) . $this->getId());
        
        $nb_alerte = self::getConfigNbAlerte();

        if ($nb_alerte == 0) { // Si pas d'enregistrement de config on enregistre des valeurs
            log::add('previsy', 'debug', __('postSave :. ', __FILE__) . __('Config Manquante. Enregistrement des valeurs à defaut.', __FILE__));
            config::save('type_degre', '°C', 'previsy');
            config::save('nb_alerte', 1, 'previsy');

            $nb_alerte = 1;
        }

        $showCommande = self::getConfigShowCommandes();

        self::createCmd(["LogicalId" => "last_update", "Name" => "SynchroLastUpDate", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
        self::createCmd(["LogicalId" => "ville", "Name" => "SynchroVille", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
        self::createCmd(["LogicalId" => "latitude", "Name" => "Latitude", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
        self::createCmd(["LogicalId" => "longitude", "Name" => "Longitude", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
        self::createCmd(["LogicalId" => "type_degre", "Name" => "Type_degre", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
        self::createCmd(["LogicalId" => "api_utilisee", "Name" => "API utilisée", "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);

        for ($i = 1; $i <= $nb_alerte; $i++) {

            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_widget', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_widget', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_type', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_type', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_date_start', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_date_start', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_date_end', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_date_end', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);

            if ($showCommande["show_condition_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_condition_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_condition_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            } else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_condition_max', $this);
            }

            if ($showCommande["show_mm_moyenne"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_mm_moyenne', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_mm_moyenne', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric", "Unite" => "MM"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_mm_moyenne', $this);
            }
            
            if ($showCommande["show_mm_min"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_mm_min', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_mm_min', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric", "Unite" => "MM"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_mm_min', $this);
            }
            
            if ($showCommande["show_mm_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_mm_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_mm_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric", "Unite" => "MM"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_mm_max', $this);
            }
            
            if ($showCommande["show_mm_total"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_mm_total', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_mm_total', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric", "Unite" => "MM"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_mm_total', $this);
            }

            if ($showCommande["show_temp_moyenne"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_temp_moyenne', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_temp_moyenne', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_temp_moyenne', $this);
            }
            
            if ($showCommande["show_temp_min"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_temp_min', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_temp_min', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_temp_min', $this);
            }
            
            if ($showCommande["show_temp_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_temp_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_temp_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_temp_max', $this);
            }

            if ($showCommande["show_humidite_moyenne"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_humidite_moyenne', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_humidite_moyenne', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_humidite_moyenne', $this);
            }
            
            if ($showCommande["show_humidite_min"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_humidite_min', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_humidite_min', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_humidite_min', $this);
            }
            
            if ($showCommande["show_humidite_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_humidite_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_humidite_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_humidite_max', $this);
            }

            if ($showCommande["show_vent_moyenne"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_vent_moyenne', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_vent_moyenne', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_vent_moyenne', $this);
            }
            
            if ($showCommande["show_vent_min"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_vent_min', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_vent_min', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_vent_min', $this);
            }
            
            if ($showCommande["show_vent_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_vent_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_vent_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_vent_max', $this);
            }

            if ($showCommande["show_rafale_moyenne"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_rafale_moyenne', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_rafale_moyenne', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_rafale_moyenne', $this);
            }
            
            if ($showCommande["show_rafale_min"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_rafale_min', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_rafale_min', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_rafale_min', $this);
            }
            
            if ($showCommande["show_rafale_max"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_rafale_max', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_rafale_max', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_rafale_max', $this);
            }

            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_duree', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_duree', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "numeric", "Unite" => "H"], $this);

            if ($showCommande["show_txt_start"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_start', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_start', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_txt_start', $this);
            }

            self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_full', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_full', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);

            if ($showCommande["show_txt_mm"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_mm', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_mm', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_txt_mm', $this);
            }

            if ($showCommande["show_txt_temperature"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_temperature', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_temperature', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_txt_temperature', $this);
            }

            if ($showCommande["show_txt_humidite"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_humidite', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_humidite', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_txt_humidite', $this);
            }

            if ($showCommande["show_txt_vent"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_txt_vent', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_txt_vent', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_txt_vent', $this);
            }

            if ($showCommande["show_vent_nom"] == 1) {
                self::createCmd(["LogicalId" => 'alerte_' . self::printZeroDizaine($i) . '_vent_nom', "Name" => 'Alerte+' . self::printZeroDizaine($i) . '_vent_nom', "Historized" => 0, "Visible" => 0, "Type" => "info", "SubType" => "string"], $this);
            }else {
                self::removeCmd('alerte_' . self::printZeroDizaine($i) . '_vent_nom', $this);
            }
        }

        self::createCmd(["LogicalId" => "refresh", "Name" => "Rafraichir", "Historized" => 0, "Visible" => 1, "Type" => "action", "SubType" => "other"], $this);

        previsy_alertes::createCmds($this);
        previsy_vigilance::createCmds($this);

        log::add('previsy', 'debug', __('postSave :. ', __FILE__) . __('Fin de la création ou Mise à jour des commandes #ID# ', __FILE__) . $this->getId());

        $eqLogic = self::byId($this->getId());
        if ($eqLogic->getIsEnable() == 1 AND ($eqLogic->getConfiguration("ville") != "" OR $eqLogic->getConfiguration("latitude") != "")) {
            log::add('previsy', 'debug', __('postSave :. ', __FILE__) . __('miseEnCacheJson : ', __FILE__) . $eqLogic->getConfiguration("ville"));
            self::updateJsonDatas($this->getId());
        }

        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
    }

    public function postAjax() {
        previsy_alertes::removeObsoleteCmds($this);
        previsy_vigilance::removeObsoleteCmds($this);
    }

    public function preRemove() {
        @unlink(self::$_jsonTampon . 'vigilance_zone_' . $this->getId() . '.json');
        $tempJson = self::$_jsonTampon . $this->getId() . ".json";
        if (is_file($tempJson)) {
            log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
            log::add('previsy', 'debug', __('preRemove :. ', __FILE__) . __('Suppression du fichier Json : ', __FILE__) . $tempJson);
            unlink($tempJson);
        }
    }

    public function toHtml($_version = 'dashboard') {

        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('toHtml :. ', __FILE__) . __('Lancement', __FILE__));

        $replace = $this->preToHtml($_version); //récupère les informations de notre équipement

        if (!is_array($replace)) {
            return $replace;
        }

        $showCommande = self::getConfigShowCommandes();

        $this->emptyCacheWidget(); //vide le cache. Pratique pour le développement
        $version = jeedom::versionAlias($_version);

        $last_date = $this->getCmd(null, 'ville');
        $replace['#ville#'] = (is_object($last_date)) ? $last_date->execCmd() : '';

        $last_date = $this->getCmd(null, 'latitude');
        $replace['#latitude#'] = (is_object($last_date)) ? $last_date->execCmd() : '';

        $last_date = $this->getCmd(null, 'longitude');
        $replace['#longitude#'] = (is_object($last_date)) ? $last_date->execCmd() : '';

        $last_date = $this->getCmd(null, 'last_update');
        $replace['#last_date#'] = (is_object($last_date)) ? $last_date->execCmd() : '';

        $last_date = $this->getCmd(null, 'type_degre');
        $replace['#type_degre#'] = (is_object($last_date)) ? $last_date->execCmd() : '';

        for ($i = 1; $i <= self::$_maxAlerte; $i++) {
            $tmp_cmd = "alerte_0" . $i . "_dans_heure";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            $tmp_cmd = "alerte_0" . $i . "_date_start";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            $tmp_cmd = "alerte_0" . $i . "_date_end";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            $tmp_cmd = "alerte_0" . $i . "_type";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            $tmp_cmd = "alerte_0" . $i . "_duree";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            if ($showCommande["show_mm_min"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_mm_min";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_mm_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_mm_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_mm_moyenne"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_mm_moyenne";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_mm_total"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_mm_total";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_temp_min"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_temp_min";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_temp_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_temp_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_temp_moyenne"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_temp_moyenne";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_humidite_min"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_humidite_min";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_humidite_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_humidite_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_humidite_moyenne"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_humidite_moyenne";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_vent_min"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_vent_min";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_vent_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_vent_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_vent_moyenne"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_vent_moyenne";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_rafale_min"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_rafale_min";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_rafale_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_rafale_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            if ($showCommande["show_rafale_moyenne"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_rafale_moyenne";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? number_format(floatval(${$tmp_cmd}->execCmd()), 1) : '';
            }

            $tmp_cmd = "alerte_0" . $i . "_txt_full";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';

            if ($showCommande["show_txt_start"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_txt_start";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_txt_mm"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_txt_mm";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_txt_temperature"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_txt_temperature";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_txt_humidite"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_txt_humidite";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_txt_vent"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_txt_vent";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_vent_nom"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_vent_nom";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            if ($showCommande["show_condition_max"] == 1) {
                $tmp_cmd = "alerte_0" . $i . "_condition_max";
                ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
                $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
            }

            $tmp_cmd = "alerte_0" . $i . "_widget";
            ${$tmp_cmd} = $this->getCmd(null, $tmp_cmd);
            $replace["#" . $tmp_cmd . "#"] = (is_object(${$tmp_cmd})) ? ${$tmp_cmd}->execCmd() : '';
        }

        log::add('previsy', 'debug', __('toHtml :. ', __FILE__) . __('fin', __FILE__));
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');

        $replace['#previsy_source#'] = $this->getCache('widget_source', '');
        $replace['#previsy_vigilance#'] = $this->getCache('widget_vigilance', '');
        $replace['#previsy_perso#'] = $this->getCache('widget_perso', '');

        return template_replace($replace, getTemplate('core', $version, 'previsy', 'previsy'));
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# START -> PREVISY
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// 

    public function get($_id) {
        log::add('previsy', 'debug', '#######################################################################################');
        log::add('previsy', 'debug', __('get :. ', __FILE__) . '#ID# ' . $_id . " " . __('Lancement', __FILE__));

        $eqLogic = self::byId($_id);

        log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Chargement du module de construction des phrases', __FILE__));
        require_once 'previsy.class.phrases.php';

        $now = $tmp_now = $al_last = array();

        if ($eqLogic->getIsEnable() == 1) {

            // Données de configuration widget
            $now["GLOBAL"]["TYPE_DEGRE"] = self::getConfigFormatDegres();

            // Récuprération des données JSON
            log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Chargement des données du Json en Tampon #ID# ', __FILE__) . $_id);

            $getJson = self::getJsonTampon($eqLogic->getId());
            $json = $getJson["datas"];
            
            $now["GLOBAL"]["LAST_SYNCHRO"] = $getJson["datetime"];

            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('LAST_SYNCHRO : [', __FILE__) . date("d/m/Y H:i", $now["GLOBAL"]["LAST_SYNCHRO"]) . ']');
            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('DATE : [', __FILE__) . date("d/m/Y H:i") . ']');

            list($now["GLOBAL"]["VILLE"], $now["GLOBAL"]["LATITUDE"], $now["GLOBAL"]["LONGITUDE"]) = self::getLocalistion($json, $eqLogic);

            if (!previsy_bridge::hasError($json)) {

                $date = new DateTime("Now");
                $date_plus_un = new DateTime("Now");
                $date_plus_un->add(new DateInterval('PT1H'));
                $lang = new previsy_language;

                $tmp_day = $lang->getDate($date);

                $dur = 0;
                $alertes = 0;

                /* if ($date->format('i') >= 50) { // A partir de 50 on annonce le temps de l'heure suivante
                  $date->add(new DateInterval('PT1H'));
                  $date_plus_un->add(new DateInterval('PT2H'));
                  $now["GLOBAL"]["HEURE+0"] = self::formatHeure($date->format('H')) . "H00";
                  } else { */
                $now["GLOBAL"]["HEURE+0"] = self::formatHeure($date->format('H')) . "H00";
                //}

                for ($i = 0; $i <= self::$_prevHeure; $i++) {

                    $tmp_now = NULL;
                    $tmp_now["+H"] = $i;

                    if ($i == 0) {
                        $a = 0;
                        $tmp_now["TMP"]["HOUR_JSON"] = $now["GLOBAL"]["HEURE+0"];
                    } else {
                        $date->add(new DateInterval('PT1H'));
                        $date_plus_un->add(new DateInterval('PT1H'));
                        $tmp_now["TMP"]["HOUR_JSON"] = self::formatHeure($date->format('H')) . "H00";
                    }

                    if ($date->format('H') == "00" AND $i != 0) {
                        $a++;
                    }

                    $tmp_now["+J"] = $a;

                    $tmp_day = $lang->getDate($date);
                    $tmp_day_plus_un = $lang->getDate($date_plus_un);

                    $getInfoJson = self::getInfoJson($json, $a, $tmp_now["TMP"]["HOUR_JSON"]);
                    
                    // Récupération des alertes météo
                    $txt_meteo = ($eqLogic->getConfiguration("desactiveAlertesMeteo") == 1) ? NULL : $lang->infosCondition($getInfoJson["CONDITION"]);
                    if (!is_array($txt_meteo)) { $txt_meteo = array(); }
                    if (!isset($txt_meteo["ALERTE"])) { $txt_meteo["ALERTE"] = NULL; }
                    if (!isset($txt_meteo["TXT"])) { $txt_meteo["TXT"] = NULL; }

                    if (isset($txt_meteo["ALERTE"]) AND $alertes <= self::getConfigNbAlerte()) {
                        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
                        log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Alerte [', __FILE__) . $txt_meteo["ALERTE"] . ' -> ' . $getInfoJson["CONDITION"] . ']');
                    }

                    $alerteVent = self::getAlerteVent($getInfoJson, $eqLogic->getConfiguration("seuilVent"), $txt_meteo["ALERTE"]);

                    if(empty($al_last["TYPE"])){ $al_last["TYPE"] = NULL; }
                    
                    // Récupération des traitements d'alerte
                    
                    if(empty($traitement)) { $traitement = NULL; }
                    $traitement_old = $traitement;
                    $traitement = self::setAlerte($txt_meteo["ALERTE"], $alerteVent, $al_last["TYPE"]);
                    if($traitement_old != $traitement) { unset($al_last); }
                    
                    // Récupération des traitements d'alerte

                    if ($alertes <= self::getConfigNbAlerte() AND $traitement != NULL) {
                        log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Type de traitement [', __FILE__) . $traitement . ']');
                    }

                    if ($alertes <= self::getConfigNbAlerte() AND $traitement != NULL) {
                        
                        $lastAlerte = $txt_meteo["ALERTE"];

                        if (!isset($al_last["TYPE"])) {

                            log::add('previsy', 'debug', '=======================================================================================');

                            $alertes++;
                            $al_last["START"] = $date->format('YmdH') . "00";
                            $al_last["START_TXT"] = $tmp_day["JOUR_TXT"];

                            $dur = 1;
                            $al_last["DANS_JOUR"] = $a;
                            $al_last["DANS_HEURE"] = $i;

                            $al_last["HEURE"] = $date->format('G');

                            if (isset($txt_meteo["ALERTE"])) {
                                $al_last["TYPE"] = $txt_meteo["ALERTE"];
                            } else {
                                $al_last["TYPE"] = "vent";
                            }

                            if ($alertes <= self::getConfigNbAlerte()) {
                                log::add('previsy', 'debug', __('get :. ', __FILE__) . __('START [', __FILE__) . $al_last["START"] . __('] TYPE [', __FILE__) . $al_last["TYPE"] . __('] VILLE [', __FILE__) . $now["GLOBAL"]["VILLE"] . ']');
                            }
                        }

                        if ($alertes > self::getConfigNbAlerte()) {
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Alertes suivantes Ignorées : ', __FILE__) . self::getConfigNbAlerte() . __(' en paramètre.', __FILE__));
                        }

                        $al_last["END"] = $date_plus_un->format('YmdH') . "00";
                        $al_last["END_TXT"] = $tmp_day_plus_un["JOUR_TXT"];

                        $al_last["DUREE_HEURE"] = $dur++;

                        $al_last["ICON"] = self::getIcon($al_last["TYPE"]);

                        list($al_last["MM"], $al_last["CONDITION_MAX"], $mm) = self::setAlertesPrevision($getInfoJson, $al_last, $txt_meteo);
                        if($al_last["CONDITION_MAX"] == NULL){
                            $al_last["CONDITION_MAX"] = __($getInfoJson["CONDITION"],  __FILE__);
                        }
                        
                        if(empty($al_last["TEMPERATURE"])){ $al_last["TEMPERATURE"] = NULL; }
                        $al_last["TEMPERATURE"] = self::getMinMaxMoyenne($al_last["TEMPERATURE"], self::setAlertesTemperature($getInfoJson["TMP2m"]));

                        // Autres données
                        if (empty($al_last["HUMIDITE"])) { $al_last["HUMIDITE"] = NULL; }
                        $al_last["HUMIDITE"] = self::getMinMaxMoyenne($al_last["HUMIDITE"], $getInfoJson["RH2m"]);

                        if (empty($al_last["VENT_VITESSE"])) { $al_last["VENT_VITESSE"] = NULL; }
                        $al_last["VENT_VITESSE"] = self::getMinMaxMoyenne($al_last["VENT_VITESSE"], $getInfoJson["WNDSPD10m"]);

                        if (empty($al_last["VENT_RAFALES"])) { $al_last["VENT_RAFALES"] = NULL; }
                        $al_last["VENT_RAFALES"] = self::getMinMaxMoyenne($al_last["VENT_RAFALES"], $getInfoJson["WNDGUST10m"]);

                        $al_last["VENT_NOM"] = $lang->echelleBeaufort($al_last["VENT_VITESSE"]["MOY"]);

                        $al_last["AFFICHE_TXT_WIDGET"] = $eqLogic->getConfiguration("afficheTxt");

                        $al_last["TXT"] = $lang->constructTxt($al_last, $now["GLOBAL"]["TYPE_DEGRE"]);

                        if ($alertes <= self::getConfigNbAlerte()) {
                            $now["ALERTES"]["GROUP"][$alertes] = $al_last;
                            $now["ALERTES"]["DETAILS"][] = $tmp_now;
                        }
                        if ($alertes <= self::getConfigNbAlerte()) {
                            log::add('previsy', 'debug', '=======================================================================================');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('DAY_JSON [', __FILE__) . "fcst_day_" . $a . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('HOUR_JSON [', __FILE__) . $tmp_now["TMP"]["HOUR_JSON"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('DUREE [', __FILE__) . $al_last["DUREE_HEURE"] . 'H]');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('CONDITION_KEY [', __FILE__) . $getInfoJson["CONDITION_KEY"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('TEMPERATURE [', __FILE__) . $getInfoJson["TMP2m"] . '°]');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('MM [', __FILE__) . $mm . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('HUMIDITE [', __FILE__) . $getInfoJson["RH2m"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('VENT_VITESSE [', __FILE__) . $getInfoJson["WNDSPD10m"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('VENT_RAFALES [', __FILE__) . $getInfoJson["WNDGUST10m"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('VENT_NOM [', __FILE__) . $al_last["VENT_NOM"] . ']');
                            log::add('previsy', 'debug', __('get :. ', __FILE__) . __('START [', __FILE__) . $al_last["START"] . ' (' . $mm . ')] / END [' . $al_last["END"] . ']');
                        }
                    }
                }

                if (!isset($now["ALERTES"]["DETAILS"][0]["CONDITION_KEY"])) {
                    log::add('previsy', 'debug', __('get :. ', __FILE__) . __('Aucune alerte pour ', __FILE__) . $now["GLOBAL"]["VILLE"]);
                }

                return $now;
            } else {
                log::add('previsy', 'error', '---------------------------------------------------------------------------------------');
                $erreur = (is_object($json) AND isset($json->errors[0]->text)) ? $json->errors[0]->text : '';
                log::add('previsy', 'error', __('get :. ', __FILE__) . __('La ville ', __FILE__) . $now["GLOBAL"]["VILLE"] . __(' n\'est pas référencée sur ', __FILE__) . previsy_bridge::getLabel(previsy_bridge::getApi($eqLogic)) . ' ' . $erreur);
                log::add('previsy', 'error', '---------------------------------------------------------------------------------------');
                $now["ERROR"] = TRUE;
                return $now;
            }
        }

        log::add('previsy', 'debug', '#######################################################################################');
    }

    public static function ifExist($_value = NULL){
        if(!empty($_value)){
            return $_value;
        } else {
            return NULL;
        }
    }
    
    public static function getLocalistion($_json, $_eqLogic){
        $return = array("VILLE" => NULL, "LATITUDE" => NULL, "LONGITUDE" => NULL);
        if (!empty($_eqLogic->getConfiguration("latitude")) AND ! empty($_eqLogic->getConfiguration("longitude"))) {
            $return["VILLE"] = NULL;
            $return["LATITUDE"] = $_eqLogic->getConfiguration("latitude");
            $return["LONGITUDE"] = $_eqLogic->getConfiguration("longitude");
        } elseif (!empty($_eqLogic->getConfiguration("ville"))) {
            $return["VILLE"] = $_eqLogic->getConfiguration("ville");
            $return["LATITUDE"] = $_json->city_info->latitude ?? NULL;
            $return["LONGITUDE"] = $_json->city_info->longitude ?? NULL;
        }
        return array($return["VILLE"], $return["LATITUDE"], $return["LONGITUDE"]);
    }
    
    public static function getInfoJson($_json, $_a, $_hour_json){
        $day_json = "fcst_day_" . $_a;
        $hour = (is_object($_json) AND isset($_json->{$day_json}->hourly_data->{$_hour_json})) ? $_json->{$day_json}->hourly_data->{$_hour_json} : NULL;

        $return = array();
        // isset() et non empty() : une valeur à 0 (0°C, vent nul, 0 mm) est une vraie valeur
        foreach (array("CONDITION", "CONDITION_KEY", "RH2m", "TMP2m", "WNDGUST10m", "WNDSPD10m", "APCPsfc", "DPT2m", "WNDCHILL2m", "PRMSL", "ISSNOW", "WNDDIR10m") as $key) {
            $return[$key] = (is_object($hour) AND isset($hour->{$key}) AND $hour->{$key} !== '') ? $hour->{$key} : NULL;
        }
        return $return;
    }
    
    public static function getAlerteVent($_getInfoJson, $_seuilVent, $_alerte) {

        // Pas de seuil configuré ou pas de donnée de vent : pas d'alerte vent
        if (!is_numeric($_seuilVent) OR !is_numeric($_getInfoJson["WNDSPD10m"])) {
            return FALSE;
        }

        if (!isset($_alerte)) {
            if (floatval($_getInfoJson["WNDSPD10m"]) >= floatval($_seuilVent)) {
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    public static function setAlerte($_txt_meteo, $_alerteVent, $_type = NULL) {
        // Regroupe les alertes
        if (isset($_txt_meteo)) { // Si alerte météo
            return "meteo";
        } elseif ($_alerteVent == TRUE) { // Si alerte vent
            return "vent";
        } else {
            return NULL;
        }
    }

    public static function setAlertesTemperature($_TMP2m) {
        if ($_TMP2m === NULL OR self::getConfigFormatDegres() == "°C") {
            return $_TMP2m;
        } else {
            return self::celsiusToFahrenheit($_TMP2m);
        }
    }

    public static function setAlertesPrevision($_getInfoJson, $_al_last, $_txt_meteo) {

        $mm = $_getInfoJson["APCPsfc"];
        
        if(empty($_al_last["CONDITION_MAX"])){ $_al_last["CONDITION_MAX"] = NULL; }
        if(empty($_al_last["MM"])){ $_al_last["MM"] = NULL; }
        
        if ($mm > 0) {

            $_al_last["MM"]["ARRAY"][] = $mm;

            $_al_last["MM"]["MIN"] = self::getMinAndMax("min", $_al_last["MM"]["ARRAY"]);
            if ($mm == $_al_last["MM"]["MIN"]) {
                $_al_last["MM"]["CONDITION_MIN_TXT"] = $_txt_meteo["TXT"];
            }

            $_al_last["MM"]["MAX"] = self::getMinAndMax("max", $_al_last["MM"]["ARRAY"]);
            if ($mm == $_al_last["MM"]["MAX"]) {
                $_al_last["MM"]["CONDITION_MAX_TXT"] = $_txt_meteo["TXT"];
                $_al_last["CONDITION_MAX"] = __($_getInfoJson["CONDITION"], __FILE__);
            }

            $_al_last["MM"]["TOTAL"] = round(array_sum($_al_last["MM"]["ARRAY"]), 2);
            $_al_last["MM"]["MOY"] = $_al_last["MM"]["TOTAL"] / count($_al_last["MM"]["ARRAY"]);
        }

        return array($_al_last["MM"], $_al_last["CONDITION_MAX"], $mm);
    }

    public static function getMinAndMax($_minOrmax, $_array) { 
        if (!empty($_array)) {
            if ($_minOrmax == "min") {
                return min($_array);
            } elseif ($_minOrmax == "max") {
                return max($_array);
            }
        } else {
            return NULL;
        }
    }

    public function clearDatas($_previsy) {
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('clearDatas :. ', __FILE__) . __('Lancement du nettoyage des commandes', __FILE__));
        $cmds = $_previsy->getCmd();
        foreach ($cmds as $cmd) {
            // Les commandes perso_* et vigilance_* ne sont pas vidées : sinon un scénario déclenché sur leur changement repartirait à chaque mise à jour
            if ($cmd->getLogicalId() != 'refresh' AND strpos($cmd->getLogicalId(), 'perso_') !== 0 AND strpos($cmd->getLogicalId(), 'vigilance_') !== 0) {
                $_previsy->checkAndUpdateCmd($cmd->getLogicalId(), NULL);
                log::add('previsy', 'debug', __('clearDatas :. ', __FILE__) . __('#ID# ', __FILE__) . $cmd->getLogicalId());
            }
        }
        log::add('previsy', 'debug', __('clearDatas :. ', __FILE__) . __('Fin du nettoyage des commandes', __FILE__));
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
    }

    public function getIdEtNameCmd($_idKey, $_sarray) {
        $return = NULL;
        foreach ($_sarray as $name) {
            $nameCmd = 'alerte_' . $_idKey . '_' . $name;
            $tempCmd = $this->getCmd(null, $nameCmd);
            $return[$name]["id"] = $tempCmd->getId();
            $return[$name]["name"] = $nameCmd;
        }
        return $return;
    }

    public static function printArray($_array){
        echo "<pre style='background-color: #1b2426 !important; color: white !important;'>".print_r($_array, true)."</pre>";
    }
    
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# STOP -> PREVISY
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# START -> GESTION DES OUTILS
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getMinMaxMoyenne($_array, $_input) {
        $return = $_array;
        if ($_input === NULL) { // Donnée absente : on ne fausse pas min / max / moyenne
            if ($_array == NULL) {
                return array("ARRAY" => array(), "MIN" => NULL, "MAX" => NULL, "MOY" => NULL);
            }
            return $_array;
        }
        if ($_array == NULL OR empty($_array["ARRAY"])) {
            $return = array();
            $return["ARRAY"][] = $_input;
            $return["MIN"] = $_input;
            $return["MAX"] = $_input;
            $return["MOY"] = $_input;
        } else {
            $return["ARRAY"][] = $_input;
            $return["MIN"] = min($return["ARRAY"]);
            $return["MAX"] = max($return["ARRAY"]);
            $return["MOY"] = array_sum($return["ARRAY"]) / count($return["ARRAY"]);
        }
        return $return;
    }

    public static function showDebug($_array) {
        echo "<pre>";
        print_r($_array);
        echo "</pre>";
    }

    public static function getIcon($_type) {

        switch ($_type) {
            case "pluie":
                return self::$_icons . "pluie.svg";
                break;
            case "neige":
                return self::$_icons . "neige.svg";
                break;
            case "neige_pluie":
                return self::$_icons . "neige.svg";
                break;
            case "orage":
                return self::$_icons . "eclair.svg";
                break;
            case "brouillard":
                return self::$_icons . "brouillard.svg";
                break;
            case "vent":
                return self::$_icons . "vent.svg";
                break;
        }
    }

    public static function celsiusToFahrenheit($_temp) {
        return $_temp * 9 / 5 + 32;
    }

    public static function formatHeure($_heure) {
        if ($_heure < 10) {
            return substr($_heure, 1, 1);
        } else {
            return $_heure;
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# STOP -> GESTION DES OUTILS
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# START -> GESTION JSON
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getJsonTampon($_id) {

        // La migration des anciens Json (core/json -> data/json) est faite une seule fois dans previsy_update()
        $file = self::$_jsonTampon . $_id . ".json";
        $return["datas"] = is_file($file) ? json_decode(file_get_contents($file)) : NULL;
        $return["datetime"] = is_file($file) ? filemtime($file) : time();
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('getJsonTampon :. ', __FILE__) . __('Chargement du Json : ', __FILE__) . self::$_jsonTampon . $_id . '.json');
        return $return;
    }

    public function updateJsonDatas($_id = NULL, $_force = FALSE) {
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');

        $eqLogic = eqLogic::byId($_id);
        if (!is_object($eqLogic)) {
            return;
        }

        $tempJson = self::$_jsonTampon . $_id . ".json";
        $api = previsy_bridge::getLabel(previsy_bridge::getApi($eqLogic));

        // Rafraichir manuel : on recharge depuis l'API même si le cache est valide (sauf rechargement très récent)
        $force = ($_force AND is_file($tempJson) AND (time() - filemtime($tempJson)) > self::$_timeRefreshForce);

        if ($force OR self::jsonTestTimeRefresh($tempJson, $eqLogic) == TRUE) {
            log::add('previsy', 'debug', __('updateJsonDatas :. ', __FILE__) . __('Cache expiré, absent ou configuration modifiée : rechargement depuis ', __FILE__) . $api);
            if ($eqLogic->miseEnCacheJson($_id) != TRUE) {
                // API injoignable : on ne réutilise l'ancien cache que s'il correspond toujours au lieu et à l'API configurés
                if (!is_file($tempJson) OR !self::jsonCorrespondConfig($tempJson, $eqLogic)) {
                    log::add('previsy', 'warning', __('updateJsonDatas :. ', __FILE__) . __('Aucune donnée disponible depuis ', __FILE__) . $api . ' #ID# ' . $_id);
                    return;
                }
                log::add('previsy', 'debug', __('updateJsonDatas :. ', __FILE__) . __('API injoignable, utilisation du json en cache', __FILE__));
            }
        } else {
            log::add('previsy', 'debug', __('updateJsonDatas :. ', __FILE__) . __('Mise à jour depuis le json en cache', __FILE__));
        }

        $eqLogic->UpdateDatas($eqLogic);
    }

    public static function jsonTestTimeRefresh($_file, $_eqLogic = NULL) {
        if (!is_file($_file) OR self::getJson($_file) == NULL) {
            return TRUE;
        }
        if ($_eqLogic !== NULL AND !self::jsonCorrespondConfig($_file, $_eqLogic)) {
            return TRUE; // L'API ou le lieu a changé depuis la mise en cache
        }
        $json = self::getJson($_file);
        if (!empty($json["previsy"]["repli"])) {
            // Données de l'API de repli : on retente l'API principale à chaque cycle (tâche horaire)
            return (time() - filemtime($_file)) > 3000;
        }
        return (time() - filemtime($_file)) > self::$_timeSynchro;
    }

    // Le json en cache a-t-il été produit avec l'API et le lieu actuellement configurés ?
    public static function jsonCorrespondConfig($_file, $_eqLogic) {
        $json = self::getJson($_file);
        if (!isset($json["previsy"]["source"])) {
            // Cache d'avant le choix d'API : forcément prevision-meteo.ch
            return previsy_bridge::getApi($_eqLogic) == previsy_bridge::LEGACY_API;
        }
        return $json["previsy"]["source"] == previsy_bridge::getSourceKey($_eqLogic);
    }

    public function miseEnCacheJson($_id = NULL) {
        log::add('previsy', 'debug', __('miseEnCacheJson :. ', __FILE__) . __('Récupération des données ', __FILE__) . "#" . $_id);
        self::prepareJsonFolder();
        $json = self::getInfoApi($_id);
        if($json != NULL){
            self::createJsonFile(self::$_jsonTampon . $_id, $json);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Récupère les prévisions via le bridge de l'API choisie sur l'équipement (format pivot)
    public static function getInfoApi($_id) {
        log::add('previsy', 'debug', __('getInfoApi :. ', __FILE__) . __('Récupération des données ', __FILE__) . "#" . $_id);
        $eqLogic = eqLogic::byId($_id);
        if (!is_object($eqLogic)) {
            return NULL;
        }
        return previsy_bridge::fetch($eqLogic);
    }

    public static function prepareJsonFolder() {
        log::add('previsy', 'debug', 'prepareJsonFolder :. Lancement');
        if (!is_dir(self::$_jsonTampon)) {
            log::add('previsy', 'debug', 'prepareJsonFolder :.  Création du dossier :' . self::$_jsonTampon);
            mkdir(self::$_jsonTampon, 0777);
        }
    }

    public static function getJson($_file) {
        log::add('previsy', 'debug', 'getJson :. Lancement');
        if (!is_file($_file)) {
            return NULL;
        } else {
            try {
                return json_decode(file_get_contents($_file), true);
            } catch (Exception $e) {
                return NULL;
            }
        }
    }

    public static function createJsonFile($_file, $_data) {
        log::add('previsy', 'debug', __('createJsonFile :. ', __FILE__) . __('Enregistrement du Json : ', __FILE__) . $_file);

        $fichier = fopen($_file . '.temp', 'w');
        fputs($fichier, json_encode($_data));
        fclose($fichier);
        
        if(is_file($_file . '.json')){
            unlink($_file . '.json');
        }
        
        rename($_file . '.temp', $_file . '.json');
        chmod($_file . '.json', 0777);
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# STOP -> GESTION JSON
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# START -> GESTION DU WIDGET
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getWidget($_datas, $_cmdIds = NULL) {

        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('getWidget :. ', __FILE__) . __('Lancement de la création ou de la mise à jour du Widget #ID# ', __FILE__) . $_cmdIds["widget"]["id"]);

        $degre = self::getConfigFormatDegres();

        if ($_datas["DANS_HEURE"] == 0) {
            $dansHeure = "<span style='font-weight: bold;'>" . __('En ce moment', __FILE__) . "</span>";
        } else {
            $dansHeure = __('Dans ', __FILE__) . "<span style='font-weight: bold;'>" . $_datas["DANS_HEURE"] . __(' h', __FILE__) . "</span>";
        }

        $return = "<div data-cmd_id='" . $_cmdIds["widget"]["id"] . "' class='previsyWidget'>
        
                    
                        <div style ='text-align: center; display: inline-block; margin: 2px;'>
                            <div style='font-size: 1em;'>" . $dansHeure . "</div>
                            <div style='font-size: 4em; margin-top: -10px;'><img title='Alerte " . $_datas["TYPE"] . "' src='" . $_datas["ICON"] . "' width='60px'></img></div>
                            <div style='font-size:0.9em;'><span style='font-weight: bold;'>" . $_datas["START_TXT"] . "</span> | <span style='font-weight: bold;'>" . $_datas["END_TXT"] . "</span></div>
                            <div style='font-size:0.9em' margin-top: -10px;><span style='font-weight: bold;'>" . $_datas["CONDITION_MAX"] . "</span>" . __(' au plus haut et pour une durée de ', __FILE__) . "<span style='font-weight: bold;'>" . $_datas["DUREE_HEURE"] . __(' h', __FILE__) . "</span></div>
                            <div style='font-size:0.9em;'>" . __('Type de vent : ', __FILE__) . "<span style='font-weight: bold;'>" . $_datas["VENT_NOM"] . "</span></div>                        
                        </div>
                    
                    <div style='display: inline-block; text-align: center;'>
                        <div class='previsyBlock previsyBlock1'>
                            <div class='previsyBlockMoyenne'>
                                <div><i title='" . __('Total des précipitation', __FILE__) . "' class='fas fa-tachometer-alt' style='font-size:2em; height: 31px;'></i></div>";
        if (isset($_datas["MM"]["TOTAL"])) {
            $return .= "<div class='previsySubTitleMoyenne'>- " . __('Moyenne', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>" . self::formatNombre($_datas["MM"]["MOY"]) . "<span style='font-size:0.7em'>mm</span></div>
                                <div class='previsySubTitleMoyenne'>" . __('Précipitation', __FILE__) . " (" . $_datas["MM"]["TOTAL"] . "<span style='font-size:0.6em'>mm</span>)</div>
                            </div>";
        } else {
            $return .= "<div class='previsySubTitleMoyenne'>- " . __('Néant', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>&ndash;</div>
                                <div class='previsySubTitleMoyenne'>" . __('Précipitation', __FILE__) . "</div>
                            </div>";
        }
        if ($_datas["DUREE_HEURE"] > 1 AND isset($_datas["MM"]["MIN"])) {
            $return .= "<div class='previsyBlockMinMax'>
                                <div class ='previsySubBlock previsySubBlock_G'>
                                    <div class='previsySubChiffre'>" . $_datas["MM"]["MIN"] . "<span style='font-size:0.7em'>mm</span></div>
                                    <div class='previsySubTitleMoyenne'>Min.</div>
                                </div>
                                <div class ='previsySubBlock previsySubBlock_D'>
                                    <div class='previsySubChiffre'>" . $_datas["MM"]["MAX"] . "<span style='font-size:0.7em'>mm</span></div>
                                    <div class='previsySubTitleMoyenne'>Max.</div>
                                </div>
                            </div>";
        }
        $return .= "</div>
                        <div class='previsyBlock  previsyBlock2'>
                            <div class='previsyBlockMoyenne'>
                                <div><i title='" . __('Tempétature moyenne', __FILE__) . "' class='icon jeedom-thermo-moyen' style='font-size:2em'></i></div>
                                <div class='previsySubTitleMoyenne'>- " . __('Moyenne', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>" . self::formatNombre($_datas["TEMPERATURE"]["MOY"]) . "<span style='font-size:0.7em'>" . $degre . "</span></div>
                                <div class='previsySubTitleMoyenne'>" . __('Température', __FILE__) . "</div>
                            </div>";
        if ($_datas["DUREE_HEURE"] > 1) {
            $return .= "<div class='previsyBlockMinMax'>
                                <div class ='previsySubBlock previsySubBlock_G'>
                                    <div class='previsySubChiffre'>" . $_datas["TEMPERATURE"]["MIN"] . "<span style='font-size:0.7em'>" . $degre . "</span></div>
                                    <div class='previsySubTitleMoyenne'>Min.</div>
                                </div>
                                <div class ='previsySubBlock previsySubBlock_D'>
                                    <div class='previsySubChiffre'>" . $_datas["TEMPERATURE"]["MAX"] . "<span style='font-size:0.7em'>" . $degre . "</span></div>
                                    <div class='previsySubTitleMoyenne'>Max.</div>
                                </div>
                            </div>";
        }
        $return .= "</div>
                        <div class='previsyBlock  previsyBlock3'>
                            <div class='previsyBlockMoyenne'>
                                <div><i title='" . __('Humidité moyenne en pourcentage', __FILE__) . "' class='icon jeedomapp-humidity' style='font-size:2em'></i></div>
                                <div class='previsySubTitleMoyenne'>- " . __('Moyenne', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>" . self::formatNombre($_datas["HUMIDITE"]["MOY"]) . "<span style='font-size:0.7em'>%</span></div>
                                <div class='previsySubTitleMoyenne'>" . __('Humidité', __FILE__) . "</div>
                            </div>";
        if ($_datas["DUREE_HEURE"] > 1) {
            $return .= "<div class='previsyBlockMinMax'>
                                <div class ='previsySubBlock previsySubBlock_G'>
                                    <div style='font-size:1em'>" . $_datas["HUMIDITE"]["MIN"] . "<span style='font-size:0.7em'>%</span></div>
                                    <div class='previsySubTitleMoyenne'>Min.</div>
                                </div>
                                <div class ='previsySubBlock previsySubBlock_D'>
                                    <div class='previsySubChiffre'>" . $_datas["HUMIDITE"]["MAX"] . "<span style='font-size:0.7em'>%</span></div>
                                    <div class='previsySubTitleMoyenne'>Max.</div>
                                </div>
                            </div>";
        }
        $return .= "</div>
                        <div class='previsyBlock  previsyBlock4'>
                            <div class='previsyBlockMoyenne'>
                                <div><i title='" . __('Vitesse moyenne du vent', __FILE__) . "' class='icon meteo-vent' style='font-size:2em'></i></div>
                                <div class='previsySubTitleMoyenne'>- " . __('Moyenne', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>" . self::formatNombre($_datas["VENT_VITESSE"]["MOY"]) . "<span style='font-size:0.7em'>km/h</span></div>
                                <div class='previsySubTitleMoyenne'>" . __('Vitesse du vent', __FILE__) . "</div>
                            </div>";
        if ($_datas["DUREE_HEURE"] > 1) {
            $return .= "<div class='previsyBlockMinMax'>
                                <div class ='previsySubBlock previsySubBlock_G'>
                                    <div class='previsySubChiffre'>" . $_datas["VENT_VITESSE"]["MIN"] . "<span style='font-size:0.7em'>km/h</span></div>
                                    <div class='previsySubTitleMoyenne'>Min.</div>
                                </div>
                                <div class ='previsySubBlock previsySubBlock_D'>
                                    <div style='font-size:1em'>" . $_datas["VENT_VITESSE"]["MAX"] . "<span style='font-size:0.7em'>km/h</span></div>
                                    <div class='previsySubTitleMoyenne'>Max.</div>
                                </div>
                            </div>";
        }
        $return .= "</div>

                        <div class='previsyBlock  previsyBlock5'>
                            <div class='previsyBlockMoyenne'>
                                <div><i title='" . __('Vitesse moyenne des rafales', __FILE__) . "' class='icon techno-ventilation' style='font-size:2em'></i></div>
                                <div class='previsySubTitleMoyenne'>- " . __('Moyenne', __FILE__) . " -</div>
                                <div class='previsySubChiffreMoyenne'>" . self::formatNombre($_datas["VENT_RAFALES"]["MOY"]) . "<span style='font-size:0.7em'>km/h</span></div>
                                <div class='previsySubTitleMoyenne'>" . __('Force Rafales', __FILE__) . "</div>
                            </div>";
        if ($_datas["DUREE_HEURE"] > 1) {
            $return .= "<div class='previsyBlockMinMax'>
                                <div class ='previsySubBlock previsySubBlock_G'>
                                    <div class='previsySubChiffre'>" . $_datas["VENT_RAFALES"]["MIN"] . "<span style='font-size:0.7em'>km/h</span></div>
                                    <div class='previsySubTitleMoyenne'>Min.</div>
                                </div>
                                <div class ='previsySubBlock previsySubBlock_D'>
                                    <div class='previsySubChiffre'>" . $_datas["VENT_RAFALES"]["MAX"] . "<span style='font-size:0.7em'>km/h</span></div>
                                    <div class='previsySubTitleMoyenne'>Max.</div>
                                </div>
                            </div>";
        }
        $return .= "</div>
                    </div>";

        if ($_datas["AFFICHE_TXT_WIDGET"] == 1) {
            $return .= "<div data-cmd_id='" . $_cmdIds["txt_full"]["id"] . "'>
                        <textarea rows='4' style='width:100%;'>" . $_datas["TXT"]["FULL"] . "</textarea>
                    </div>";
        }
        $return .= "<div style='float:none; clear:both'></div>
            </div>";

        log::add('previsy', 'debug', __('getWidget :. ', __FILE__) . __('Fin de la création ou de la mise à jour du Widget #ID# ', __FILE__) . $_cmdIds["widget"]["id"]);
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');

        return $return;
    }

    public static function formatNombre($_value, $_decimales = 1) {
        // Valeur absente : on affiche un tiret plutôt qu'un faux "0.0"
        if ($_value === NULL OR $_value === '' OR !is_numeric($_value)) {
            return '&ndash;';
        }
        return number_format(floatval($_value), $_decimales);
    }

    public static function getWidgetNull() {
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('getWidgetNull :. ', __FILE__) . __('Création widget rien à signaler', __FILE__));
        return "<div style='width:100%; padding:0 5px;'>
                    <div class='previsyWidget' style='margin:0; padding:5px; width:100%;'>
                        <div style ='text-align: center; display: inline-block; margin: 2px;'>
                            <div style='font-size: 4em; margin-top: -10px;'><i class='far fa-check-circle'></i></div>
                            <div style='font-size: 1em;'>" . __('Aucune alerte à déclarer', __FILE__) . "</div>  
                        </div>
                   </div>
                </div>";
    }

    public static function getWidgetError($_ville, $_api = 'prevision-meteo.ch') {
        log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
        log::add('previsy', 'debug', __('getWidgetError :. ', __FILE__) . __('Création widget error', __FILE__));
        return "<div style='width:100%; padding:0 5px;'>
                    <div class='previsyWidget' style='margin:0; padding:5px; width:100%;'>
                        <div style ='text-align: center; display: inline-block; margin: 2px;'>
                            <div style='font-size: 4em; margin-top: -10px;'><i class='fas fa-exclamation-triangle'></i></div>
                            <div style='font-size: 1em;'>" . __('La ville de ', __FILE__) . "<span style='font-weight: bold;'>" . $_ville . "</span>" . __(' n\'est pas référencée sur ', __FILE__) . $_api . "</div>
                       </div>
                    </div>
                   </div>";
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# STOP -> GESTION DU WIDGET
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

class previsyCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic();
        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande 			
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave 
                log::add('previsy', 'debug', '---------------------------------------------------------------------------------------');
                log::add('previsy', 'debug', __('execute :. ', __FILE__) . __('Lancement de la commande refresh : #ID# ', __FILE__) . $eqlogic->getId());
                $eqlogic->updateJsonDatas($eqlogic->getId(), TRUE);
                break;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
