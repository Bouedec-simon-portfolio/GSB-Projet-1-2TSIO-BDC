<?php
/** Base MySQL jetable uniquement. Exiger un port explicitement choisi pour éviter la base de travail.
 * GSB_ACCOUNTS_TEST_PORT=13316 php tests/accounts.php
 * Serveur local isolé, utilisateur root sans mot de passe ; aucune VM de travail.
 */
if (PHP_SAPI !== 'cli' || !getenv('GSB_ACCOUNTS_TEST_PORT')) exit("Définir GSB_ACCOUNTS_TEST_PORT pour le serveur MySQL de test isolé.\n");
$root = dirname(__DIR__);
$legacy = dirname($root) . '/Site-GSB';
$port = (int) getenv('GSB_ACCOUNTS_TEST_PORT');
$db = new mysqli('127.0.0.1', 'root', '', '', $port);
$db->set_charset('utf8mb4');
$suffix = bin2hex(random_bytes(5));
$ciDb = 'gsb_accounts_test_' . $suffix;
$oldDb = 'gsb_accounts_legacy_test_' . $suffix;
$servers = [];
$created = [];
$checks = 0;
if (!is_dir(__DIR__ . '/.runtime')) mkdir(__DIR__ . '/.runtime', 0700, true);
function check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
function command(array $args, string $input, string $cwd, array $env): array {
    $p = proc_open(array_merge([PHP_BINARY, '-d', 'xdebug.mode=off'], $args), [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, $cwd, $env);
    fwrite($pipes[0], $input); fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    return [proc_close($p), $out . $err];
}
function sqlScript(mysqli $db, string $sql): void {
    $db->multi_query($sql);
    do { if ($r = $db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
}
function http(string $base, string $path, array $data = null, string $cookie = ''): array {
    $c = curl_init($base . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>10]);
    if ($cookie !== '') curl_setopt($c, CURLOPT_COOKIE, $cookie);
    if ($data !== null) curl_setopt_array($c, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query($data)]);
    $response = curl_exec($c);
    if ($response === false) throw new RuntimeException('Serveur HTTP inaccessible.');
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $html = substr($response, $headerSize);
    preg_match_all('/^Set-Cookie: ([^;\r\n]+)/mi', $headers, $matches);
    curl_close($c);
    return [$status, $headers, $html, implode('; ', $matches[1])];
}
function ciLogin(string $base, string $login, string $password): array {
    [, , $html, $cookie] = http($base, 'connexion');
    check((bool) preg_match('/name="csrf_test_name" value="([^"]+)"/', $html, $token), 'Jeton CSRF absent');
    $result = http($base, 'connexion', ['login'=>$login, 'password'=>$password, 'csrf_test_name'=>$token[1]], $cookie);
    $result[3] = $result[3] ?: $cookie;
    return $result;
}
try {
    foreach ([$ciDb, $oldDb] as $name) {
        $db->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $created[] = $name;
    }
    $db->select_db($ciDb);
    sqlScript($db, file_get_contents($root . '/database/schema.sql'));
    $env = array_merge(getenv(), [
        'CI_ENVIRONMENT'=>'production', 'app.baseURL'=>'http://127.0.0.1:18181/', 'app.indexPage'=>'',
        'database.default.hostname'=>'127.0.0.1', 'database.default.port'=>(string)$port,
        'database.default.database'=>$ciDb, 'database.default.username'=>'root', 'database.default.password'=>'',
        'database.default.DBDriver'=>'MySQLi', 'gsb.adminIds'=>'a00',
    ]);
    [$code, $out] = command(['spark','gsb:accounts'], '', $root, $env);
    check($code === 0, 'Import CodeIgniter : ' . $out);
    $identities = json_decode(file_get_contents($root . '/database/visiteurs.json'), true);
    check(count($identities) === 28, '28 identités attendues');
    foreach ($identities as $row) {
        $stored = $db->query("SELECT * FROM Visiteur WHERE id='" . $db->real_escape_string($row['id']) . "'")->fetch_assoc();
        foreach ($row as $key=>$value) check($stored[$key] === $value, 'Identité non conservée : ' . $key);
        check(password_get_info($stored['mdp'])['algoName'] !== 'unknown', 'Hash initial invalide');
    }
    $before = $db->query('SELECT mdp FROM Visiteur ORDER BY id')->fetch_all();
    check(command(['spark','gsb:accounts'], '', $root, $env)[0] === 0, 'Second import');
    check($before === $db->query('SELECT mdp FROM Visiteur ORDER BY id')->fetch_all(), 'Import non idempotent');
    $password = bin2hex(random_bytes(16));
    foreach (['admin','lvillachane'] as $login) {
        [$code, $out] = command(['spark','gsb:password'], "$login\n$password\n$password\n", $root, $env);
        check($code === 0 && !str_contains($out, $password), 'Réinitialisation CI ou secret affiché');
    }
    check(command(['spark','gsb:password'], "admin\ncourt\ncourt\n", $root, $env)[0] !== 0, 'Mot de passe court accepté');
    check(command(['spark','gsb:password'], "admin\n$password\nautre\n", $root, $env)[0] !== 0, 'Confirmation différente acceptée');
    $hash = $db->query("SELECT mdp FROM Visiteur WHERE id='a00'")->fetch_assoc()['mdp'];
    check(password_verify($password, $hash), 'Échec de hash après refus');
    $server = proc_open([PHP_BINARY,'-d','xdebug.mode=off','-S','127.0.0.1:18181','-t','public','public/index.php'], [0=>['pipe','r'],1=>['file',__DIR__.'/.runtime/http.log','a'],2=>['file',__DIR__.'/.runtime/http.log','a']], $pipes, $root, $env);
    $servers[] = $server;
    usleep(700000);
    $base = 'http://127.0.0.1:18181/';
    $visitor = ciLogin($base, 'lvillachane', $password);
    check($visitor[0] === 303 || $visitor[0] === 302, 'Connexion visiteur refusée');
    check(str_contains($visitor[1], '/fiches'), 'Destination visiteur incorrecte');
    check(http($base, 'administration', null, $visitor[3])[0] === 403, 'Droits administrateur pour visiteur');
    $admin = ciLogin($base, 'admin', $password);
    check(str_contains($admin[1], '/administration'), 'Destination administrateur incorrecte');
    $page = http($base, 'administration', null, $admin[3]);
    check($page[0] === 200 && !str_contains($page[2], $password) && !str_contains($page[2], $hash), 'Administration ou secret exposé');
    check(str_contains(ciLogin($base, 'admin', 'incorrect')[1], '/connexion'), 'Mauvais mot de passe accepté');
    // Un secret en clair en base est refusé, puis remplacé par un hash aléatoire.
    $db->query("UPDATE Visiteur SET mdp='test-invalide' WHERE id='a17'");
    check(str_contains(ciLogin($base, 'dandre', 'test-invalide')[1], '/connexion'), 'Clair accepté par CI');
    check(command(['spark','gsb:accounts'], '', $root, $env)[0] === 0, 'Migration CI');
    $plainHash = $db->query("SELECT mdp FROM Visiteur WHERE id='a17'")->fetch_assoc()['mdp'];
    check(password_get_info($plainHash)['algoName'] !== 'unknown' && !password_verify('test-invalide', $plainHash), 'Ancien secret conservé');
    // Ancien site : import SQL, réinitialisation et connexions HTTP réelles.
    $sql = file_get_contents($legacy . '/GSB_V2.sql');
    $sql = preg_replace('/DROP DATABASE IF EXISTS gsbV2;\s*/', '', $sql);
    $sql = str_replace(['CREATE DATABASE gsbV2', 'USE gsbV2;'], ["USE `$oldDb`;\n-- CREATE DATABASE", "USE `$oldDb`;"], $sql);
    // Retirer la déclaration CREATE DATABASE et ses deux lignes de charset.
    $sql = preg_replace('/-- CREATE DATABASE\s*CHARACTER SET utf8mb4\s*COLLATE utf8mb4_general_ci;/', '', $sql);
    sqlScript($db, $sql);
    check((int)$db->query('SELECT COUNT(*) FROM Visiteur')->fetch_row()[0] === 28, 'Import SQL incomplet');
    $oldEnv = array_merge(getenv(), ['GSB_DB_HOST'=>'127.0.0.1','GSB_DB_PORT'=>(string)$port,'GSB_DB_NAME'=>$oldDb,'GSB_DB_USER'=>'root','GSB_DB_PASS'=>'']);
    foreach (['a00','a131'] as $id) {
        [$code, $out] = command(['account.php','reset',$id], "$password\n$password\n", $legacy, $oldEnv);
        check($code === 0 && !str_contains($out, $password), 'Reset ancien site : ' . $out);
    }
    check(command(['account.php','reset','a00'], "court\ncourt\n", $legacy, $oldEnv)[0] !== 0, 'Secret court ancien site');
    check(command(['account.php','create','t001','localtest','Test','Local'], "$password\n$password\n", $legacy, $oldEnv)[0] === 0, 'Création ancien site');
    check(command(['spark','gsb:user'], "t001\nlocaltest\nTest\nLocal\n$password\n$password\n", $root, $env)[0] === 0, 'Création CI');
    $servers[] = proc_open([PHP_BINARY,'-d','xdebug.mode=off','-d','session.save_path='.__DIR__.'/.runtime','-S','127.0.0.1:18182','-t','.'], [0=>['pipe','r'],1=>['file',__DIR__.'/.runtime/http.log','a'],2=>['file',__DIR__.'/.runtime/http.log','a']], $pipes, $legacy, $oldEnv);
    usleep(700000);
    $oldBase = 'http://127.0.0.1:18182/';
    foreach (['admin','lvillachane'] as $login) {
        $res = http($oldBase, 'connexion.php', ['login'=>$login,'password'=>$password]);
        check(str_contains($res[1], 'tableau-bord.php'), 'Connexion ancien site : ' . $login);
        $res = http($oldBase, 'admin-fiches.php', null, $res[3]);
        check($login === 'admin' ? $res[0] === 200 : str_contains($res[1], 'tableau-bord.php'), 'Droits ancien site');
        check(!str_contains($res[2], $password), 'Mot de passe visible ancien site');
    }
    $db->query("UPDATE Visiteur SET mdp='test-invalide' WHERE id='a17'");
    check(!str_contains(http($oldBase, 'connexion.php', ['login'=>'dandre','password'=>'test-invalide'])[1], 'tableau-bord.php'), 'Clair accepté ancien site');
    check(command(['hash-passwords.php'], '', $legacy, $oldEnv)[0] === 0, 'Migration ancien site');
    $hash = $db->query("SELECT mdp FROM Visiteur WHERE id='a17'")->fetch_assoc()['mdp'];
    check(password_get_info($hash)['algoName'] !== 'unknown' && !password_verify('test-invalide', $hash), 'Migration ancien site incorrecte');
    foreach (['account.php','create-admin.php','hash-passwords.php'] as $path) check(http($oldBase, $path)[0] === 403, 'Commande locale accessible HTTP');
    $replacement = bin2hex(random_bytes(16));
    check(command(['spark','gsb:password'], "admin\n$replacement\n$replacement\n", $root, $env)[0] === 0, 'Second reset CI');
    check(str_contains(ciLogin($base, 'admin', $password)[1], '/connexion'), 'Ancien secret CI encore accepté');
    check(str_contains(ciLogin($base, 'admin', $replacement)[1], '/administration'), 'Nouveau secret CI refusé');
    check(command(['account.php','reset','a00'], "$replacement\n$replacement\n", $legacy, $oldEnv)[0] === 0, 'Second reset ancien site');
    check(!str_contains(http($oldBase, 'connexion.php', ['login'=>'admin','password'=>$password])[1], 'tableau-bord.php'), 'Ancien secret encore accepté');
    check(str_contains(http($oldBase, 'connexion.php', ['login'=>'admin','password'=>$replacement])[1], 'tableau-bord.php'), 'Nouveau secret refusé');
    echo "$checks contrôles réussis : 28 identités, hash, import idempotent, création, reset, refus clair/court, connexions visiteur/admin, droits et absence de secrets affichés.\n";
} finally {
    foreach ($servers as $server) { proc_terminate($server); proc_close($server); }
    foreach ($created as $name) $db->query("DROP DATABASE `$name`");
}
