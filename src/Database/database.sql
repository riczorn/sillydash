CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') DEFAULT 'user',
  `allowed_accounts` TEXT DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) UNSIGNED DEFAULT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `home_directory` VARCHAR(255) NOT NULL,
  `db_names` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `domain` (`domain`),
  KEY `username` (`username`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'sizes',
  `file_date` DATETIME NOT NULL,
  `processed` TINYINT(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `records` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_id` INT(11) UNSIGNED NOT NULL,
  `account_id` INT(11) UNSIGNED DEFAULT NULL,
  `size_bytes` BIGINT(20) UNSIGNED DEFAULT 0,
  `kind` VARCHAR(50) DEFAULT 'disk',
  `path` VARCHAR(512) NOT NULL,
  `time` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `fk_records_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_records_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
