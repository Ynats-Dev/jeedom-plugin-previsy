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
# Bridge prevision-meteo.ch (France, Belgique, Suisse)
# Documentation : https://www.prevision-meteo.ch/uploads/pdf/recuperation-donnees-meteo.pdf
# Le Json de cette API est déjà au format pivot de Previsy : il est transmis tel quel.
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class previsy_bridge_prevision_meteo_ch {

    public static $_urlApi = "https://www.prevision-meteo.ch/services/json/";

    public static function getLabel() {
        return 'prevision-meteo.ch';
    }

    public static function fetch($_eqLogic) {
        $localisation = previsy_bridge::getLocalisation($_eqLogic);

        if ($localisation['latitude'] !== NULL) {
            // Format imposé par l'API : lat=46.259lng=5.235 (sans séparateur)
            $searchBy = "lat=" . $localisation['latitude'] . "lng=" . $localisation['longitude'];
        } elseif ($localisation['ville'] != '') {
            $searchBy = rawurlencode($localisation['ville']);
        } else {
            return previsy_bridge::error(__('Aucune ville ni coordonnées renseignées', __FILE__));
        }

        return previsy_bridge::httpGetJson(self::$_urlApi . $searchBy);
    }

}
