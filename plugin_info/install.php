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

function previsy_install() {
    // Nouvelle installation : Open-Meteo pour tous les équipements, avec repli sur prevision-meteo.ch
    require_once __DIR__ . '/../core/class/bridges/previsy.bridge.php';
    config::save('api_mode', previsy_bridge::DEFAULT_API, 'previsy');
    config::save('api_repli', 1, 'previsy');
}

function previsy_update() {
    if(@glob(__DIR__ . "/../../../plugins/previsy/core/json/*")){
        shell_exec("sudo chmod 777 -R ". __DIR__ . "/../../../plugins/previsy/data");
        shell_exec("sudo mv " . dirname(__DIR__) . "/../../../plugins/previsy/core/json/*.json " . dirname(__DIR__) . "/../../../plugins/previsy/data/json");
    }

    // Choix de l'API météo : les équipements existants restent sur prevision-meteo.ch
    // (sinon la liste déroulante afficherait Open-Meteo et le basculerait à la prochaine sauvegarde)
    require_once __DIR__ . '/../core/class/bridges/previsy.bridge.php';
    // Mise à jour : on garde le choix d'API par équipement tant que l'utilisateur ne choisit pas une API globale
    if (config::byKey('api_mode', 'previsy', '') == '') {
        config::save('api_mode', previsy_bridge::MODE_EQUIPEMENT, 'previsy');
    }
    if (config::byKey('api_repli', 'previsy', '') === '') {
        config::save('api_repli', 1, 'previsy');
    }
    foreach (eqLogic::byType('previsy') as $eqLogic) {
        if ($eqLogic->getConfiguration('api', '') == '') {
            $eqLogic->setConfiguration('api', previsy_bridge::LEGACY_API);
            $eqLogic->save(true);
        }
    }
}

function previsy_remove() {
    
}

?>
