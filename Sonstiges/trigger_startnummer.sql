-- Autor: Sebastian Rieg
-- Trigger: Vergibt automatisch die nächste Startnummer pro Rennen
-- Die Startnummern beginnen bei 1 und werden aufsteigend vergeben

DELIMITER $$

DROP TRIGGER IF EXISTS startnummer_vergeben$$

CREATE TRIGGER startnummer_vergeben
BEFORE INSERT ON nimmtTeil
FOR EACH ROW
BEGIN
    DECLARE max_nr SMALLINT;
    SELECT IFNULL(MAX(Startnummer), 0) INTO max_nr
    FROM nimmtTeil WHERE RennID = NEW.RennID;
    SET NEW.Startnummer = max_nr + 1;
END$$

DELIMITER ;