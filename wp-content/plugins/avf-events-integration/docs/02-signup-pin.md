# Anmeldung mit PIN

Das Formular verlangt bei neuen Anmeldungen 4–6 Ziffern als PIN. Unterhalb steht
„Bestehende Anmeldung bearbeiten“. Vulgo und PIN öffnen die bisherigen Angaben;
„Änderungen speichern“ aktualisiert denselben Eintrag. Der PIN bleibt nur während
der Bearbeitung im Formular und wird beim Beenden, Speichern oder Verlassen der
Seite entfernt. Er wird nicht im Flash-Transient, URL oder Browser-Speicher abgelegt.

Die Bearbeitung verwendet den POST-AJAX-Handler `avf_event_edit`, eine WordPress-
Nonce sowie die bestehende signierte Django-Anbindung. Persönliche Antworten
werden nicht gecacht. Nach dem Speichern werden der öffentliche API-Cache geleert
und die sichtbare Teilnehmerzeile aktualisiert. JavaScript ist zum Bearbeiten nötig;
ohne JavaScript erscheint ein entsprechender Hinweis.

PIN vergessen oder noch kein PIN: ausschliesslich über den Admin im internen
Anlasseditor. Die öffentlichen Formulare können niemandem nachträglich einen PIN
zuweisen. Intern bleiben Änderung und Löschung ohne PIN auch nach Anmeldeschluss
möglich. Der öffentliche Bearbeiten-Bereich erscheint nur bei offener Anmeldung.

## Einführung

Dieses Plugin benötigt den zugehörigen Django-Stand mit der events-Migration 0009.
Backend-Migration und beide Code-Stände in einem gemeinsamen Wartungsfenster
ausliefern und danach die öffentlichen Seitencaches leeren. Alte und neue Stände
sind beim Anmelde-POST nicht kompatibel. Keine Live-Auslieferung ist Teil des Commits.
Weitere Betriebsdetails: `events/PIN_SIGNUPS.md` im internen Repository.

## Prüfungen

- `php tests/signup-pin-check.php`: isolierte PHP-Contract-Prüfung für PIN-Validierung,
  Erhalt führender Nullen, AJAX-Operationen, Nonce, Cache und eindeutige Formular-IDs.
- `python tests/signup-pin-browser.py /pfad/zu/php`: echte PHP-Formulare/CSS/JavaScript
  in einem Headless-Edge-Browser bei 1280 und 390 Pixeln, mit simulierten API-Antworten.
  Benötigt Python-Paket `playwright` und installiertes Microsoft Edge. Screenshots
  werden als `avf-pin-edit-1280.png` / `avf-pin-edit-390.png` im Temp-Verzeichnis gespeichert.
- PHP-Syntaxprüfung für die geänderten PHP-Dateien.

Die Browserprüfung ersetzt keinen gemeinsamen Test der ausgerollten WordPress-
und Django-Instanzen. Sie sendet keine Anmeldungen an die Live-Seite.
