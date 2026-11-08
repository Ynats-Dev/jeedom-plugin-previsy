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
# Bridge Open-Meteo (monde entier, gratuit, sans clé API)
# Documentation : https://open-meteo.com/en/docs
# Géocodage     : https://open-meteo.com/en/docs/geocoding-api
# Convertit la réponse Open-Meteo au format pivot de Previsy (voir previsy.bridge.php).
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class previsy_bridge_open_meteo {

    public static $_urlApi = "https://api.open-meteo.com/v1/forecast";
    public static $_urlGeocoding = "https://geocoding-api.open-meteo.com/v1/search";
    public static $_nbJour = 5;

    public static $_hourly = array(
        'temperature_2m', 'relative_humidity_2m', 'dew_point_2m', 'apparent_temperature',
        'precipitation', 'rain', 'showers', 'snowfall', 'weather_code', 'pressure_msl',
        'wind_speed_10m', 'wind_direction_10m', 'wind_gusts_10m',
    );

    public static function getLabel() {
        return 'Open-Meteo';
    }

    public static function fetch($_eqLogic) {
        $localisation = previsy_bridge::getLocalisation($_eqLogic);
        $city = array('name' => $localisation['ville'], 'country' => NULL);

        if ($localisation['latitude'] === NULL) {
            if ($localisation['ville'] == '') {
                return previsy_bridge::error(__('Aucune ville ni coordonnées renseignées', __FILE__));
            }
            $geo = self::geocode($localisation['ville']);
            if ($geo === NULL) {
                return NULL; // API injoignable : on garde le cache existant
            }
            if ($geo === FALSE) {
                return previsy_bridge::error(__('Ville introuvable : ', __FILE__) . $localisation['ville']);
            }
            $localisation['latitude'] = $geo['latitude'];
            $localisation['longitude'] = $geo['longitude'];
            $city = array('name' => $geo['name'], 'country' => $geo['country']);
        }

        $url = self::$_urlApi
                . '?latitude=' . $localisation['latitude']
                . '&longitude=' . $localisation['longitude']
                . '&hourly=' . implode(',', self::$_hourly)
                . '&daily=sunrise,sunset'
                . '&wind_speed_unit=kmh'
                . '&forecast_days=' . self::$_nbJour
                // Heures exprimées dans le fuseau de Jeedom, comme le reste du plugin
                . '&timezone=' . rawurlencode(date_default_timezone_get());

        $json = previsy_bridge::httpGetJson($url);
        if ($json === NULL) {
            return NULL;
        }
        if (!empty($json->error)) {
            // Quota dépassé, service en erreur... : traité comme une indisponibilité (repli possible)
            log::add('previsy', 'warning', 'previsy_bridge_open_meteo::fetch :. ' . (isset($json->reason) ? $json->reason : __('erreur inconnue', __FILE__)));
            return NULL;
        }

        return self::mapping($json, $city);
    }

    // Retourne array(latitude, longitude, name, country), FALSE si ville inconnue, NULL si API injoignable
    public static function geocode($_ville) {
        // Le champ ville est normalisé (minuscules, tirets) : "le-havre" -> "le havre"
        $name = str_replace('-', ' ', $_ville);
        $lang = (class_exists('translate') AND translate::getLanguage() == 'en_US') ? 'en' : 'fr';
        $json = previsy_bridge::httpGetJson(self::$_urlGeocoding . '?name=' . rawurlencode($name) . '&count=1&format=json&language=' . $lang);
        if ($json === NULL) {
            return NULL;
        }
        if (empty($json->results[0]->latitude)) {
            return FALSE;
        }
        $result = $json->results[0];
        log::add('previsy', 'debug', 'previsy_bridge_open_meteo::geocode :. ' . $_ville . ' -> ' . $result->name . ' (' . $result->latitude . ', ' . $result->longitude . ')');
        return array(
            'latitude' => $result->latitude,
            'longitude' => $result->longitude,
            'name' => $result->name,
            'country' => isset($result->country) ? $result->country : NULL,
        );
    }

    // Conversion Open-Meteo -> format pivot
    public static function mapping($_json, $_city) {
        $return = new stdClass();

        $sunrise = isset($_json->daily->sunrise[0]) ? substr($_json->daily->sunrise[0], 11, 5) : NULL;
        $sunset = isset($_json->daily->sunset[0]) ? substr($_json->daily->sunset[0], 11, 5) : NULL;

        $return->city_info = (object) array(
            'name' => $_city['name'],
            'country' => $_city['country'],
            'latitude' => $_json->latitude,
            'longitude' => $_json->longitude,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
        );

        $today = new DateTime(date('Y-m-d'));
        $hourly = $_json->hourly;

        foreach ($hourly->time as $index => $time) { // ex. "2026-11-08T05:00"
            $date = new DateTime(substr($time, 0, 10));
            $jour = (int) $today->diff($date)->format('%r%a'); // 0 = aujourd'hui
            if ($jour < 0) {
                continue;
            }

            $dayKey = 'fcst_day_' . $jour;
            if (!isset($return->{$dayKey})) {
                $return->{$dayKey} = (object) array('date' => $date->format('d.m.Y'), 'hourly_data' => new stdClass());
            }

            $v = function ($_key) use ($hourly, $index) {
                return isset($hourly->{$_key}[$index]) ? $hourly->{$_key}[$index] : NULL;
            };

            $precipitation = $v('precipitation');
            $snowfall = $v('snowfall');
            $pluie = floatval($v('rain')) + floatval($v('showers'));
            list($condition, $conditionKey) = self::condition($v('weather_code'), $precipitation, $pluie, $snowfall);

            $heureKey = intval(substr($time, 11, 2)) . 'H00'; // "5H00"
            $return->{$dayKey}->hourly_data->{$heureKey} = (object) array(
                'CONDITION' => $condition,
                'CONDITION_KEY' => $conditionKey,
                'TMP2m' => $v('temperature_2m'),
                'DPT2m' => $v('dew_point_2m'),
                'WNDCHILL2m' => $v('apparent_temperature'),
                'RH2m' => $v('relative_humidity_2m'),
                'PRMSL' => $v('pressure_msl'),
                'APCPsfc' => $precipitation,
                'ISSNOW' => (floatval($snowfall) > 0) ? 1 : 0,
                'WNDSPD10m' => $v('wind_speed_10m'),
                'WNDGUST10m' => $v('wind_gusts_10m'),
                'WNDDIR10m' => $v('wind_direction_10m'),
                'WNDDIRCARD10' => previsy_bridge::degresToCardinal($v('wind_direction_10m')),
            );
        }

        return $return;
    }

    // Code météo WMO -> libellé compris par previsy_language::infosCondition()
    public static function condition($_code, $_precipitation, $_pluie, $_neige) {
        if ($_code === NULL) {
            return array(NULL, NULL);
        }
        $code = (int) $_code;

        // Pluie et neige en même temps : WMO n'a pas de code dédié
        if (floatval($_pluie) > 0 AND floatval($_neige) > 0 AND $code >= 51 AND $code <= 86) {
            $mm = floatval($_precipitation);
            if ($mm < 1) {
                return array('Pluie et neige mêlée faible', 'pluie-neige-faible');
            } elseif ($mm < 4) {
                return array('Pluie et neige mêlée modérée', 'pluie-neige-moderee');
            }
            return array('Pluie et neige mêlée forte', 'pluie-neige-forte');
        }

        switch ($code) {
            case 0: return array('Ensoleillé', 'ensoleille');
            case 1: return array('Ciel voilé', 'ciel-voile');
            case 2: return array('Faiblement nuageux', 'faiblement-nuageux');
            case 3: return array('Fortement nuageux', 'fortement-nuageux');
            case 45:
            case 48: return array('Brouillard', 'brouillard');
            case 51:
            case 53:
            case 55:
            case 56:
            case 57:
            case 61: return array('Pluie faible', 'pluie-faible');
            case 63:
            case 66: return array('Pluie modérée', 'pluie-moderee');
            case 65:
            case 67: return array('Pluie forte', 'pluie-forte');
            case 71:
            case 77: return array('Neige faible', 'neige-faible');
            case 73: return array('Neige modérée', 'neige-moderee');
            case 75:
            case 86: return array('Neige forte', 'neige-forte');
            case 80: return array('Averses de pluie faible', 'averses-de-pluie-faible');
            case 81: return array('Averses de pluie modérée', 'averses-de-pluie-moderee');
            case 82: return array('Averses de pluie forte', 'averses-de-pluie-forte');
            case 85: return array('Averses de neige faible', 'averses-de-neige-faible');
            case 95: return array('Orage modéré', 'orage-modere');
            case 96:
            case 99: return array('Fortement orageux', 'fortement-orageux');
        }
        return array(NULL, NULL);
    }

}
