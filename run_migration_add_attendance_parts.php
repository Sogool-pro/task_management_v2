<?php
require 'DB_connection.php';

try {
    $sql = file_get_contents(__DIR__ . '/migration_add_attendance_parts.sql');
    if ($sql === false) {
        throw new Exception('Cannot read migration file');
    }
    $conn->exec($sql);
    echo "Migration applied successfully";
} catch (Exception $e) {
    echo 'Migration failed: ' . $e->getMessage();
}
