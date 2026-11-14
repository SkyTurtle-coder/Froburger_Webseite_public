# Öffentliche Anmeldung für interne Anlässe

Zuerst das Django-Paket `app/intern/deploy/Deploy-MemberSignup.ps1 -Deploy` ausführen. Danach hier:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\docs\member-signup\Deploy-MemberSignup.ps1 -Deploy
```

Ohne Schalter: nur lokales Paket. `-Upload`: übertragen und SSH-Befehl ausgeben. `-Deploy`: übertragen, überprüfen und installieren.

Das Formular bleibt auf WordPress. Django entscheidet über die Mitgliedschaft; WordPress bekommt keine Mitgliederliste. Der PIN für spätere Änderungen bleibt bestehen. Teilnehmerlisten interner Anlaesse werden oeffentlich angezeigt; ausgeblendete Eintraege und private Zusatzfelder bleiben verborgen. Fehler der Mitgliederprüfung lauten einheitlich „Sorry, nur für Froburger.“

Das Skript sichert die fünf geänderten Dateien, prüft die Backend-Version und verweigert das Überschreiben abweichender Serverdateien. Es bereinigt Plugin- und Elementor-Caches. Die abschliessende Prüfung verwendet den internen Anlass `schluss-acbc`; dieser muss existieren und intern markiert sein. Bei fehlgeschlagenem WordPress-Deployment werden die gesicherten WordPress-Dateien wiederhergestellt. Das Backend bleibt geschützt.

Validierung: `php wp-content/plugins/avf-events-integration/tests/member-signup-check.php`, `php wp-content/plugins/avf-events-integration/tests/status-badges-check.php` und PHP-Lint. Die PHP-Tests verwenden einen simulierten HTTP-Transport; auf Produktion wurden keine Anmeldungen angelegt.

## Gepruefter Produktionsaltstand

Der Installer akzeptiert fuer `class-avf-event-signup-handler.php` zusaetzlich genau den heruntergeladenen und vollstaendig verglichenen Produktionsstand mit SHA-256 `9c92713eb94fc65cf4834887e3bcfafa64c01dcb993bc6f91bfc40b38427bf66` (CRLF auf LF normalisiert). Gegenueber der bisherigen Baseline unterscheidet sich ausschliesslich die aeltere Integer-Umwandlung: beliebige Integer wurden zu bool konvertiert. Das Update installiert die bereits getestete strikte Pruefung auf 0/1 sowie die allgemeine Mitgliedschafts-Fehlermeldung. Andere Abweichungen bleiben gesperrt; die Dateisicherung bleibt aktiv.

## Update: oeffentliche Teilnehmerliste

Fuer dieses Update beide Deployment-Skripte erneut ausfuehren: zuerst Django, danach WordPress. Die Mitgliederpruefung, PIN-Pruefung und gestaffelte IP-Sperre bleiben aktiv. Auf dem bereits aktualisierten Backend ist keine weitere Datenbankmigration erforderlich. Der Installer prueft jetzt die Anzeige der oeffentlichen Teilnehmerliste statt deren Ausblendung.
