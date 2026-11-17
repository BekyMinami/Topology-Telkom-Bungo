<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Nama file download otomatis dengan tanggal hari ini
$filename = "Rekap_NMS_Telkom_Bungo_" . date('Y-m-d') . ".xls";

// Header untuk memberitahu browser bahwa file ini adalah dokumen Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data dari database
$query = mysqli_query($koneksi, "SELECT * FROM perangkat");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Data Perangkat NMS</title>
</head>
<body>
    <center>
        <h3>REKAPITULASI DATA INFRASTRUKTUR JARINGAN FTTH</h3>
        <h4>PT Telkom Indonesia Wilayah Bungo</h4>
        <p>Tanggal Export: <?= date('d-m-Y H:i:s'); ?></p>
    </center>
    <br>
    <table border="1">
        <thead>
            <tr style="background-color: #cc0000; color: white;">
                <th>No</th>
                <th>Nama Perangkat / Node</th>
                <th>Jenis</th>
                <th>IP Address</th>
                <th>Kapasitas Port</th>
                <th>Parent ID</th>
                <th>Latitude</th>
                <th>Longitude</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($query)) : 
        ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['nama_perangkat']; ?></td>
                <td><?= $row['jenis']; ?></td>
                <td><?= !empty($row['ip_address']) ? $row['ip_address'] : '-'; ?></td>
                <td><?= !empty($row['kapasitas_port']) ? $row['kapasitas_port'] : '-'; ?></td>
                <td><?= !empty($row['parent_id']) ? $row['parent_id'] : '-'; ?></td>
                <td><?= $row['latitude']; ?></td>
                <td><?= $row['longitude']; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>