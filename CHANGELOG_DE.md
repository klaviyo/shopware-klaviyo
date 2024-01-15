# 2.8.3
# Fix: Das Ereignis „Zum Warenkorb hinzufügen“ wird jetzt im Echtzeitmodus und nicht nach einem Zeitplan ausgelöst
# Fix: Ein Fehler beim Ausführen asynchroner Abonnementvorgänge wurde behoben
# Fix: Ein Problem beim Synchronisieren von Bestellungen mit gelöschten Kunden wurde behoben

# 2.8.3
# Fix: Es wurde ein Problem behoben, bei dem bei einem produktbezogenen Ereignis der Produktname fehlte
# Fix: Es wurde ein Problem behoben, bei dem einige Transaktionsdaten nicht synchronisiert wurden
# Fix: Es wurde ein Problem behoben, bei dem einige Daten für warenkorbbezogene Ereignisse fehlten

# 2.8.1
# Fix: Kundensprachfeld hinzugefügt, das bei der Synchronisierung von Newsletter-Empfängern zusammen mit anderen Daten an Klaviyo gesendet wird.
# Fix: Tippfehler behoben

# 2.8.0
# Neu: Bessere Protokollierung im gesamten Plugin hinzugefügt.
# Fix: Das Problem wurde behoben, bei dem Kunden im Raster des Admin-Panels keine Werbeaktionen für den Export auswählen konnten.
# Fix: Es wurde ein Problem behoben, das bei einigen Kunden auftreten konnte, wenn gekaufte/aufgegebene Bestellungen nicht ordnungsgemäß aktualisiert wurden.
# Fix: Es wurde ein Problem behoben, bei dem das E-Mail-Opt-in-Banner in Storefront fehlte

# 2.7.1
# Fix: Ein Fehler/Problem mit Cookiebot wurde behoben, das in der Browserkonsole ausgegeben wurde, wenn „Standard-Cookie-Benachrichtigung verwenden“ auf „Ja“ gesetzt war.
# Fix: Es wurde ein Problem behoben, bei dem die Bestell-ID in den Ereignissen zu rückerstatteten Bestellungen falsch angezeigt wurde (die Bestell-ID wurde anstelle der Bestellnummer angezeigt, obwohl sie in der Plugin-Konfiguration auf Bestellnummer eingestellt war).

# 2.7.0
# Fix: Korrekturen für eine stabile Arbeit mit dem Cookie-Manager – CookieBot – hinzugefügt.
# Fix: Korrekturen beim Synchronisieren von Abonnenten hinzugefügt.
# Neu: Die Konfiguration „Tägliche Abonnentensynchronisierung“ wurde hinzugefügt.
# Neu: Die Konfiguration „Bereinigung alter Jobs aktivieren“ wurde hinzugefügt.

# 2.6.0
# Funktion: Jetzt ist Double-Opt in der Nachrichtenübermittlung auf der Konfigurationsseite in allen Konfigurationsbereichen/Vertriebskanälen sichtbar.

# 2.5.2
# Fix: Das Problem wurde behoben, bei dem der Selektor in der Konfiguration „Klaviyo-Listenname für Abonnenten“ nicht angezeigt wurde.

# 2.5.1
# Fix: Das Problem wurde behoben, bei dem die Validierung des öffentlichen API-Schlüssels von Klaviyo nicht wie vorgesehen funktionierte

# 2.5.0
# Neu: Option hinzugefügt, um die Zuordnung für ihre Bestellung sowie den Lieferstatus als Pflichtfeld in einem Dropdown-Menü auszuwählen, damit dieser Status auch in Klaviyo ankommt.

# 2.4.0
# Feature: Kompatibilität mit „Consentmanager“ von Consentmanager.net hinzugefügt
# Verbesserung: Die Implementierung des Ereignisses „Checkout gestartet“ im Plugin wurde für eine bessere Kompatibilität mit Checkout-Anpassungen und Plugins (z. B. 1-Schritt-Checkout und andere) überarbeitet.
# HINWEIS: Wenn Sie beim Auschecken umfangreiche Anpassungen der Plugin-Dateien vorgenommen haben, empfehlen wir Ihnen, die Anpassungen auf Ihrer Seite zu überprüfen und zu überprüfen.
# Fix: Das Problem wurde behoben, bei dem „Abbestellen“ auf der Seite „Mein Konto“ nicht funktionierte.

# 2.3.2
# Fix: Das Ereignis „Rückerstattete Bestellung“ wird jetzt angezeigt, nachdem auf die Schaltfläche „Historische Ereignisse synchronisiert“ geklickt wurde
# Fix: Behebung des Problems, bei dem der Task-Manager zum Stillstand kommen/stoppen kann.

# 2.3.1
# Fix: Ereignisreihenfolge „Bestelltes Produkt“ nach historischer Synchronisierung

# 2.3.0
# Fix: Das Ereignis „Bezahlte Bestellung“ wurde nach der historischen Synchronisierung nicht für nicht bezahlte Bestellungen angezeigt
# Fix: Das Problem wurde behoben, wenn Ereignisse nach jeder historischen Synchronisierung in den Aktivitätsprotokollen des Profils dupliziert wurden
# Fix: Aufrufmethode „dump(extensionData)“ in der Twig-Datei entfernt
# Fix: Beim Hinzufügen von „Echtzeit“-Benutzern zu Klaviyo wird jetzt die API „Liste abonnieren“ verwendet
# Fix: Die Admin-Konfigurationsoption „Abonnentenliste“ ist kein Auswahl-/Dropdown-Menü mit Werten, die vom Klaviyo-Dienst abgerufen werden (wenn die API-Anmeldeinformationen gültig sind).

# 2.2.0
# Neu: Der Link zur Warenkorb-Wiederherstellung füllt jetzt die Adressdaten aus, die der Kunde vor dem Verlassen des Warenkorbs angegeben hat (falls zutreffend).
# Fix: Das Problem wurde behoben, bei dem einige Kunden möglicherweise falsche Daten der an den Klaviyo-Service übergebenen Ereignisse sehen (Ereignisse für erfüllte Bestellungen usw.).

# 2.1.0
# Fix: Die Synchronisierung von Klaviyo-Ereignissen wurde behoben, wenn die Tracking-Kontrollkästchen im Admin-Bereich deaktiviert waren.
# Fix: Korrektur der Synchronisierung historischer Datenauftragsstatus.
# Fix: Ein Problem mit dem Modal „Wieder auf Lager“ wurde behoben.
# Neu: Ein neuer Endpunkt wurde hinzugefügt, dank dem Sie die aktuelle Version des installierten Klaviyo-Plugins herausfinden können.

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
