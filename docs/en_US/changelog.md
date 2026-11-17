# 11/17/2026 | Beta 2.0.1
- NEW: Choice of the Open-Meteo model in the configuration. Default "Automatic": Météo-France models (AROME, 1.3 km grid) in France and neighbouring countries, best global model elsewhere. More reliable forecasts, especially for showers.
- NEW: Minimum rain for an alert (0.2 mm/h by default): unreliable traces of rain or snow no longer raise a standard alert.
- NEW: Minimum rain probability for an alert (30% by default, Open-Meteo only): an unlikely hour of rain or snow no longer raises a standard alert. With the Météo-France model, which provides no probability, the one from the best global model is used.
- BUG: Open-Meteo: a "rain" alert could be raised for hours without precipitation (precipitation "None").
- BUG: A "0" was displayed under the widget when there was only one alert.
- BUG: The bottom of the widget was stuck to, or even cut off below, the last alert.

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
- BUG: "fputs(): Argument #1 ($stream) must be of type resource" error in the scheduled task when the data/json folder is not writable: permissions are fixed automatically, otherwise the cache is stored in the Jeedom temporary folder, without blocking the plugin.
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