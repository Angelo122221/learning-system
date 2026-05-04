-- learning_system schema export (generated from Laravel migrations)
-- Date: 2026-04-20
-- Note: schema-only export (no row data)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `resource_trackings`;
DROP TABLE IF EXISTS `announcement_user_states`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `featured_videos`;
DROP TABLE IF EXISTS `carousel_images`;
DROP TABLE IF EXISTS `resource_files`;
DROP TABLE IF EXISTS `folders`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `role` VARCHAR(255) NOT NULL DEFAULT 'teacher',
  `district` VARCHAR(255) DEFAULT NULL,
  `school_name` VARCHAR(255) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT DEFAULT NULL,
  `cancelled_at` INT DEFAULT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `folders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `unlock_starts_at` TIMESTAMP NULL DEFAULT NULL,
  `unlock_ends_at` TIMESTAMP NULL DEFAULT NULL,
  `parent_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folders_parent_id_foreign` (`parent_id`),
  CONSTRAINT `folders_parent_id_foreign`
    FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `resource_files` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(255) DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `preview_image_path` VARCHAR(255) DEFAULT NULL,
  `file_type` VARCHAR(255) NOT NULL,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `unlock_starts_at` TIMESTAMP NULL DEFAULT NULL,
  `unlock_ends_at` TIMESTAMP NULL DEFAULT NULL,
  `opens_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_files_folder_id_foreign` (`folder_id`),
  CONSTRAINT `resource_files_folder_id_foreign`
    FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carousel_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `featured_videos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `youtube_link` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `announcement_user_states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `dismissed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announcement_user_states_announcement_id_user_id_unique` (`announcement_id`, `user_id`),
  KEY `announcement_user_states_user_id_foreign` (`user_id`),
  CONSTRAINT `announcement_user_states_announcement_id_foreign`
    FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `announcement_user_states_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `resource_trackings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `folder_id` BIGINT UNSIGNED DEFAULT NULL,
  `resource_file_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_trackings_user_id_foreign` (`user_id`),
  KEY `resource_trackings_folder_id_foreign` (`folder_id`),
  KEY `resource_trackings_resource_file_id_foreign` (`resource_file_id`),
  CONSTRAINT `resource_trackings_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `resource_trackings_folder_id_foreign`
    FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `resource_trackings_resource_file_id_foreign`
    FOREIGN KEY (`resource_file_id`) REFERENCES `resource_files` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
