CREATE TABLE IF NOT EXISTS `log_gangguan` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `perangkat_id` INT NOT NULL,
  `keterangan_gangguan` TEXT NOT NULL,
  `teknisi` VARCHAR(100) NOT NULL,
  `tanggal` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;db_telkom_bungo