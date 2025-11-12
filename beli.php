<?php
// beli.php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID event tidak valid.');
}

$event_id = (int) $_GET['id'];
$mysqli = db_connect();

// ambil detail event (minimal nama & kuota)
$stmt = $mysqli->prepare("SELECT id, nama_event, harga, kuota FROM event WHERE id = ?");
$stmt->bind_param('i', $event_id);
$stmt->execute();
$res = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$event) {
    die('Event tidak ditemukan.');
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Beli Tiket - <?php echo htmlspecialchars($event['nama_event']); ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    form { max-width: 480px; background:#f9f9f9; padding:16px; border-radius:6px; border:1px solid #eee; }
    label { display:block; margin-top:8px; }
    input[type="text"], input[type="email"] { width:100%; padding:8px; margin-top:4px; box-sizing:border-box; }
    button { margin-top:12px; padding:8px 12px; border:none; background:#28a745; color:#fff; border-radius:4px; cursor:pointer; }
    .info { margin-bottom:12px; }
  </style>
</head>
<body>
  <h1>Beli Tiket</h1>
  <div class="info">
    <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong><br>
    Harga: <?php echo number_format($event['harga'],0,',','.'); ?><br>
    Kuota tersisa: <?php echo htmlspecialchars($event['kuota']); ?>
  </div>

  <?php if ((int)$event['kuota'] <= 0): ?>
    <p>Maaf, kuota event ini telah habis.</p>
    <p><a href="index.php">Kembali ke daftar event</a></p>
  <?php else: ?>
    <form method="POST" action="process.php" novalidate>
      <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
      <label>Nama Lengkap
        <input type="text" name="nama_pembeli" required maxlength="255" placeholder="Masukkan nama lengkap">
      </label>

      <label>Email
        <input type="email" name="email_pembeli" required maxlength="255" placeholder="contoh@domain.com">
      </label>

      <button type="submit">Beli Sekarang</button>
    </form>
    <p><a href="index.php">Kembali ke daftar event</a></p>
  <?php endif; ?>
</body>
</html>