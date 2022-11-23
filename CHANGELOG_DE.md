# 1.0.11
* Fix: Kontext wird für Hintergrundprozesse beibehalten

# 1.0.10
* Fix: Problem mit Plugin-Localstorage-Item-Set ohne Cookie-Einwilligung behoben

# 1.0.9
* Fix: Problem mit der Initialisierung des Klaviyo-Skripts ohne Cookie-Zustimmung behoben
* Fix: Problem mit unsicherer Anzeige des Felds "Private API Schlüssel" behoben

#1.0.8
* Neu: Neue Klviyo-Markensymbole hinzugefügt
* Neu: Neue Funktion hinzugefügt, um die Synchronisierung von Bestellereignissen gelöschter Konten zu aktivieren/deaktivieren
* Neu: Reinigungsmechanismus für Joblisten hinzugefügt
* Neu: Zusätzliche Informationsmeldungen während der Verarbeitung von Hintergrundjobs hinzugefügt
* Neu: Job Scheduler Update - Jobbenachrichtigung mit korrekter Sortierreihenfolge hinzugefügt
* Fix: Verbesserter Plugin-Deinstallationsprozess
* Fix: Mögliches Problem mit Klaviyo-Listen-ID-Caching behoben
* Fix: Mögliches Problem beim Synchronisieren von nicht abonnierten Empfängern von Klaviyo behoben
* Fix: Mögliches Problem mit der Hintergrundverarbeitung von Bestellpositionen behoben
* Fix: Mögliches Problem mit fehlender Plugin-Konfiguration während der Auftragssynchronisierung behoben

#1.0.7
* Neu: API-Schlüsselvalidierung in der Klaviyo-Konfiguration hinzugefügt
* Neu: Aktivierung und Deaktivierung des Klaviyo-Trackings durch Cookies hinzugefügt
* Neu: Bereinigen Sie ausstehende Jobs während des Deinstallationsprozesses
* Neu: Symbol und Name des Klaviyo-Plugins geändert
* Fix: Einschränkungen der Vertriebskanaloptionen in der Klaviyo-Konfiguration entfernt
* Fix: Übersetzungen für alle Klaviyo-Texte hinzugefügt

#1.0.6
 * Fix: Wir haben das Problem mit der Leerlieferung der Bestellung behoben
 * Fix: Wir haben das Problem mit den Checkout-Tracker-Kategorien behoben
 * Fix: Wir haben ein Problem mit der Feed-Generierung behoben, wenn es kein Titelbild gibt

#1.0.5
 * Neu: Jetzt wird der Produkthersteller auf alle produktbezogenen Klaviyo-Events übertragen.
 * Neu: Jetzt können Klaviyo-Kontoanmeldeinformationen nur auf Vertriebskanalebene konfiguriert werden.
 * Neu: Jetzt kann das Klaviyo-Konto des Vertriebskanals deaktiviert werden, um die Verarbeitung von Ereignissen auf dem zugehörigen Kanal zu verhindern.
 * Fix: Wir haben ein Problem mit der Storefront-Ereignisverfolgung mit aktivierter A/B-Testfunktion in Klaviyo behoben.
 * Fix: Wir haben ein mögliches Problem mit der Kundendatensynchronisierung behoben.
 * Fix: Wir haben ein potenzielles Ereignisbehandlungsproblem auf Kanälen mit falschen Anmeldeinformationen/Konfigurationen behoben.
 * Fix: Wir haben das Problem mit dem Fehler beim Ändern des Bestellstatus von der Admin-Benutzeroberfläche behoben.
 * Fix: Wir haben das Problem mit der historischen Synchronisierung von Bestellungen mit gelöschten Produkten behoben.

#1.0.4
 * Neu: Job Scheduler Update - verbesserte Admin-Benutzeroberfläche und praktische Nachrichtenbehandlung.
 * Neu: Wir haben den Klaviyo Person API-Workflow aktualisiert.
 * Fix: Problem mit der Verarbeitung von Gastbestellungen behoben.
 * Fix: Das Problem mit dem "localhost"-Produktlink in nachverfolgten Bestellereignissen wurde behoben.
 * Fix: Problem mit Klaviyo Tracking JS auf Seiten mit benutzerdefiniertem Layout behoben.
 * Fix: Unnötige Einstellung "Catalog Feed Products Count" aus der Plugin-Konfiguration entfernt

#1.0.3
 * Neue E-Mail-Benachrichtigungsfunktion "Wieder auf Lager" hinzugefügt.
 * Neue Funktion "Bidirektionale (un)Abonnenten-Synchronisation" hinzugefügt. Jetzt kann das Plugin Newsletter-Abmeldungen von Klaviyo zu Shopware und umgekehrt synchronisieren.

#1.0.2
 * Leistungsverbesserungen. Refactoring der Plugin-Codebasis. System-Job-Scheduler-Bundle hinzugefügt.

#1.0.1
 * Einheiten- und Integrationstest hinzugefügt.

#1.0.0
 * Implementierung der grundlegenden Plugin-Funktionalität.
