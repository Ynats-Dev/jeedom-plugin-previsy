# 11/08/2026 | Beta 2.0.0
- NEW: Widget for the mobile app.
- NEW: The Rafraichir command really reloads the forecast from the API (at most once every 5 minutes).
- NEW: Warning area suggestions for the chosen country.
- NEW: Warning colour translated according to the Jeedom language.
- NEW: Choice of the weather API in the plugin configuration, for all equipments at once: Open-Meteo (worldwide, no key, recommended) or prevision-meteo.ch, or choice per equipment. An update keeps the current behaviour (choice per equipment, existing equipments on prevision-meteo.ch).
- NEW: Automatic fallback to the other API when unavailable, with a warning in the logs, a message in the message center, a note in the widget and an "API utilisée" command. Automatic return to the chosen API as soon as it responds.
- NEW: Custom alerts ("My alerts" tab): combine rain, snow, wind, gusts, wind direction, temperature, feels-like temperature, humidity, pressure, time range and minimum duration. Each alert has its own commands.
- NEW: National weather warnings via MeteoAlarm (Météo-France, RMI, MeteoSwiss...): banner in the widget and dedicated commands. In France, the department is detected automatically.
- NEW: Standard weather alerts and the wind alert can be disabled.
- NEW: The cache is reloaded automatically when the API or the location changes.
- BUG: The minimum warning level was displayed empty on an existing equipment; the "API utilisée" and "Type_degre" commands can no longer be deleted; the warning and custom alert frames no longer overflow the widget; the refresh icon is back in the widget title bar.
- BUG: PHP warning in the predictive text of an alert without precipitation (wind, fog).
- BUG: An equipment defined only by its coordinates was not updated when saved.
- BUG: "City not found" errors from prevision-meteo.ch were not detected.
- BUG: Negative coordinates (West longitude, southern hemisphere) could not be entered.
- BUG: The widget no longer displays "0 mm" when the alert has no precipitation (wind alert).
- BUG: Values equal to 0 (0 °C, no wind, 0 mm) were ignored and distorted min / max / averages.
- BUG: A missing value is displayed as "–" instead of "0.0"; the precipitation total is rounded.
- BUG: Saving an equipment is no longer interrupted (ajax::success removed from postSave).
- BUG: Beaufort scale: 118 km/h is classified as "hurricane".
- BUG: No wind alert when no threshold is set or when the wind data is missing.
- 15 s timeout on API calls so the scheduled task is no longer blocked.
- PHP 8 compatibility (warnings and deprecations).
- Removal of the sudo commands run at each update (the migration is only done when updating the plugin).

# 10/19/2020 | Beta 1.0.4 (By Piug)
- English translation added

# 08/29/2020 | Stable 1.0.3
- Switching to stable