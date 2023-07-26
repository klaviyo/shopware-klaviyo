# 2.0.2
# Fix: Das Problem mit der Fehlermeldung in einigen Fällen beim Abonnieren des Newsletters wurde behoben.

# 2.0.1
# Fix: Das Problem mit der Fehlermeldung in einigen Fällen beim Abonnieren des Newsletters wurde behoben.

# 2.0.0
# Kompatibilitätsfreigabe mit Shopwrae 6.5^
# Fix: Verwendung entfernter Klassen und Dateien ersetzt.
# Fix: Kleinere Änderungen an Erweiterungskonfigurationsklassen/-vorlagen (auf der Erweiterungskonfigurationsseite).
# Neu: Job Scheduler Update – Kompatibilität mit Shopawre 6.5^-Versionen implementiert.
# Neu: Job-Scheduler-Update – Job-Scheduler-Handler erweitern jetzt empfohlene Schnittstellen.
# Neu: Controller-Routen verfügen jetzt über eine Annotationsdeklaration im neuen Format.
# Neu: Einige vorgenommene Änderungen machen die Erweiterung abwärtsinkompatibel. Sie können die Abhängigkeiten in der Datei „composer.json“ sehen.

# 1.0.19
# Funktion: Möglichkeit hinzugefügt, die Bestellidentifikationsvariable zu ändern, die an das Klaviyo gesendet wird (war vorher: Bestell-Hash | jetzt können Sie entweder wählen: Bestell-Hash ODER Bestell-ID)

# 1.0.18
# Fix: Das Problem wurde behoben, bei dem „Back In Stock at Product Pate“ keine Daten an klaviyo sendete

# 1.0.17
* Fix: Das Problem wurde behoben, bei dem Produkte falsche Links (in klaviyo) zu Shops anderer Sprachen/Domains (shopware) hatten, wenn einem einzelnen Verkaufskanal zahlreiche Domains zugewiesen waren.

# 1.0.16
* Neu: Vertriebskanalinformationen wurden zum Klaviyo-Kunden hinzugefügt.

# 1.0.15
* New: Kompatibilität mit CookieBot hinzugefügt
* New: Zusätzliche Kompatibilität mit den neuesten Versionen

# 1.0.14
* Neu: Auswahl von Variantenbezeichnern für BIS hinzugefügt

# 1.0.13
* Neu: Tracking für "PAID"-Bestellungen hinzugefügt
* Neu: Produkt-SKU in der Funktion "Benachrichtigen, wenn auf Lager" hinzugefügt

# 1.0.12
* Neu: Warenkorb-Reset-Funktionalität hinzugefügt

#1.0.11
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
