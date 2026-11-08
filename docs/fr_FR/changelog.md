# 08/11/2026 | Beta 2.0.0
- NOUVEAU : Widget pour l'application mobile.
- NOUVEAU : La commande Rafraichir recharge réellement les prévisions depuis l'API (au plus une fois toutes les 5 minutes).
- NOUVEAU : Suggestions de zones de vigilance pour le pays choisi.
- NOUVEAU : Couleur de vigilance traduite selon la langue de Jeedom.
- NOUVEAU : Alertes personnalisées (onglet « Mes alertes ») : combinez pluie, neige, vent, rafales, direction du vent, température, température ressentie, humidité, pression, plage horaire et durée minimale. Chaque alerte a ses propres commandes.
- NOUVEAU : Vigilance météo nationale via MeteoAlarm (Météo-France, IRM, MétéoSuisse...) : bandeau dans le widget et commandes dédiées. En France, le département est détecté automatiquement.
- NOUVEAU : Possibilité de désactiver les alertes météo standards et l'alerte vent.
- NOUVEAU : Choix de l'API météo dans la configuration du plugin, pour tous les équipements d'un coup : Open-Meteo (monde entier, sans clé, recommandé) ou prevision-meteo.ch, ou choix par équipement. Une mise à jour conserve le fonctionnement actuel (choix par équipement, équipements existants sur prevision-meteo.ch). Architecture « bridge » pour ajouter d'autres API.
- NOUVEAU : Repli automatique sur l'autre API en cas d'indisponibilité, avec warning dans les logs, message dans le centre de messages, mention dans le widget et commande « API utilisée ». Retour automatique sur l'API choisie dès qu'elle répond.
- NOUVEAU : Le cache est rechargé automatiquement quand l'API ou le lieu change.
- BUG : Le niveau minimum de vigilance s'affichait vide sur un équipement existant ; les commandes « API utilisée » et « Type_degre » ne sont plus supprimables ; les cadres de vigilance et d'alertes personnalisées ne débordent plus du widget ; l'icône de rafraîchissement est de nouveau dans le bandeau de titre du widget.
- BUG : Un équipement défini uniquement par ses coordonnées n'était pas mis à jour à la sauvegarde.
- BUG : Warning PHP dans le texte prédictif d'une alerte sans précipitation (vent, brouillard).
- BUG : Les erreurs « ville introuvable » de prevision-meteo.ch n'étaient pas détectées.
- BUG : Les coordonnées négatives (longitude Ouest, hémisphère Sud) ne pouvaient pas être saisies.
- BUG : Le total de précipitations est arrondi.
- BUG : Le widget n'affiche plus « 0 mm » quand l'alerte n'a pas de précipitation (alerte vent).
- BUG : Les valeurs à 0 (0 °C, vent nul, 0 mm) n'étaient plus prises en compte et faussaient les min / max / moyennes.
- BUG : Une valeur absente s'affiche « – » au lieu de « 0.0 ».
- BUG : La sauvegarde d'un équipement n'est plus interrompue (suppression d'un ajax::success dans postSave).
- BUG : Pas d'alerte vent si aucun seuil n'est configuré ou si la donnée de vent est absente.
- BUG : Échelle de Beaufort : 118 km/h est bien classé « ouragan ».
- Timeout de 15 s sur l'appel à prevision-meteo.ch pour ne plus bloquer le cron.
- Suppression des commandes sudo exécutées à chaque mise à jour (migration faite uniquement à la mise à jour du plugin).
- Compatibilité PHP 8 (warnings et dépréciations).

# 19/10/2020 | beta 1.0.4 (Par Piug)
- Ajout de la traduction en Anglais
- Ajout d'une courte description des commandes dans la configuration

# 29/08/2020 | Stable 1.0.3
- Passage en stable

# 27/08/2020 | Beta 1.0.3

- Suppression à la règle qui annonce la prédiction de l'heure d'après à 50.
- Texte prédictif : Correction si précipitation min et max sont identiques.
- Texte prédictif : Ajustement des espaces en fin de phrase.
- Affichage la version dans la page de configuration.
- Ajout de logs

# 25/08/2020 | Beta 1.0.2

- BUG : Correction sur les alertes et plus particulièrement sur le cumul avec le vent.
- BUG : Correction d'une faute de frappe dans le message
- Les alertes météos sont prioritaires à celles du vent
- Modification du widget quand il n'y a pas de précipitation
- Amélioration des logs en mode debug 

# 03/07/2020 | Stable 1.0.1

- BUG : Dans le message prédictif, l'heure annoncée était l'heure de fin de l'alerte

# 01/07/2020 | Stable 1

- Passage en stable
- BUG : Fix PHP Warning - number_format() expects parameter 1 to be float
- BUG : Fix PHP Notice - Undefined property: stdClass::$errors

# 30/06/2020 | Beta 1.2

- BUG : Priorisation des alertes par rapport aux alertes vent
- BUG : Correction du décompte du temps d'alerte
- Possibilité d'installer sur une V3 pour test
- Fix décalage config en V3

# 05/06/2020 | Beta 1.1

- BUG : Récupération du type d'alerte trop tard entrainant une icone vent au lieu de la pluie
- Enregistrement des configurations à defauts si pas enregitrées lors de l'installation
- Ajout de la commande Type_degre

# 01/06/2020 | Beta 1

- Première version du plugin previsy 
- Documentation Fr