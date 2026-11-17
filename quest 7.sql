CREATE DATABASE IF NOT EXISTS db_telkom_bungo;
USE db_telkom_bungo;

CREATE TABLE IF NOT EXISTS perangkat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_perangkat VARCHAR(100),
  jenis VARCHAR(50),
  ip_address VARCHAR(50),
  kapasitas_port VARCHAR(50),
  parent_id INT,
  latitude VARCHAR(50),
  longitude VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS log_gangguan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  perangkat_id INT,
  keterangan_gangguan TEXT,
  teknisi VARCHAR(100),
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP
);