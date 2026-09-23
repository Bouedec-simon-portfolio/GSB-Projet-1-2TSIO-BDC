# Vérification par rapport au sujet du professeur

Référence vérifiée : « PPE Situation 5 : MVC CodeIgniter », septembre.

## Périmètre fonctionnel retenu

L'application reste centrée sur les deux cas d'utilisation demandés :

1. consulter ses fiches de frais ;
2. saisir ou modifier les frais du mois courant.

La connexion est commune aux visiteurs et à l'administrateur. Après validation des identifiants, un visiteur arrive sur ses fiches et le compte administrateur `a00` arrive directement sur la consultation administrative.

## Correspondance avec le sujet

| Demande du sujet | Réponse dans le projet | État |
|---|---|---|
| Migrer l'application de première année vers CodeIgniter 4 | Nouvelle application dans `GSB-CodeIgniter`, ancien site conservé dans `Site-GSB` | Réalisé |
| Utiliser une architecture MVC | Contrôleurs `Auth` et `Frais`, modèles `VisiteurModel` et `FicheModel`, vues dans `app/Views` | Réalisé |
| Consulter une fiche de frais | Liste personnelle puis détail d'une fiche | Réalisé |
| Saisir une fiche de frais | Quantités forfaitaires et frais hors forfait | Réalisé |
| Lire, insérer et modifier la base | Requêtes regroupées dans les modèles CodeIgniter | Réalisé |
| Contrôler les saisies | Mois, date, libellé, montant et quantités contrôlés avant écriture | Réalisé |
| Utiliser GitHub et présenter les versions | Sources placées sur la branche de migration avec historique de commits | Réalisé, démonstration à préparer |
| Fournir le SQL, l'architecture, le modèle, les maquettes, les droits et la recette | Documents préparés ; captures et résultats réels de la VM à compléter | En cours |
| Fournir le cahier de recette final et le suivi Trello/Gantt | Recette prévue dans `docs/RECETTE.md` | À terminer en fin de projet |
| Nommer et partager GitHub/Trello selon la consigne | Nom demandé : `Gr_7_SLAM_[année]_PHP_MVC_[nom]`, partage professeur requis | À vérifier manuellement |
| Déposer sur OneDrive et envoyer par courriel | Action extérieure au code | À faire aux dates demandées |

## Fonctions volontairement simples

- Un seul formulaire de connexion pour tous les comptes.
- Une session contenant l'identité et l'indication administrateur.
- Deux contrôleurs métier seulement.
- Deux modèles seulement.
- Administration limitée à la lecture des fiches.
- Création et réinitialisation des comptes par des commandes locales simples.

Les mots de passe hachés, les sessions, les contrôles de saisie, le jeton CSRF, l'échappement HTML et les requêtes paramétrées sont conservés. Ce sont des protections de base et non des fonctions supplémentaires à présenter comme des modules métier.

## Fonctions hors périmètre

Cette version ne comprend pas :

- la validation ou le remboursement par un comptable ;
- le dépôt de justificatifs ;
- un écran de gestion complète des utilisateurs ;
- l'affichage des mots de passe ;
- une API, l'envoi de courriels ou des statistiques avancées ;
- l'import automatique de l'ancienne base.

Ces éléments ne sont pas demandés dans la fiche de situation et ne doivent pas être ajoutés avant que le socle consultation/saisie soit entièrement testé et compris.
