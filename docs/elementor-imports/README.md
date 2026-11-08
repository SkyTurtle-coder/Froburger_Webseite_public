# Anlassseite: Elementor-first Layout-Migration

`anlaesse-elementor-layout-migration.json` ist eine aus dem aktuellen
Test-Export erzeugte Seite. Sie verlagert die steuernden Layoutwerte in
Elementor und markiert den neuen Stand mit `avf-layout-elementor`.

## Was Elementor danach steuert

- Inhaltsachsen und Seitenabstaende auf Desktop, Tablet und Mobil.
- Die obere Anordnung von Titelbereich und Kalenderaktion.
- Anzahl, Umbruch und Abstand der Regionalstamm-Karten (3 / 2 / 1 Spalten).
- Die Kartenkopf-Anordnung inklusive Wappenposition.
- Die Anordnung der Rueckblick-Ueberschrift und die Platzierung der
  vergangenen Anlaesse.

CSS bleibt fuer Farben, Hintergruende, Typografie, Kartenoberflaechen,
Schatten, Trennlinien, Hover-Zustaende und die Event-Shortcodes zustaendig.

## Sichere Abnahme

`test.avfroburger.ch` teilt derzeit die WordPress-Datenbank mit der
Produktion. Deshalb darf die JSON-Datei dort nicht importiert und die Seite
**Anlaesse** dort nicht aktualisiert werden: beides schreibt in die gemeinsame
Datenbank.

1. Zuerst eine isolierte Staging-Instanz mit eigener Datenbank bereitstellen.
2. Die beiden Plugin-Dateien deployen und die JSON-Datei in dieser isolierten
   Instanz als gespeicherte Vorlage importieren.
3. Die importierte Vorlage an Desktop, Tablet und Mobil kontrollieren.
   Insbesondere: oberer Titel-/Kalenderbereich, Regionalstamm-Raster und
   Rueckblick.
4. Erst danach die gleiche Datei in der Produktion importieren und ihren
   Inhalt in die Seite **Anlaesse** uebernehmen.
5. Unter **Elementor > Werkzeuge** die CSS-Dateien und Daten gezielt neu
   erzeugen; anschliessend Browser- und allfaellige Server-Caches leeren.

Die zugehoerige Anpassung von `avf-site-styles` muss mit ausgerollt werden:
Sie laedt die visuellen Projektstile auch in Elementors Vorschau und wendet
die alten Layout-Overrides nicht auf Seiten mit `avf-layout-elementor` an.
