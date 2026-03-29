<?php
// log_audit.php
// Logs admin actions to the audit_logs table.
// Call this after any significant admin operation.

function logAudit($conn, $adminName, $adminRole, $action, $details, $targetUser, $status) {
    if (!$conn) return;
    try {
        // Create audit_logs table if it doesn't exist yet
        $conn->query("
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id`           int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `admin_name`   varchar(120) NOT NULL,
                `admin_role`   varchar(60)  NOT NULL,
                `action`       varchar(120) NOT NULL,
                `details`      text         DEFAULT NULL,
                `target_user`  varchar(120) DEFAULT NULL,
                `status`       varchar(30)  NOT NULL DEFAULT 'Success',
                `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $stmt = $conn->prepare("
            INSERT INTO audit_logs (admin_name, admin_role, action, details, target_user, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssssss", $adminName, $adminRole, $action, $details, $targetUser, $status);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Non-fatal — just log to server error log so it never breaks the main flow
        error_log("Audit log failed: " . $e->getMessage());
    }
}
