Hero-Bild – WordPress Plugin v1.4.0

Installation / Update:
1. ZIP über Plugins > Installieren > Plugin hochladen hochladen; alte Version ersetzen.
2. Bisherige Bildzuordnungen, Beitragsbild-Loop-Einstellungen und Blockdaten bleiben erhalten.

NEU v1.4.0:
- Einheitliches Symbolbild unter Hero-Bild im Backend aus der Mediathek auswählen
  und mit „Einstellungen speichern“ sichern.
- Alle Hero-Blöcke für Seiten und Beiträge verwenden dieselbe Reihenfolge:
  gültiges Hero-Bild, sonst Beitragsbild, sonst Symbolbild.
- Gilt auch im Query-Loop und bei individuellen Darstellungseinstellungen.
- Kategorie-Archive verwenden ihr Kategorie-Bild, sonst das Symbolbild.
- Ohne gültiges Bild auf allen Stufen bleibt der Block ausgeblendet.
- Die bisherige Loop-Auswahl betrifft nur den WordPress-Block „Beitragsbild“.

FIX v1.3.2:
- In Einzelansichten verwendet der Hero-Bild-Block das Beitragsbild, wenn kein
  gültiges Hero-Bild hinterlegt ist. Das gilt für Beiträge und Seiten sowie
  unterstützte eigene Inhaltstypen mit und ohne Block-postId-Kontext.
- Fehlen beide Bilder, bleibt der Block leer. Kategorie-Bilder und die
  globale Bildauswahl für Query-Loops werden unverändert berücksichtigt.

FIX v1.3.1:
- Die globale Bildauswahl gilt jetzt auch für Hero-Bild-Blöcke im Query-Loop.
- Beitragsbild zuerst, Hero-Bild zuerst und nur Beitragsbild werden berücksichtigt.
- Fehlende oder ungültige Bilder werden beim Hero-Block übersprungen.
- Einzelansichten und Kategorie-Hero-Bilder bleiben unabhängig von der Loop-Auswahl.

NEU v1.3.0: Eigener Menüpunkt „Hero-Bild“ im WordPress-Backend
- Globale Einstellungen für Bildbreite, Desktop/Tablet/Mobil-Höhe, Bildausschnitt, Radius.
- Hover-Effekt inklusive Zoomstärke und Animationsdauer.
- Informationskasten: Anzeige, Text, Position, Farben, Größe, Abstände, Radius.
- Beitragsbild-Ersatz in Abfrage-Loops an derselben Stelle (bisher Einstellungen > Hero-Bild).
- Die Einstellungen gelten standardmäßig für alle Hero-Bild-Blöcke – auch bestehende.
- Pro Block „Globale Einstellungen verwenden“ deaktivieren, um alte individuell
  gespeicherte Werte weiterhin für das jeweilige Template zu nutzen.
- Die Bildquelle (Automatisch/Kategorie/Beitrag) bleibt pro Block einstellbar.
- Die eigentliche Bildzuordnung erfolgt weiter pro Beitrag/Seite/Taxonomie.

Bestehende Funktionen:
- Separates Hero-Bild für Beiträge, Seiten und unterstützte öffentliche Custom Post Types.
- Pro Kategorie / Taxonomie eigenes Hero-Bild, automatische Anzeige im Archiv-Template.
- Query-Loop-Beitragsbild-Fallback ohne das echte Beitragsbild zu verändern.
- Bildinformation mit Titel und Bildbeschriftung aus der Mediathek.
- Schließen des Informationskastens bei Klick außerhalb oder Escape.
- Responsive Darstellung mit Breakpoints bei 1024 px und 600 px.
- Sanfter Hover-Effekt, CSS-Ausgabe pro Block, reduced-motion-Unterstützung.

Kategorie-Archive:
- Beiträge > Kategorien > Kategorie bearbeiten > Hero-Bild auswählen.
- Im Template den Hero-Bild-Block außerhalb des Query-Loops einfügen und
  „Aktuelle Kategorie / Taxonomie“ als Bildquelle wählen.
- Im Site-Editor kann ohne eine konkrete Kategorie kein Bild vorausgewählt werden.

Hinweis: Bei Theme-seitig begrenztem Container muss ggf. der Elternblock
auf volle Breite gestellt werden. Externe Loop-Plugins können abweichend rendern.
