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
# VIGILANCE MÉTÉO NATIONALE (MeteoAlarm)
#
# MeteoAlarm (https://meteoalarm.org) diffuse, sans clé API, les vigilances officielles des services météo
# européens (Météo-France, IRM, MétéoSuisse...). Le flux contient aussi l'historique des messages :
# les messages remplacés (champ CAP "references"), annulés ou expirés sont ignorés.
#
# Zone : nom de la zone tel que publié par le service national (en France : le département).
# En France, si la zone n'est pas renseignée, le département est trouvé depuis les coordonnées (geo.api.gouv.fr).
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class previsy_vigilance {

    public static $_urlFeed = "https://feeds.meteoalarm.org/api/v1/warnings/feeds-";
    public static $_urlGeoGouv = "https://geo.api.gouv.fr/communes";
    public static $_cacheFeed = 1800; // Le flux d'un pays est rechargé au plus toutes les 30 minutes

    public static function getPays() {
        return array(
            'france' => __('France', __FILE__),
            'belgium' => __('Belgique', __FILE__),
            'switzerland' => __('Suisse', __FILE__),
            'luxembourg' => __('Luxembourg', __FILE__),
            'germany' => __('Allemagne', __FILE__),
            'spain' => __('Espagne', __FILE__),
            'italy' => __('Italie', __FILE__),
            'portugal' => __('Portugal', __FILE__),
            'netherlands' => __('Pays-Bas', __FILE__),
            'austria' => __('Autriche', __FILE__),
            'andorra' => __('Andorre', __FILE__),
            'ireland' => __('Irlande', __FILE__),
            'united-kingdom' => __('Royaume-Uni', __FILE__),
        );
    }

    public static function getNiveaux() {
        return array(
            // Couleur traduite selon la langue de Jeedom (en anglais : none, green, yellow, orange, red)
            0 => array('couleur' => __('aucune', __FILE__), 'hex' => ''),
            1 => array('couleur' => __('vert', __FILE__), 'hex' => '#31AA35'),
            2 => array('couleur' => __('jaune', __FILE__), 'hex' => '#FFD600'),
            3 => array('couleur' => __('orange', __FILE__), 'hex' => '#FF9800'),
            4 => array('couleur' => __('rouge', __FILE__), 'hex' => '#E53935'),
        );
    }

    public static $_infos = array(
        'niveau' => array('Vigilance niveau', 'numeric', ''),
        'couleur' => array('Vigilance couleur', 'string', ''),
        'en_cours' => array('Vigilance en cours', 'binary', ''),
        'txt' => array('Vigilance texte', 'string', ''),
        'zone' => array('Vigilance zone', 'string', ''),
        'date_start' => array('Vigilance début', 'numeric', ''),
        'date_end' => array('Vigilance fin', 'numeric', ''),
    );

    public static function getPaysEquipement($_eqLogic) {
        $pays = $_eqLogic->getConfiguration('vigilancePays', '');
        return array_key_exists($pays, self::getPays()) ? $pays : '';
    }

    /*     * ************************* Commandes ************************* */

    public static function createCmds($_eqLogic) {
        if (self::getPaysEquipement($_eqLogic) == '') {
            return;
        }
        foreach (self::$_infos as $suffixe => $def) {
            $cmd = $_eqLogic->getCmd(null, 'vigilance_' . $suffixe);
            if (!is_object($cmd)) {
                $cmd = new previsyCmd();
                $cmd->setLogicalId('vigilance_' . $suffixe);
                $cmd->setEqLogic_id($_eqLogic->getId());
                $cmd->setName(__($def[0], __FILE__));
                $cmd->setType('info');
                $cmd->setSubType($def[1]);
                $cmd->setIsVisible(0);
                $cmd->setIsHistorized(($suffixe == 'niveau') ? 1 : 0);
                $cmd->save();
            }
        }
    }

    // Vigilance désactivée : suppression des commandes (appelé dans postAjax)
    public static function removeObsoleteCmds($_eqLogic) {
        if (self::getPaysEquipement($_eqLogic) != '') {
            return;
        }
        foreach ($_eqLogic->getCmd() as $cmd) {
            if (strpos($cmd->getLogicalId(), 'vigilance_') === 0) {
                $cmd->remove();
            }
        }
    }

    /*     * ************************* Données ************************* */

    // Flux MeteoAlarm d'un pays (cache fichier partagé par tous les équipements)
    public static function getFeed($_pays) {
        $file = previsy::$_jsonTampon . 'meteoalarm_' . $_pays . '.json';
        if (is_file($file) AND (time() - filemtime($file)) < self::$_cacheFeed) {
            $json = json_decode(file_get_contents($file));
            if (is_object($json)) {
                return $json;
            }
        }
        $json = previsy_bridge::httpGetJson(self::$_urlFeed . $_pays, 20);
        if (is_object($json) AND isset($json->warnings)) {
            previsy::prepareJsonFolder();
            file_put_contents($file . '.temp', json_encode($json));
            rename($file . '.temp', $file);
            return $json;
        }
        // MeteoAlarm injoignable : on se contente du dernier flux connu
        return is_file($file) ? json_decode(file_get_contents($file)) : NULL;
    }

    // Zone de vigilance : saisie manuelle, sinon département français trouvé depuis les coordonnées
    public static function getZone($_eqLogic, $_json = NULL) {
        $zone = trim($_eqLogic->getConfiguration('vigilanceZone', ''));
        if ($zone != '' OR self::getPaysEquipement($_eqLogic) != 'france') {
            return $zone;
        }

        $localisation = previsy_bridge::getLocalisation($_eqLogic);
        $lat = $localisation['latitude'];
        $lon = $localisation['longitude'];
        if ($lat === NULL AND is_object($_json) AND isset($_json->city_info->latitude) AND is_numeric($_json->city_info->latitude)) {
            $lat = floatval($_json->city_info->latitude);
            $lon = floatval($_json->city_info->longitude);
        }
        if ($lat === NULL) {
            return '';
        }

        // Cache par équipement et par position
        $cle = round($lat, 3) . ',' . round($lon, 3);
        $file = previsy::$_jsonTampon . 'vigilance_zone_' . $_eqLogic->getId() . '.json';
        $cache = is_file($file) ? json_decode(file_get_contents($file), true) : NULL;
        if (isset($cache['cle'], $cache['zone']) AND $cache['cle'] == $cle) {
            return $cache['zone'];
        }

        $geo = previsy_bridge::httpGetJson(self::$_urlGeoGouv . '?lat=' . $lat . '&lon=' . $lon . '&fields=departement&format=json', 15, TRUE);
        if (is_array($geo) AND isset($geo[0]->departement->nom)) {
            $zone = $geo[0]->departement->nom;
            previsy::prepareJsonFolder();
            file_put_contents($file, json_encode(array('cle' => $cle, 'zone' => $zone)));
            log::add('previsy', 'debug', 'previsy_vigilance::getZone :. ' . $cle . ' -> ' . $zone);
            return $zone;
        }
        log::add('previsy', 'warning', 'previsy_vigilance::getZone :. ' . __('Département introuvable pour ', __FILE__) . $cle);
        return '';
    }

    // Noms des zones présentes dans le flux du pays (suggestions pour la saisie de la zone).
    // Le flux ne contient que les zones ayant eu un message récemment : la liste peut être incomplète.
    public static function getZonesConnues($_pays) {
        if (!array_key_exists($_pays, self::getPays())) {
            return array();
        }
        $feed = self::getFeed($_pays);
        $zones = array();
        if (is_object($feed) AND isset($feed->warnings)) {
            foreach ($feed->warnings as $warning) {
                foreach ((isset($warning->alert->info) ? $warning->alert->info : array()) as $info) {
                    foreach ((isset($info->area) ? $info->area : array()) as $area) {
                        if (!empty($area->areaDesc)) {
                            $zones[self::normaliser($area->areaDesc)] = $area->areaDesc;
                        }
                    }
                }
            }
        }
        natcasesort($zones);
        return array_values($zones);
    }

    public static function normaliser($_texte) {
        $texte = mb_strtolower(trim($_texte), 'UTF-8');
        $texte = strtr($texte, array(
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n', 'ß' => 'ss',
        ));
        return preg_replace('/[^a-z0-9]/', '', $texte);
    }

    // Vigilances en cours ou à venir pour la zone, au niveau minimum demandé
    public static function getVigilances($_feed, $_zone, $_niveauMin = 2, $_now = NULL) {
        $now = ($_now === NULL) ? time() : $_now;
        $zone = self::normaliser($_zone);
        if (!is_object($_feed) OR !isset($_feed->warnings) OR $zone == '') {
            return array();
        }
        $langue = (class_exists('translate') AND translate::getLanguage() == 'en_US') ? 'en' : 'fr';

        // Messages remplacés par un message plus récent (champ CAP "references" : "sender,identifier,sent ...")
        $remplaces = array();
        foreach ($_feed->warnings as $warning) {
            if (!empty($warning->alert->references)) {
                foreach (preg_split('/\s+/', trim($warning->alert->references)) as $reference) {
                    $parties = explode(',', $reference);
                    if (isset($parties[1])) {
                        $remplaces[$parties[1]] = TRUE;
                    }
                }
            }
        }

        $return = array();
        foreach ($_feed->warnings as $warning) {
            $alert = isset($warning->alert) ? $warning->alert : NULL;
            if (!is_object($alert) OR empty($alert->info) OR (isset($alert->status) AND $alert->status != 'Actual')) {
                continue;
            }
            if ((isset($alert->msgType) AND $alert->msgType == 'Cancel') OR isset($remplaces[$alert->identifier])) {
                continue;
            }
            $info = self::choisirInfo($alert->info, $langue);
            if ($info === NULL OR empty($info->area) OR !self::concerneZone($info->area, $zone)) {
                continue;
            }

            $params = array();
            foreach ((isset($info->parameter) ? $info->parameter : array()) as $param) {
                $params[$param->valueName] = $param->value;
            }
            $niveau = isset($params['awareness_level']) ? intval($params['awareness_level']) : 0;
            $fin = isset($info->expires) ? strtotime($info->expires) : NULL;
            if ($niveau < $_niveauMin OR ($fin !== NULL AND $fin <= $now)) {
                continue; // Niveau insuffisant (dont "vert" = fin de vigilance) ou expirée
            }

            $vigilance = array(
                'type' => isset($params['awareness_type']) ? $params['awareness_type'] : '',
                'niveau' => $niveau,
                'evenement' => isset($info->event) ? $info->event : (isset($info->headline) ? $info->headline : ''),
                'debut' => strtotime(isset($info->onset) ? $info->onset : (isset($info->effective) ? $info->effective : $alert->sent)),
                'fin' => $fin,
                'description' => isset($info->description) ? $info->description : '',
            );
            $return[$vigilance['type'] . '|' . $vigilance['niveau'] . '|' . $vigilance['debut'] . '|' . $vigilance['fin']] = $vigilance; // dédoublonnage
        }

        $return = array_values($return);
        usort($return, function ($a, $b) {
            return ($b['niveau'] - $a['niveau']) ?: ($a['debut'] - $b['debut']);
        });
        return $return;
    }

    public static function concerneZone($_areas, $_zone) {
        foreach ($_areas as $area) {
            if (isset($area->areaDesc) AND self::normaliser($area->areaDesc) == $_zone) {
                return TRUE;
            }
            foreach ((isset($area->geocode) ? $area->geocode : array()) as $geocode) {
                if (self::normaliser($geocode->value) == $_zone) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    public static function choisirInfo($_infos, $_langue) {
        $premiere = NULL;
        foreach ($_infos as $info) {
            if ($premiere === NULL) {
                $premiere = $info;
            }
            if (isset($info->language) AND stripos($info->language, $_langue) === 0) {
                return $info;
            }
        }
        return $premiere;
    }

    /*     * ************************* Mise à jour ************************* */

    public static function update($_eqLogic, $_json = NULL) {
        $pays = self::getPaysEquipement($_eqLogic);
        if ($pays == '') {
            return NULL;
        }
        $zone = self::getZone($_eqLogic, $_json);
        $niveauMin = intval($_eqLogic->getConfiguration('vigilanceNiveauMin', 2)) ?: 2;
        $feed = self::getFeed($pays);
        $vigilances = self::getVigilances($feed, $zone, $niveauMin);

        $niveaux = self::getNiveaux();
        $niveau = empty($vigilances) ? 0 : $vigilances[0]['niveau'];
        $now = time();
        $enCours = 0;
        $debut = $fin = NULL;
        $textes = array();
        foreach ($vigilances as $vigilance) {
            $textes[] = $vigilance['evenement'];
            if ($vigilance['debut'] <= $now) {
                $enCours = 1;
            }
            $debut = ($debut === NULL) ? $vigilance['debut'] : min($debut, $vigilance['debut']);
            if ($vigilance['fin'] !== NULL) {
                $fin = ($fin === NULL) ? $vigilance['fin'] : max($fin, $vigilance['fin']);
            }
        }

        if ($zone == '') {
            $txt = __('Zone de vigilance inconnue : renseignez-la dans l\'onglet Vigilance.', __FILE__);
        } elseif ($feed === NULL) {
            $txt = __('Vigilance indisponible (MeteoAlarm injoignable).', __FILE__);
        } elseif (empty($vigilances)) {
            $txt = __('Aucune vigilance en cours pour', __FILE__) . ' ' . $zone . '.';
        } else {
            $txt = implode(', ', $textes) . ' (' . $zone . ')';
            if ($fin !== NULL) {
                $txt .= ' ' . __('jusqu\'au', __FILE__) . ' ' . date('d/m/Y H:i', $fin);
            }
            $txt .= '.';
        }

        $valeurs = array(
            'niveau' => $niveau,
            'couleur' => $niveaux[$niveau]['couleur'],
            'en_cours' => $enCours,
            'txt' => $txt,
            'zone' => $zone,
            'date_start' => ($debut !== NULL) ? date('YmdHi', $debut) : '',
            'date_end' => ($fin !== NULL) ? date('YmdHi', $fin) : '',
        );
        foreach ($valeurs as $suffixe => $valeur) {
            $_eqLogic->checkAndUpdateCmd('vigilance_' . $suffixe, $valeur);
        }
        log::add('previsy', 'debug', 'previsy_vigilance::update :. [' . $pays . ' / ' . $zone . '] ' . $txt);

        return array('niveau' => $niveau, 'vigilances' => $vigilances, 'zone' => $zone, 'txt' => $txt, 'en_cours' => $enCours);
    }

    /*     * ************************* Widget ************************* */

    public static function getWidget($_resultat) {
        if (!is_array($_resultat) OR empty($_resultat['vigilances'])) {
            return '';
        }
        $niveaux = self::getNiveaux();
        $lignes = '';
        foreach ($_resultat['vigilances'] as $vigilance) {
            $periode = date('d/m H:i', $vigilance['debut']) . (($vigilance['fin'] !== NULL) ? ' &rarr; ' . date('d/m H:i', $vigilance['fin']) : '');
            $lignes .= "<div title='" . htmlspecialchars($vigilance['description'], ENT_QUOTES) . "'><span style='display:inline-block; width:10px; height:10px; border-radius:50%; background:"
                    . $niveaux[$vigilance['niveau']]['hex'] . "; margin-right:5px;'></span><span style='font-weight:bold;'>" . htmlspecialchars($vigilance['evenement']) . "</span> <span style='opacity:0.7;'>(" . $periode . ")</span></div>";
        }
        $couleur = $niveaux[$_resultat['niveau']]['hex'];
        return "<div class='previsyWidget previsyVigilance' style='width:calc(100% - 10px); box-sizing:border-box; text-align:left; border:2px solid " . $couleur . "; border-left-width:8px;'>"
                . "<div style='font-size:0.9em; opacity:0.8;'><i class='fas fa-exclamation-triangle' style='color:" . $couleur . ";'></i> " . __('Vigilance', __FILE__) . ' ' . htmlspecialchars($_resultat['zone']) . "</div>"
                . $lignes . "</div>";
    }

}
