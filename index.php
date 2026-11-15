<?php
require 'config.php';
$rows = db()->query('SELECT id,title_enc,body_enc,created_at FROM contents ORDER BY id DESC LIMIT 12')->fetchAll();
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Brankas · Sistem Informasi Terenkripsi</title><link rel="stylesheet" href="assets/style.css">
<header class="top"><b class="logo">Brankas</b><a class="btn sm" href="login.php">Masuk admin</a></header>
<main>
<section class="hero">
  <h1 id="scr" data-text="Semua isi halaman admin terkunci rapat.">Semua isi halaman admin terkunci rapat.</h1>
  <p>Setiap tulisan dienkripsi dengan AES-256, kuncinya dibungkus RSA-2048, lalu ditandatangani. Pengunjung hanya melihat sandi di bawah ini.</p>
  <div class="chips"><span class="chip v">AES-256-GCM</span><span class="chip c">RSA-2048 OAEP</span><span class="chip m">Tanda tangan SHA-256</span></div>
</section>
<section class="grid">
<?php foreach ($rows as $i => $r): ?>
  <article class="card tilt<?= $i % 3 ?>">
    <small><?= h($r['created_at']) ?></small>
    <h3 class="ct"><?= h(substr($r['title_enc'], 0, 28)) ?>…</h3>
    <p class="ct"><?= h(substr($r['body_enc'], 0, 110)) ?>…</p>
  </article>
<?php endforeach; if (!$rows) echo '<p class="card">Belum ada konten. Masuk sebagai admin untuk menulis yang pertama.</p>'; ?>
</section></main>
<script>
// Satu momen: judul "teracak" lalu terbaca jelas
const el=document.getElementById('scr'),t=el.dataset.text,ch='ABCDEFGHJKLMNPQRSTUVWXYZ0123456789+/=';let f=0;
if(!matchMedia('(prefers-reduced-motion: reduce)').matches){const iv=setInterval(()=>{
 el.textContent=[...t].map((c,i)=>c===' '||i<f?c:ch[Math.random()*ch.length|0]).join('');
 f+=.6;if(f>=t.length){el.textContent=t;clearInterval(iv)}},35)}
</script>
