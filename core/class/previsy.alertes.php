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
# ALERTES PERSONNALISÉES
#
# L'utilisateur définit des règles dans l'onglet « Mes alertes » (configuration "alertesPerso" de l'équipement).
# Toutes les conditions renseignées d'une règle doivent être vraies sur une même heure (ET logique).
# Les heures consécutives qui correspondent forment une « période » ; seules les périodes d'au moins
# "dureeMin" heures sont retenues. Chaque règle a ses propres commandes : perso_<id>_<info>.
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class previsy_alertes {

    const MAX_REGLES = 20;

    // Conditions numériques : clé de la règle => array(clé du format pivot, comparaison)
    public static $_bornes = array(
        'ventMin' => array('WNDSPD10m', '>='), 'ventMax' => array('WNDSPD10m', '<='),
        'rafaleMin' => array('WNDGUST10m', '>='), 'rafaleMax' => array('WNDGUST10m', '<='),
        'tempMin' => array('TMP2m', '>='), 'tempMax' => array('TMP2m', '<='),
        'ressentieMin' => array('WNDCHILL2m', '>='), 'ressentieMax' => array('WNDCHILL2m', '<='),
        'humiditeMin' => array('RH2m', '>='), 'humiditeMax' => array('RH2m', '<='),
        'pressionMin' => array('PRMSL', '>='), 'pressionMax' => array('PRMSL', '<='),
    );

    public static $_directions = array('N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO');

    // Informations publiées pour chaque règle : suffixe => array(nom, sous-type, unité)
    public static $_infos = array(
        'en_cours' => array('En cours', 'binary', ''),
        'dans_heure' => array('Dans (heures)', 'numeric', 'h'),
        'date_start' => array('Début', 'numeric', ''),
        'date_end' => array('Fin', 'numeric', ''),
        'duree' => array('Durée', 'numeric', 'h'),
        'nb' => array('Nombre de périodes', 'numeric', ''),
        'txt' => array('Texte', 'string', ''),
    );

    /*     * ************************* Règles ************************* */

    // Règles de l'équipement, nettoyées et typées
    public static function getRegles($_eqLogic) {
        $regles = $_eqLogic->getConfiguration('alertesPerso', array());
        if (is_string($regles)) {
            $regles = json_decode($regles, true);
        }
        if (!is_array($regles)) {
            return array();
        }

        $return = array();
        foreach ($regles as $regle) {
            if (!is_array($regle) OR !isset($regle['id']) OR !is_numeric($regle['id'])) {
                continue;
            }
            $propre = array(
                'id' => intval($regle['id']),
                'nom' => trim(isset($regle['nom']) ? $regle['nom'] : '') ?: __('Alerte', __FILE__) . ' ' . intval($regle['id']),
                'actif' => (!isset($regle['actif']) || $regle['actif'] == 1),
                'widget' => (!isset($regle['widget']) || $regle['widget'] == 1),
                'message' => trim(isset($regle['message']) ? $regle['message'] : ''),
                'pluie' => (isset($regle['pluie']) AND in_array($regle['pluie'], array('oui', 'non'))) ? $regle['pluie'] : '',
                'neige' => (isset($regle['neige']) AND in_array($regle['neige'], array('oui', 'non'))) ? $regle['neige'] : '',
                'directions' => array(),
                'heureMin' => self::nombre($regle, 'heureMin'),
                'heureMax' => self::nombre($regle, 'heureMax'),
                'dureeMin' => max(1, intval(self::nombre($regle, 'dureeMin'))),
            );
            foreach (array_keys(self::$_bornes) as $cle) {
                $propre[$cle] = self::nombre($regle, $cle);
            }
            if (isset($regle['directions']) AND is_array($regle['directions'])) {
                $propre['directions'] = array_values(array_intersect(self::$_directions, $regle['directions']));
            }
            $return[] = $propre;
            if (count($return) >= self::MAX_REGLES) {
                break;
            }
        }
        return $return;
    }

    public static function nombre($_array, $_cle) {
        if (!isset($_array[$_cle])) {
            return NULL;
        }
        $valeur = str_replace(',', '.', trim($_array[$_cle]));
        return is_numeric($valeur) ? floatval($valeur) : NULL;
    }

    // Une règle sans aucune condition ne se déclenche jamais (sinon elle serait vraie en permanence)
    public static function aDesConditions($_regle) {
        if ($_regle['pluie'] != '' OR $_regle['neige'] != '' OR !empty($_regle['directions'])) {
            return TRUE;
        }
        foreach (array_keys(self::$_bornes) as $cle) {
            if ($_regle[$cle] !== NULL) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /*     * ************************* Évaluation ************************* */

    // Liste des heures de prévision à partir de l'heure courante (format pivot + date)
    public static function getHeures($_json, $_nbHeures = 72) {
        $heures = array();
        $date = new DateTime(date('Y-m-d H:00:00'));
        $aujourdhui = new DateTime(date('Y-m-d'));

        for ($i = 0; $i <= $_nbHeures; $i++) {
            $jour = (int) $aujourdhui->diff(new DateTime($date->format('Y-m-d')))->format('%a');
            $data = previsy::getInfoJson($_json, $jour, $date->format('G') . 'H00');
            if ($data['TMP2m'] === NULL AND $data['WNDSPD10m'] === NULL AND $data['APCPsfc'] === NULL) {
                break; // Fin des données disponibles
            }
            $data['DATE'] = clone $date;
            $data['DANS_HEURE'] = $i;
            $heures[] = $data;
            $date->modify('+1 hour');
        }
        return $heures;
    }

    // L'heure respecte-t-elle toutes les conditions de la règle ?
    public static function evaluer($_regle, $_heure) {
        // Plage horaire (gère le passage de minuit, ex. 22h -> 6h)
        if ($_regle['heureMin'] !== NULL OR $_regle['heureMax'] !== NULL) {
            $h = intval($_heure['DATE']->format('G'));
            $min = ($_regle['heureMin'] !== NULL) ? $_regle['heureMin'] : 0;
            $max = ($_regle['heureMax'] !== NULL) ? $_regle['heureMax'] : 23;
            $dansPlage = ($min <= $max) ? ($h >= $min AND $h <= $max) : ($h >= $min OR $h <= $max);
            if (!$dansPlage) {
                return FALSE;
            }
        }

        // Précipitations
        $mm = $_heure['APCPsfc'];
        $neige = ($mm !== NULL AND floatval($mm) > 0 AND intval($_heure['ISSNOW']) == 1);
        $pluie = ($mm !== NULL AND floatval($mm) > 0 AND !$neige);
        if ($_regle['pluie'] != '' AND ($mm === NULL OR ($_regle['pluie'] == 'oui') != $pluie)) {
            return FALSE;
        }
        if ($_regle['neige'] != '' AND ($mm === NULL OR ($_regle['neige'] == 'oui') != $neige)) {
            return FALSE;
        }

        // Seuils numériques (donnée absente = condition non remplie)
        foreach (self::$_bornes as $cle => $def) {
            if ($_regle[$cle] === NULL) {
                continue;
            }
            $valeur = $_heure[$def[0]];
            if (!is_numeric($valeur)) {
                return FALSE;
            }
            if ($def[1] == '>=' AND floatval($valeur) < $_regle[$cle]) {
                return FALSE;
            }
            if ($def[1] == '<=' AND floatval($valeur) > $_regle[$cle]) {
                return FALSE;
            }
        }

        // Direction du vent (calculée depuis les degrés : identique quelle que soit l'API)
        if (!empty($_regle['directions'])) {
            $cardinal = previsy_bridge::degresToCardinal($_heure['WNDDIR10m']);
            if ($cardinal === NULL OR !in_array($cardinal, $_regle['directions'])) {
                return FALSE;
            }
        }

        return TRUE;
    }

    // Périodes (heures consécutives) où la règle est vraie, d'au moins dureeMin heures
    public static function getPeriodes($_regle, $_heures) {
        $periodes = array();
        $courante = NULL;
        foreach ($_heures as $heure) {
            if (self::evaluer($_regle, $heure)) {
                if ($courante === NULL) {
                    $courante = array('debut' => $heure, 'heures' => array());
                }
                $courante['heures'][] = $heure;
            } elseif ($courante !== NULL) {
                $periodes[] = $courante;
                $courante = NULL;
            }
        }
        if ($courante !== NULL) {
            $periodes[] = $courante;
        }

        $return = array();
        foreach ($periodes as $periode) {
            $nb = count($periode['heures']);
            if ($nb < $_regle['dureeMin']) {
                continue;
            }
            $derniere = $periode['heures'][$nb - 1];
            $fin = clone $derniere['DATE'];
            $fin->modify('+1 hour');
            $return[] = array(
                'debut' => $periode['debut']['DATE'],
                'fin' => $fin,
                'dans_heure' => $periode['debut']['DANS_HEURE'],
                'duree' => $nb,
            );
        }
        return $return;
    }

    /*     * ************************* Commandes ************************* */

    public static function logicalId($_regle, $_info) {
        return 'perso_' . $_regle['id'] . '_' . $_info;
    }

    // Crée / renomme les commandes des règles (appelé dans postSave)
    public static function createCmds($_eqLogic) {
        $noms = array();
        foreach (self::getRegles($_eqLogic) as $regle) {
            $nom = $regle['nom'];
            if (isset($noms[mb_strtolower($nom)])) {
                $nom .= ' #' . $regle['id']; // Les noms de commandes doivent être uniques
            }
            $noms[mb_strtolower($nom)] = TRUE;

            foreach (self::$_infos as $suffixe => $def) {
                $logicalId = self::logicalId($regle, $suffixe);
                $cmd = $_eqLogic->getCmd(null, $logicalId);
                if (!is_object($cmd)) {
                    $cmd = new previsyCmd();
                    $cmd->setLogicalId($logicalId);
                    $cmd->setEqLogic_id($_eqLogic->getId());
                    $cmd->setType('info');
                    $cmd->setIsVisible(0);
                    $cmd->setIsHistorized(0);
                }
                $cmd->setName(mb_substr($nom . ' - ' . __($def[0], __FILE__), 0, 127));
                $cmd->setSubType($def[1]);
                if ($def[2] != '') {
                    $cmd->setUnite($def[2]);
                }
                $cmd->save();
            }
        }
    }

    // Supprime les commandes des règles supprimées (appelé dans postAjax, après l'enregistrement des commandes par Jeedom)
    public static function removeObsoleteCmds($_eqLogic) {
        $ids = array();
        foreach (self::getRegles($_eqLogic) as $regle) {
            $ids[] = $regle['id'];
        }
        foreach ($_eqLogic->getCmd() as $cmd) {
            if (preg_match('/^perso_(\d+)_/', $cmd->getLogicalId(), $match) AND !in_array(intval($match[1]), $ids)) {
                log::add('previsy', 'debug', 'previsy_alertes::removeObsoleteCmds :. ' . $cmd->getLogicalId());
                $cmd->remove();
            }
        }
    }

    /*     * ************************* Mise à jour ************************* */

    // Calcule toutes les règles et met à jour leurs commandes. Retourne les résultats (pour le widget).
    public static function update($_eqLogic, $_json) {
        $regles = self::getRegles($_eqLogic);
        if (empty($regles)) {
            return array();
        }
        $heures = self::getHeures($_json);
        $lang = new previsy_language;
        $resultats = array();

        foreach ($regles as $regle) {
            $periodes = ($regle['actif'] AND self::aDesConditions($regle)) ? self::getPeriodes($regle, $heures) : array();
            $premiere = isset($periodes[0]) ? $periodes[0] : NULL;
            $txt = empty($heures) ? $regle['nom'] . ' : ' . __('données météo indisponibles.', __FILE__) : self::phrase($regle, $premiere, $lang);

            $valeurs = array(
                'en_cours' => ($premiere !== NULL AND $premiere['dans_heure'] == 0) ? 1 : 0,
                'dans_heure' => ($premiere !== NULL) ? $premiere['dans_heure'] : '',
                'date_start' => ($premiere !== NULL) ? $premiere['debut']->format('YmdH') . '00' : '',
                'date_end' => ($premiere !== NULL) ? $premiere['fin']->format('YmdH') . '00' : '',
                'duree' => ($premiere !== NULL) ? $premiere['duree'] : 0,
                'nb' => count($periodes),
                'txt' => $txt,
            );
            foreach ($valeurs as $suffixe => $valeur) {
                $_eqLogic->checkAndUpdateCmd(self::logicalId($regle, $suffixe), $valeur);
            }

            log::add('previsy', 'debug', 'previsy_alertes::update :. [' . $regle['nom'] . '] ' . count($periodes) . ' période(s) - ' . $txt);
            $resultats[] = array('regle' => $regle, 'periodes' => $periodes, 'txt' => $txt);
        }
        return $resultats;
    }

    public static function phrase($_regle, $_periode, $_lang) {
        if (!$_regle['actif']) {
            return $_regle['nom'] . ' : ' . __('alerte désactivée.', __FILE__);
        }
        if (!self::aDesConditions($_regle)) {
            return $_regle['nom'] . ' : ' . __('aucune condition définie.', __FILE__);
        }
        if ($_periode === NULL) {
            return $_regle['nom'] . ' : ' . __('pas de période correspondante dans les 72 prochaines heures.', __FILE__);
        }

        $fin = $_lang->getDate($_periode['fin']);
        $heures = $_periode['duree'] . ' ' . (($_periode['duree'] > 1) ? __('heures', __FILE__) : __('heure', __FILE__));
        if ($_periode['dans_heure'] == 0) {
            $txt = $_regle['nom'] . ' : ' . __('en ce moment, jusqu\'au', __FILE__) . ' ' . $fin['JOUR_TXT'] . ' (' . $heures . ').';
        } else {
            $debut = $_lang->getDate($_periode['debut']);
            $txt = $_regle['nom'] . ' : ' . __('dans', __FILE__) . ' ' . $_periode['dans_heure'] . ' h, ' . __('du', __FILE__) . ' ' . $debut['JOUR_TXT'] . ' ' . __('au', __FILE__) . ' ' . $fin['JOUR_TXT'] . ' (' . $heures . ').';
        }
        if ($_regle['message'] != '') {
            $txt .= ' ' . $_regle['message'];
        }
        return $txt;
    }

    /*     * ************************* Widget ************************* */

    public static function getWidget($_resultats) {
        $lignes = '';
        foreach ($_resultats as $resultat) {
            if (!$resultat['regle']['widget'] OR !$resultat['regle']['actif'] OR !self::aDesConditions($resultat['regle'])) {
                continue;
            }
            $periode = isset($resultat['periodes'][0]) ? $resultat['periodes'][0] : NULL;
            if ($periode === NULL) {
                $etat = "<span style='opacity:0.6;'>" . __('Rien de prévu sur 72 h', __FILE__) . "</span>";
                $icone = 'far fa-bell-slash';
            } elseif ($periode['dans_heure'] == 0) {
                $etat = "<span style='font-weight:bold;'>" . __('En cours', __FILE__) . "</span>, " . __('jusqu\'à', __FILE__) . ' ' . $periode['fin']->format('H') . 'H00 (' . $periode['duree'] . ' h)';
                $icone = 'fas fa-bell';
            } else {
                $etat = __('Dans', __FILE__) . " <span style='font-weight:bold;'>" . $periode['dans_heure'] . ' h</span> : '
                        . $periode['debut']->format('d/m') . ' ' . $periode['debut']->format('H') . 'H00 &rarr; ' . $periode['fin']->format('H') . 'H00 (' . $periode['duree'] . ' h)';
                $icone = 'far fa-bell';
            }
            $nb = count($resultat['periodes']) > 1 ? " <span style='opacity:0.6;'>(" . count($resultat['periodes']) . ' ' . __('périodes', __FILE__) . ')</span>' : '';
            $lignes .= "<div style='margin:2px 0;' title='" . htmlspecialchars($resultat['txt'], ENT_QUOTES) . "'><i class='" . $icone . "'></i> <span style='font-weight:bold;'>"
                    . htmlspecialchars($resultat['regle']['nom']) . "</span> : " . $etat . $nb . "</div>";
        }
        if ($lignes == '') {
            return '';
        }
        return "<div class='previsyWidget previsyPerso' style='width:calc(100% - 10px); box-sizing:border-box; text-align:left;'>" . $lignes . "</div>";
    }

}
