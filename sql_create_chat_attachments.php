<?php
include "maintenance_guard.php";
include "DB_connection.php";

enforce_maintenance_script_access();

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === "mysql") {
        $sql = "CREATE TABLE IF NOT EXISTS chat_attachments (
            attachment_id INT NOT NULL AUTO_INCREMENT,
            chat_id INT NOT NULL,
            attachment_name VARCHAR(255) NOT NULL,
            CONSTRAINT chat_attachments_pkey PRIMARY KEY (attachment_id),
            CONSTRAINT chat_attachments_chat_id_fkey FOREIGN KEY (chat_id) REFERENCES chats(chat_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS chat_attachments (
            attachment_id SERIAL PRIMARY KEY,
            chat_id INT NOT NULL,
            attachment_name VARCHAR(255) NOT NULL,
            FOREIGN KEY (chat_id) REFERENCES chats(chat_id) ON DELETE CASCADE
        )";
    }

    $pdo->exec($sql);
    echo "Table 'chat_attachments' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
