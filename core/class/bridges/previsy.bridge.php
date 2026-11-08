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
# BRIDGES API METEO
#
# Chaque bridge récupère les prévisions de son API et les convertit dans le FORMAT PIVOT de Previsy
# (historiquement celui de prevision-meteo.ch). Le reste du plugin ne lit que ce format :
#
#   {
#     "city_info": { "name", "country", "latitude", "longitude", "sunrise", "sunset" },
#     "fcst_day_0": {                       // 0 = aujourd'hui, 1 = demain, ...
#       "date": "dd.mm.YYYY",
#       "hourly_data": {
#         "0H00" ... "23H00": {
#           "CONDITION"     : libellé FR (doit correspondre à previsy_language::infosCondition pour lever une alerte)
#           "CONDITION_KEY" : clé technique de la condition
#           "TMP2m"         : température (°C)
#           "DPT2m"         : point de rosée (°C)
#           "WNDCHILL2m"    : température ressentie (°C)
#           "RH2m"          : humidité (%)
#           "PRMSL"         : pression au niveau de la mer (hPa)
#           "APCPsfc"       : précipitations (mm)
#           "ISSNOW"        : 1 si neige
#           "WNDSPD10m"     : vent moyen (km/h)
#           "WNDGUST10m"    : rafales (km/h)
#           "WNDDIR10m"     : direction du vent (degrés)
#           "WNDDIRCARD10"  : direction du vent (N, NE, E, SE, S, SO, O, NO)
#         }
#       }
#     },
#     "errors": [ { "text": "..." } ]       // présent uniquement en cas d'erreur (ville introuvable...)
#   }
#
# Pour ajouter une API : créer previsy.bridge.<id>.php avec une classe previsy_bridge_<id>
# (méthodes statiques getLabel() et fetch($_eqLogic)), puis l'ajouter dans previsy_bridge::getList().
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once __DIR__ . '/previsy.bridge.prevision_meteo_ch.php';
require_once __DIR__ . '/previsy.bridge.open_meteo.php';

class previsy_bridge {

    const DEFAULT_API = 'open_meteo';          // API proposée par défaut (nouvelle installation, nouvel équipement)
    const LEGACY_API = 'prevision_meteo_ch';   // API des équipements créés avant le choix d'API
    const MODE_EQUIPEMENT = 'equipement';      // Mode global : chaque équipement choisit son API

    // Liste des API disponibles : id => classe
    public static function getList() {
        return array(
            'open_meteo' => 'previsy_bridge_open_meteo',
            'prevision_meteo_ch' => 'previsy_bridge_prevision_meteo_ch',
        );
    }

    /*     * ************************* Configuration ************************* */

    // Mode choisi dans la configuration du plugin : une API pour tous les équipements, ou "equipement"
    public static function getMode() {
        $mode = config::byKey('api_mode', 'previsy', self::MODE_EQUIPEMENT);
        $list = self::getList();
        return isset($list[$mode]) ? $mode : self::MODE_EQUIPEMENT;
    }

    // Repli automatique sur l'autre API en cas d'indisponibilité (activé par défaut)
    public static function repliActif() {
        return config::byKey('api_repli', 'previsy', 1) == 1;
    }

    // API principale de l'équipement : celle de la configuration globale, sinon celle de l'équipement
    public static function getApi($_eqLogic) {
        $mode = self::getMode();
        if ($mode != self::MODE_EQUIPEMENT) {
            return $mode;
        }
        $api = $_eqLogic->getConfiguration('api', '');
        $list = self::getList();
        return isset($list[$api]) ? $api : self::LEGACY_API;
    }

    // API de repli : la première autre API disponible
    public static function getApiRepli($_api) {
        foreach (array_keys(self::getList()) as $api) {
            if ($api != $_api) {
                return $api;
            }
        }
        return NULL;
    }

    public static function getClass($_api) {
        $list = self::getList();
        return isset($list[$_api]) ? $list[$_api] : $list[self::LEGACY_API];
    }

    public static function getLabel($_api) {
        $class = self::getClass($_api);
        return $class::getLabel();
    }

    /*     * ************************* Récupération ************************* */

    // Récupère les prévisions de l'équipement au format pivot (objet).
    // NULL = API indisponible ; objet avec "errors" = erreur de configuration (ex. ville introuvable), sans repli.
    public static function fetch($_eqLogic) {
        $api = self::getApi($_eqLogic);
        log::add('previsy', 'debug', 'previsy_bridge::fetch :. API [' . $api . '] #ID# ' . $_eqLogic->getId());
        $class = self::getClass($api);
        $data = $class::fetch($_eqLogic);
        $apiUtilisee = $api;

        if ($data === NULL) {
            $repli = self::repliActif() ? self::getApiRepli($api) : NULL;
            if ($repli !== NULL) {
                $classRepli = self::getClass($repli);
                $dataRepli = $classRepli::fetch($_eqLogic);
                if ($dataRepli !== NULL) {
                    self::signalerRetour($repli); // L'API de repli répond : son éventuel message d'indisponibilité n'a plus lieu d'être
                }
                // Le repli n'est retenu que s'il fournit des prévisions utilisables (pas "ville introuvable"...)
                if (is_object($dataRepli) AND !self::hasError($dataRepli)) {
                    $data = $dataRepli;
                    $apiUtilisee = $repli;
                } else {
                    log::add('previsy', 'warning', 'previsy_bridge::fetch :. #ID# ' . $_eqLogic->getId() . ' ' . self::getLabel($repli) . ' ' . __('n\'a pas fourni de prévisions utilisables pour ce lieu.', __FILE__));
                }
            }
            self::signalerIndisponibilite($api, $apiUtilisee != $api ? $apiUtilisee : NULL, $_eqLogic);
        } else {
            self::signalerRetour($api);
        }

        if (is_object($data)) {
            // Signature des données : permet d'invalider le cache si l'API ou le lieu change
            $data->previsy = (object) array(
                'api' => $apiUtilisee,
                'repli' => ($apiUtilisee != $api),
                'source' => self::getSourceKey($_eqLogic),
                'timestamp' => time(),
            );
        }
        return $data;
    }

    /*     * ************************* Signalement ************************* */

    public static function logicalIdMessage($_api) {
        return 'api_indisponible_' . $_api;
    }

    // Warning dans les logs à chaque fois, et un seul message dans le centre de messages Jeedom par API indisponible
    public static function signalerIndisponibilite($_api, $_apiRepli, $_eqLogic) {
        if ($_apiRepli !== NULL) {
            $texte = self::getLabel($_api) . ' ' . __('est indisponible : Previsy utilise', __FILE__) . ' ' . self::getLabel($_apiRepli) . ' ' . __('en repli. Retour automatique dès que', __FILE__) . ' ' . self::getLabel($_api) . ' ' . __('répond à nouveau.', __FILE__);
        } elseif (self::repliActif()) {
            $texte = self::getLabel($_api) . ' ' . __('est indisponible et aucune prévision n\'a pu être obtenue en repli : les données en cache sont conservées.', __FILE__);
        } else {
            $texte = self::getLabel($_api) . ' ' . __('est indisponible (repli automatique désactivé) : les données en cache sont conservées.', __FILE__);
        }
        log::add('previsy', 'warning', 'previsy_bridge::fetch :. #ID# ' . $_eqLogic->getId() . ' ' . $texte);

        // Un seul message dans le centre de messages tant que l'API reste indisponible
        if (config::byKey('api_indisponible_' . $_api, 'previsy', 0) != 1) {
            message::add('previsy', $texte, '', self::logicalIdMessage($_api));
            config::save('api_indisponible_' . $_api, 1, 'previsy');
        }
    }

    // L'API principale répond : on retire le message d'indisponibilité s'il y en avait un
    public static function signalerRetour($_api) {
        if (config::byKey('api_indisponible_' . $_api, 'previsy', 0) != 1) {
            return;
        }
        message::removeAll('previsy', self::logicalIdMessage($_api));
        config::save('api_indisponible_' . $_api, 0, 'previsy');
        log::add('previsy', 'info', 'previsy_bridge::fetch :. ' . self::getLabel($_api) . ' ' . __('répond à nouveau : fin du repli.', __FILE__));
    }

    // Identifie "API + lieu" pour savoir si le cache Json correspond toujours à la configuration
    public static function getSourceKey($_eqLogic) {
        return self::getApi($_eqLogic) . '|' . trim($_eqLogic->getConfiguration('ville')) . '|' . trim($_eqLogic->getConfiguration('latitude')) . '|' . trim($_eqLogic->getConfiguration('longitude'));
    }

    // Lieu de l'équipement : les coordonnées prennent le dessus sur la ville
    public static function getLocalisation($_eqLogic) {
        $latitude = trim($_eqLogic->getConfiguration('latitude'));
        $longitude = trim($_eqLogic->getConfiguration('longitude'));
        if (is_numeric($latitude) AND is_numeric($longitude)) {
            return array('latitude' => floatval($latitude), 'longitude' => floatval($longitude), 'ville' => NULL);
        }
        return array('latitude' => NULL, 'longitude' => NULL, 'ville' => trim($_eqLogic->getConfiguration('ville')));
    }

    // Appel HTTP avec timeout (ne bloque pas le cron si l'API ne répond pas). Retourne l'objet Json ou NULL.
    // $_tableau = TRUE accepte aussi une réponse Json de type tableau.
    public static function httpGetJson($_url, $_timeout = 15, $_tableau = FALSE) {
        $context = stream_context_create(array('http' => array(
            'timeout' => $_timeout,
            'ignore_errors' => true, // pour lire le message d'erreur Json renvoyé avec un code 4xx
            'header' => "User-Agent: Jeedom-Previsy\r\n",
        )));
        $raw = @file_get_contents($_url, false, $context);
        if ($raw === false) {
            log::add('previsy', 'warning', 'previsy_bridge::httpGetJson :. ' . __('Pas de réponse de ', __FILE__) . $_url);
            return NULL;
        }
        $json = json_decode($raw);
        if (!is_object($json) AND !($_tableau AND is_array($json))) {
            log::add('previsy', 'warning', 'previsy_bridge::httpGetJson :. ' . __('Réponse illisible de ', __FILE__) . $_url);
            return NULL;
        }
        return $json;
    }

    // Réponse d'erreur au format pivot (ex. ville introuvable)
    public static function error($_text) {
        return (object) array('errors' => array((object) array('text' => $_text)));
    }

    public static function hasError($_json) {
        return !is_object($_json) OR !empty($_json->errors) OR !empty($_json->error);
    }

    // Degrés -> point cardinal (notation française utilisée par prevision-meteo.ch)
    public static function degresToCardinal($_degres) {
        if (!is_numeric($_degres)) {
            return NULL;
        }
        $cardinaux = array('N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO');
        return $cardinaux[((int) round(fmod(floatval($_degres) + 360, 360) / 45)) % 8];
    }

}
