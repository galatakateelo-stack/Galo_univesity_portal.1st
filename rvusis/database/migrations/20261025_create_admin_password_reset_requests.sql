CREATE TABLE IF NOT EXISTS `admin_password_reset_requests` (
    `request_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT(11) NULL,
    `submitted_username` VARCHAR(50) NOT NULL,
    `submitted_full_name` VARCHAR(100) NOT NULL,
    `status` ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME NULL DEFAULT NULL,
    `reviewed_by` INT(11) NULL DEFAULT NULL,
    PRIMARY KEY (`request_id`),
    KEY `idx_admin_password_reset_status_requested` (`status`, `requested_at`),
    KEY `idx_admin_password_reset_admin_status` (`admin_id`, `status`),
    CONSTRAINT `fk_admin_password_reset_target`
        FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_admin_password_reset_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`admin_id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
