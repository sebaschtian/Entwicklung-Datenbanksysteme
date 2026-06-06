-- Autor: Marlies Achterhold
-- Trigger: Vergibt automatisch die nächste FahrerID pro Team
-- Die FahrerIDs beginnen bei 1 und werden pro Team aufsteigend vergeben

DELIMITER $$

DROP TRIGGER IF EXISTS fahrerid_vergeben$$

CREATE TRIGGER fahrerid_vergeben
BEFORE INSERT ON Fahrer
FOR EACH ROW
BEGIN
    DECLARE max_id INT;
    SELECT IFNULL(MAX(FahrerID), 0) INTO max_id
    FROM Fahrer WHERE TeamName = NEW.TeamName;
    SET NEW.FahrerID = max_id + 1;
END$$

DELIMITER ;
