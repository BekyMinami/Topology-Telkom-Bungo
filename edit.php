<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Ambil ID dari URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$query = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data perangkat tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Proses update saat form disubmit
if (isset($_POST['update'])) {
    $nama_perangkat = mysqli_real_escape_string($koneksi, $_POST['nama_perangkat']);
    $jenis          = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $ip_address     = mysqli_real_escape_string($koneksi, $_POST['ip_address']);
    $kapasitas_port = mysqli_real_escape_string($koneksi, $_POST['kapasitas_port']);
    $parent_id      = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";
    $latitude       = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $longitude      = mysqli_real_escape_string($koneksi, $_POST['longitude']);

    // Perbaikan penulisan query untuk parent_id (jika NULL tidak usah pakai tanda kutip tunggal)
    $parent_sql = ($parent_id === "NULL") ? "NULL" : "'$parent_id'";

    $update_query = "UPDATE perangkat SET 
        nama_perangkat = '$nama_perangkat', 
        jenis = '$jenis', 
        ip_address = '$ip_address', 
        kapasitas_port = '$kapasitas_port', 
        parent_id = $parent_sql, 
        latitude = '$latitude', 
        longitude = '$longitude' 
        WHERE id = '$id'";

    if (mysqli_query($koneksi, $update_query)) {
        echo "<script>alert('Perangkat berhasil diperbarui!'); window.location='index.php';</script>";
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Perangkat - NMS Telkom Bungo</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .form-container { max-width: 600px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #b30000; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select { width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #b30000; color: white; }
        .btn-primary:hover { background: #800000; }
        .btn-secondary { background: #6c757d; color: white; margin-left: 10px; }
        .alert { background: #dc3545; color: white; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Data Perangkat</h2>
    <p style="color: #666; font-size: 13px;">PT Telkom Indonesia Wilayah Bungo</p>

    <?php if (isset($error)): ?>
        <div class="alert"><?= $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>Nama Perangkat / Node:</label>
            <input type="text" name="nama_perangkat" value="<?= htmlspecialchars($data['nama_perangkat'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Jenis Perangkat:</label>
            <select name="jenis" required>
                <option value="Router" <?= (($data['jenis'] ?? '') == 'Router') ? 'selected' : ''; ?>>Router</option>
                <option value="Switch" <?= (($data['jenis'] ?? '') == 'Switch') ? 'selected' : ''; ?>>Switch</option>
                <option value="Access Point" <?= (($data['jenis'] ?? '') == 'Access Point') ? 'selected' : ''; ?>>Access Point</option>
                <option value="OLT" <?= (($data['jenis'] ?? '') == 'OLT') ? 'selected' : ''; ?>>OLT</option>
                <option value="ODC" <?= (($data['jenis'] ?? '') == 'ODC') ? 'selected' : ''; ?>>ODC</option>
                <option value="ODP" <?= (($data['jenis'] ?? '') == 'ODP') ? 'selected' : ''; ?>>ODP</option>
            </select>
        </div>

        <div class="form-group">
            <label>IP Address (Kosongkan jika perangkat pasif):</label>
            <input type="text" name="ip_address" value="<?= htmlspecialchars($data['ip_address'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Kapasitas Port:</label>
            <input type="text" name="kapasitas_port" value="<?= htmlspecialchars($data['kapasitas_port'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Parent Perangkat (Asal Jalur / Uplink):</label>
            <select name="parent_id">
                <option value="">-- Tidak Ada (Root Utama) --</option>
                <?php
                $parents = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE id != '$id'");
                while ($p = mysqli_fetch_assoc($parents)) {
                    $selected = ($p['id'] == ($data['parent_id'] ?? '')) ? 'selected' : '';
                    echo "<option value='{$p['id']}' $selected>{$p['nama_perangkat']} ({$p['jenis']})</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Latitude (Koordinat Peta):</label>
            <input type="text" name="latitude" value="<?= htmlspecialchars($data['latitude'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Longitude (Koordinat Peta):</label>
            <input type="text" name="longitude" value="<?= htmlspecialchars($data['longitude'] ?? ''); ?>" required>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
</div>

</body>
</html>