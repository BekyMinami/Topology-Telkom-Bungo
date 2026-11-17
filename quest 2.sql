DROP TABLE IF EXISTS `perangkat`;

CREATE TABLE `perangkat` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_perangkat` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(50) NOT NULL,
  `jenis` VARCHAR(50) NOT NULL,
  `parent_id` INT(11) DEFAULT NULL,
  `latitude` VARCHAR(50) NOT NULL,
  `longitude` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;