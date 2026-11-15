<?php
session_start();
const DB_HOST = 'localhost', DB_NAME = 'securevault', DB_USER = 'root', DB_PASS = '';
const KEY_PASS = 'ganti-passphrase-ini';          // passphrase pelindung private key
const ADMIN_USER = 'admin', ADMIN_PASS = 'admin123'; // akun admin yang dibuat otomatis
define('KEY_DIR', __DIR__ . '/keys/');

/* Semua disiapkan otomatis: database, tabel, kunci RSA, akun admin.
   Memakai MySQL (Laragon/XAMPP); jika tidak tersedia, otomatis memakai SQLite. */
function db(): PDO {
    static $p;
    if ($p) return $p;
    if (!is_dir(KEY_DIR)) mkdir(KEY_DIR, 0777, true);
    $o = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    try {
        $p = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $o);
        $p->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
        $p->exec('USE `' . DB_NAME . '`');
        $ai = 'INT AUTO_INCREMENT PRIMARY KEY'; $big = 'MEDIUMTEXT'; $ts = 'TIMESTAMP';
    } catch (Throwable $e) {
        $p = new PDO('sqlite:' . KEY_DIR . 'data.sqlite', null, null, $o);
        $ai = 'INTEGER PRIMARY KEY AUTOINCREMENT'; $big = 'TEXT'; $ts = 'DATETIME';
    }
    $p->exec("CREATE TABLE IF NOT EXISTS users (id $ai, username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL, created_at $ts DEFAULT CURRENT_TIMESTAMP)");
    $p->exec("CREATE TABLE IF NOT EXISTS contents (id $ai, title_enc TEXT NOT NULL, body_enc $big NOT NULL,
        enc_key TEXT NOT NULL, signature TEXT NOT NULL, created_at $ts DEFAULT CURRENT_TIMESTAMP)");
    $st = $p->prepare('SELECT COUNT(*) FROM users WHERE username=?'); $st->execute([ADMIN_USER]);
    if (!$st->fetchColumn())
        $p->prepare('INSERT INTO users(username,password_hash) VALUES(?,?)')->execute([ADMIN_USER, password_hash(ADMIN_PASS, PASSWORD_DEFAULT)]);
    ensure_keys();
    return $p;
}
function ensure_keys(): void {
    if (is_file(KEY_DIR . 'private.pem')) return;
    $cnf = str_replace('\\', '/', KEY_DIR) . 'openssl.cnf';   // config minimal agar OpenSSL di Windows tidak error
    file_put_contents($cnf, "[req]\ndistinguished_name=dn\n[dn]\n");
    $k = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $cnf]);
    if (!$k) die('Gagal membuat kunci RSA: ' . openssl_error_string() . ' (aktifkan extension openssl di php.ini)');
    openssl_pkey_export($k, $priv, KEY_PASS, ['config' => $cnf]);
    file_put_contents(KEY_DIR . 'private.pem', $priv);
    file_put_contents(KEY_DIR . 'public.pem', openssl_pkey_get_details($k)['key']);
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function need_login() { if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; } }
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }

/* ---------- SIMETRIS: AES-256-GCM ---------- */
function aes_enc(string $plain, string $key): string {
    $iv = random_bytes(12);
    $ct = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $ct);
}
function aes_dec(string $b64, string $key): string|false {
    $r = base64_decode($b64);
    return openssl_decrypt(substr($r, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($r, 0, 12), substr($r, 12, 16));
}

/* ---------- ASIMETRIS: RSA-2048 ---------- */
function pub_key()  { db(); return openssl_pkey_get_public(file_get_contents(KEY_DIR . 'public.pem')); }
function priv_key() { db(); return openssl_pkey_get_private(file_get_contents(KEY_DIR . 'private.pem'), KEY_PASS); }

/* Hybrid: konten dienkripsi AES, kunci AES dibungkus RSA publik, hasilnya ditandatangani RSA privat */
function seal(string $title, string $body): array {
    $key = random_bytes(32);
    $t = aes_enc($title, $key);
    $b = aes_enc($body, $key);
    openssl_public_encrypt($key, $wrapped, pub_key(), OPENSSL_PKCS1_OAEP_PADDING);
    openssl_sign($t . $b, $sig, priv_key(), OPENSSL_ALGO_SHA256);
    return [$t, $b, base64_encode($wrapped), base64_encode($sig)];
}
function unseal(array $r): array {
    $ok = openssl_verify($r['title_enc'] . $r['body_enc'], base64_decode($r['signature']), pub_key(), OPENSSL_ALGO_SHA256) === 1;
    if (!openssl_private_decrypt(base64_decode($r['enc_key']), $key, priv_key(), OPENSSL_PKCS1_OAEP_PADDING))
        return ['(gagal dekripsi)', '', false];
    return [aes_dec($r['title_enc'], $key), aes_dec($r['body_enc'], $key), $ok];
}
