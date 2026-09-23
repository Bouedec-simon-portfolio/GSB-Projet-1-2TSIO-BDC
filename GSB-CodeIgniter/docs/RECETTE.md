# Cahier de recette

Base de test dédiée uniquement. Pour chaque essai, noter date, opérateur, résultat et preuve.

Vérifications déjà observées sur GitHub Actions : installation Composer, syntaxe PHP et 21 tests métier réussis. La recette HTTP automatisée avec MariaDB a réussi le 18 septembre 2026 (GitHub Actions, exécution 35355916018). La VM Debian et Apache ne sont pas encore testés.

| ID | Action | Résultat attendu | Résultat VM |
|---|---|---|---|
| T01 | Ouvrir /fiches sans connexion | Redirection vers la connexion | À exécuter |
| T02 | Mauvais mot de passe puis bon mot de passe | Refus puis accès aux fiches | À exécuter |
| T03 | Créer deux fois la fiche du mois | Une seule fiche pour le visiteur et le mois | À exécuter |
| T04 | Saisir 2 REP, 10 KM et un taxi à 12,50 € | Total déclaré 68,70 € ; montant validé inchangé | À exécuter |
| T05 | Modifier les quantités forfaitaires | Valeurs remplacées et total recalculé | À exécuter |
| T06 | Montant négatif, zéro ou trois décimales | Refus et aucune insertion | À exécuter |
| T07 | Date inexistante, future ou hors mois | Refus et aucune insertion | À exécuter |
| T08 | Libellé vide ou supérieur à 100 caractères | Refus côté serveur | À exécuter |
| T09 | Supprimer une ligne appartenant au visiteur | Ligne supprimée et total recalculé | À exécuter |
| T10 | Consulter une fiche appartenant à un autre visiteur | Aucune donnée de cet autre visiteur | À exécuter |
| T11 | Envoyer un ID de ligne appartenant à un autre visiteur | Suppression refusée | À exécuter |
| T12 | Modifier une fiche ancienne, future, CL, VA ou RB | Écriture refusée côté serveur | À exécuter |
| T13 | Envoyer un POST sans jeton CSRF valide | Aucune modification enregistrée | À exécuter |
| T14 | Saisir un libellé contenant une balise script | Texte échappé, aucun script exécuté | À exécuter |
| T15 | Connexion visiteur puis connexion administrateur | Le visiteur arrive sur ses fiches sans lien Administration ; a00 arrive directement sur la liste administrative | À exécuter |
| T16 | Déconnexion puis accès à une ancienne URL | Nouvelle connexion exigée | À exécuter |
| T17 | Recharger la page après un enregistrement | Pas de nouvel envoi du formulaire POST | À exécuter |
| T18 | Contrôler les tables après chaque modification | Correspondance interface et lignes SQL | À exécuter |
| T19 | Ouvrir le site depuis Windows via Apache Debian | Connexion sur le port 8080 | À exécuter |
| T20 | Demander /.env, /app/ et /database/schema.sql | Fichiers privés inaccessibles via HTTP | À exécuter |
| T21 | Redémarrer la VM | Services actifs, données conservées | À exécuter |

Ne pas remplacer les résultats attendus par « réussi » sans exécution réelle.
