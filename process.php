<?php
// process.php
require_once 'config.php';

// validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ambil data POST dengan trimming
$event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
$nama_pembeli = isset($_POST['nama_pembeli']) ? trim($_POST['nama_pembeli']) : '';
$email_pembeli = isset($_POST['email_pembeli']) ? trim($_POST['email_pembeli']) : '';

// validasi sederhana
$errors = [];
if ($event_id <= 0) $errors[] = 'ID event tidak valid.';
if ($nama_pembeli === '') $errors[] = 'Nama pembeli harus diisi.';
if ($email_pembeli === '' || !filter_var($email_pembeli, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';

if (!empty($errors)) {
    foreach ($errors as $err) {
        echo '<p style="color:red;">' . htmlspecialchars($err) . '</p>';
    }
    echo '<p><a href="javascript:history.back()">Kembali</a></p>';
    exit;
}

$mysqli = db_connect();

try {
    // mulai transaksi
    $mysqli->begin_transaction();

    // ambil kuota dengan lock (FOR UPDATE) untuk mencegah race condition
    $stmt = $mysqli->prepare("SELECT kuota, nama_event FROM event WHERE id = ? FOR UPDATE");
    if (!$stmt) throw new Exception($mysqli->error);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $event = $res->fetch_assoc();
    $stmt->close();

    if (!$event) {
        throw new Exception('Event tidak ditemukan.');
    }

    $kuota = (int)$event['kuota'];

    if ($kuota <= 0) {
        // rollback, kuota habis
        $mysqli->rollback();
        echo '<p style="color:red;">Maaf, kuota tiket telah habis untuk event: ' . htmlspecialchars($event['nama_event']) . '</p>';
        echo '<p><a href="index.php">Kembali ke daftar event</a></p>';
        $mysqli->close();
        exit;
    }

    // update kuota (kurangi 1)
    $stmt = $mysqli->prepare("UPDATE event SET kuota = kuota - 1 WHERE id = ?");
    if (!$stmt) throw new Exception($mysqli->error);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        // sesuatu aneh, rollback
        $stmt->close();
        throw new Exception('Gagal mengurangi kuota.');
    }
    $stmt->close();

    // generate kode tiket (unik sederhana)
    $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $kode_tiket = 'TKT' . date('Ymd') . $random; // contoh: TKT20251111ABC12345

    // insert ke tabel pembelian
    $stmt = $mysqli->prepare("INSERT INTO pembelian (event_id, nama_pembeli, email_pembeli, kode_tiket) VALUES (?, ?, ?, ?)");
    if (!$stmt) throw new Exception($mysqli->error);
    $stmt->bind_param('isss', $event_id, $nama_pembeli, $email_pembeli, $kode_tiket);
    $stmt->execute();
    $insert_id = $stmt->insert_id;
    $stmt->close();

    // commit jika semua ok
    $mysqli->commit();

    // tampilkan sukses
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Pembelian Berhasil</title>
      <style>
        body { font-family: Arial, sans-serif; padding:20px; }
        .card { max-width:600px; border:1px solid #eee; padding:16px; border-radius:8px; background:#fafafa; }
        .ok { color: #155724; background:#d4edda; border:1px solid #c3e6cb; padding:8px; border-radius:4px; }
      </style>
    </head>
    <body>
      <div class="card">
        <h2>Pembelian Berhasil</h2>
        <p class="ok"><strong>Terima kasih, <?php echo htmlspecialchars($nama_pembeli); ?>.</strong></p>
        <p>Event: <strong><?php echo htmlspecialchars($event['nama_event']); ?></strong></p>
        <p>Kode Tiket: <strong><?php echo htmlspecialchars($kode_tiket); ?></strong></p>
        <p>Email: <?php echo htmlspecialchars($email_pembeli); ?></p>
        <p>ID Pembelian: <?php echo htmlspecialchars($insert_id); ?></p>
        <p><a href="index.php">Kembali ke daftar event</a></p>
      </div>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    // rollback jika terjadi error
    if ($mysqli->in_transaction) {
        $mysqli->rollback();
    }
    echo '<p style="color:red;">Terjadi kesalahan: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="index.php">Kembali</a></p>';
} finally {
    $mysqli->close();
}
?>