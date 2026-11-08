<?php

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
# Documentation : https://www.prevision-meteo.ch/uploads/pdf/recuperation-donnees-meteo.pdf
# API : https://www.prevision-meteo.ch/services/json/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class previsy_language {

    public static function test() {
        return "Calss chargée";
    }

    public static function getDate($_date) {
        $return["HEURE"] = $_date->format('H:i');
        $return["DATE"] = $_date->format('d/m/Y');
        $return["JOUR"] = $_date->format('l');
		
		$language = translate::getLanguage();
		if($language == "en_US") {
			$return["JOUR_TXT"] = $_date->format('l') . " " . $_date->format('d') . " " . $_date->format('F') . " " . $_date->format('Y') . " at " . $_date->format('H') . "H00";
		} else {
			$return["JOUR_TXT"] = self::traduitDateTimeJour($_date->format('l')) . " " . $_date->format('d') . " " . self::traduitDateTimeMois($_date->format('F')) . " " . $_date->format('Y') . __(" à",  __FILE__) . " " .$_date->format('H') . "H00";
		}
        return $return;
    }

    public static function traduitDateTimeMois($_string) {
        $english_months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $translate_months = array(__('Janvier',  __FILE__), __('Février',  __FILE__), __('Mars',  __FILE__), __('Avril',  __FILE__), __('Mai',  __FILE__), __('Juin',  __FILE__), __('Juillet',  __FILE__), __('Août',  __FILE__), __('Septembre',  __FILE__), __('Octobre',  __FILE__), __('Novembre',  __FILE__), __('Décembre',  __FILE__));
        return str_replace($english_months, $translate_months, $_string);
    }

    public static function traduitDateTimeJour($_string) {
        $english_months = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $translate_months = array(__('Lundi',  __FILE__), __('Mardi',  __FILE__), __('Mercredi',  __FILE__), __('Jeudi',  __FILE__), __('Vendredi',  __FILE__), __('Samedi',  __FILE__), __('Dimanche',  __FILE__));
        return str_replace($english_months, $translate_months, $_string);
    }

    public static function infosCondition($_condition) {
        switch ($_condition) {
            case "Brouillard":
                $return["TXT"] = __("il va y avoir du brouillard",  __FILE__);
                $return["ALERTE"] = "brouillard";
                break;
            case "Averses de pluie faible":
                $return["TXT"] = __("il y aura de faibles averses de pluie",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Nuit avec averses":
                $return["TXT"] = __("il y aura des averses durant la nuit",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Averses de pluie modérée":
                $return["TXT"] = __("il y aura des averses de pluie modérées",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Averses de pluie forte":
                $return["TXT"] = __("il y aura de fortes averses de pluie",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Couvert avec averses":
                $return["TXT"] = __("le ciel sera couvert avec des averses",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Pluie faible":
                $return["TXT"] = __("il y aura de faibles précipitations de pluie",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Pluie forte":
                $return["TXT"] = __("il y aura de fortes précipitations de pluie",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Pluie modérée":
                $return["TXT"] = __("il va pleuvoir",  __FILE__);
                $return["ALERTE"] = "pluie";
                break;
            case "Faiblement orageux":
                $return["TXT"] = __("le ciel sera faiblement orageux",  __FILE__);
                $return["ALERTE"] = "orage";
                break;
            case "Nuit faiblement orageuse":
                $return["TXT"] = __("la nuit sera faiblement orageuse",  __FILE__);
                $return["ALERTE"] = "orage";
                break;
            case "Orage modéré":
                $return["TXT"] = __("le ciel sera orageux",  __FILE__);
                $return["ALERTE"] = "orage";
                break;
            case "Fortement orageux":
                $return["TXT"] = __("le ciel sera fortement orageux",  __FILE__);
                $return["ALERTE"] = "orage";
                break;
            case "Averses de neige faible":
                $return["TXT"] = __("il y aura de faibles averses de neige",  __FILE__);
                $return["ALERTE"] = "neige";
                break;
            case "Nuit avec averses de neige faible":
                $return["TXT"] = __("cette nuit, il y aura de faibles averses de neige",  __FILE__);
                $return["ALERTE"] = "neige";
                break;
            case "Neige faible":
                $return["TXT"] = __("il va neiger un petit peu",  __FILE__);
                $return["ALERTE"] = "neige";
                break;
            case "Neige modérée":
                $return["TXT"] = __("il va neiger",  __FILE__);
                $return["ALERTE"] = "neige";
                break;
            case "Neige forte":
                $return["TXT"] = __("il y aura de forte tombé de neige",  __FILE__);
                $return["ALERTE"] = "neige";
                break;
            case "Pluie et neige mêlée faible":
                $return["TXT"] = __("il y aura de la pluie mélée à un petit peu de neige",  __FILE__);
                $return["ALERTE"] = "neige_pluie";
                break;
            case "Pluie et neige mêlée modérée":
                $return["TXT"] = __("il y aura de la pluie mélée à de la neige",  __FILE__);
                $return["ALERTE"] = "neige_pluie";
                break;
            case "Pluie et neige mêlée forte":
                $return["TXT"] = __("il y aura de la pluie mélée à de grosses tombées de neige",  __FILE__);
                $return["ALERTE"] = "neige_pluie";
                break;
            default:
                return NULL;
        }

        return $return;
    }
    
    public static function echelleBeaufort($_input) {
        if ($_input < 1) {
            return __('calme',  __FILE__);
        } elseif ($_input >= 1 AND $_input < 6) {
            return __('très légère brise',  __FILE__);
        } elseif ($_input >= 6 AND $_input < 12) {
            return __('légère brise',  __FILE__);
        } elseif ($_input >= 12 AND $_input < 20) {
            return __('petite brise',  __FILE__);
        } elseif ($_input >= 20 AND $_input < 29) {
            return __('jolie brise',  __FILE__);
        } elseif ($_input >= 29 AND $_input < 39) {
            return __('bonne brise',  __FILE__);
        } elseif ($_input >= 39 AND $_input < 50) {
            return __('vent frais',  __FILE__);
        } elseif ($_input >= 50 AND $_input < 62) {
            return __('grand vent frais',  __FILE__);
        } elseif ($_input >= 62 AND $_input < 75) {
            return __('coup de vent',  __FILE__);
        } elseif ($_input >= 75 AND $_input < 89) {
            return __('fort coup de vent',  __FILE__);
        } elseif ($_input >= 89 AND $_input < 103) {
            return __('tempête',  __FILE__);
        } elseif ($_input >= 103 AND $_input < 118) {
            return __('violente tempête',  __FILE__);
        } elseif ($_input >= 118) {
            return __('ouragan',  __FILE__);
        }
    }
	
    public static function formatHeure_txt($_heure) {

        if ($_heure == 0) {
            return __("minuit",  __FILE__);
        } elseif ($_heure == 1) {
            return $_heure . __(" heure",  __FILE__);
        } else {
            return $_heure . __(" heures",  __FILE__);
        }
    }

    public static function constructTxt($_input, $_degre = "°C") {
        
        $return["START"] = NULL;
        
        if ($_input["DANS_HEURE"] > 0 AND $_input["DANS_HEURE"] <= 6) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures,",  __FILE__);
        } elseif ($_input["DANS_JOUR"] == 0 AND $_input["DANS_HEURE"] > 6) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures, soit aujourd'hui à partir de ",  __FILE__) . self::formatHeure_txt($_input["HEURE"]) . ",";
        } elseif ($_input["DANS_JOUR"] == 1 AND $_input["DANS_HEURE"] < 6) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures, soit tout à l'heure à ",  __FILE__) . self::formatHeure_txt($_input["HEURE"]) . ",";
        } elseif ($_input["DANS_JOUR"] == 1) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures, soit demain à partir de ",  __FILE__) . self::formatHeure_txt($_input["HEURE"]) . ",";
        } elseif ($_input["DANS_JOUR"] == 2) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures, soit après-demain à partir de ",  __FILE__) . self::formatHeure_txt($_input["HEURE"]) . ",";
        } elseif ($_input["DANS_JOUR"] > 2) {
            $return["START"] = __("Dans ",  __FILE__) . $_input["DANS_HEURE"] . __(" heures, soit ",  __FILE__) . $_input["START_TXT"] . ",";
        }

        if ($_input["DANS_HEURE"] > 0) {
            if ($_input["TYPE"] == "pluie") {
                $return["START"] .= __(" il va pleuvoir durant ",  __FILE__) . $_input["DUREE_HEURE"];
            } elseif ($_input["TYPE"] == "orage") {
                $return["START"] .= __(" un orage est prévu durant ",  __FILE__) . $_input["DUREE_HEURE"];
            } elseif ($_input["TYPE"] == "neige") {
                $return["START"] .= __(" il va neiger durant ",  __FILE__) . $_input["DUREE_HEURE"];
            } elseif ($_input["TYPE"] == "neige_pluie") {
                $return["START"] .= __(" un mélange de neige et de pluie est prévu durant ",  __FILE__) . $_input["DUREE_HEURE"];
            } elseif ($_input["TYPE"] == "brouillard") {
                $return["START"] .= __(" un brouillard est prévu durant ",  __FILE__) . $_input["DUREE_HEURE"];
            } elseif ($_input["TYPE"] == "vent") {
                $return["START"] .= __(" il va y avoir du vent pendant ",  __FILE__) . $_input["DUREE_HEURE"];
            }
			if ($_input["DUREE_HEURE"] > 1) {
				$return["START"] .= __(" heures",  __FILE__);
			} else {
				$return["START"] .= __(" heure",  __FILE__);
			}
			$return["START"] .= ". ";
        }
        else{
            if ($_input["TYPE"] == "pluie") {
                $return["START"] .= __("Il est en train de pleuvoir mais cela va se calmer d'ici peu de temps.",  __FILE__);
            } elseif ($_input["TYPE"] == "orage") {
                $return["START"] .= __("Il y a un orage qui va s'arrêter d'ici peu de temps.",  __FILE__);
            } elseif ($_input["TYPE"] == "neige") {
                $return["START"] .= __("Il neige mais cela va s'arrêter d'ici peu de temps.",  __FILE__);
            } elseif ($_input["TYPE"] == "neige_pluie") {
                $return["START"] .= __("Il y a un mélange de neige et de pluie qui va s'arrêter d'ici peu de temps.",  __FILE__);
            } elseif ($_input["TYPE"] == "brouillard") {
                $return["START"] .= __("Il y a du brouillard mais cela va s'arrêter d'ici peu de temps.",  __FILE__);
            }            
        }
        
        $return["MM"] = NULL;
        
        if(!empty($_input["MM"]["TOTAL"]) AND $_input["MM"]["TOTAL"] > 0){ // MM vaut NULL pour une alerte sans précipitation (vent, brouillard)
            $return["MM"] .= __("Il y aura un total de ",  __FILE__) . number_format(@$_input["MM"]["TOTAL"], 1) . __(" millimètre",  __FILE__);
            if ($_input["MM"]["TOTAL"] > 1) {
                $return["MM"] .= "s";
            }
            $return["MM"] .= __(" de précipitation",  __FILE__); 
            
            if ($_input["DUREE_HEURE"] > 1 AND $_input["MM"]["MOY"] != $_input["MM"]["TOTAL"]) {
            $return["MM"] .= __(" soit une moyenne de ",  __FILE__) . number_format(@$_input["MM"]["MOY"], 1) . __(" millimètre",  __FILE__);
            if ($_input["MM"]["MOY"] > 1) {
                $return["MM"] .= "s";
            }
            $return["MM"] .= __(" au m² et par heure.",  __FILE__);

            if ($_input["MM"]["CONDITION_MAX_TXT"] != $_input["MM"]["CONDITION_MIN_TXT"]) {
                $return["MM"] .= __(" Au plus bas, ",  __FILE__) . $_input["MM"]["CONDITION_MIN_TXT"] . __(", avec ",  __FILE__) . number_format($_input["MM"]["MIN"], 1) . __(" millimètre",  __FILE__);
                if ($_input["MM"]["MIN"] > 1) {
                    $return["MM"] .= "s";
                }
                $return["MM"] .= __(" de précipitation. ",  __FILE__);

                $return["MM"] .= __(" Au plus haut, ",  __FILE__) . $_input["MM"]["CONDITION_MAX_TXT"] . __(", avec ",  __FILE__) . number_format($_input["MM"]["MAX"], 1) . __(" millimètre",  __FILE__);
                if ($_input["MM"]["MAX"] > 1) {
                    $return["MM"] .= "s";
                }
                $return["MM"] .= __(" de précipitation.",  __FILE__);
            } else {
                $return["MM"] .= __(" En résumé, ",  __FILE__) . $_input["MM"]["CONDITION_MAX_TXT"] . __(", avec des précipitations allant de ",  __FILE__) . number_format($_input["MM"]["MIN"], 1);
                $return["MM"] .= __(" à ",  __FILE__) . $_input["MM"]["MAX"] . __(" millimètre",  __FILE__);
                if ($_input["MM"]["MAX"] > 1) {
                    $return["MM"] .= "s";
                }
                $return["MM"] .= ". ";
            }
        } else {
            $return["MM"] .= ". ";
        }
            
        } else {
            $return["MM"] = NULL;
        }
                 
        $return["TEMPERATURE"] = NULL;
        
        if ($_input["TEMPERATURE"]["MIN"] != $_input["TEMPERATURE"]["MAX"]) {
            $return["TEMPERATURE"] = __("La température moyenne sera de ",  __FILE__) . number_format($_input["TEMPERATURE"]["MOY"], 1) . $_degre;
            $return["TEMPERATURE"] .= __(" allant d'une amplitude de ",  __FILE__) . number_format($_input["TEMPERATURE"]["MIN"], 1) . $_degre . __(" à ",  __FILE__) . number_format($_input["TEMPERATURE"]["MAX"], 1) . $_degre . ". ";
        } else {
            $return["TEMPERATURE"] = __("La température sera de ",  __FILE__) . number_format($_input["TEMPERATURE"]["MOY"], 1) . $_degre . ". ";
        }

        $return["HUMIDITE"] = NULL;
        
        if ($_input["HUMIDITE"]["MIN"] != $_input["HUMIDITE"]["MAX"]) {
            $return["HUMIDITE"] = __("Le taux d'humidité sera en moyenne à ",  __FILE__) . number_format($_input["HUMIDITE"]["MOY"], 1) . "%";
            $return["HUMIDITE"] .= __(" allant d'une amplitude de ",  __FILE__) . number_format($_input["HUMIDITE"]["MIN"], 1) . "%" . __(" à ",  __FILE__) . number_format($_input["HUMIDITE"]["MAX"], 1) . "%. ";
        } else {
            $return["HUMIDITE"] = __("Le taux d'humidité sera de ",  __FILE__) . number_format($_input["HUMIDITE"]["MOY"], 1) . "%. ";
        }

        $return["VENT"] = NULL;
        
        $return["VENT"] = __("Le vent soufflera en moyenne à ",  __FILE__) . number_format($_input["VENT_VITESSE"]["MOY"], 1) . " km/h";
        $return["VENT"] .= __(" avec des rafales pouvant aller jusqu'à ",  __FILE__) . number_format($_input["VENT_RAFALES"]["MAX"], 1) . " km/h. ";

        $return["FULL"] = @$return["START"] . @$return["MM"] . @$return["HUMIDITE"] . @$return["TEMPERATURE"] . @$return["VENT"];

        return $return;
    }

}

?>