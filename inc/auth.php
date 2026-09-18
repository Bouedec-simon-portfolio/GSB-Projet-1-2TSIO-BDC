<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Retourne les informations de l'utilisateur connecté ou null.
 *
 * @return array|null
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Indique si un utilisateur est connecté.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']['id']);
}

/**
 * Liste des IDs visiteur qui sont admins.
 *
 * @return array
 */
function getAdminIds(): array
{
    return ADMIN_IDS;
}

/**
 * Vérifie si l'utilisateur actuel est un admin.
 *
 * @return bool
 */
function isAdmin(): bool
{
    $user = currentUser();
    return $user ? in_array($user['id'], getAdminIds(), true) : false;
}

/**
 * Redirige vers la connexion si l'utilisateur n'est pas connecté,
 * ou vers le tableau de bord si l'utilisateur connecté n'est pas admin.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        header('Location: connexion.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: tableau-bord.php');
        exit;
    }
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: connexion.php');
        exit;
    }
}

/**
 * Tente de connecter un utilisateur.
 *
 * @param string $login
 * @param string $password
 * @return bool
 */
function login(string $login, string $password): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, nom, prenom, login, mdp FROM Visiteur WHERE login = :login LIMIT 1');
    $stmt->execute(['login' => $login]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }

    $stored = $user['mdp'];
    $valid = false;
    $shouldRehash = false;

    if (password_verify($password, $stored)) {
        $valid = true;
        $shouldRehash = password_needs_rehash($stored, PASSWORD_DEFAULT);
    } elseif (hash_equals($stored, $password)) {
        $valid = true;
        $shouldRehash = true;
    }

    if (!$valid) {
        return false;
    }

    if ($shouldRehash) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        if ($newHash !== false) {
            $update = $pdo->prepare('UPDATE Visiteur SET mdp = :mdp WHERE id = :id');
            $update->execute(['mdp' => $newHash, 'id' => $user['id']]);
        }
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'login' => $user['login'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
    ];

    return true;
}

/**
 * Déconnecte l'utilisateur courant.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
