-- Importer UNE SEULE FOIS dans une base vide dédiée. Aucun DROP.
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


