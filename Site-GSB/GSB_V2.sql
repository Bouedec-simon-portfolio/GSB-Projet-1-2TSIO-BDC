DROP DATABASE IF EXISTS gsbV2;
CREATE DATABASE gsbV2
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;
USE gsbV2;



CREATE TABLE FraisForfait (
    id CHAR(3),
    libelle VARCHAR(100) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id)
)ENGINE=InnoDB;

INSERT INTO FraisForfait VALUES
('ETP', 'Forfait Etape', 110.00),
('KM', 'Frais Kilométrique', 0.62),
('NUI', 'Nuitée Hôtel', 80.00),
('REP', 'Repas Restaurant', 25.00);

CREATE TABLE Etat (
    id CHAR(2),
    libelle VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
)ENGINE=InnoDB;

INSERT INTO Etat VALUES
('RB', 'Remboursée'),
('CL', 'Saisie clôturée'),
('CR', 'Fiche créée, saisie en cours'),
('VA', 'Validée et mise en paiement');


CREATE TABLE Visiteur (
    id CHAR(4),
    nom VARCHAR(30) NOT NULL,
    prenom VARCHAR(30) NOT NULL,
    login VARCHAR(20) NOT NULL,
    mdp VARCHAR(255) NOT NULL,
    adresse VARCHAR(100),
    cp CHAR(5),
    ville VARCHAR(50),
    dateEmbauche DATE,
    PRIMARY KEY (id)
)ENGINE=InnoDB;

-- INSERT INTO Visiteur VALUES
-- ('a00','Admin','Système','admin','admin1234','123 rue Admin','75000','Paris','2026-01-01'),
-- ('a131','Villechalane','Louis','lvillachane','jux7g','8 rue des Charmes','46000','Cahors','2005-12-21'),
-- ('a17','Andre','David','dandre','oppg5','1 rue Petit','46200','Lalbenque','1998-11-23'),
-- ('a55','Bedos','Christian','cbedos','gmhxd','1 rue Peranud','46250','Montcuq','1995-01-12'),
-- ('a93','Tusseau','Louis','ltusseau','ktp3s','22 rue des Ternes','46123','Gramat','2000-05-01'),
-- ('b13','Bentot','Pascal','pbentot','doyw1','11 allée des Cerises','46512','Bessines','1992-07-09'),
-- ('b16','Bioret','Luc','lbioret','hrjfs','1 Avenue gambetta','46000','Cahors','1998-05-11'),
-- ('b19','Bunisset','Francis','fbunisset','4vbnd','10 rue des Perles','93100','Montreuil','1987-10-21'),
-- ('b25','Bunisset','Denise','dbunisset','s1y1r','23 rue Manin','75019','Paris','2010-12-05'),
-- ('b28','Cacheux','Bernard','bcacheux','uf7r3','114 rue Blanche','75017','Paris','2009-11-12'),
-- ('b34','Cadic','Eric','ecadic','6u8dc','123 avenue de la République','75011','Paris','2008-09-23'),
-- ('b4','Charoze','Catherine','ccharoze','u817o','100 rue Petit','75019','Paris','2005-11-12'),
-- ('b50','Clepkens','Christophe','cclepkens','bw1us','12 allée des Anges','93230','Romainville','2003-08-11'),
-- ('b59','Cottin','Vincenne','vcottin','2hoh9','36 rue Des Roches','93100','Monteuil','2001-11-18'),
-- ('c14','Daburon','François','fdaburon','7oqpv','13 rue de Chanzy','94000','Créteil','2002-02-11'),
-- ('c3','De','Philippe','pde','gk9kx','13 rue Barthes','94000','Créteil','2010-12-14'),
-- ('c54','Debelle','Michel','mdebelle','od5rt','181 avenue Barbusse','93210','Rosny','2006-11-23'),
-- ('d13','Debelle','Jeanne','jdebelle','nvwqq','134 allée des Joncs','44000','Nantes','2000-05-11'),
-- ('d51','Debroise','Michel','mdebroise','sghkb','2 Bld Jourdain','44000','Nantes','2001-04-17'),
-- ('e22','Desmarquest','Nathalie','ndesmarquest','f1fob','14 Place d Arc','45000','Orléans','2005-11-12'),
-- ('e24','Desnost','Pierre','pdesnost','4k2o5','16 avenue des Cèdres','23200','Guéret','2001-02-05'),
-- ('e39','Dudouit','Frédéric','fdudouit','44im8','18 rue de l église','23120','GrandBourg','2000-08-01'),
-- ('e49','Duncombe','Claude','cduncombe','qf77j','19 rue de la tour','23100','La souterraine','1987-10-10'),
-- ('e5','Enault-Pascreau','Céline','cenault','y2qdu','25 place de la gare','23200','Gueret','1995-09-01'),
-- ('e52','Eynde','Valérie','veynde','i7sn3','3 Grand Place','13015','Marseille','1999-11-01'),
-- ('f21','Finck','Jacques','jfinck','mpb3t','10 avenue du Prado','13002','Marseille','2001-11-10'),
-- ('f39','Frémont','Fernande','ffremont','xs5tq','4 route de la mer','13012','Allauh','1998-10-01'),
-- ('f4','Gest','Alain','agest','dywvt','30 avenue de la mer','13025','Berre','1985-11-01');
--
-- NOTE: les mots de passe ci-dessus étaient en clair dans le fichier d'origine.
-- Pour des raisons de sécurité, importez la base sans ces utilisateurs,
-- ou remplacez ces valeurs par des hash bcrypt générés par PHP.



CREATE TABLE FicheFrais (
    idVisiteur CHAR(4),
    mois CHAR(6),
    nbJustificatifs INT DEFAULT 0,
    montantValide DECIMAL(10,2) DEFAULT 0,
    dateModif DATE,
    idEtat CHAR(2),
    PRIMARY KEY (idVisiteur, mois),
    FOREIGN KEY (idVisiteur) REFERENCES Visiteur(id),
    FOREIGN KEY (idEtat) REFERENCES Etat(id)
)ENGINE=InnoDB;



CREATE TABLE LigneFraisForfait (
    idVisiteur CHAR(4),
    mois CHAR(6),
    idFraisForfait CHAR(3),
    quantite INT DEFAULT 0,
    PRIMARY KEY (idVisiteur, mois, idFraisForfait),
    FOREIGN KEY (idVisiteur, mois)
        REFERENCES FicheFrais(idVisiteur, mois),
    FOREIGN KEY (idFraisForfait)
        REFERENCES FraisForfait(id)
)ENGINE=InnoDB;


CREATE TABLE LigneFraisHorsForfait (
    id INT AUTO_INCREMENT,
    idVisiteur CHAR(4),
    mois CHAR(6),
    libelle VARCHAR(100),
    date DATE,
    montant DECIMAL(10,2),
    PRIMARY KEY (id),
    FOREIGN KEY (idVisiteur, mois)
        REFERENCES FicheFrais(idVisiteur, mois)
)ENGINE=InnoDB;


DROP USER IF EXISTS 'superAdmin'@'localhost';
DROP USER IF EXISTS 'admin'@'localhost';
DROP USER IF EXISTS 'lecteur'@'localhost';
DROP USER IF EXISTS 'responsable'@'localhost';
DROP USER IF EXISTS 'delegue'@'localhost';
DROP USER IF EXISTS 'visiteur'@'localhost';

CREATE USER 'superAdmin'@'localhost' IDENTIFIED BY 'SuperAdmin123!';
GRANT ALL PRIVILEGES ON gsbV2.* TO 'superAdmin'@'localhost';

CREATE USER 'admin'@'localhost' IDENTIFIED BY 'Admin123!';
GRANT SELECT, INSERT, UPDATE ON gsbV2.* TO 'admin'@'localhost';

CREATE USER 'lecteur'@'localhost' IDENTIFIED BY 'Lecteur123!';
GRANT SELECT ON gsbV2.* TO 'lecteur'@'localhost';

CREATE USER 'responsable'@'localhost' IDENTIFIED BY '1234';
GRANT ALL PRIVILEGES ON gsbV2.* TO 'responsable'@'localhost';

CREATE USER 'delegue'@'localhost' IDENTIFIED BY '1234';
GRANT SELECT, INSERT, UPDATE ON gsbV2.* TO 'delegue'@'localhost';

CREATE USER 'visiteur'@'localhost' IDENTIFIED BY '1234';
GRANT SELECT, INSERT, UPDATE ON gsbV2.FicheFrais TO 'visiteur'@'localhost';
GRANT SELECT, INSERT, UPDATE ON gsbV2.LigneFraisForfait TO 'visiteur'@'localhost';
GRANT SELECT ON gsbV2.FraisForfait TO 'visiteur'@'localhost';

FLUSH PRIVILEGES;
