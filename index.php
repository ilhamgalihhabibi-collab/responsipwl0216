<?php
// index.php
require_once 'config.php';

$mysqli = db_connect();

// ambil daftar event yang kuota > 0
$sql = "SELECT id, nama_event, harga, kuota FROM event WHERE kuota > 0 ORDER BY id";
$result = $mysqli->query($sql);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Event</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; width: 100%; max-width: 800px; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
    th { background:#f4f4f4; }
    .btn { padding:6px 10px; background:#2d89ef; color:white; text-decoration:none; border-radius:4px; }
    .note { margin-top:12px; color:#555; }
  </style>
</head>
<body>
  <h1>Daftar Event (Kuota Tersedia)</h1>

  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nama Event</th>
          <th>Harga</th>
          <th>Kuota</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['id']); ?></td>
          <td><?php echo htmlspecialchars($row['nama_event']); ?></td>
          <td><?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
          <td><?php echo htmlspecialchars($row['kuota']); ?></td>
          <td><a class="btn" href="beli.php?id=<?php echo urlencode($row['id']); ?>">Beli Tiket</a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>Tidak ada event dengan kuota tersedia.</p>
  <?php endif; ?>

  <p class="note">Catatan: untuk pengujian, pastikan ada event dengan kuota 1 atau 2.</p>
</body>
</html>
<?php
$mysqli->close();
?>