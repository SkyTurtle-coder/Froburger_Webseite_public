# Öffentliche Anmeldung für interne Anlässe

Zuerst das Django-Paket `app/intern/deploy/Deploy-MemberSignup.ps1 -Deploy` ausführen. Danach hier:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\docs\member-signup\Deploy-MemberSignup.ps1 -Deploy
```

Ohne Schalter: nur lokales Paket. `-Upload`: übertragen und SSH-Befehl ausgeben. `-Deploy`: übertragen, überprüfen und installieren.

Das Formular bleibt auf WordPress. Django entscheidet über die Mitgliedschaft; WordPress bekommt keine Mitgliederliste. Der PIN für spätere Änderungen bleibt bestehen. Teilnehmerlisten interner Anlässe werden ausgeblendet. Fehler der Mitgliederprüfung lauten einheitlich „Sorry, nur für Froburger.“

Das Skript sichert die fünf geänderten Dateien, prüft die Backend-Version und verweigert das Überschreiben abweichender Serverdateien. Es bereinigt Plugin- und Elementor-Caches. Die abschliessende Prüfung verwendet den internen Anlass `schluss-acbc`; dieser muss existieren und intern markiert sein. Bei fehlgeschlagenem WordPress-Deployment werden die gesicherten WordPress-Dateien wiederhergestellt. Das Backend bleibt geschützt.

Validierung: `php wp-content/plugins/avf-events-integration/tests/member-signup-check.php`, `php wp-content/plugins/avf-events-integration/tests/status-badges-check.php` und PHP-Lint. Die PHP-Tests verwenden einen simulierten HTTP-Transport; auf Produktion wurden keine Anmeldungen angelegt.
