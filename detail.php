<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$q_device = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE id='$id'");
$device = mysqli_fetch_assoc($q_device);

if (!$device) {
    echo "Perangkat tidak ditemukan! <a href='index.php'>Kembali</a>";
    exit;
}

// Logika Tambah Catatan Penanganan Gangguan oleh Teknisi/Admin
$pesan = "";
if (isset($_POST['tambah_log'])) {
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $teknisi = $_SESSION['username'];
    
    $q_log = "INSERT INTO log_gangguan (perangkat_id, keterangan_gangguan, teknisi) VALUES ('$id', '$ket', '$teknisi')";
    if (mysqli_query($koneksi, $q_log)) {
        $pesan = "<p style='color:green;'>Catatan penanganan berhasil disimpan!</p>";
    } else {
        $pesan = "<p style='color:red;'>Gagal menyimpan catatan.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Perangkat - <?= $device['nama_perangkat']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; margin: 0; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #b30000; margin-top: 0; }
        .info-group { margin-bottom: 15px; }
        .info-group label { font-weight: bold; color: #555; display: block; font-size: 13px; }
        .info-group span { font-size: 16px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; font-size: 14px; }
        th { background: #b30000; color: white; }
        textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; height: 80px; }
        button { background: #b30000; color: white; padding: 8px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { background: #800000; }
        .btn-kembali { display: inline-block; margin-bottom: 15px; color: #333; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <a href="index.php" class="btn-kembali">&larr; Kembali ke Peta Utama</a>
        <h2>Detail Perangkat: <?= $device['nama_perangkat']; ?></h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8f9fa; padding: 15px; border-radius: 6px;">
            <div class="info-group">
                <label>Jenis Perangkat:</label>
                <span><?= $device['jenis']; ?></span>
            </div>
            <div class="info-group">
                <label>Alamat IP:</label>
                <span><?= !empty($device['ip_address']) ? $device['ip_address'] : '<i>Tidak ada (Perangkat Pasif)</i>'; ?></span>
            </div>
            <div class="info-group">
                <label>Kapasitas Port:</label>
                <span><?= !empty($device['kapasitas_port']) ? $device['kapasitas_port'] : '-'; ?></span>
            </div>
            <div class="info-group">
                <label>Koordinat (Lat, Lng):</label>
                <span><?= $device['latitude']; ?>, <?= $device['longitude']; ?></span>
            </div>
        </div>

        <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">

        <h3>Riwayat Penanganan & Catatan Gangguan</h3>
        <?= $pesan; ?>
        
        <!-- Form Tambah Log Catatan -->
        <form action="" method="POST" style="margin-bottom: 20px;">
            <label style="font-weight: bold; font-size: 13px; color: #555;">Tambah Catatan Perbaikan / Laporan Lapangan:</label>
            <textarea name="keterangan" required placeholder="Contoh: Perbaikan konektor ODP karena redaman tinggi / kabel tertekuk..."></textarea>
            <button type="submit" name="tambah_log">Simpan Catatan</button>
        </form>

        <!-- Tabel Log Riwayat -->
        <table>
            <tr>
                <th>Waktu</th>
                <th>Teknisi / Pelapor</th>
                <th>Keterangan Gangguan / Tindakan</th>
            </tr>
            <?php 
            $q_logs = mysqli_query($koneksi, "SELECT * FROM log_gangguan WHERE perangkat_id='$id' ORDER BY id DESC");
            if (mysqli_num_rows($q_logs) > 0) {
                while($log = mysqli_fetch_assoc($q_logs)){
                    echo "<tr>";
                    echo "<td>{$log['tanggal']}</td>";
                    echo "<td><b>{$log['teknisi']}</b></td>";
                    echo "<td>{$log['keterangan_gangguan']}</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3' style='text-align:center; color:#777;'>Belum ada riwayat catatan penanganan untuk perangkat ini.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>