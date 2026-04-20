ERM Entwicklung Datenbanken





Team 	 (***TeamName***)



Teamchef (***LoginName***, NameTeamchef, Kennwort, Teamname REFERENCES Team)



Fahrer 	 (FahrerName, ***FahrerID***, PLZ, StraßeHausnummer, OrtName, ***TeamName* References Team**)



Training (gefahreneKM, ***Datum*, <i>(TeamName, FahrerID) REFERENCES Fahrer</i>,** Trainingsziel REFERENCES Trainingsziel**)**



Trainingsziel (***Trainingsziel***)



Rennen   (***RennID***, Datum, Startort, StreckenKM, Höhenmeter, MaxSteigung, VeranstalterName REFERENCES Rennveranstalter)



nimmtTeil (Startnummer, gefahreneZeit, ***RennID REFERENCES Rennen***, ***(FahrerID, TeamName) REFERENCES Fahrer***, Rennprämie, Platzierung, FahrerPrämie,)



Rennveranstalter(**VeranstalterName**, Kennwort)



Telefonnummer(**Telefonnummer**, ***(TeamName, FahrerID) REFERENCES Fahrer***)



