-- Migration: Add user_files table for file uploads
-- Created: 2026-03-27
-- Description: Creates table to track files uploaded by users

CREATE TABLE IF NOT EXISTS `user_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  KEY `user_id_idx` (`user_id`),
  KEY `uploaded_at_idx` (`uploaded_at`),
  CONSTRAINT `fk_user_files_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_user_files_user_id` ON `user_files` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_user_files_uploaded_at` ON `user_files` (`uploaded_at`);
