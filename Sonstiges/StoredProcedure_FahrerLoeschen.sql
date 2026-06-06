DELIMITER 
// Autor: Marlies Achterholt

CREATE PROCEDURE FahrerLoeschen(
    IN p_FahrerID INT,
    IN p_TeamName VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        DELETE FROM nimmtTeil
        WHERE FahrerID = p_FahrerID AND TeamName = p_TeamName;

        DELETE FROM Training
        WHERE FahrerID = p_FahrerID AND TeamName = p_TeamName;

        DELETE FROM Telefonnummer
        WHERE FahrerID = p_FahrerID AND TeamName = p_TeamName;

        DELETE FROM Fahrer
        WHERE FahrerID = p_FahrerID AND TeamName = p_TeamName;
    COMMIT;
END //

DELIMITER ;