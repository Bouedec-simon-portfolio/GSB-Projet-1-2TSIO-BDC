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
    login VARCHAR(20) NOT NULL UNIQUE,
    mdp VARCHAR(255) NOT NULL,
    adresse VARCHAR(100),
    cp CHAR(5),
    ville VARCHAR(50),
    dateEmbauche DATE,
    PRIMARY KEY (id)
)ENGINE=InnoDB;

-- 28 comptes conservés. Hash PHP de secrets aléatoires non conservés.
-- Définir localement un nouveau mot de passe avec php account.php reset ID.
INSERT INTO Visiteur VALUES
('a00','Admin','Système','admin','$2y$10$LiReQyR8EPrVpZsCR/5Sb.bCQYbUL82fxyt8yUAdbjl5NeBNY16d2','123 rue Admin','75000','Paris','2026-01-01'),
('a131','Villechalane','Louis','lvillachane','$2y$10$wsg64m4Yti0W6NXsrClHUu.xxXhq4RoX5exKeToaf.pXd1dDjie02','8 rue des Charmes','46000','Cahors','2005-12-21'),
('a17','Andre','David','dandre','$2y$10$zXxKxPVm48pVUwxuOfrIseoLCkETgKeIsxm8Tlzb4vc78ROX0QHwa','1 rue Petit','46200','Lalbenque','1998-11-23'),
('a55','Bedos','Christian','cbedos','$2y$10$WlmTRAclsYjjLWxto4MdreqGRgT0js4nUYjcAThp0INqmTYtJuFyO','1 rue Peranud','46250','Montcuq','1995-01-12'),
('a93','Tusseau','Louis','ltusseau','$2y$10$PcXUwJ5/oFQ8Rs.YZ5ItieHYsfmI4jvqrXyaKsS4mZzOld/gDqu7i','22 rue des Ternes','46123','Gramat','2000-05-01'),
('b13','Bentot','Pascal','pbentot','$2y$10$0qiKsfVaZsCscDH/Tj7Sh.AdOo7z/fjTMk67AYSj7FRjZbCTaz/fu','11 allée des Cerises','46512','Bessines','1992-07-09'),
('b16','Bioret','Luc','lbioret','$2y$10$FgnqdwQVyT8AXYWw30vpGO9VtvQ4R.vWE83wK3Wd3oxGmn77ffs4K','1 Avenue gambetta','46000','Cahors','1998-05-11'),
('b19','Bunisset','Francis','fbunisset','$2y$10$WO6HZCIJmgwm1l95l4rAM.Pr0J4w.7CWXYCnJe19EPH.CGUpWq0qm','10 rue des Perles','93100','Montreuil','1987-10-21'),
('b25','Bunisset','Denise','dbunisset','$2y$10$KrP462s4RZzjDz.7gKKK0O35b32sjt35eK/3vwQ5IMzl5ELKzbA4K','23 rue Manin','75019','Paris','2010-12-05'),
('b28','Cacheux','Bernard','bcacheux','$2y$10$1dUzMQZODwNcpSKyqLwQq.nGeewrhR6PJ7FLE0UbzsFvxBL5ri5IW','114 rue Blanche','75017','Paris','2009-11-12'),
('b34','Cadic','Eric','ecadic','$2y$10$XQbEMkg7k4VELjcmKewTx.x1iChskHd6N0qzj8XPGpDSE0V4ab5DW','123 avenue de la République','75011','Paris','2008-09-23'),
('b4','Charoze','Catherine','ccharoze','$2y$10$5zqFh29KZkjhqf5tSdVoKuApBCzXUyj2kQNKz3gMlZJG4lm5CSRhO','100 rue Petit','75019','Paris','2005-11-12'),
('b50','Clepkens','Christophe','cclepkens','$2y$10$.diZy2OmmfJyVcFHLp9WGe/Nd9MK.vt6MbRQOu6pzI6jpd79i1Edu','12 allée des Anges','93230','Romainville','2003-08-11'),
('b59','Cottin','Vincenne','vcottin','$2y$10$92gyaaW/aDriFbXRcKAcUe6XM3ei8zq1Eemtx.Btxz14TsNuwAntS','36 rue Des Roches','93100','Monteuil','2001-11-18'),
('c14','Daburon','François','fdaburon','$2y$10$K6vnNgvPOJc4BEDIU4uuRO4HUsTHLp.Z/IdPLUuvW8nXXjnAzfhVS','13 rue de Chanzy','94000','Créteil','2002-02-11'),
('c3','De','Philippe','pde','$2y$10$5gh07w7fExcjpMsDnGmmmOLAlOLd/omdr.xqLX0LuzedL9SV9cm.O','13 rue Barthes','94000','Créteil','2010-12-14'),
('c54','Debelle','Michel','mdebelle','$2y$10$J.2DxeEsX.Es3n3om0UB5uhyJpLKmAuw2LSefWSrh6u3S1Yv7gtFe','181 avenue Barbusse','93210','Rosny','2006-11-23'),
('d13','Debelle','Jeanne','jdebelle','$2y$10$Vff5QGo.w3pwUpP9MWRo/.QaOz5F3Ojopnlt9R9Jaxrfa/jDiMBZm','134 allée des Joncs','44000','Nantes','2000-05-11'),
('d51','Debroise','Michel','mdebroise','$2y$10$bBPdiONzeSs1tAAVcCA1l.ahrdUU3DK1FgAFeZXaFxAKhBYBnxsV2','2 Bld Jourdain','44000','Nantes','2001-04-17'),
('e22','Desmarquest','Nathalie','ndesmarquest','$2y$10$HGR3AKAEzGEh5P8zfCoGyuq54gT73ZCgKDNP6Osne31L.BzpMhj8m','14 Place d Arc','45000','Orléans','2005-11-12'),
('e24','Desnost','Pierre','pdesnost','$2y$10$FDqm2d7pU/zn/pw1dNMIqO6UvKACtwz0fOTNH/NT43Gm8mJBEbX5S','16 avenue des Cèdres','23200','Guéret','2001-02-05'),
('e39','Dudouit','Frédéric','fdudouit','$2y$10$FFLEMqlTc2JwcBCrdMD.DuDWnUbCD7D0di2lgbs1wPDMH/6MouNuu','18 rue de l église','23120','GrandBourg','2000-08-01'),
('e49','Duncombe','Claude','cduncombe','$2y$10$JpzNZfUoonRwYMI7u32x9.Kqy2otwQet2CCrKGu6vI5nzF9xA0i5e','19 rue de la tour','23100','La souterraine','1987-10-10'),
('e5','Enault-Pascreau','Céline','cenault','$2y$10$Q9ZEgAeyt1ZnQTcf0vLy1eA407HEAdcPnzqN/NohIbPBqFqML7xua','25 place de la gare','23200','Gueret','1995-09-01'),
('e52','Eynde','Valérie','veynde','$2y$10$fAKzj.7zzIDOIDD615s2qO3DEY60oMcsZSgArCORBAXsFZI68LS2e','3 Grand Place','13015','Marseille','1999-11-01'),
('f21','Finck','Jacques','jfinck','$2y$10$42EKlijB6NUfUVSZ9eiq7u92xweshQ0kV0zbGfvyycc/m5e0a1HFG','10 avenue du Prado','13002','Marseille','2001-11-10'),
('f39','Frémont','Fernande','ffremont','$2y$10$HgbhM9PVZBsn3.QWOWwu2ORQ3h3M.Y6oxvVnhXyV/peaUCPDHvWvW','4 route de la mer','13012','Allauh','1998-10-01'),
('f4','Gest','Alain','agest','$2y$10$M/z0liV9.c5l48g4USeFV.6azt9C0ZTnokNc9sAHE7KP4oOQAA.ee','30 avenue de la mer','13025','Berre','1985-11-01');

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


-- Configurer séparément l'utilisateur MySQL et ses droits sur la VM.
-- Aucun mot de passe de connexion MySQL n'est fourni dans ce dépôt.
