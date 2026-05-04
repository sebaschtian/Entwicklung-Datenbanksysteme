-- Autor: Sebastian Rieg
-- Trigger: Vergibt automatisch die nächste Startnummer pro Rennen
-- Die Startnummern beginnen bei 1 und werden aufsteigend vergeben

DELIMITER $$

CREATE TRIGGER startnummer_vergeben
BEFORE INSERT ON nimmtTeil
FOR EACH ROW
BEGIN
    DECLARE naechsteNummer INT;

    -- Höchste vorhandene Startnummer für dieses Rennen ermitteln
    -- COALESCE gibt 0 zurück falls noch keine Einträge vorhanden
    SELECT COALESCE(MAX(Startnummer), 0) + 1
    INTO naechsteNummer
    FROM nimmtTeil
    WHERE RennID = NEW.RennID;

    -- Nächste Startnummer setzen
    SET NEW.Startnummer = naechsteNummer;
END$$

DELIMITER ;