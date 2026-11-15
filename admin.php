<?php
require 'config.php'; need_login();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf(), $_POST['csrf'] ?? '')) die('Token tidak valid');
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['act'] ?? '') === 'save' && trim($_POST['title'] ?? '') !== '') {
        [$t, $b, $k, $s] = seal(trim($_POST['title']), trim($_POST['body'] ?? ''));
        if ($id) $pdo->prepare('UPDATE contents SET title_enc=?,body_enc=?,enc_key=?,signature=? WHERE id=?')->execute([$t, $b, $k, $s, $id]);
        else     $pdo->prepare('INSERT INTO contents(title_enc,body_enc,enc_key,signature) VALUES(?,?,?,?)')->execute([$t, $b, $k, $s]);
    }
    if (($_POST['act'] ?? '') === 'delete') $pdo->prepare('DELETE FROM contents WHERE id=?')->execute([$id]);
    header('Location: admin.php'); exit;
}
$rows = $pdo->query('SELECT * FROM contents ORDER BY id DESC')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) foreach ($rows as $r) if ($r['id'] == $_GET['edit']) { [$et, $eb] = unseal($r); $edit = ['id' => $r['id'], 't' => $et, 'b' => $eb]; }
$fp = substr(hash('sha256', file_get_contents(KEY_DIR . 'public.pem')), 0, 24);
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · Brankas</title><link rel="stylesheet" href="assets/style.css">
<header class="top"><b class="logo">Brankas</b><span>Halo, <?= h($_SESSION['user']) ?> · <a href="index.php">Lihat publik</a> · <a href="logout.php">Keluar</a></span></header>
<main class="admin">
<aside>
  <form class="card" method="post">
    <h2><?= $edit ? 'Ubah konten' : 'Tulis konten baru' ?></h2>
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? 0 ?>">
    <label>Judul<input name="title" required value="<?= h($edit['t'] ?? '') ?>"></label>
    <label>Isi<textarea name="body" rows="7"><?= h($edit['b'] ?? '') ?></textarea></label>
    <button class="btn">Enkripsi dan simpan</button>
    <?php if ($edit) echo '<a class="link" href="admin.php">Batal</a>'; ?>
  </form>
  <div class="card keys"><h3>Kunci aktif</h3>
    <p>Simetris: AES-256-GCM, kunci acak per konten.</p>
    <p>Asimetris: RSA-2048, sidik jari publik <code><?= h($fp) ?></code></p></div>
</aside>
<section>
<?php foreach ($rows as $r): [$t, $b, $ok] = unseal($r); ?>
  <article class="card">
    <div class="row"><h3><?= h($t) ?></h3>
      <span class="chip <?= $ok ? 'm' : 'c' ?>"><?= $ok ? 'Tanda tangan valid' : 'Data berubah!' ?></span></div>
    <p><?= nl2br(h($b)) ?></p>
    <details><summary>Lihat versi terenkripsi di database</summary>
      <code>judul: <?= h(substr($r['title_enc'], 0, 80)) ?>…</code>
      <code>isi: <?= h(substr($r['body_enc'], 0, 160)) ?>…</code>
      <code>kunci AES (dibungkus RSA): <?= h(substr($r['enc_key'], 0, 80)) ?>…</code></details>
    <form method="post" class="acts"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>">
      <a class="btn sm ghost" href="?edit=<?= $r['id'] ?>">Ubah</a>
      <button class="btn sm danger" name="act" value="delete" onclick="return confirm('Hapus konten ini?')">Hapus</button></form>
  </article>
<?php endforeach; if (!$rows) echo '<p class="card">Belum ada konten. Tulis yang pertama di formulir sebelah kiri.</p>'; ?>
</section></main>
