# Préparer la démonstration

Présenter le besoin : permettre au visiteur médical de déclarer et consulter ses frais. Expliquer pourquoi le code initial a été séparé en modèles, vues et contrôleurs sous CodeIgniter 4.

Démonstration : connexion ; création du mois ; 2 repas et 10 km ; taxi de 12,50 € ; total de 68,70 € ; saisie négative refusée ; consultation d’une fiche ancienne ; déconnexion. Montrer ensuite un commit et expliquer son changement.

Questions à maîtriser :
- Route : associe une URL et une méthode HTTP à une méthode de contrôleur.
- Modèle : effectue les accès SQL. Le pilote utilisé ici est MySQLi via CodeIgniter, alors que l’ancien projet utilisait PDO.
- Vue : produit le HTML et échappe les données avec esc().
- Session : conserve l’identité côté serveur entre les requêtes.
- CSRF : jeton associé à la session, vérifié pour les requêtes qui modifient des données.
- Transaction : enregistre toutes les opérations ensemble, sinon les annule.
- Clé composée : idVisiteur et mois identifient ensemble une fiche.
- Contrôle de propriété : l’ID du visiteur vient de la session, jamais d’un champ utilisateur.
- LAMP : Linux, Apache, MariaDB/MySQL et PHP ; MVC est une organisation du code, pas un serveur.
- Git : historique local ; GitHub : dépôt distant et revue des changements.

Présenter uniquement les opérations que vous avez réellement comprises et réalisées. Expliquer les limites : validation comptable et pièces jointes non implémentées ; recette du serveur à terminer si elle ne l’est pas encore.
