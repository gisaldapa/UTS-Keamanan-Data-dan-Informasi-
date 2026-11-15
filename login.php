<?php
require 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare('SELECT * FROM users WHERE username=?'); $st->execute([$_POST['username'] ?? '']);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true); $_SESSION['uid'] = $u['id']; $_SESSION['user'] = $u['username'];
        header('Location: admin.php'); exit;
    }
    $err = 'Username atau password salah. Periksa lagi lalu coba masuk.';
}
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk · Brankas</title><link rel="stylesheet" href="assets/style.css">
<body class="center"><form class="card narrow" method="post">
<h1>Masuk ke brankas</h1><p class="muted">Hanya admin yang bisa membuka isi konten.</p>
<?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
<label>Username<input name="username" required autofocus></label>
<label>Password<input name="password" type="password" required></label>
<button class="btn">Masuk</button><a href="index.php" class="link">Kembali ke beranda</a></form>
