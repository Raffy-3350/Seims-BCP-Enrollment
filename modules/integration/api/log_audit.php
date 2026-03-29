<?php
// log_audit.php
// Logs admin actions to the audit_logs table.

function logAudit($conn, $adminName, $adminRole, $action, $details, $targetUser, $status) {
    if (!$conn) return;
    try {
        // PostgreSQL-compatible CREATE TABLE IF NOT EXISTS
        $conn->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id            SERIAL PRIMARY KEY,
                admin_name    VARCHAR(120) NOT NULL,
                admin_role    VARCHAR(60)  NOT NULL,
                action        VARCHAR(120) NOT NULL,
                details       TEXT         DEFAULT NULL,
                target_user   VARCHAR(120) DEFAULT NULL,
                status        VARCHAR(30)  NOT NULL DEFAULT 'Success',
                created_at    TIMESTAMP    NOT NULL DEFAULT NOW()
            )
        ");

        $stmt = $conn->prepare("
            INSERT INTO audit_logs (admin_name, admin_role, action, details, target_user, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$adminName, $adminRole, $action, $details, $targetUser, $status]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
