<?php

function logAudit($conn, $adminName, $adminRole, $action, $details, $targetUser = '', $status = 'Success') {
    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (
                performed_by,
                role,
                action,
                details,
                target_user,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssss",
            $adminName,
            $adminRole,
            $action,
            $details,
            $targetUser,
            $status
        );

        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail so it doesn't break the main response
        error_log("logAudit error: " . $e->getMessage());
    }
}